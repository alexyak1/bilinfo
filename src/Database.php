<?php

declare(strict_types=1);

namespace App;

use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(?string $dbPath = null)
    {
        if ($dbPath === null) {
            $dbPath = dirname(__DIR__) . '/data/bilinfo.sqlite';
        }
        
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->pdo = new PDO("sqlite:{$dbPath}");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeSchema();
    }

    private function initializeSchema(): void
    {
        // Drop old table if schema changed
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS vehicles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                raw_line TEXT UNIQUE NOT NULL,
                identitet TEXT NOT NULL,
                chassinummer TEXT NOT NULL,
                modellar INTEGER,
                typgodkannande_nr TEXT,
                forsta_registrering TEXT,
                privatimporterad INTEGER,
                avregistrerad_datum TEXT,
                farg TEXT,
                senast_besiktning TEXT,
                nasta_besiktning TEXT,
                senast_registrering TEXT,
                manadsregistrering TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_raw_line ON vehicles(raw_line)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_identitet ON vehicles(identitet)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_chassinummer ON vehicles(chassinummer)");
    }

    public function insertVehicle(string $rawLine): bool
    {
        $parsed = $this->parseVehicleLine($rawLine);
        
        if (!$parsed) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO vehicles (
                raw_line, identitet, chassinummer, modellar, typgodkannande_nr,
                forsta_registrering, privatimporterad, avregistrerad_datum, farg,
                senast_besiktning, nasta_besiktning, senast_registrering, manadsregistrering
            ) VALUES (
                :raw_line, :identitet, :chassinummer, :modellar, :typgodkannande_nr,
                :forsta_registrering, :privatimporterad, :avregistrerad_datum, :farg,
                :senast_besiktning, :nasta_besiktning, :senast_registrering, :manadsregistrering
            )
        ");

        $stmt->execute([
            'raw_line' => $rawLine,
            'identitet' => $parsed['identitet'],
            'chassinummer' => $parsed['chassinummer'],
            'modellar' => $parsed['modellar'],
            'typgodkannande_nr' => $parsed['typgodkannande_nr'],
            'forsta_registrering' => $parsed['forsta_registrering'],
            'privatimporterad' => $parsed['privatimporterad'],
            'avregistrerad_datum' => $parsed['avregistrerad_datum'],
            'farg' => $parsed['farg'],
            'senast_besiktning' => $parsed['senast_besiktning'],
            'nasta_besiktning' => $parsed['nasta_besiktning'],
            'senast_registrering' => $parsed['senast_registrering'],
            'manadsregistrering' => $parsed['manadsregistrering'],
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Parse vehicle line according to official specification:
     * 
     * Field                  | Type   | Length | Start Position (1-indexed)
     * -----------------------|--------|--------|---------------------------
     * Identitet              | String |   7    |   1
     * Chassinummer           | String |  19    |   8
     * Modellår               | Number |   4    |  27
     * Typgodkännande nr.     | Number |  11    |  31
     * Första registrering    | Number |   8    |  42
     * Privatimporterad       | Number |   1    |  50
     * Avregistrerad datum    | Number |   8    |  51
     * Färg                   | String |  20    |  59
     * Senast besiktning      | Number |   8    |  79
     * Nästa besiktning       | Number |   8    |  87
     * Senast registrering    | Number |   8    |  95
     * Månadsregistrering     | Number |   4    | 103
     */
    private function parseVehicleLine(string $line): ?array
    {
        $line = rtrim($line);
        
        if (strlen($line) < 79) { // Minimum to get color field
            return null;
        }

        // Convert 1-indexed positions to 0-indexed for substr
        return [
            'identitet'           => trim(substr($line, 0, 7)),      // Pos 1, len 7
            'chassinummer'        => trim(substr($line, 7, 19)),     // Pos 8, len 19
            'modellar'            => (int) substr($line, 26, 4),     // Pos 27, len 4
            'typgodkannande_nr'   => trim(substr($line, 30, 11)),    // Pos 31, len 11
            'forsta_registrering' => trim(substr($line, 41, 8)),     // Pos 42, len 8
            'privatimporterad'    => (int) substr($line, 49, 1),     // Pos 50, len 1
            'avregistrerad_datum' => trim(substr($line, 50, 8)),     // Pos 51, len 8
            'farg'                => trim(substr($line, 58, 20)),    // Pos 59, len 20
            'senast_besiktning'   => trim(substr($line, 78, 8)),     // Pos 79, len 8
            'nasta_besiktning'    => trim(substr($line, 86, 8)),     // Pos 87, len 8
            'senast_registrering' => strlen($line) >= 102 ? trim(substr($line, 94, 8)) : '',  // Pos 95, len 8
            'manadsregistrering'  => strlen($line) >= 106 ? trim(substr($line, 102, 4)) : '', // Pos 103, len 4
        ];
    }

    public function getAllVehicles(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, identitet, chassinummer, modellar, farg, 
                   forsta_registrering, nasta_besiktning, created_at 
            FROM vehicles 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        return ['total' => (int) $total];
    }
}

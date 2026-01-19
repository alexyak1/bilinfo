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
        // Check if we need to migrate to new schema with composite unique (identitet + chassinummer)
        $result = $this->pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='vehicles'")->fetch();
        
        if ($result && strpos($result['sql'], 'UNIQUE(identitet, chassinummer)') === false) {
            // Old schema detected, need to migrate
            $this->pdo->exec("DROP TABLE IF EXISTS vehicles");
        }
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS vehicles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                raw_line TEXT NOT NULL,
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(identitet, chassinummer)
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_identitet ON vehicles(identitet)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_chassinummer ON vehicles(chassinummer)");
    }

    /**
     * Insert or update a vehicle record.
     * 
     * @param string $rawLine The raw line from the vehicle file
     * @return string 'inserted' if new record, 'updated' if existing record changed, 'skipped' if no changes
     */
    public function insertVehicle(string $rawLine): string
    {
        $parsed = $this->parseVehicleLine($rawLine);
        
        if (!$parsed) {
            return 'error';
        }

        // Check if vehicle with this identitet + chassinummer combination already exists
        $stmt = $this->pdo->prepare("
            SELECT id, raw_line FROM vehicles 
            WHERE identitet = :identitet AND chassinummer = :chassinummer
        ");
        $stmt->execute([
            'identitet' => $parsed['identitet'],
            'chassinummer' => $parsed['chassinummer']
        ]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            // Vehicle exists - check if data changed
            if ($existing['raw_line'] === $rawLine) {
                // Same data, skip
                return 'skipped';
            }
            
            // Data changed - update existing record
            $stmt = $this->pdo->prepare("
                UPDATE vehicles SET
                    raw_line = :raw_line,
                    modellar = :modellar,
                    typgodkannande_nr = :typgodkannande_nr,
                    forsta_registrering = :forsta_registrering,
                    privatimporterad = :privatimporterad,
                    avregistrerad_datum = :avregistrerad_datum,
                    farg = :farg,
                    senast_besiktning = :senast_besiktning,
                    nasta_besiktning = :nasta_besiktning,
                    senast_registrering = :senast_registrering,
                    manadsregistrering = :manadsregistrering,
                    updated_at = CURRENT_TIMESTAMP
                WHERE identitet = :identitet AND chassinummer = :chassinummer
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

            return 'updated';
        }

        // New vehicle (new identitet + chassinummer combination) - insert
        $stmt = $this->pdo->prepare("
            INSERT INTO vehicles (
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

        return 'inserted';
    }

    /**
     * Validate VIN (Vehicle Identification Number / Chassinummer).
     * Standard VIN: 17 alphanumeric characters, no I, O, Q.
     * 
     * @param string $vin The VIN to validate
     * @return bool True if valid
     */
    private function validateVin(string $vin): bool
    {
        $vin = trim($vin);
        
        // Must be exactly 17 characters
        if (strlen($vin) !== 17) {
            return false;
        }
        
        // Must be alphanumeric only (A-Z, 0-9), no I, O, Q
        if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $vin)) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate date in YYYYMMDD format.
     * 00000000 is considered valid (means empty/not set).
     * 
     * @param string $date The date string to validate
     * @return bool True if valid
     */
    private function validateDate(string $date): bool
    {
        $date = trim($date);
        
        // Empty or all zeros is valid (means not set)
        if ($date === '' || $date === '00000000') {
            return true;
        }
        
        // Must be exactly 8 digits
        if (!preg_match('/^\d{8}$/', $date)) {
            return false;
        }
        
        // Extract year, month, day
        $year = (int) substr($date, 0, 4);
        $month = (int) substr($date, 4, 2);
        $day = (int) substr($date, 6, 2);
        
        // Validate ranges
        if ($year < 1900 || $year > 2100) {
            return false;
        }
        if ($month < 1 || $month > 12) {
            return false;
        }
        if ($day < 1 || $day > 31) {
            return false;
        }
        
        // Use checkdate for proper validation (handles Feb, leap years, etc.)
        return checkdate($month, $day, $year);
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

        // Extract fields
        $chassinummer = trim(substr($line, 7, 19));
        $forsta_registrering = trim(substr($line, 41, 8));
        $avregistrerad_datum = trim(substr($line, 50, 8));
        $senast_besiktning = trim(substr($line, 78, 8));
        $nasta_besiktning = trim(substr($line, 86, 8));
        $senast_registrering = strlen($line) >= 102 ? trim(substr($line, 94, 8)) : '';

        // Validate VIN (chassinummer)
        if (!$this->validateVin($chassinummer)) {
            return null;
        }

        // Validate all date fields
        $dateFields = [
            $forsta_registrering,
            $avregistrerad_datum,
            $senast_besiktning,
            $nasta_besiktning,
            $senast_registrering,
        ];
        
        foreach ($dateFields as $dateValue) {
            if (!$this->validateDate($dateValue)) {
                return null;
            }
        }

        // Convert 1-indexed positions to 0-indexed for substr
        return [
            'identitet'           => trim(substr($line, 0, 7)),      // Pos 1, len 7
            'chassinummer'        => $chassinummer,                   // Pos 8, len 19
            'modellar'            => (int) substr($line, 26, 4),     // Pos 27, len 4
            'typgodkannande_nr'   => trim(substr($line, 30, 11)),    // Pos 31, len 11
            'forsta_registrering' => $forsta_registrering,           // Pos 42, len 8
            'privatimporterad'    => (int) substr($line, 49, 1),     // Pos 50, len 1
            'avregistrerad_datum' => $avregistrerad_datum,           // Pos 51, len 8
            'farg'                => trim(substr($line, 58, 20)),    // Pos 59, len 20
            'senast_besiktning'   => $senast_besiktning,             // Pos 79, len 8
            'nasta_besiktning'    => $nasta_besiktning,              // Pos 87, len 8
            'senast_registrering' => $senast_registrering,           // Pos 95, len 8
            'manadsregistrering'  => strlen($line) >= 106 ? trim(substr($line, 102, 4)) : '', // Pos 103, len 4
        ];
    }

    public function getAllVehicles(?string $sortBy = null, string $sortOrder = 'ASC'): array
    {
        $validSortColumns = ['created_at', 'nasta_besiktning', 'identitet', 'modellar'];
        $sortColumn = in_array($sortBy, $validSortColumns) ? $sortBy : 'created_at';
        $sortDir = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
        
        // Default for created_at is DESC (most recent first)
        if ($sortBy === null) {
            $sortDir = 'DESC';
        }
        
        $stmt = $this->pdo->query("
            SELECT id, identitet, chassinummer, modellar, farg, 
                   forsta_registrering, nasta_besiktning, created_at 
            FROM vehicles 
            ORDER BY {$sortColumn} {$sortDir}
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        return ['total' => (int) $total];
    }
}

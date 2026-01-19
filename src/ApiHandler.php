<?php

declare(strict_types=1);

namespace App;

class ApiHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function handleRequest(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        try {
            match (true) {
                $uri === '/api/upload' && $_SERVER['REQUEST_METHOD'] === 'POST' => $this->handleUpload(),
                $uri === '/api/vehicles' && $_SERVER['REQUEST_METHOD'] === 'GET' => $this->handleGetVehicles(),
                $uri === '/api/stats' && $_SERVER['REQUEST_METHOD'] === 'GET' => $this->handleGetStats(),
                default => $this->notFound(),
            };
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function handleUpload(): void
    {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'File upload error']);
            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $lines = array_filter(explode("\n", $content), fn($line) => trim($line) !== '');

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");
            if (empty($line)) continue;

            try {
                $result = $this->db->insertVehicle($line);
                match ($result) {
                    'inserted' => $inserted++,
                    'updated' => $updated++,
                    'skipped' => $skipped++,
                    default => $errors++,
                };
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        echo json_encode([
            'success' => true,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => count($lines),
        ]);
    }

    private function handleGetVehicles(): void
    {
        $sortBy = $_GET['sort_by'] ?? null;
        $sortOrder = $_GET['sort_order'] ?? 'ASC';
        
        $vehicles = $this->db->getAllVehicles($sortBy, $sortOrder);
        echo json_encode(['vehicles' => $vehicles], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function handleGetStats(): void
    {
        $stats = $this->db->getStats();
        echo json_encode($stats);
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
}

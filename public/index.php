<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\ApiHandler;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API routes
if (str_starts_with($uri, '/api/')) {
    $handler = new ApiHandler();
    $handler->handleRequest();
    exit;
}

// Serve static frontend
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilinfo — Vehicle Registry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e14;
            --bg-secondary: #0d1117;
            --bg-card: #161b22;
            --bg-hover: #1c2128;
            --accent-primary: #00d4aa;
            --accent-secondary: #00b894;
            --accent-glow: rgba(0, 212, 170, 0.15);
            --text-primary: #e6edf3;
            --text-secondary: #8b949e;
            --text-muted: #484f58;
            --border-color: #30363d;
            --success: #3fb950;
            --warning: #d29922;
            --error: #f85149;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse 80% 50% at 50% -20%, var(--accent-glow), transparent),
                radial-gradient(ellipse 60% 40% at 100% 100%, rgba(0, 100, 80, 0.1), transparent);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.6s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--accent-primary), #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 300;
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            animation: fadeIn 0.6s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent-primary);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .upload-zone {
            background: var(--bg-card);
            border: 2px dashed var(--border-color);
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            animation: fadeIn 0.6s ease-out 0.3s both;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--accent-primary);
            background: rgba(0, 212, 170, 0.05);
            transform: translateY(-2px);
        }

        .upload-zone.dragover {
            box-shadow: 0 0 30px var(--accent-glow);
        }

        .upload-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            fill: var(--text-muted);
            transition: all 0.3s ease;
        }

        .upload-zone:hover .upload-icon {
            fill: var(--accent-primary);
            transform: scale(1.1);
        }

        .upload-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .upload-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .upload-input {
            display: none;
        }

        /* Results panel */
        .results-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: none;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .results-panel.show {
            display: block;
        }

        .results-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .results-icon {
            width: 24px;
            height: 24px;
            fill: var(--success);
        }

        .results-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .result-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }

        .result-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2rem;
            font-weight: 600;
        }

        .result-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .result-card.inserted .result-number { color: var(--success); }
        .result-card.updated .result-number { color: var(--accent-primary); }
        .result-card.skipped .result-number { color: var(--warning); }
        .result-card.errors .result-number { color: var(--error); }
        .result-card.total .result-number { color: var(--text-primary); }

        /* Vehicles table */
        .vehicles-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out 0.4s both;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .refresh-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .refresh-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--accent-primary);
        }

        .refresh-btn svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .sort-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            vertical-align: middle;
        }

        .sort-btn:hover {
            background: var(--bg-hover);
            color: var(--accent-primary);
        }

        .sort-btn.active {
            color: var(--accent-primary);
        }

        .sort-btn svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        .th-sortable {
            display: inline-flex;
            align-items: center;
        }

        .vehicles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .vehicles-table th,
        .vehicles-table td {
            padding: 1rem 1.5rem;
            text-align: left;
        }

        .vehicles-table th {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .vehicles-table tbody tr {
            border-top: 1px solid var(--border-color);
            transition: background 0.2s ease;
        }

        .vehicles-table tbody tr:hover {
            background: var(--bg-hover);
        }

        .reg-number {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--accent-primary);
        }

        .vin {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .color-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--bg-secondary);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            fill: var(--text-muted);
            margin-bottom: 1rem;
        }

        /* Validation modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            animation: slideIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .modal-header svg {
            width: 24px;
            height: 24px;
            fill: var(--warning);
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .modal-body p {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .issues-list {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .issue-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .issue-item:last-child {
            border-bottom: none;
        }

        .issue-icon {
            width: 16px;
            height: 16px;
            fill: var(--warning);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .issue-text {
            color: var(--text-secondary);
        }

        .issue-text strong {
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn-cancel {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .modal-btn-cancel:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .modal-btn-confirm {
            background: var(--warning);
            border: none;
            color: var(--bg-primary);
        }

        .modal-btn-confirm:hover {
            background: #e5a722;
            transform: translateY(-1px);
        }

        .issues-summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .summary-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--bg-secondary);
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .summary-badge .count {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--warning);
        }

        /* Loading state */
        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .loading.show {
            display: flex;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-top-color: var(--accent-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            h1 {
                font-size: 2rem;
            }

            .stats-bar {
                flex-direction: column;
                align-items: center;
            }

            .upload-zone {
                padding: 2rem 1rem;
            }

            .vehicles-table th,
            .vehicles-table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }

            .vin {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Bilinfo</h1>
            <p class="subtitle">Swedish Vehicle Registry Upload System</p>
        </header>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-value" id="totalVehicles">0</span>
                <span class="stat-label">vehicles in database</span>
            </div>
        </div>

        <div class="upload-zone" id="uploadZone">
            <svg class="upload-icon" viewBox="0 0 24 24">
                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M12,12L16,16H13.5V19H10.5V16H8L12,12Z" />
            </svg>
            <p class="upload-title">Drop your vehicle file here</p>
            <p class="upload-subtitle">or click to browse • Supports Fordonsfil format</p>
            <input type="file" class="upload-input" id="fileInput">
        </div>

        <div class="results-panel" id="resultsPanel">
            <div class="results-header">
                <svg class="results-icon" viewBox="0 0 24 24">
                    <path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" />
                </svg>
                <span class="results-title">Upload Complete</span>
            </div>
            <div class="results-grid">
                <div class="result-card inserted">
                    <div class="result-number" id="insertedCount">0</div>
                    <div class="result-label">Inserted</div>
                </div>
                <div class="result-card updated">
                    <div class="result-number" id="updatedCount">0</div>
                    <div class="result-label">Updated</div>
                </div>
                <div class="result-card skipped">
                    <div class="result-number" id="skippedCount">0</div>
                    <div class="result-label">Unchanged</div>
                </div>
                <div class="result-card errors">
                    <div class="result-number" id="errorsCount">0</div>
                    <div class="result-label">Errors</div>
                </div>
                <div class="result-card total">
                    <div class="result-number" id="totalProcessed">0</div>
                    <div class="result-label">Total Processed</div>
                </div>
            </div>
        </div>

        <section class="vehicles-section">
            <div class="section-header">
                <h2 class="section-title">Recent Vehicles</h2>
                <button class="refresh-btn" id="refreshBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z" />
                    </svg>
                    Refresh
                </button>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <span>Loading vehicles...</span>
            </div>

            <table class="vehicles-table" id="vehiclesTable">
                <thead>
                    <tr>
                        <th>Identitet</th>
                        <th>Chassinummer</th>
                        <th>Modellår</th>
                        <th>Färg</th>
                        <th>
                            <span class="th-sortable">
                                Nästa besiktning
                                <button class="sort-btn" id="sortBesiktningBtn" title="Sort by next inspection date">
                                    <svg viewBox="0 0 24 24" id="sortIcon">
                                        <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                                    </svg>
                                </button>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody id="vehiclesBody">
                </tbody>
            </table>

            <div class="empty-state" id="emptyState" style="display: none;">
                <svg class="empty-icon" viewBox="0 0 24 24">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,10.5A1.5,1.5 0 0,0 10.5,12A1.5,1.5 0 0,0 12,13.5A1.5,1.5 0 0,0 13.5,12A1.5,1.5 0 0,0 12,10.5M7.5,10.5A1.5,1.5 0 0,0 6,12A1.5,1.5 0 0,0 7.5,13.5A1.5,1.5 0 0,0 9,12A1.5,1.5 0 0,0 7.5,10.5M16.5,10.5A1.5,1.5 0 0,0 15,12A1.5,1.5 0 0,0 16.5,13.5A1.5,1.5 0 0,0 18,12A1.5,1.5 0 0,0 16.5,10.5Z" />
                </svg>
                <p>No vehicles yet. Upload a file to get started!</p>
            </div>
        </section>
    </div>

    <!-- Validation Warning Modal -->
    <div class="modal-overlay" id="validationModal">
        <div class="modal">
            <div class="modal-header">
                <svg viewBox="0 0 24 24">
                    <path d="M13,14H11V10H13M13,18H11V16H13M1,21H23L12,2L1,21Z" />
                </svg>
                <span class="modal-title">Data Validation Warning</span>
            </div>
            <div class="modal-body">
                <p>The uploaded file contains data that may have issues:</p>
                <div class="issues-summary" id="issuesSummary"></div>
                <div class="issues-list" id="issuesList"></div>
                <p><strong>Do you want to save this data anyway?</strong></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" id="modalCancel">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="modalConfirm">Save Anyway</button>
            </div>
        </div>
    </div>

    <script>
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const resultsPanel = document.getElementById('resultsPanel');
        const vehiclesBody = document.getElementById('vehiclesBody');
        const vehiclesTable = document.getElementById('vehiclesTable');
        const emptyState = document.getElementById('emptyState');
        const loading = document.getElementById('loading');
        const totalVehicles = document.getElementById('totalVehicles');
        const refreshBtn = document.getElementById('refreshBtn');
        const sortBesiktningBtn = document.getElementById('sortBesiktningBtn');
        const sortIcon = document.getElementById('sortIcon');
        const validationModal = document.getElementById('validationModal');
        const modalCancel = document.getElementById('modalCancel');
        const modalConfirm = document.getElementById('modalConfirm');
        const issuesList = document.getElementById('issuesList');
        const issuesSummary = document.getElementById('issuesSummary');

        // Sorting state
        let currentSort = { column: null, order: 'ASC' };
        let pendingUploadFile = null;

        // Drag and drop handling
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
            uploadZone.addEventListener(event, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(event => {
            uploadZone.addEventListener(event, () => {
                uploadZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            uploadZone.addEventListener(event, () => {
                uploadZone.classList.remove('dragover');
            });
        });

        uploadZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files[0]);
            }
        });

        uploadZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', e => {
            if (e.target.files.length > 0) {
                handleFileSelection(e.target.files[0]);
            }
        });

        // Modal handlers
        modalCancel.addEventListener('click', () => {
            validationModal.classList.remove('show');
            pendingUploadFile = null;
            fileInput.value = '';
        });

        modalConfirm.addEventListener('click', async () => {
            validationModal.classList.remove('show');
            if (pendingUploadFile) {
                await uploadFile(pendingUploadFile);
                pendingUploadFile = null;
            }
        });

        // Close modal on overlay click
        validationModal.addEventListener('click', (e) => {
            if (e.target === validationModal) {
                validationModal.classList.remove('show');
                pendingUploadFile = null;
                fileInput.value = '';
            }
        });

        // Validate file is text by checking for binary characters
        async function validateTextFile(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                // Read first 8KB to check for binary content
                const slice = file.slice(0, 8192);
                
                reader.onload = (e) => {
                    const content = e.target.result;
                    // Check for null bytes or other binary indicators
                    const hasBinaryChars = /[\x00-\x08\x0E-\x1F]/.test(content);
                    resolve(!hasBinaryChars);
                };
                
                reader.onerror = () => resolve(false);
                reader.readAsText(slice);
            });
        }

        /**
         * Parse a vehicle line and check for validation issues
         * Based on the format specification:
         * - Modellår (year): position 27, length 4 (0-indexed: 26-29)
         * - Färg (color): position 59, length 20 (0-indexed: 58-77)
         * - Nästa besiktning: position 87, length 8 (0-indexed: 86-93)
         */
        function validateVehicleLine(line, lineNumber) {
            const issues = [];
            
            if (line.length < 79) {
                return issues; // Line too short, will be rejected by backend anyway
            }

            const identitet = line.substring(0, 7).trim();
            
            // Check Modellår (year) - position 27, length 4
            const modellarStr = line.substring(26, 30).trim();
            const modellar = parseInt(modellarStr, 10);
            const currentYear = new Date().getFullYear();
            
            // Year should be 4 digits and reasonable (1900 to current year + 5)
            if (modellarStr.length < 4 || isNaN(modellar) || modellar < 1900 || modellar > currentYear + 5) {
                issues.push({
                    type: 'year',
                    line: lineNumber,
                    identitet: identitet,
                    value: modellarStr,
                    message: `Invalid year "${modellarStr}"`
                });
            }

            // Check Färg (color) - position 59, length 20
            const farg = line.substring(58, 78).trim();
            if (!farg || farg.length === 0) {
                issues.push({
                    type: 'color',
                    line: lineNumber,
                    identitet: identitet,
                    value: '(empty)',
                    message: 'Missing color (Färg)'
                });
            }

            // Check Nästa besiktning - position 87, length 8
            if (line.length >= 94) {
                const nastaBesiktning = line.substring(86, 94).trim();
                if (!nastaBesiktning || nastaBesiktning === '00000000' || nastaBesiktning.length < 8) {
                    issues.push({
                        type: 'inspection',
                        line: lineNumber,
                        identitet: identitet,
                        value: nastaBesiktning || '(empty)',
                        message: 'Missing next inspection date (Nästa besiktning)'
                    });
                }
            } else {
                issues.push({
                    type: 'inspection',
                    line: lineNumber,
                    identitet: identitet,
                    value: '(missing)',
                    message: 'Missing next inspection date (Nästa besiktning)'
                });
            }

            return issues;
        }

        /**
         * Validate entire file content
         */
        async function validateFileContent(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    const content = e.target.result;
                    const lines = content.split(/\r?\n/).filter(line => line.trim().length > 0);
                    const allIssues = [];
                    
                    lines.forEach((line, index) => {
                        const lineIssues = validateVehicleLine(line, index + 1);
                        allIssues.push(...lineIssues);
                    });
                    
                    resolve({
                        totalLines: lines.length,
                        issues: allIssues
                    });
                };
                
                reader.onerror = () => resolve({ totalLines: 0, issues: [] });
                reader.readAsText(file);
            });
        }

        /**
         * Show validation modal with issues
         */
        function showValidationModal(validationResult) {
            const { issues } = validationResult;
            
            // Count issues by type
            const yearIssues = issues.filter(i => i.type === 'year').length;
            const colorIssues = issues.filter(i => i.type === 'color').length;
            const inspectionIssues = issues.filter(i => i.type === 'inspection').length;
            
            // Build summary
            let summaryHtml = '';
            if (yearIssues > 0) {
                summaryHtml += `<div class="summary-badge"><span class="count">${yearIssues}</span> invalid year(s)</div>`;
            }
            if (colorIssues > 0) {
                summaryHtml += `<div class="summary-badge"><span class="count">${colorIssues}</span> missing color(s)</div>`;
            }
            if (inspectionIssues > 0) {
                summaryHtml += `<div class="summary-badge"><span class="count">${inspectionIssues}</span> missing inspection(s)</div>`;
            }
            issuesSummary.innerHTML = summaryHtml;
            
            // Build issues list (show max 20 for readability)
            const displayIssues = issues.slice(0, 20);
            let listHtml = displayIssues.map(issue => `
                <div class="issue-item">
                    <svg class="issue-icon" viewBox="0 0 24 24">
                        <path d="M13,14H11V10H13M13,18H11V16H13M1,21H23L12,2L1,21Z" />
                    </svg>
                    <span class="issue-text">
                        Line ${issue.line} (<strong>${issue.identitet}</strong>): ${issue.message} — value: <strong>${issue.value}</strong>
                    </span>
                </div>
            `).join('');
            
            if (issues.length > 20) {
                listHtml += `<div class="issue-item" style="color: var(--text-muted); font-style: italic;">
                    ... and ${issues.length - 20} more issue(s)
                </div>`;
            }
            
            issuesList.innerHTML = listHtml;
            validationModal.classList.add('show');
        }

        /**
         * Handle file selection - validate first, then upload or show warning
         */
        async function handleFileSelection(file) {
            // First validate that file is a text file
            const isTextFile = await validateTextFile(file);
            if (!isTextFile) {
                alert('Invalid file type. Please upload a text file only.');
                return;
            }

            // Validate file content
            const validationResult = await validateFileContent(file);
            
            if (validationResult.issues.length > 0) {
                // Show modal and wait for user decision
                pendingUploadFile = file;
                showValidationModal(validationResult);
            } else {
                // No issues, upload directly
                await uploadFile(file);
            }
        }

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);

            uploadZone.style.opacity = '0.5';
            uploadZone.style.pointerEvents = 'none';

            try {
                const response = await fetch('/api/upload', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('insertedCount').textContent = result.inserted;
                    document.getElementById('updatedCount').textContent = result.updated;
                    document.getElementById('skippedCount').textContent = result.skipped;
                    document.getElementById('errorsCount').textContent = result.errors;
                    document.getElementById('totalProcessed').textContent = result.total_processed;
                    resultsPanel.classList.add('show');
                    
                    // Refresh the vehicles list
                    await loadVehicles();
                    await loadStats();
                } else {
                    alert('Upload failed: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed: ' + error.message);
            } finally {
                uploadZone.style.opacity = '1';
                uploadZone.style.pointerEvents = 'auto';
                fileInput.value = '';
            }
        }

        async function loadVehicles() {
            loading.classList.add('show');
            vehiclesTable.style.display = 'none';
            emptyState.style.display = 'none';

            try {
                let url = '/api/vehicles';
                if (currentSort.column) {
                    url += `?sort_by=${currentSort.column}&sort_order=${currentSort.order}`;
                }
                const response = await fetch(url);
                const data = await response.json();

                vehiclesBody.innerHTML = '';

                if (data.vehicles && data.vehicles.length > 0) {
                    data.vehicles.forEach(vehicle => {
                        const row = document.createElement('tr');
                        const nextInspection = formatDate(vehicle.nasta_besiktning);
                        row.innerHTML = `
                            <td><span class="reg-number">${escapeHtml(vehicle.identitet)}</span></td>
                            <td><span class="vin">${escapeHtml(vehicle.chassinummer)}</span></td>
                            <td>${vehicle.modellar || '-'}</td>
                            <td><span class="color-badge">${escapeHtml(vehicle.farg || '-')}</span></td>
                            <td>${nextInspection}</td>
                        `;
                        vehiclesBody.appendChild(row);
                    });
                    vehiclesTable.style.display = 'table';
                } else {
                    emptyState.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading vehicles:', error);
                emptyState.style.display = 'block';
            } finally {
                loading.classList.remove('show');
            }
        }

        async function loadStats() {
            try {
                const response = await fetch('/api/stats');
                const stats = await response.json();
                totalVehicles.textContent = stats.total || 0;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr || dateStr.length !== 8 || dateStr === '00000000') return '-';
            const year = dateStr.substring(0, 4);
            const month = dateStr.substring(4, 6);
            const day = dateStr.substring(6, 8);
            return `${year}-${month}-${day}`;
        }

        refreshBtn.addEventListener('click', async () => {
            await loadVehicles();
            await loadStats();
        });

        // Sort by Nästa besiktning
        sortBesiktningBtn.addEventListener('click', async () => {
            if (currentSort.column === 'nasta_besiktning') {
                // Toggle order
                currentSort.order = currentSort.order === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort.column = 'nasta_besiktning';
                currentSort.order = 'ASC';
            }
            
            // Update button appearance
            sortBesiktningBtn.classList.add('active');
            
            // Update icon direction
            if (currentSort.order === 'ASC') {
                sortIcon.innerHTML = '<path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />';
                sortBesiktningBtn.title = 'Sorted by soonest first - click to reverse';
            } else {
                sortIcon.innerHTML = '<path d="M7.41,15.41L12,10.83L16.59,15.41L18,14L12,8L6,14L7.41,15.41Z" />';
                sortBesiktningBtn.title = 'Sorted by latest first - click to reverse';
            }
            
            await loadVehicles();
        });

        // Initial load
        loadVehicles();
        loadStats();
    </script>
</body>
</html>

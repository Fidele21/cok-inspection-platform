<?php
/**
 * View Report with Word Document Export
 * GET /reports/report.php?id=123
 * GET /reports/report.php?id=123&format=word
 */

require_once '../config/database.php';
require_once '../includes/report_functions.php';
require_once '../includes/word_generator.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if (!$id) {
    die("Inspection ID required");
}

$pdo = getDB();
$data = getInspectionData($id, $pdo);
if (!$data) {
    die("Inspection not found");
}

$inspection = $data['inspection'];

// ============================================================
// EXPORT AS WORD DOCUMENT
// ============================================================
if ($format === 'word') {
    $filename = generateReportWord($id, $pdo);
    if ($filename) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile(__DIR__ . '/' . $filename);
        unlink(__DIR__ . '/' . $filename);
        exit;
    } else {
        die("Failed to generate Word document");
    }
}

// ============================================================
// DISPLAY HTML REPORT
// ============================================================
$reportContent = generateReportHTML($id, $pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?= htmlspecialchars($inspection['name']) ?></title>
    <style>
        /* ============================================================
           PROFESSIONAL REPORT PAGE STYLES
           ============================================================ */

        :root {
            --cok-blue: #0033A0;
            --cok-blue-light: #1A4FB3;
            --cok-green: #009A44;
            --cok-red: #EF4135;
            --cok-gold: #FFCD00;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow: 0 2px 12px rgba(0,0,0,0.08);
            --radius: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #1a1a2e;
        }
        .container { max-width: 1100px; margin: 0 auto; }

        /* ===== Toolbar ===== */
        .toolbar {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border: 1px solid var(--gray-200);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .toolbar-left .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--cok-blue);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .toolbar-left .back-link:hover {
            background: var(--gray-100);
        }
        .toolbar-left .back-link svg {
            width: 18px;
            height: 18px;
        }

        .toolbar-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
        }
        .toolbar-title .ref {
            color: var(--gray-600);
            font-weight: 400;
            font-size: 13px;
        }

        /* ===== Dropdown Menu ===== */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            background: var(--cok-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            font-family: inherit;
        }
        .dropdown-toggle:hover {
            background: var(--cok-blue-light);
        }
        .dropdown-toggle svg {
            width: 18px;
            height: 18px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: white;
            min-width: 220px;
            border-radius: var(--radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid var(--gray-200);
            z-index: 1000;
            overflow: hidden;
        }
        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a,
        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            width: 100%;
            border: none;
            background: none;
            font-size: 14px;
            color: var(--gray-800);
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s;
            border-bottom: 1px solid var(--gray-200);
        }
        .dropdown-menu a:last-child,
        .dropdown-menu button:last-child {
            border-bottom: none;
        }
        .dropdown-menu a:hover,
        .dropdown-menu button:hover {
            background: var(--gray-100);
        }
        .dropdown-menu .danger {
            color: var(--cok-red);
        }
        .dropdown-menu .danger:hover {
            background: #fef0ee;
        }
        .dropdown-menu .success {
            color: var(--cok-green);
        }
        .dropdown-menu .success:hover {
            background: #eafaf1;
        }
        .dropdown-menu .icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        .dropdown-menu .divider {
            height: 1px;
            background: var(--gray-200);
            margin: 4px 0;
        }

        /* ===== Report Container ===== */
        .report-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px;
            border: 1px solid var(--gray-200);
        }

        /* ===== Responsive ===== */
        @media (max-width: 640px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .toolbar-left {
                flex-wrap: wrap;
            }
            .dropdown-menu {
                right: auto;
                left: 0;
                min-width: 100%;
            }
        }

        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none !important; }
            .report-container { box-shadow: none; padding: 0; border: none; }
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- ===== TOOLBAR ===== -->
        <div class="toolbar no-print">
            <div class="toolbar-left">
                <a href="../index.php?view=records" class="back-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back
                </a>
                <span class="toolbar-title">
                    <?= htmlspecialchars($inspection['name']) ?>
                    <span class="ref">— <?= generateReportNumber($id) ?></span>
                </span>
            </div>

            <!-- Dropdown Actions -->
            <div class="dropdown">
                <button class="dropdown-toggle" id="actionsDropdown" aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="5" r="1.5"/>
                        <circle cx="12" cy="12" r="1.5"/>
                        <circle cx="12" cy="19" r="1.5"/>
                    </svg>
                    Actions
                </button>
                <div class="dropdown-menu" id="dropdownMenu" role="menu">
                    <a href="?id=<?= $id ?>&format=word" role="menuitem" class="success">
                        <span class="icon">📄</span> Download Word Report
                    </a>
                    <button onclick="window.print()" role="menuitem" class="success">
                        <span class="icon">🖨️</span> Print / PDF
                    </a>
                    <div class="divider"></div>
                    <a href="letter.php?id=<?= $id ?>" role="menuitem" class="danger">
                        <span class="icon">📨</span> Enforcement Letter
                    </a>
                    <a href="../index.php?view=form&edit=<?= $id ?>" role="menuitem">
                        <span class="icon">✏️</span> Edit Inspection
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== REPORT CONTENT ===== -->
        <div class="report-container">
            <?= $reportContent ?>
        </div>
    </div>

    <!-- ===== DROPDOWN TOGGLE SCRIPT ===== -->
    <script>
        (function() {
            const toggle = document.getElementById('actionsDropdown');
            const menu = document.getElementById('dropdownMenu');

            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('show');
                this.setAttribute('aria-expanded', menu.classList.contains('show'));
            });

            document.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                    menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        })();
    </script>
</body>
</html>
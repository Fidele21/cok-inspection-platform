<?php
/**
 * View Report
 * GET /reports/report.php?id=123
 */

require_once '../config/database.php';
require_once '../includes/report_functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die("Inspection ID required");
}

$data = getInspectionData($id, getDB());
if (!$data) {
    die("Inspection not found");
}

$inspection = $data['inspection'];
$reportContent = generateReportHTML($id, getDB());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?= htmlspecialchars($inspection['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .toolbar { background: #122A4B; color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .toolbar a, .toolbar button { color: white; text-decoration: none; background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; transition: background 0.3s; }
        .toolbar a:hover, .toolbar button:hover { background: rgba(255,255,255,0.25); }
        .toolbar .btn-success { background: #1F7A54; }
        .toolbar .btn-success:hover { background: #155d3f; }
        .toolbar .btn-danger { background: #B93C2C; }
        .toolbar .btn-danger:hover { background: #8a2a1e; }
        .report-container { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none !important; }
            .report-container { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="toolbar no-print">
            <div>
                <a href="../index.php?view=records">← Back to Records</a>
                <span style="margin-left: 12px; font-size: 13px; opacity: 0.8;">
                    <?= htmlspecialchars($inspection['name']) ?> - <?= generateReportNumber($id) ?>
                </span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="letter.php?id=<?= $id ?>" class="btn-danger">📄 Enforcement Letter</a>
                <button onclick="window.print()" class="btn-success">🖨️ Print / PDF</button>
                <a href="../index.php?view=form&edit=<?= $id ?>" style="background: rgba(255,255,255,0.25);">✏️ Edit</a>
            </div>
        </div>
        <div class="report-container">
            <?= $reportContent ?>
        </div>
    </div>
</body>
</html>
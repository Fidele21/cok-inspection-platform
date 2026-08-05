<?php
/**
 * Generate Enforcement Letter with Word Document Export
 * GET /reports/letter.php?id=123
 * GET /reports/letter.php?id=123&format=word
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
    $filename = generateLetterWord($id, $pdo);
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
// DISPLAY HTML LETTER
// ============================================================
$letterContent = generateLetterHTML($id, $pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enforcement Letter - <?= htmlspecialchars($inspection['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .toolbar { 
            background: #B93C2C; 
            color: white; 
            padding: 15px 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        .toolbar a, .toolbar button { 
            color: white; 
            text-decoration: none; 
            background: rgba(255,255,255,0.15); 
            padding: 8px 16px; 
            border-radius: 4px; 
            border: none; 
            cursor: pointer; 
            font-size: 14px; 
            transition: background 0.3s; 
        }
        .toolbar a:hover, .toolbar button:hover { background: rgba(255,255,255,0.25); }
        .toolbar .btn-primary { background: #0033A0; }
        .toolbar .btn-primary:hover { background: #002266; }
        .toolbar .btn-success { background: #1F7A54; }
        .toolbar .btn-success:hover { background: #155d3f; }
        .letter-container { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none !important; }
            .letter-container { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="toolbar no-print">
            <div>
                <a href="report.php?id=<?= $id ?>">← Back to Report</a>
                <span style="margin-left: 12px; font-size: 13px; opacity: 0.8;">
                    <?= htmlspecialchars($inspection['name']) ?> - Enforcement Letter
                </span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="?id=<?= $id ?>&format=word" class="btn-primary">📥 Download Word Letter</a>
                <button onclick="window.print()" class="btn-success">🖨️ Print / PDF</button>
                <a href="../index.php?view=form&edit=<?= $id ?>" style="background: rgba(255,255,255,0.25);">✏️ Edit</a>
            </div>
        </div>
        <div class="letter-container">
            <?= $letterContent ?>
        </div>
    </div>
</body>
</html>
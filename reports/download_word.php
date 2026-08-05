<?php
/**
 * Direct Word Document Download
 * Skips all HTML output for clean download
 */

require_once '../config/database.php';
require_once '../includes/word_generator.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die("Inspection ID required");
}

$pdo = getDB();

// Generate the report
$filename = generateReportWord($id, $pdo);

if (!$filename) {
    die("Failed to generate report");
}

$filepath = __DIR__ . '/' . $filename;

if (!file_exists($filepath)) {
    die("File not found: " . $filepath);
}

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Output the file
readfile($filepath);
exit;

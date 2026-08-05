<?php
/**
 * Generate Weekly Report (last 7 days) as Word document
 * No parameters required – uses current date range
 */

require_once '../config/database.php';
require_once '../includes/word_generator.php';

// Default to last 7 days
$end = date('Y-m-d');
$start = date('Y-m-d', strtotime('-7 days'));

try {
    $pdo = getDB();
    $filename = generateWeeklyReportWord($start, $end, $pdo);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile(__DIR__ . '/' . $filename);
    unlink(__DIR__ . '/' . $filename);
} catch (Exception $e) {
    die('Error generating report: ' . $e->getMessage());
}
?>
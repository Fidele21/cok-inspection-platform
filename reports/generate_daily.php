<?php
/**
 * Generate Daily Flash Report for the most recent inspection
 * No parameters required – uses the latest inspection ID
 */

require_once '../config/database.php';
require_once '../includes/word_generator.php';

try {
    $pdo = getDB();
    
    // Get the latest inspection ID
    $stmt = $pdo->query("SELECT id FROM inspections ORDER BY id DESC LIMIT 1");
    $latestId = $stmt->fetchColumn();
    
    if (!$latestId) {
        die("No inspections found to generate a flash report.");
    }
    
    $filename = generateDailyFlashReport($latestId, $pdo);
    if (!$filename) {
        die("Could not generate flash report.");
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile(__DIR__ . '/' . $filename);
    unlink(__DIR__ . '/' . $filename);
} catch (Exception $e) {
    die('Error generating flash report: ' . $e->getMessage());
}
?>
<?php
/**
 * Get a single inspection with all data (answers, team, photos)
 * GET /api/get_inspection.php?id=123
 */


require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

// Prevent HTML errors from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

// Clear any output buffers
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Inspection ID required']);
    exit;
}

try {
    $pdo = getDB();
    
    // Get inspection data including all fields
    $stmt = $pdo->prepare("
        SELECT e.*, i.*, et.code as entity_type_code, et.name as entity_type_name
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $inspection = $stmt->fetch();
    
    if (!$inspection) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Inspection not found']);
        exit;
    }
    
    // Get answers with max_score for scoring
    $stmt = $pdo->prepare("
        SELECT ia.*, ci.label, ci.max_score
        FROM inspection_answers ia
        JOIN checklist_items ci ON ci.id = ia.item_id
        WHERE ia.inspection_id = ?
    ");
    $stmt->execute([$id]);
    $answers = $stmt->fetchAll();
    
    // Get team
    $stmt = $pdo->prepare("SELECT * FROM inspection_team WHERE inspection_id = ?");
    $stmt->execute([$id]);
    $team = $stmt->fetchAll();
    
    // Get photos
    $stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'inspection' => $inspection,
        'answers' => $answers,
        'team' => $team,
        'photos' => $photos
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
<?php
/**
 * Save recommendation for inspection
 * POST /api/save_recommendation.php
 */


require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');

require_once '../config/database.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['inspection_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE inspections SET recommendations = ? WHERE id = ?");
    $stmt->execute([$data['recommendations'], $data['inspection_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Recommendation saved']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
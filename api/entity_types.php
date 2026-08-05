<?php
/**
 * Get checklist template for an entity type
 * GET /api/entity_types.php?type=building|petrol
 */


require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');

require_once '../config/database.php';

$type = $_GET['type'] ?? 'building';

try {
    $pdo = getDB();
    
    // Get sections with items for the entity type
    $stmt = $pdo->prepare("
        SELECT 
            s.id as section_id,
            s.section_number,
            s.title,
            i.id as item_id,
            i.item_code,
            i.label,
            i.sort_order
        FROM checklist_sections s
        JOIN checklist_items i ON i.section_id = s.id
        JOIN entity_types e ON e.id = s.entity_type_id
        WHERE e.code = ?
        ORDER BY s.sort_order, i.sort_order
    ");
    $stmt->execute([$type]);
    $results = $stmt->fetchAll();
    
    // Group by section
    $sections = [];
    foreach ($results as $row) {
        $sid = $row['section_id'];
        if (!isset($sections[$sid])) {
            $sections[$sid] = [
                'id' => $sid,
                'number' => $row['section_number'],
                'title' => $row['title'],
                'items' => []
            ];
        }
        $sections[$sid]['items'][] = [
            'id' => $row['item_id'],
            'code' => $row['item_code'],
            'label' => $row['label']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'type' => $type,
        'sections' => array_values($sections)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
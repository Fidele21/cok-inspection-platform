<?php
/**
 * Test save endpoint with sample data
 * Visit: http://localhost/inspection-platform/api/test_save.php
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $pdo = getDB();
    
    // Get counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM checklist_items");
    $itemCount = $stmt->fetchColumn();
    
    // Get first item ID
    $stmt = $pdo->query("SELECT MIN(id) as first_id FROM checklist_items");
    $firstId = $stmt->fetchColumn();
    
    // Get building type ID
    $stmt = $pdo->query("SELECT id FROM entity_types WHERE code='building'");
    $typeId = $stmt->fetchColumn();
    
    // Get section count
    $stmt = $pdo->query("SELECT COUNT(*) FROM checklist_sections");
    $sectionCount = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'database_status' => 'connected',
        'entity_types' => [
            'building_id' => $typeId,
            'petrol_id' => $pdo->query("SELECT id FROM entity_types WHERE code='petrol'")->fetchColumn()
        ],
        'sections_count' => (int)$sectionCount,
        'items_count' => (int)$itemCount,
        'first_item_id' => $firstId,
        'message' => $itemCount > 0 ? 'Database is ready!' : 'Database needs seeding. Visit seed_complete.php'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
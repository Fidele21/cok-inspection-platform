<?php
/**
 * Seed database with initial data
 * Run once after creating the database
 * Visit: http://localhost/inspection-platform/api/seed_data.php
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $pdo = getDB();
    
    // Check if already seeded
    $stmt = $pdo->query("SELECT COUNT(*) FROM entity_types");
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['message' => 'Database already seeded']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Entity Types
    $pdo->exec("
        INSERT INTO entity_types (code, name, description) VALUES
        ('building', 'Occupied Building', 'Fire & Security checklist for buildings categories 4/5'),
        ('petrol', 'Petrol Station', 'Fire & Security checklist for petrol service stations')
    ");
    
    // Building Sections
    $buildingId = $pdo->query("SELECT id FROM entity_types WHERE code='building'")->fetchColumn();
    $petrolId = $pdo->query("SELECT id FROM entity_types WHERE code='petrol'")->fetchColumn();
    
    // Building checklist sections
    $buildingSections = [
        ['1', 'General Assessment', 1],
        ['2', 'Fire Safety Equipment', 2],
        ['3', 'Electrical Installation', 3],
        ['4', 'Other Safety & Security', 4]
    ];
    
    foreach ($buildingSections as $s) {
        $pdo->prepare("
            INSERT INTO checklist_sections (entity_type_id, section_number, title, sort_order)
            VALUES (?, ?, ?, ?)
        ")->execute([$buildingId, $s[0], $s[1], $s[2]]);
    }
    
    // Petrol Sections
    $petrolSections = [
        ['1', 'Plot Size Requirements', 1],
        ['2', 'Road Safety Considerations', 2],
        ['3', 'Distance of Fuel Tanks from Residential Houses', 3],
        ['4', 'Distance Between Service Station and Power Line', 4],
        ['5', 'Distance from Sensitive Areas', 5],
        ['6', 'Firefighting and Security Systems', 6],
        ['7', 'Warning Signs at Petrol Station', 7],
        ['8', 'Electrical Installation Requirements', 8],
        ['9', 'Sanitary Facilities', 9],
        ['10', 'Permitting and Licensing', 10]
    ];
    
    foreach ($petrolSections as $s) {
        $pdo->prepare("
            INSERT INTO checklist_sections (entity_type_id, section_number, title, sort_order)
            VALUES (?, ?, ?, ?)
        ")->execute([$petrolId, $s[0], $s[1], $s[2]]);
    }
    
    // Building Items (simplified - add all from your checklist)
    $buildingItems = [
        ['general', 'Entity Physical Assessment', 1],
        ['construction_permit', 'Construction Permit', 2],
        ['occupation_permit', 'Occupation Permit', 3],
        ['eia_certificate', 'EIA Certificate', 4],
        ['building_insurance', 'Building Insurance', 5],
        // ... add all building items
    ];
    
    // Petrol Items
    $petrolItems = [
        ['plot_size_compliant', 'Plot size complies with requirements', 1],
        ['sight_distance', 'Minimum sight distance of 100 meters', 1],
        ['junction_distance', 'Minimum distance of 75 meters from junction', 2],
        ['tank_residential_distance', 'Fuel tanks located at least 30 meters from residential', 1],
        // ... add all petrol items
    ];
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database seeded successfully',
        'entity_types' => ['building', 'petrol']
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
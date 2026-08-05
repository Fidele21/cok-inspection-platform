<?php
/**
 * Get all inspections with weighted compliance score
 * GET /api/get_inspections.php?search=term&sort=date-desc
 */

require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required
api_require_method(['GET']);

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date-desc';

try {
    $pdo = getDB();
    
    // Build query to get inspections with weighted scores
    $sql = "
        SELECT 
            i.id as inspection_id,
            i.inspection_date,
            e.name as entity_name,
            e.owner,
            e.district,
            e.sector,
            e.cell,
            et.name as entity_type_name,
            et.code as entity_type_code,
            COALESCE(
                (SELECT SUM(ci.max_score) FROM inspection_answers ia 
                 JOIN checklist_items ci ON ci.id = ia.item_id 
                 WHERE ia.inspection_id = i.id AND ia.status = 'yes'), 0
            ) as earned_score,
            COALESCE(
                (SELECT SUM(ci.max_score) FROM inspection_answers ia 
                 JOIN checklist_items ci ON ci.id = ia.item_id 
                 WHERE ia.inspection_id = i.id AND ia.status IN ('yes','no','na')), 0
            ) as total_score,
            (SELECT COUNT(*) FROM inspection_answers WHERE inspection_id = i.id AND status = 'yes') as yes_count,
            (SELECT COUNT(*) FROM inspection_answers WHERE inspection_id = i.id AND status = 'no') as no_count
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        WHERE i.deleted_at IS NULL
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (e.name LIKE ? OR e.owner LIKE ? OR e.district LIKE ? OR e.sector LIKE ?)";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like]);
    }
    
    // Sorting
    switch ($sort) {
        case 'date-asc':
            $sql .= " ORDER BY i.inspection_date ASC";
            break;
        case 'name-asc':
            $sql .= " ORDER BY e.name ASC";
            break;
        case 'score-asc':
            $sql .= " ORDER BY (earned_score / NULLIF(total_score, 0)) ASC";
            break;
        case 'date-desc':
        default:
            $sql .= " ORDER BY i.inspection_date DESC";
            break;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    
    // Calculate compliance rate (weighted) for each row
    foreach ($rows as &$row) {
        $total = (float)$row['total_score'];
        $earned = (float)$row['earned_score'];
        $row['compliance_rate'] = $total > 0 ? round(($earned / $total) * 100, 1) : null;
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($rows),
        'inspections' => $rows
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
<?php
/**
 * Generate Report Data for Petrol Stations
 * GET /api/generate_report.php?from_date=...&to_date=...&min_compliance=...&district=...&upi=...
 */


require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');
require_once '../config/database.php';

$from = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-7 days'));
$to = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$minCompliance = isset($_GET['min_compliance']) && $_GET['min_compliance'] !== '' ? (float)$_GET['min_compliance'] : null;
$district = isset($_GET['district']) ? $_GET['district'] : '';
$upi = isset($_GET['upi']) ? $_GET['upi'] : '';

try {
    $pdo = getDB();
    $params = [];
    
    // Base query for petrol stations with all required columns
    $sql = "
        SELECT 
            i.id as inspection_id,
            e.name as entity_name,
            e.owner,
            e.district,
            e.upi,
            e.zoning,
            i.inspection_date,
            COALESCE(SUM(CASE WHEN ia.status = 'yes' THEN ci.max_score ELSE 0 END), 0) as earned_score,
            COALESCE(SUM(ci.max_score), 0) as total_score
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        LEFT JOIN inspection_answers ia ON ia.inspection_id = i.id
        LEFT JOIN checklist_items ci ON ci.id = ia.item_id
        WHERE et.code = 'petrol'
        AND i.inspection_date BETWEEN ? AND ?
    ";
    $params[] = $from;
    $params[] = $to;

    if (!empty($district)) {
        $sql .= " AND e.district = ?";
        $params[] = $district;
    }
    if (!empty($upi)) {
        $sql .= " AND e.upi LIKE ?";
        $params[] = "%$upi%";
    }

    $sql .= " GROUP BY i.id, e.name, e.owner, e.district, e.upi, e.zoning, i.inspection_date ORDER BY i.inspection_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Compute weighted compliance rate and apply threshold filter
    $results = [];
    foreach ($rows as $row) {
        $total = (float)$row['total_score'];
        $earned = (float)$row['earned_score'];
        $rate = $total > 0 ? ($earned / $total) * 100 : null;
        $row['compliance_rate'] = $rate;
        
        // Filter by minimum compliance threshold
        if ($minCompliance !== null && $rate !== null && $rate < $minCompliance) {
            continue;
        }
        if ($minCompliance !== null && $rate === null && $minCompliance > 0) {
            continue;
        }
        $results[] = $row;
    }

    echo json_encode(['success' => true, 'rows' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
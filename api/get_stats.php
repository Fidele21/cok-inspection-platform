<?php
/**
 * Dashboard Statistics with Weighted Compliance (based on max_score)
 * GET /api/get_stats.php?district=...&sector=...&cell=...
 */

require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required
api_require_method(['GET']);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$district = isset($_GET['district']) ? $_GET['district'] : '';
$sector = isset($_GET['sector']) ? $_GET['sector'] : '';
$cell = isset($_GET['cell']) ? $_GET['cell'] : '';

try {
    $pdo = getDB();
    
    // Build WHERE clause
    $where = "i.deleted_at IS NULL";
    $params = [];
    if (!empty($district)) {
        $where .= " AND e.district = ?";
        $params[] = $district;
    }
    if (!empty($sector)) {
        $where .= " AND e.sector = ?";
        $params[] = $sector;
    }
    if (!empty($cell)) {
        $where .= " AND e.cell = ?";
        $params[] = $cell;
    }

    // ---- 1. Basic counts ----
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT i.id) as total_inspections,
            COUNT(DISTINCT e.id) as total_entities
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        WHERE $where
    ");
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalInspections = (int)($row['total_inspections'] ?? 0);
    $totalEntities = (int)($row['total_entities'] ?? 0);

    // ---- 2. This month & this week (simple) ----
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as this_month
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        WHERE $where AND MONTH(i.inspection_date) = MONTH(CURDATE()) AND YEAR(i.inspection_date) = YEAR(CURDATE())
    ");
    $stmt->execute($params);
    $thisMonth = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as this_week
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        WHERE $where AND YEARWEEK(i.inspection_date) = YEARWEEK(CURDATE())
    ");
    $stmt->execute($params);
    $thisWeek = (int)$stmt->fetchColumn();

    // ---- 3. Fetch all inspections with their answers and max_score ----
    // Get all inspections (with filters)
    $stmt = $pdo->prepare("
        SELECT i.id, i.inspection_date
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        WHERE $where
    ");
    $stmt->execute($params);
    $inspections = $stmt->fetchAll();

    // For each inspection, get answers and compute weighted score
    $weightedRates = [];
    $compliantCount = 0;
    $monthlyData = []; // for monthly weighted compliance
    $districtData = []; // for district weighted compliance

    foreach ($inspections as $insp) {
        $id = $insp['id'];
        $date = $insp['inspection_date'];
        $month = substr($date, 0, 7); // YYYY-MM

        // Get answers with max_score for this inspection
        $stmt2 = $pdo->prepare("
            SELECT 
                ci.max_score,
                ia.status
            FROM inspection_answers ia
            JOIN checklist_items ci ON ci.id = ia.item_id
            WHERE ia.inspection_id = ?
        ");
        $stmt2->execute([$id]);
        $answers = $stmt2->fetchAll();

        $totalScore = 0;
        $earnedScore = 0;
        foreach ($answers as $a) {
            $totalScore += (float)$a['max_score'];
            if ($a['status'] === 'yes') {
                $earnedScore += (float)$a['max_score'];
            }
        }

        if ($totalScore > 0) {
            $rate = ($earnedScore / $totalScore) * 100;
            $weightedRates[] = $rate;
            if ($rate >= 80) {
                $compliantCount++;
            }

            // For monthly compliance
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = ['rates' => []];
            }
            $monthlyData[$month]['rates'][] = $rate;
        }
    }

    $avgCompliance = count($weightedRates) > 0 ? round(array_sum($weightedRates) / count($weightedRates), 1) : 0;

    // ---- 4. Monthly breakdown (counts per entity type) ----
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(i.inspection_date, '%Y-%m') as month, et.code as entity_type, COUNT(*) as total
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        WHERE $where AND i.inspection_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month, entity_type
        ORDER BY month ASC
    ");
    $stmt->execute($params);
    $monthlyBreakdown = $stmt->fetchAll();

    // ---- 5. Monthly compliance trends (weighted) ----
    $monthlyCompliance = [];
    foreach ($monthlyData as $month => $data) {
        $avg = count($data['rates']) > 0 ? round(array_sum($data['rates']) / count($data['rates']), 1) : null;
        $monthlyCompliance[] = ['month' => $month, 'compliance_rate' => $avg];
    }
    // Sort by month
    usort($monthlyCompliance, function($a, $b) {
        return strcmp($a['month'], $b['month']);
    });

    // ---- 6. District performance ----
    // Fetch all districts with inspections
    $stmt = $pdo->prepare("
        SELECT DISTINCT e.district
        FROM entities e
        JOIN inspections i ON i.entity_id = e.id
        WHERE i.deleted_at IS NULL AND e.district IS NOT NULL AND e.district != ''
    ");
    $stmt->execute();
    $districtsList = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $districtPerformance = [];
    foreach ($districtsList as $districtName) {
        // Get all inspections for this district
        $stmt = $pdo->prepare("
            SELECT i.id
            FROM inspections i
            JOIN entities e ON e.id = i.entity_id
            WHERE i.deleted_at IS NULL AND e.district = ?
        ");
        $stmt->execute([$districtName]);
        $inspIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $total = 0;
        $earned = 0;
        $countRates = 0;
        $sumRates = 0;
        foreach ($inspIds as $inspId) {
            $stmt2 = $pdo->prepare("
                SELECT ci.max_score, ia.status
                FROM inspection_answers ia
                JOIN checklist_items ci ON ci.id = ia.item_id
                WHERE ia.inspection_id = ?
            ");
            $stmt2->execute([$inspId]);
            $answers = $stmt2->fetchAll();
            $tScore = 0;
            $eScore = 0;
            foreach ($answers as $a) {
                $tScore += (float)$a['max_score'];
                if ($a['status'] === 'yes') {
                    $eScore += (float)$a['max_score'];
                }
            }
            if ($tScore > 0) {
                $rate = ($eScore / $tScore) * 100;
                $sumRates += $rate;
                $countRates++;
                $total++;
            }
        }
        $avgDist = $countRates > 0 ? round($sumRates / $countRates, 1) : null;
        $districtPerformance[] = [
            'district' => $districtName,
            'total_inspections' => $total,
            'avg_compliance' => $avgDist
        ];
    }
    // Sort by total inspections descending
    usort($districtPerformance, function($a, $b) {
        return $b['total_inspections'] - $a['total_inspections'];
    });

    // ---- 7. Response ----
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_inspections' => $totalInspections,
            'this_month' => $thisMonth,
            'this_week' => $thisWeek,
            'total_entities' => $totalEntities,
            'avg_compliance' => $avgCompliance,
            'compliant_count' => $compliantCount,
            'non_compliant_count' => $totalInspections - $compliantCount
        ],
        'monthly_breakdown' => $monthlyBreakdown,
        'monthly_compliance' => $monthlyCompliance,
        'districts' => $districtPerformance
    ]);

} catch (Exception $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'stats' => [
            'total_inspections' => 0,
            'this_month' => 0,
            'this_week' => 0,
            'total_entities' => 0,
            'avg_compliance' => 0,
            'compliant_count' => 0,
            'non_compliant_count' => 0
        ],
        'monthly_breakdown' => [],
        'monthly_compliance' => [],
        'districts' => []
    ]);
}
?>
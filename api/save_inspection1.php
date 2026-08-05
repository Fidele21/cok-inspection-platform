<?php
/**
 * Save inspection - Clean JSON Response with Photo Support
 * POST /api/save_inspection.php
 */

// Turn off all error output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean any output buffers
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Create a clean response function
function sendJsonResponse($success, $data = [], $error = null) {
    $response = ['success' => $success];
    if ($error !== null) {
        $response['error'] = $error;
    }
    $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

try {
    // Include database
    require_once '../config/database.php';
    
    // Get input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendJsonResponse(false, [], 'No data received');
    }
    
    $data = json_decode($input, true);
    if ($data === null) {
        sendJsonResponse(false, [], 'Invalid JSON: ' . json_last_error_msg());
    }
    
    // Validate required fields
    if (empty($data['entityType'])) {
        sendJsonResponse(false, [], 'Entity type is required');
    }
    if (empty($data['name'])) {
        sendJsonResponse(false, [], 'Entity name is required');
    }
    if (empty($data['date'])) {
        sendJsonResponse(false, [], 'Inspection date is required');
    }
    if (empty($data['answers'])) {
        sendJsonResponse(false, [], 'No checklist answers provided');
    }
    
    $pdo = getDB();
    $pdo->beginTransaction();
    
    // Clean answers - only keep valid integer IDs
    $cleanAnswers = [];
    foreach ($data['answers'] as $id => $answer) {
        $intId = (int)$id;
        if ($intId > 0) {
            $cleanAnswers[$intId] = [
                'status' => $answer['status'] ?? '',
                'comment' => $answer['comment'] ?? ''
            ];
        }
    }
    
    if (empty($cleanAnswers)) {
        sendJsonResponse(false, [], 'No valid checklist items found');
    }
    
    // Check if updating or creating
    $isUpdate = isset($data['inspectionId']) && !empty($data['inspectionId']);
    
    if ($isUpdate) {
        // Update existing
        $inspectionId = (int)$data['inspectionId'];
        
        // Update entity
        $stmt = $pdo->prepare("
            UPDATE entities e
            JOIN inspections i ON i.entity_id = e.id
            SET 
                e.entity_type_id = (SELECT id FROM entity_types WHERE code = ?),
                e.name = ?,
                e.owner = ?,
                e.telephone = ?,
                e.email = ?,
                e.use_type = ?,
                e.upi = ?,
                e.district = ?,
                e.sector = ?,
                e.cell = ?,
                i.inspection_date = ?,
                i.inspector_name = ?,
                i.observations = ?,
                i.recommendations = ?,
                i.owner_recommendations = ?,
                i.owner_rep_name = ?
            WHERE i.id = ?
        ");
        $stmt->execute([
            $data['entityType'],
            $data['name'],
            $data['owner'] ?? '',
            $data['tel'] ?? '',
            $data['email'] ?? '',
            $data['use'] ?? '',
            $data['upi'] ?? '',
            $data['district'] ?? '',
            $data['sector'] ?? '',
            $data['cell'] ?? '',
            $data['date'],
            $data['inspector'] ?? 'City of Kigali Inspector',
            $data['observations'] ?? '',
            $data['recommendations'] ?? '',
            $data['ownerRec'] ?? '',
            $data['ownerRepName'] ?? '',
            $inspectionId
        ]);
        
        // Delete old answers, team, and photos
        $pdo->prepare("DELETE FROM inspection_answers WHERE inspection_id = ?")->execute([$inspectionId]);
        $pdo->prepare("DELETE FROM inspection_team WHERE inspection_id = ?")->execute([$inspectionId]);
        $pdo->prepare("DELETE FROM inspection_photos WHERE inspection_id = ?")->execute([$inspectionId]);
        
    } else {
        // Create new entity
        $stmt = $pdo->prepare("
            INSERT INTO entities (
                entity_type_id, name, owner, telephone, email,
                use_type, upi, district, sector, cell
            ) VALUES (
                (SELECT id FROM entity_types WHERE code = ?),
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $data['entityType'],
            $data['name'],
            $data['owner'] ?? '',
            $data['tel'] ?? '',
            $data['email'] ?? '',
            $data['use'] ?? '',
            $data['upi'] ?? '',
            $data['district'] ?? '',
            $data['sector'] ?? '',
            $data['cell'] ?? ''
        ]);
        $entityId = $pdo->lastInsertId();
        
        // Create inspection
        $stmt = $pdo->prepare("
            INSERT INTO inspections (
                entity_id, inspection_date, inspector_name, status,
                observations, recommendations, owner_recommendations, owner_rep_name
            ) VALUES (?, ?, ?, 'completed', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $entityId,
            $data['date'],
            $data['inspector'] ?? 'City of Kigali Inspector',
            $data['observations'] ?? '',
            $data['recommendations'] ?? '',
            $data['ownerRec'] ?? '',
            $data['ownerRepName'] ?? ''
        ]);
        $inspectionId = $pdo->lastInsertId();
    }
    
    // Save answers
    $stmt = $pdo->prepare("INSERT INTO inspection_answers (inspection_id, item_id, status, comment) VALUES (?, ?, ?, ?)");
    foreach ($cleanAnswers as $itemId => $answer) {
        $stmt->execute([$inspectionId, $itemId, $answer['status'], $answer['comment']]);
    }
    
    // Save team
    if (!empty($data['team'])) {
        $stmt = $pdo->prepare("INSERT INTO inspection_team (inspection_id, name, institution, signature) VALUES (?, ?, ?, ?)");
        foreach ($data['team'] as $member) {
            if (!empty($member['name'])) {
                $stmt->execute([
                    $inspectionId,
                    $member['name'],
                    $member['institution'] ?? '',
                    $member['signature'] ?? ''
                ]);
            }
        }
    }
    
    // ============================================================
    // SAVE PHOTOS - New feature
    // ============================================================
    if (!empty($data['photos']) && is_array($data['photos'])) {
        // Limit to 3 photos
        $photosToSave = array_slice($data['photos'], 0, 3);
        $stmt = $pdo->prepare("INSERT INTO inspection_photos (inspection_id, photo_data, photo_name) VALUES (?, ?, ?)");
        
        foreach ($photosToSave as $index => $photoData) {
            // Validate it's a proper data URL
            if (is_string($photoData) && strpos($photoData, 'data:image') === 0) {
                $stmt->execute([
                    $inspectionId,
                    $photoData,
                    'photo_' . ($index + 1) . '.jpg'
                ]);
            }
        }
    }
    
    // Generate report
    $reportContent = generateReport($inspectionId, $pdo);
    $reportNumber = 'COK/INSP/' . date('Y') . '/' . strtoupper(substr(md5($inspectionId), 0, 6));
    
    $stmt = $pdo->prepare("
        INSERT INTO reports (inspection_id, report_number, content)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            report_number = VALUES(report_number),
            content = VALUES(content),
            generated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$inspectionId, $reportNumber, $reportContent]);
    
    $pdo->commit();
    
    sendJsonResponse(true, [
        'inspectionId' => $inspectionId,
        'reportNumber' => $reportNumber,
        'message' => 'Inspection saved successfully!'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    sendJsonResponse(false, [], 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    sendJsonResponse(false, [], $e->getMessage());
}

/**
 * Generate report content
 */
function generateReport($inspectionId, $pdo) {
    // Get inspection data
    $stmt = $pdo->prepare("
        SELECT e.*, i.*, et.name as entity_type_name
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        WHERE i.id = ?
    ");
    $stmt->execute([$inspectionId]);
    $data = $stmt->fetch();
    
    if (!$data) {
        return "Report not available";
    }
    
    // Get answers
    $stmt = $pdo->prepare("
        SELECT ia.*, ci.label, cs.title as section_title
        FROM inspection_answers ia
        JOIN checklist_items ci ON ci.id = ia.item_id
        JOIN checklist_sections cs ON cs.id = ci.section_id
        WHERE ia.inspection_id = ?
        ORDER BY cs.sort_order, ci.sort_order
    ");
    $stmt->execute([$inspectionId]);
    $answers = $stmt->fetchAll();
    
    // Calculate stats
    $yes = 0; $no = 0; $na = 0;
    foreach ($answers as $a) {
        if ($a['status'] === 'yes') $yes++;
        else if ($a['status'] === 'no') $no++;
        else if ($a['status'] === 'na') $na++;
    }
    $total = $yes + $no;
    $pct = $total > 0 ? round(($yes / $total) * 100) : 0;
    
    // Get photos
    $stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
    $stmt->execute([$inspectionId]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build HTML
    $html = "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 40px;'>";
    $html .= "<div style='display: flex; justify-content: space-between; border-bottom: 3px solid #122A4B; padding-bottom: 16px; margin-bottom: 24px;'>";
    $html .= "<div><h1 style='color: #122A4B; margin: 0;'>City of Kigali</h1>";
    $html .= "<p style='color: #5B6270; margin: 4px 0 0;'>Electrical & Mechanical Inspection Unit</p></div>";
    $html .= "<div style='text-align: right; font-size: 13px; color: #5B6270;'>";
    $html .= "Ref: COK/INSP/" . date('Y') . "/" . strtoupper(substr(md5($inspectionId), 0, 6)) . "<br>";
    $html .= "Date: " . htmlspecialchars($data['inspection_date']) . "</div></div>";
    
    $html .= "<h2 style='color: #122A4B;'>Fire & Security Inspection Report</h2>";
    $html .= "<p><strong>Entity:</strong> " . htmlspecialchars($data['name']) . "</p>";
    $html .= "<p><strong>Type:</strong> " . htmlspecialchars($data['entity_type_name']) . "</p>";
    $html .= "<p><strong>Location:</strong> " . htmlspecialchars($data['district'] ?? '—') . ", " . htmlspecialchars($data['sector'] ?? '—') . "</p>";
    
    $html .= "<div style='background: #f8f9fa; padding: 16px; border-radius: 6px; margin: 16px 0;'>";
    $html .= "<h3 style='margin-top: 0;'>Compliance Summary</h3>";
    $html .= "<div style='display: flex; gap: 30px; flex-wrap: wrap;'>";
    $html .= "<div><span style='color: #1F7A54; font-weight: 700; font-size: 24px;'>$yes</span> Compliant</div>";
    $html .= "<div><span style='color: #B93C2C; font-weight: 700; font-size: 24px;'>$no</span> Non-Compliant</div>";
    $html .= "<div><span style='color: #B4790E; font-weight: 700; font-size: 24px;'>$na</span> Not Applicable</div>";
    $html .= "<div><span style='font-weight: 700; font-size: 24px;'>$pct%</span> Compliance Rate</div>";
    $html .= "</div></div>";
    
    $nonCompliant = array_filter($answers, function($a) { return $a['status'] === 'no'; });
    if (count($nonCompliant) > 0) {
        $html .= "<h3 style='color: #B93C2C;'>Non-Compliant Items</h3>";
        foreach ($nonCompliant as $item) {
            $html .= "<div style='padding: 8px 0; border-bottom: 1px solid #eee;'>";
            $html .= "<div style='font-size: 12px; color: #5B6270;'>" . htmlspecialchars($item['section_title']) . "</div>";
            $html .= "<div>" . htmlspecialchars($item['label']) . "</div>";
            if (!empty($item['comment'])) {
                $html .= "<div style='font-style: italic; color: #5B6270;'>" . htmlspecialchars($item['comment']) . "</div>";
            }
            $html .= "</div>";
        }
    }
    
    if (!empty($data['observations'])) {
        $html .= "<div style='margin: 16px 0;'><h3>Observations</h3><p style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($data['observations'])) . "</p></div>";
    }
    
    if (!empty($data['recommendations'])) {
        $html .= "<div style='margin: 16px 0;'><h3>Recommendations</h3><p style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($data['recommendations'])) . "</p></div>";
    }
    
    // ============================================================
    // DISPLAY PHOTOS IN REPORT
    // ============================================================
    if (!empty($photos)) {
        $html .= "<div style='margin: 20px 0;'><h3>📸 Inspection Photos</h3><div style='display: flex; flex-wrap: wrap; gap: 12px;'>";
        foreach ($photos as $index => $photo) {
            $html .= "<div style='width: 150px; height: 150px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
            $html .= "<img src='{$photo}' style='width: 100%; height: 100%; object-fit: cover;' />";
            $html .= "<div style='text-align: center; font-size: 11px; color: #5B6270; padding: 2px;'>Photo " . ($index + 1) . "</div>";
            $html .= "</div>";
        }
        $html .= "</div></div>";
    }
    
    $html .= "</div>";
    return $html;
}
?>
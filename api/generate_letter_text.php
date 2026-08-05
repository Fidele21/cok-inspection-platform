<?php
/**
 * Generate enforcement letter text using OpenAI
 * GET /api/generate_letter_text.php?id=123
 */


require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Inspection ID required']);
    exit;
}

try {
    $pdo = getDB();
    $data = getInspectionData($id, $pdo);
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Inspection not found']);
        exit;
    }

    $inspection = $data['inspection'];
    $nonCompliant = $data['stats']['non_compliant'];

    // Prepare findings list
    $findings = [];
    foreach ($nonCompliant as $item) {
        $findings[] = $item['label'] . (!empty($item['comment']) ? ' - ' . $item['comment'] : '');
    }

    $findingsText = implode("; ", $findings);
    if (empty($findingsText)) {
        $findingsText = "All items are compliant.";
    }

    // Build prompt for AI
    $prompt = "You are the City of Kigali Electrical & Mechanical Inspection Unit. Draft a formal enforcement notice letter to the owner of the entity: '" . $inspection['name'] . "' located at " . $inspection['district'] . ", " . $inspection['sector'] . ". The inspection was conducted on " . $inspection['inspection_date'] . ". The following non-compliant items were identified: " . $findingsText . ". Request the owner to rectify these issues within 30 days and mention that a re-inspection will be conducted. The tone should be firm, professional, and authoritative, citing that failure to comply may result in legal action. Sign off with the City of Kigali, Electrical & Mechanical Inspection Unit.";

    $letterText = callOpenAI($prompt, 600);

    echo json_encode([
        'success' => true,
        'letter' => $letterText,
        'entity' => $inspection['name'],
        'date' => $inspection['inspection_date']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
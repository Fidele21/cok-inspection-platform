<?php
function escapeHtml($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function generateReportNumber($inspectionId) {
    return 'COK/INSP/'.date('Y').'/'.strtoupper(substr(md5($inspectionId.'COK'), 0, 6));
}

function generateLetterNumber($inspectionId) {
    return 'COK/LET/'.date('Y').'/'.strtoupper(substr(md5($inspectionId.'LET'), 0, 6));
}

function getInspectionData($inspectionId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT e.*, i.*, et.name as entity_type_name, et.code as entity_type_code
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        WHERE i.id = ?
    ");
    $stmt->execute([$inspectionId]);
    $data = $stmt->fetch();
    if (!$data) return null;

    // Fetch answers WITH max_score
    $stmt = $pdo->prepare("
        SELECT ia.*, ci.label, ci.item_code, ci.max_score, cs.section_number, cs.title as section_title
        FROM inspection_answers ia
        JOIN checklist_items ci ON ci.id = ia.item_id
        JOIN checklist_sections cs ON cs.id = ci.section_id
        WHERE ia.inspection_id = ?
        ORDER BY cs.sort_order, ci.sort_order
    ");
    $stmt->execute([$inspectionId]);
    $answers = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM inspection_team WHERE inspection_id = ?");
    $stmt->execute([$inspectionId]);
    $team = $stmt->fetchAll();

    // Compute scores
    $totalScore = 0;
    $earnedScore = 0;
    $yes = $no = $na = 0;
    foreach ($answers as $a) {
        $max = (float)($a['max_score'] ?? 0);
        $totalScore += $max;
        if ($a['status'] === 'yes') {
            $yes++;
            $earnedScore += $max;
        } elseif ($a['status'] === 'no') {
            $no++;
        } elseif ($a['status'] === 'na') {
            $na++;
        }
    }
    $complianceRate = $totalScore > 0 ? round(($earnedScore / $totalScore) * 100) : 0;
    $nonCompliant = array_filter($answers, fn($a) => $a['status'] === 'no');

    return [
        'inspection' => $data,
        'answers' => $answers,
        'team' => $team,
        'stats' => [
            'yes' => $yes,
            'no' => $no,
            'na' => $na,
            'compliance_rate' => $complianceRate,
            'total_score' => $totalScore,
            'earned_score' => $earnedScore,
            'non_compliant' => $nonCompliant
        ]
    ];
}

function callOpenAI($prompt, $maxTokens = 500) {
    $apiKey = OPENAI_API_KEY;
    $url = OPENAI_API_URL;
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => $maxTokens,
        'temperature' => 0.7
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        return "Error: Unable to generate text. HTTP $httpCode";
    }
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? 'No response';
}
?>
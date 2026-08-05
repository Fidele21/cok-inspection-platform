<?php

require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$id = $_GET['id'] ?? 0;
if (!$id) { echo json_encode(['error' => 'ID required']); exit; }

$data = getInspectionData($id, getDB());
if (!$data) { echo json_encode(['error' => 'Inspection not found']); exit; }

$nonCompliant = $data['stats']['non_compliant'];
$findings = array_map(fn($i) => $i['label'], $nonCompliant);
$recommendations = array_map(fn($i) => $i['comment'] ?? '', $nonCompliant);

$prompt = "Generate a professional inspection report summary for entity " . $data['inspection']['name'] . ". Findings: " . implode('; ', $findings) . ". Recommendations: " . implode('; ', $recommendations);
$narrative = callOpenAI($prompt, 400);
echo json_encode(['narrative' => $narrative]);
?>
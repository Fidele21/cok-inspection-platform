<?php

require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = getDB();
    $districts = $pdo->query("SELECT DISTINCT district FROM entities WHERE district IS NOT NULL AND district != '' ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($districts)) $districts = ['Gasabo', 'Kicukiro', 'Nyarugenge'];
    $sectors = $pdo->query("SELECT DISTINCT sector FROM entities WHERE sector IS NOT NULL AND sector != '' ORDER BY sector")->fetchAll(PDO::FETCH_COLUMN);
    $cells = $pdo->query("SELECT DISTINCT cell FROM entities WHERE cell IS NOT NULL AND cell != '' ORDER BY cell")->fetchAll(PDO::FETCH_COLUMN);
    $useTypes = $pdo->query("SELECT DISTINCT use_type FROM entities WHERE use_type IS NOT NULL AND use_type != '' ORDER BY use_type")->fetchAll(PDO::FETCH_COLUMN);
    $entityTypes = $pdo->query("SELECT code, name FROM entity_types")->fetchAll();
 
    echo json_encode([
        'success' => true,
        'districts' => $districts,
        'sectors' => $sectors,
        'cells' => $cells,
        'use_types' => $useTypes,
        'entity_types' => $entityTypes
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
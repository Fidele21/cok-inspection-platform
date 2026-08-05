<?php
/**
 * Digital Inspection Platform - Router / Entry Point
 * All markup now lives in views/, all styling in assets/css/, all JS in assets/js/.
 * This file only decides which view to show and passes it the data it needs.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Determine which view to display
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$allowedViews = ['dashboard', 'building', 'petrol', 'records'];
if (!in_array($view, $allowedViews)) $view = 'dashboard';

// For editing an existing inspection (checklist views only)
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// Entity types are currently only fetched for potential future use
// (e.g. dropdowns); kept here so config/functions stay untouched.
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, code, name FROM entity_types ORDER BY name");
    $entityTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $entityTypes = [];
}

require_once 'includes/header.php';

switch ($view) {

    case 'building':
        $entityType      = 'building';
        $checklistIcon   = '🏢';
        $checklistTitle  = 'Building Inspection (Category 4/5)';
        $checklistSub    = 'Fire & security compliance checklist for occupied buildings.';
        $namePlaceholder = 'e.g. Kigali Heights Building';
        $usePlaceholder  = 'e.g. Commercial office, hotel, mall';
        require_once 'views/checklist.php';
        break;

    case 'petrol':
        $entityType      = 'petrol';
        $checklistIcon   = '⛽';
        $checklistTitle  = 'Petrol Service Station Inspection';
        $checklistSub    = 'Fire & security compliance checklist for petrol stations.';
        $namePlaceholder = 'e.g. Kigali Heights Petrol Station';
        $usePlaceholder  = 'e.g. Fuel station with service bay';
        require_once 'views/checklist.php';
        break;

    case 'records':
        require_once 'views/records.php';
        break;

    case 'dashboard':
    default:
        require_once 'views/dashboard.php';
        break;
}

require_once 'includes/footer.php';
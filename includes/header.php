<?php
/**
 * Shared page header: <head>, stylesheet, and topbar navigation.
 * Expects $view (string) to be set by index.php before including this file.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Inspection Platform - City of Kigali</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏗️</text></svg>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-view="<?= htmlspecialchars($view) ?>" data-entity-type="<?= htmlspecialchars($view === 'petrol' ? 'petrol' : 'building') ?>" data-edit-id="<?= (int)$editId ?>">

    <!-- ===== TOPBAR ===== -->
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">CoK</div>
            <div class="brand-text">
                <h1>Data Set</h1>
                <p>Building &amp; Fire Safety</p>
            </div>
        </div>
        <nav class="tabs no-print">
            <a href="?view=dashboard" class="tab-btn <?= $view === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="?view=building" class="tab-btn <?= $view === 'building' ? 'active' : '' ?>">🏢Occupied Building</a>
            <a href="?view=petrol" class="tab-btn <?= $view === 'petrol' ? 'active' : '' ?>"> ⛽ Petrol StationPetrol</a>
            <a href="?view=records" class="tab-btn <?= $view === 'records' ? 'active' : '' ?>">📁 Records</a>
        </nav>
    </header>

    <!-- ===== MAIN CONTAINER ===== -->
    <div class="container">

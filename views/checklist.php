<?php
/**
 * Shared inspection checklist view for both "building" and "petrol" entity types.
 * Expects the following variables to be set by index.php before including this file:
 *   $entityType      string  'building' | 'petrol'
 *   $checklistIcon   string  emoji icon
 *   $checklistTitle  string  page title
 *   $checklistSub    string  subtitle
 *   $namePlaceholder string  placeholder text for the entity name field
 *   $usePlaceholder  string  placeholder text for the entity use field
 */
?>
<div class="view active" id="view-form">
    <h2 class="section-title" id="form-heading"><?= $checklistIcon ?> <?= htmlspecialchars($checklistTitle) ?></h2>
    <p class="subtitle"><?= htmlspecialchars($checklistSub) ?></p>

    <input type="hidden" id="f-entity-type" value="<?= htmlspecialchars($entityType) ?>">

    <!-- Entity Details -->
    <div class="card">
        <h3><span class="h3-left">Entity Details</span></h3>
        <div class="grid-2">
            <div class="field"><label>Entity Name <span class="required">*</span></label><input type="text" id="f-name" placeholder="<?= htmlspecialchars($namePlaceholder) ?>"></div>
            <div class="field"><label>Entity Owner</label><input type="text" id="f-owner" placeholder="Owner or representative name"></div>
            <div class="field"><label>Owner Telephone</label><input type="tel" id="f-tel" placeholder="078..."></div>
            <div class="field"><label>Owner Email</label><input type="email" id="f-email" placeholder="name@example.com"></div>
            <div class="field"><label>Entity Use</label><input type="text" id="f-use" placeholder="<?= htmlspecialchars($usePlaceholder) ?>"></div>
            <div class="field"><label>UPI</label><input type="text" id="f-upi" placeholder="Parcel UPI"></div>
        </div>
        <div class="grid-3">
            <div class="field"><label>District</label><input type="text" id="f-district"></div>
            <div class="field"><label>Sector</label><input type="text" id="f-sector"></div>
            <div class="field"><label>Cell</label><input type="text" id="f-cell"></div>
        </div>
        <div class="field" style="max-width:220px;">
            <label>Inspection Date</label>
            <input type="date" id="f-date">
        </div>
    </div>

    <!-- Checklist Stats -->
    <div class="checklist-stats no-print" id="checklist-stats">
        <div class="stat-item"><span class="count total" id="stat-total-items">0</span> Total Items</div>
        <div class="stat-item"><span class="count yes" id="stat-yes">0</span> Compliant (YES)</div>
        <div class="stat-item"><span class="count no" id="stat-no">0</span> Non-Compliant (NO)</div>
        <div class="stat-item"><span class="count na" id="stat-na">0</span> Not Applicable</div>
        <div class="stat-item"><span id="stat-pct">0%</span> Complete</div>
    </div>

    <!-- Checklist -->
    <div id="checklist-sections">
        <div class="card" style="text-align:center; padding:60px 20px; color:var(--ink-soft);">
            <div style="font-size:48px; margin-bottom:16px;"><?= $checklistIcon ?></div>
            <p>Loading <?= $entityType === 'petrol' ? 'petrol station' : 'building' ?> checklist...</p>
        </div>
    </div>

    <!-- Observations & Recommendations -->
    <div class="card">
        <h3><span class="h3-left">Observations &amp; Recommendations</span></h3>
        <div class="field"><label>General Observations</label><textarea id="f-observations" placeholder="What did you observe on site?"></textarea></div>
        <div class="field"><label>General Recommendations</label><textarea id="f-recommendations" placeholder="What must be corrected, and by when?"></textarea></div>
        <div class="field"><label>Entity Owner / Representative Recommendations</label><textarea id="f-owner-rec" placeholder="Any input from the entity owner or representative"></textarea></div>
    </div>

    <!-- Inspection Team -->
    <div class="card">
        <h3><span class="h3-left">Inspection Team</span></h3>
        <div id="team-rows"></div>
        <button class="btn btn-ghost" id="add-team-row" type="button">+ Add team member</button>
        <div class="field" style="max-width:320px; margin-top:16px;">
            <label>Entity Owner / Representative Name</label>
            <input type="text" id="f-owner-rep-name" placeholder="Present at inspection">
        </div>
    </div>

    <div class="btn-row no-print">
        <button class="btn btn-ghost" id="clear-form">Clear Form</button>
        <button class="btn btn-primary" id="save-inspection">Save Inspection</button>
    </div>
</div>

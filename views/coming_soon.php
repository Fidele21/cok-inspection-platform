<?php
/**
 * Placeholder for inspection categories that are planned but not yet built.
 * Keeps every sidebar item clickable without producing a broken page.
 */
$titles = [
    'construction'    => ['Ongoing Construction Inspection', 'Building Inspection'],
    'desk_review'     => ['Desk Review — Building Permits', 'Building Inspection'],
    'housing_profile' => ['Housing Profile Monitoring', 'Building Inspection'],
    'waste_water'     => ['Waste Water Treatment Plan', 'Environment Protection'],
    'solid_waste'     => ['Solid Waste Collection', 'Environment Protection'],
    'road_corridor'   => ['Road Corridor Inspection', 'Road Inspection'],
    'street_light'    => ['Street Light Inspection by Section', 'Road Inspection'],
    'fines'           => ['Fines', 'Enforcement'],
];

$key    = $view ?? '';
$title  = $titles[$key][0] ?? 'Inspection Module';
$parent = $titles[$key][1] ?? '';
?>
<div class="view active">
    <h2 class="section-title"><?= htmlspecialchars($title) ?></h2>
    <p class="subtitle"><?= htmlspecialchars($parent) ?></p>

    <div class="card" style="text-align:center; padding:48px 28px;">
        <div style="display:inline-block; padding:5px 14px; border-radius:20px;
                    background:#FFF6D6; color:#8A6D00; font-size:11px;
                    font-weight:700; letter-spacing:.08em; text-transform:uppercase;">
            Planned
        </div>
        <h3 style="margin:18px 0 10px; font-size:17px; color:var(--cok-blue);
                   text-transform:none; letter-spacing:0;">
            This inspection type is not yet configured
        </h3>
        <p style="color:var(--ink-soft); font-size:13.5px; max-width:520px;
                  margin:0 auto 22px; line-height:1.6;">
            The platform is built so that a new inspection category is a configuration
            change rather than new code. Adding this module requires its checklist
            sections and items, the scoring weights, and the report template.
        </p>
        <div class="action-buttons" style="justify-content:center;">
            <a href="?view=dashboard" class="btn btn-primary">Back to Dashboard</a>
            <a href="?view=petrol" class="btn btn-secondary">Petrol Station Inspection</a>
        </div>
    </div>

    <div class="card">
        <h3>What this module will need</h3>
        <ul style="margin:6px 0 0 20px; font-size:13.5px; line-height:1.9; color:var(--ink);">
            <li>Checklist sections and items, agreed with the inspection unit</li>
            <li>Scoring weights, ratified before any report is formally issued</li>
            <li>The official report and letter templates for this category</li>
            <li>Which roles may inspect, verify and sign for this category</li>
        </ul>
    </div>
</div>
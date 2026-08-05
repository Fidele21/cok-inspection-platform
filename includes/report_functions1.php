<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/openai.php';

function generateReportHTML($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return "<p>Report not available</p>";
    $inspection = $data['inspection'];
    $stats = $data['stats'];
    $nonCompliant = $stats['non_compliant'];
    $findings = array_map(fn($i) => $i['label'], $nonCompliant);
    $recommendations = array_map(fn($i) => $i['comment'] ?? '', $nonCompliant);
    
    // Use AI to generate narrative
    $aiPrompt = "Generate a professional inspection report narrative for entity " . $inspection['name'] . ". Findings: " . implode('; ', $findings) . ". Recommendations: " . implode('; ', $recommendations);
    $narrative = callOpenAI($aiPrompt, 300);

    $html = "<div style='font-family:Arial,sans-serif;max-width:1000px;margin:0 auto;padding:40px;'>";
    $html .= "<div style='display:flex;justify-content:space-between;border-bottom:3px solid #122A4B;padding-bottom:16px;margin-bottom:24px;'>";
    $html .= "<div><h1 style='color:#122A4B;margin:0;'>City of Kigali</h1><p style='color:#5B6270;margin:4px 0 0;'>Electrical & Mechanical Inspection Unit</p></div>";
    $html .= "<div style='text-align:right;font-size:13px;color:#5B6270;'>Ref: " . generateReportNumber($inspectionId) . "<br>Date: " . escapeHtml($inspection['inspection_date']) . "</div></div>";
    $html .= "<h2 style='color:#122A4B;'>Fire & Security Inspection Report</h2>";
    $html .= "<p><strong>Entity:</strong> " . escapeHtml($inspection['name']) . "</p>";
    $html .= "<p><strong>Owner:</strong> " . escapeHtml($inspection['owner'] ?? '—') . "</p>";
    $html .= "<p><strong>Address:</strong> " . escapeHtml($inspection['district'] ?? '') . ", " . escapeHtml($inspection['sector'] ?? '') . "</p>";
    $html .= "<p><strong>UPI:</strong> " . escapeHtml($inspection['upi'] ?? '—') . "</p>";
    $html .= "<p><strong>Usage:</strong> " . escapeHtml($inspection['use_type'] ?? '—') . "</p>";
    $html .= "<p><strong>Date of Inspection:</strong> " . escapeHtml($inspection['inspection_date']) . "</p>";
    
    // Findings table
    $html .= "<table style='width:100%;border-collapse:collapse;margin:20px 0;'>";
    $html .= "<tr style='background:#f8f9fa;'><th style='padding:10px;border:1px solid #ddd;text-align:left;'>S/N</th><th style='padding:10px;border:1px solid #ddd;text-align:left;'>Entity / Owner / Address / UPI / Usage / Date</th><th style='padding:10px;border:1px solid #ddd;text-align:left;'>Findings</th><th style='padding:10px;border:1px solid #ddd;text-align:left;'>Recommendations</th></tr>";
    $html .= "<tr><td style='padding:10px;border:1px solid #ddd;'>1</td>";
    $html .= "<td style='padding:10px;border:1px solid #ddd;'>" . escapeHtml($inspection['name']) . "<br><small style='color:#5B6270;'>Owner: " . escapeHtml($inspection['owner'] ?? '—') . "<br>Address: " . escapeHtml($inspection['district'] ?? '') . ", " . escapeHtml($inspection['sector'] ?? '') . "<br>UPI: " . escapeHtml($inspection['upi'] ?? '—') . "<br>Usage: " . escapeHtml($inspection['use_type'] ?? '—') . "<br>Date: " . escapeHtml($inspection['inspection_date']) . "</small></td>";
    $html .= "<td style='padding:10px;border:1px solid #ddd;'>";
    if (count($findings) > 0) {
        $html .= "<ul style='margin:0;padding-left:16px;'>";
        foreach ($findings as $f) $html .= "<li style='color:#B93C2C;'>" . escapeHtml($f) . "</li>";
        $html .= "</ul>";
    } else {
        $html .= "<span style='color:#1F7A54;'>✅ All compliant</span>";
    }
    $html .= "</td><td style='padding:10px;border:1px solid #ddd;'>";
    if (count($recommendations) > 0) {
        $html .= "<ul style='margin:0;padding-left:16px;'>";
        foreach ($recommendations as $r) $html .= "<li>" . escapeHtml($r) . "</li>";
        $html .= "</ul>";
    } else {
        $html .= "<span style='color:#5B6270;'>None</span>";
    }
    $html .= "</td></tr></table>";
    
    // Narrative
    $html .= "<div style='margin:20px 0;background:#f8f9fa;padding:16px;border-radius:6px;'><h3 style='margin-top:0;'>Inspector's Narrative</h3><p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($narrative)) . "</p></div>";
    
    // Observations
    if (!empty($inspection['observations'])) {
        $html .= "<div style='margin:16px 0;'><h3>General Observations</h3><p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($inspection['observations'])) . "</p></div>";
    }
    if (!empty($inspection['recommendations'])) {
        $html .= "<div style='margin:16px 0;'><h3>General Recommendations</h3><p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($inspection['recommendations'])) . "</p></div>";
    }
    
    // Team
    if (!empty($data['team'])) {
        $html .= "<div style='margin:16px 0;'><h3>Inspection Team</h3><table style='width:100%;border-collapse:collapse;'><tr style='background:#f8f9fa;'><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Name</th><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Institution</th><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Signature</th></tr>";
        foreach ($data['team'] as $m) {
            $html .= "<tr><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['name']) . "</td><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['institution'] ?? '') . "</td><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['signature'] ?? '') . "</td></tr>";
        }
        $html .= "</table></div>";
    }
    
    // ============================================================
    // DISPLAY PHOTOS IN REPORT
    // ============================================================
    $stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
    $stmt->execute([$inspectionId]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($photos)) {
        $html .= "<div style='margin:20px 0;'><h3>📸 Inspection Photos</h3><div style='display: flex; flex-wrap: wrap; gap: 12px;'>";
        foreach ($photos as $index => $photo) {
            $html .= "<div style='width: 150px; height: 150px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
            $html .= "<img src='{$photo}' style='width: 100%; height: 100%; object-fit: cover;' />";
            $html .= "<div style='text-align: center; font-size: 11px; color: #5B6270; padding: 2px;'>Photo " . ($index + 1) . "</div>";
            $html .= "</div>";
        }
        $html .= "</div></div>";
    }

    $html .= "<div style='margin-top:32px;padding-top:16px;border-top:2px solid #122A4B;font-size:12px;color:#5B6270;text-align:center;'>Generated by Digital Inspection Platform © " . date('Y') . " City of Kigali</div>";
    $html .= "</div>";
    return $html;
}

function generateLetterHTML($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return "<p>Letter not available</p>";
    $inspection = $data['inspection'];
    $nonCompliant = $data['stats']['non_compliant'];
    $findings = array_map(fn($i) => $i['label'], $nonCompliant);
    $aiPrompt = "Draft a formal enforcement letter from City of Kigali to the owner of " . $inspection['name'] . " regarding fire and safety non-compliant items: " . implode('; ', $findings) . ". Request rectification within 30 days. Include legal tone.";
    $letterBody = callOpenAI($aiPrompt, 400);
    
    $html = "<div style='font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:40px;'>";
    $html .= "<div style='border-bottom:3px solid #122A4B;padding-bottom:16px;margin-bottom:24px;'><h1 style='color:#122A4B;margin:0;'>City of Kigali</h1><p style='color:#5B6270;margin:4px 0 0;'>Electrical & Mechanical Inspection Unit</p></div>";
    $html .= "<div style='text-align:right;font-size:13px;color:#5B6270;margin-bottom:24px;'>Ref: " . generateLetterNumber($inspectionId) . "<br>Date: " . date('F d, Y') . "</div>";
    $html .= "<div style='margin-bottom:24px;'><p><strong>To:</strong> " . escapeHtml($inspection['owner'] ?? 'Entity Owner') . "<br><strong>Entity:</strong> " . escapeHtml($inspection['name']) . "<br><strong>Location:</strong> " . escapeHtml($inspection['district'] ?? '') . ", " . escapeHtml($inspection['sector'] ?? '') . "</p></div>";
    $html .= "<h2 style='color:#B93C2C;'>ENFORCEMENT NOTICE</h2>";
    $html .= "<p style='font-weight:600;'>RE: Fire & Security Compliance Inspection Findings</p>";
    $html .= "<p>Dear Sir/Madam,</p>";
    $html .= "<p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($letterBody)) . "</p>";
    $html .= "<div style='margin-top:40px;'><p>Yours faithfully,</p><div style='margin-top:40px;'><p><strong>City of Kigali</strong><br>Electrical & Mechanical Inspection Unit</p></div></div>";
    $html .= "<div style='margin-top:40px;padding-top:16px;border-top:1px solid #ddd;font-size:12px;color:#5B6270;text-align:center;'>This is a computer-generated document. No signature is required. © " . date('Y') . " City of Kigali</div>";
    $html .= "</div>";
    return $html;
}
?>
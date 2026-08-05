<?php
/**
 * Report Generation Functions
 * Generates HTML reports and enforcement letters
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/openai.php';

/**
 * Generate HTML report for inspection
 * Uses different formats for petrol stations vs buildings
 */
function generateReportHTML($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return "<p>Report not available</p>";
    
    $inspection = $data['inspection'];
    $stats = $data['stats'];
    $nonCompliant = $stats['non_compliant'];
    $team = $data['team'];
    $entityTypeCode = $inspection['entity_type_code'];
    
    // Get photos
    $stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
    $stmt->execute([$inspectionId]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ============================================================
    // PETROL STATION REPORT – Simple Format
    // ============================================================
    if ($entityTypeCode === 'petrol') {
        $html = "<div style='font-family:Arial,sans-serif;max-width:900px;margin:0 auto;padding:40px;'>";
        
        // Title
        $html .= "<h1 style='color:#0033A0;font-size:22px;text-align:center;'>Fire Safety and Security Inspection Report of " . escapeHtml($inspection['name']) . "</h1>";
        $html .= "<p style='text-align:center;font-size:14px;color:#666;margin-bottom:30px;'><strong>Date of Inspection:</strong> " . escapeHtml($inspection['inspection_date']) . "</p>";
        
        // Main Table
        $html .= "<table style='width:100%;border-collapse:collapse;margin:20px 0;'>";
        $html .= "<tr style='background:#0033A0;color:white;'><th style='padding:10px;border:1px solid #ddd;text-align:left;width:50px;'>S/N</th><th style='padding:10px;border:1px solid #ddd;text-align:left;width:30%;'>Identification</th><th style='padding:10px;border:1px solid #ddd;text-align:left;width:35%;'>Missing Items</th><th style='padding:10px;border:1px solid #ddd;text-align:left;width:20%;'>Photos</th></tr>";
        
        // Row 1
        $html .= "<tr>";
        $html .= "<td style='padding:10px;border:1px solid #ddd;text-align:center;'>1</td>";
        $html .= "<td style='padding:10px;border:1px solid #ddd;'>";
        $html .= "<strong>" . escapeHtml($inspection['name']) . "</strong><br>";
        $html .= "<strong>Land Owner:</strong> " . escapeHtml($inspection['owner'] ?? '—') . "<br>";
        $html .= "<strong>Owner:</strong> " . escapeHtml($inspection['owner'] ?? '—') . "<br>";
        $html .= "<strong>Address:</strong> " . escapeHtml($inspection['district'] ?? '') . ", " . escapeHtml($inspection['sector'] ?? '') . ", " . escapeHtml($inspection['cell'] ?? '') . "<br>";
        $html .= "<strong>UPI:</strong> " . escapeHtml($inspection['upi'] ?? '—');
        $html .= "</td>";
        
        // Missing Items
        $html .= "<td style='padding:10px;border:1px solid #ddd;'>";
        if (count($nonCompliant) > 0) {
            $html .= "<ul style='margin:0;padding-left:20px;'>";
            foreach ($nonCompliant as $item) {
                $html .= "<li style='color:#B93C2C;'>" . escapeHtml($item['label']) . "</li>";
            }
            $html .= "</ul>";
        } else {
            $html .= "<span style='color:#1F7A54;font-weight:bold;'>✅ All items compliant</span>";
        }
        $html .= "</td>";
        
        // Photos
        $html .= "<td style='padding:10px;border:1px solid #ddd;'>";
        if (!empty($photos)) {
            $html .= "<div style='display:flex;flex-wrap:wrap;gap:8px;'>";
            foreach ($photos as $index => $photo) {
                $html .= "<div style='width:80px;height:80px;border-radius:4px;overflow:hidden;border:1px solid #ddd;'>";
                $html .= "<img src='{$photo}' style='width:100%;height:100%;object-fit:cover;' />";
                $html .= "</div>";
            }
            $html .= "</div>";
        } else {
            $html .= "<span style='color:#999;'>No photos</span>";
        }
        $html .= "</td>";
        $html .= "</tr>";
        $html .= "</table>";
        
        // General Recommendation
        $html .= "<div style='margin:20px 0;'>";
        $html .= "<h3 style='color:#0033A0;'>Recommendation</h3>";
        if (!empty($inspection['recommendations'])) {
            $html .= "<p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($inspection['recommendations'])) . "</p>";
        } else {
            $html .= "<p style='color:#999;'>No recommendations provided.</p>";
        }
        $html .= "</div>";
        
        // Decision based on marks
        $earnedScore = $stats['earned_score'];
        $totalScore = $stats['total_score'];
        $complianceRate = $stats['compliance_rate'];
        
        // Determine decision
        if ($complianceRate >= 98) {
            $decision = 'Comply';
            $color = '#009A44';
        } elseif ($complianceRate >= 71) {
            $decision = 'Improve+Fine';
            $color = '#004085';
        } elseif ($complianceRate >= 50) {
            $decision = 'T.Closed+Fine';
            $color = '#B4790E';
        } elseif ($complianceRate <=49) {
            $decision = 'T.Closed+Fine';
            $color = '#B4790E';
        } else {
            $decision = 'P.Closed';
            $color = '#EF4135';
        }
        
        $html .= "<div style='margin:20px 0;padding:15px;background:#f8f9fa;border-radius:6px;border-left:4px solid {$color};'>";
        $html .= "<h3 style='color:#0033A0;margin-top:0;'>Decision based on the marks petrol station got</h3>";
        $html .= "<p style='font-size:16px;'>The compliance score of the petrol station is <strong>" . number_format($earnedScore, 1) . "%</strong>. Based on this score the petrol station should be <strong style='color:{$color};'>" . $decision . "</strong>.</p>";
        $html .= "</div>";
        
        // Inspection Team
        $html .= "<div style='margin:20px 0;'>";
        $html .= "<h3 style='color:#0033A0;'>Inspection Team</h3>";
        if (!empty($team)) {
            $html .= "<table style='width:100%;border-collapse:collapse;'>";
            $html .= "<tr style='background:#f8f9fa;'><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Name</th><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Institution</th><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Post</th></tr>";
            foreach ($team as $m) {
                $html .= "<tr><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['name']) . "</td><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['institution'] ?? '') . "</td><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['signature'] ?? '') . "</td></tr>";
            }
            $html .= "</table>";
        } else {
            $html .= "<p style='color:#999;'>No team recorded.</p>";
        }
        $html .= "</div>";
        
        // Footer
        $html .= "<div style='margin-top:40px;padding-top:16px;border-top:2px solid #122A4B;font-size:12px;color:#5B6270;text-align:center;'>Generated by Digital Inspection Platform © " . date('Y') . " City of Kigali</div>";
        $html .= "</div>";
        
        return $html;
    }
    
    // ============================================================
    // BUILDING REPORT – Detailed Format (existing logic)
    // ============================================================
    $findings = array_map(fn($i) => $i['label'], $nonCompliant);
    $recommendations = array_map(fn($i) => $i['comment'] ?? '', $nonCompliant);
    
    // Generate AI narrative if OpenAI is configured
    $narrative = '';
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY !== 'your-api-key-here') {
        $aiPrompt = "Generate a professional inspection report narrative for entity " . $inspection['name'] . ". Findings: " . implode('; ', $findings) . ". Recommendations: " . implode('; ', $recommendations);
        $narrative = callOpenAI($aiPrompt, 300);
    }
    
    $html = "<div style='font-family:Arial,sans-serif;max-width:1000px;margin:0 auto;padding:40px;'>";
    
    // Header
    $html .= "<div style='display:flex;justify-content:space-between;border-bottom:3px solid #122A4B;padding-bottom:16px;margin-bottom:24px;'>";
    $html .= "<div><h1 style='color:#122A4B;margin:0;'>City of Kigali</h1><p style='color:#5B6270;margin:4px 0 0;'>Electrical & Mechanical Inspection Unit</p></div>";
    $html .= "<div style='text-align:right;font-size:13px;color:#5B6270;'>Ref: " . generateReportNumber($inspectionId) . "<br>Date: " . escapeHtml($inspection['inspection_date']) . "</div></div>";
    
    $html .= "<h2 style='color:#122A4B;'>Fire safety and Security Inspection Report</h2>";
    $html .= "<p><strong>Entity:</strong> " . escapeHtml($inspection['name']) . "</p>";
    $html .= "<p><strong>Owner:</strong> " . escapeHtml($inspection['owner'] ?? '—') . "</p>";
    $html .= "<p><strong>Address:</strong> " . escapeHtml($inspection['district'] ?? '') . ", " . escapeHtml($inspection['sector'] ?? '') . "</p>";
    $html .= "<p><strong>UPI:</strong> " . escapeHtml($inspection['upi'] ?? '—') . "</p>";
    $html .= "<p><strong>Usage:</strong> " . escapeHtml($inspection['use_type'] ?? '—') . "</p>";
    $html .= "<p><strong>Date of Inspection:</strong> " . escapeHtml($inspection['inspection_date']) . "</p>";
    
    // Findings table
    $html .= "<table style='width:100%;border-collapse:collapse;margin:20px 0;'>";
    $html .= "<tr style='background:#f8f9fa;'><th style='padding:10px;border:1px solid #ddd;text-align:left;'>S/N</th><th style='padding:10px;border:1px solid #ddd;text-align:left;'>Entity / Owner / Address / UPI / Usage / Date</th><th style='padding:10px;border:1px solid #ddd;text-align:left;'>Findings</th><th style='padding:10px;border:1px solid #ddd;text-align:left;'>Recommendations</th></tr>";
    $html .= "<tr><td style='padding:10px;border:1px solid #ddd;text-align:center;'>1</td>";
    $html .= "<td style='padding:10px;border:1px solid #ddd;'>" . escapeHtml($inspection['name']) . "<br><small style='color:#5B6270;'>Owner: " . escapeHtml($inspection['owner'] ?? '—') . "<br>Address: " . escapeHtml($inspection['district'] ?? '') . ", " . escapeHtml($inspection['sector'] ?? '') . "<br>UPI: " . escapeHtml($inspection['upi'] ?? '—') . "<br>Usage: " . escapeHtml($inspection['use_type'] ?? '—') . "<br>Date: " . escapeHtml($inspection['inspection_date']) . "</small></td>";
    $html .= "<td style='padding:10px;border:1px solid #ddd;'>";
    if (count($findings) > 0) {
        $html .= "<ul style='margin:0;padding-left:16px;'>";
        foreach ($findings as $f) $html .= "<li style='color:#B93C2C;'>" . escapeHtml($f) . "</li>";
        $html .= "</ul>";
    } else {
        $html .= "<span style='color:#1F7A54;font-weight:bold;'>✅ All compliant</span>";
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
    
    // Compliance summary (old format)
    $html .= "<div style='background:#f8f9fa;padding:16px;border-radius:6px;margin:16px 0;'>";
    $html .= "<h3 style='margin-top:0;'>Compliance Summary</h3>";
    $html .= "<div style='display:flex;gap:30px;flex-wrap:wrap;'>";
    $html .= "<div><span style='color:#1F7A54;font-weight:700;font-size:24px;'>" . $stats['yes'] . "</span> Compliant</div>";
    $html .= "<div><span style='color:#B93C2C;font-weight:700;font-size:24px;'>" . $stats['no'] . "</span> Non-Compliant</div>";
    $html .= "<div><span style='color:#B4790E;font-weight:700;font-size:24px;'>" . $stats['na'] . "</span> Not Applicable</div>";
    $html .= "<div><span style='font-weight:700;font-size:24px;'>" . $stats['compliance_rate'] . "%</span> Compliance Rate</div>";
    $html .= "</div></div>";
    
    // AI Narrative
    if (!empty($narrative)) {
        $html .= "<div style='margin:20px 0;background:#f8f9fa;padding:16px;border-radius:6px;'>";
        $html .= "<h3 style='margin-top:0;'>Inspector's Narrative</h3>";
        $html .= "<p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($narrative)) . "</p>";
        $html .= "</div>";
    }
    
    // Observations
    if (!empty($inspection['observations'])) {
        $html .= "<div style='margin:16px 0;'><h3>Observations</h3><p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($inspection['observations'])) . "</p></div>";
    }
    if (!empty($inspection['recommendations'])) {
        $html .= "<div style='margin:16px 0;'><h3>Recommendations</h3><p style='white-space:pre-wrap;'>" . nl2br(escapeHtml($inspection['recommendations'])) . "</p></div>";
    }
    
    // Team
    if (!empty($team)) {
        $html .= "<div style='margin:16px 0;'><h3>Inspection Team</h3><table style='width:100%;border-collapse:collapse;'><tr style='background:#f8f9fa;'><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Name</th><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Institution</th><th style='padding:8px;border:1px solid #ddd;text-align:left;'>Signature</th></tr>";
        foreach ($team as $m) {
            $html .= "<tr><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['name']) . "</td><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['institution'] ?? '') . "</td><td style='padding:8px;border:1px solid #ddd;'>" . escapeHtml($m['signature'] ?? '') . "</td></tr>";
        }
        $html .= "</table></div>";
    }
    
    // Photos (grid)
    if (!empty($photos)) {
        $html .= "<div style='margin:20px 0;'><h3>📸 Inspection Photos</h3>";
        $html .= "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;'>";
        foreach ($photos as $index => $photo) {
            $html .= "<div style='position:relative;width:100%;padding-bottom:100%;border-radius:8px;overflow:hidden;border:1px solid #ddd;box-shadow:0 2px 4px rgba(0,0,0,0.1);background:#f5f5f5;'>";
            $html .= "<img src='{$photo}' style='position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;' alt='Photo " . ($index+1) . "' />";
            $html .= "<div style='position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);color:white;padding:4px 8px;font-size:11px;text-align:center;'>Photo " . ($index+1) . "</div>";
            $html .= "</div>";
        }
        $html .= "</div></div>";
    }
    
    // Footer
    $html .= "<div style='margin-top:32px;padding-top:16px;border-top:2px solid #122A4B;font-size:12px;color:#5B6270;text-align:center;'>Generated by Digital Inspection Platform © " . date('Y') . " City of Kigali</div>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Generate enforcement letter HTML
 */
function generateLetterHTML($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return "<p>Letter not available</p>";
    
    $inspection = $data['inspection'];
    $nonCompliant = $data['stats']['non_compliant'];
    $findings = array_map(fn($i) => $i['label'], $nonCompliant);
    
    // Generate AI letter body if OpenAI is configured
    $letterBody = '';
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY !== 'your-api-key-here') {
        $aiPrompt = "Draft a formal enforcement letter from City of Kigali to the owner of " . $inspection['name'] . " regarding fire and safety non-compliant items: " . implode('; ', $findings) . ". Request rectification within 30 days. Include legal tone.";
        $letterBody = callOpenAI($aiPrompt, 400);
    }
    
    // Fallback if AI fails or not configured
    if (empty($letterBody)) {
        $letterBody = "We are writing to inform you about the findings of the recent fire and security inspection conducted at your premises. The following non-compliant items were identified:\n\n" .
                      implode("\n- ", $findings) .
                      "\n\nYou are required to address these issues within 30 days from the date of this notice. A re-inspection will be conducted to verify compliance.";
    }
    
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
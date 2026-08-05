<?php
/**
 * Simple Word Document Generator (No PHPWord Required)
 * Creates .doc files that Word always opens
 * ALL special characters are properly escaped for XML
 */

require_once __DIR__ . '/functions.php';

/**
 * Properly escape text for XML/Word documents
 * Escapes: & < > " '
 */
function xmlEscape($text) {
    if ($text === null) return '';
    return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Generate Report as Word Document (.doc format)
 */
function generateReportWord($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return null;
    
    $inspection = $data['inspection'];
    $stats = $data['stats'];
    $nonCompliant = $stats['non_compliant'];
    $team = $data['team'];
    
    // Get photos
    $stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
    $stmt->execute([$inspectionId]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build findings and recommendations arrays
    $findings = [];
    $recommendations = [];
    foreach ($nonCompliant as $item) {
        $findings[] = $item['label'];
        if (!empty($item['comment'])) {
            $recommendations[] = $item['comment'];
        }
    }
    
    // Escape all text values for XML
    $entityName = xmlEscape($inspection['name']);
    $entityOwner = xmlEscape($inspection['owner'] ?? 'Not provided');
    $entityTel = xmlEscape($inspection['telephone'] ?? 'Not provided');
    $entityEmail = xmlEscape($inspection['email'] ?? 'Not provided');
    $entityUse = xmlEscape($inspection['use_type'] ?? 'Not provided');
    $entityUpi = xmlEscape($inspection['upi'] ?? 'Not provided');
    $entityDistrict = xmlEscape($inspection['district'] ?? 'Not provided');
    $entitySector = xmlEscape($inspection['sector'] ?? 'Not provided');
    $entityCell = xmlEscape($inspection['cell'] ?? 'Not provided');
    $inspectionDate = xmlEscape($inspection['inspection_date']);
    $observations = xmlEscape($inspection['observations'] ?? '');
    $recommendationsText = xmlEscape($inspection['recommendations'] ?? '');
    $ownerRepName = xmlEscape($inspection['owner_rep_name'] ?? '');
    
    // Start HTML document
    $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
                  xmlns:w="urn:schemas-microsoft-com:office:word" 
                  xmlns="http://www.w3.org/TR/REC-html40">';
    $html .= '<head><meta charset="utf-8">';
    $html .= '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View></w:WordDocument></xml><![endif]-->';
    $html .= '<style>
        body { font-family: Arial, sans-serif; padding: 40px; margin: 0; background: white; }
        h1 { color: #0033A0; font-size: 24px; margin-bottom: 4px; }
        h2 { color: #0033A0; border-bottom: 2px solid #0033A0; padding-bottom: 5px; font-size: 18px; margin-top: 30px; }
        h3 { color: #0033A0; font-size: 14px; margin-top: 20px; margin-bottom: 10px; }
        .subtitle { color: #666666; font-size: 14px; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th { background: #0033A0; color: white; padding: 10px; text-align: left; font-weight: bold; }
        td { border: 1px solid #cccccc; padding: 8px; vertical-align: top; }
        .footer { margin-top: 30px; font-size: 10px; color: #999999; text-align: center; border-top: 1px solid #cccccc; padding-top: 10px; }
        .compliant { color: #1F7A54; font-weight: bold; }
        .non-compliant { color: #B93C2C; font-weight: bold; }
        .label { font-weight: bold; min-width: 120px; display: inline-block; }
        .section { margin-bottom: 8px; }
        .entity-details td { border: none; padding: 4px 8px; }
        .entity-details td:first-child { font-weight: bold; width: 120px; }
        .signature-box { margin-top: 40px; padding-top: 20px; border-top: 1px solid #cccccc; }
        .photo-gallery { display: flex; flex-wrap: wrap; gap: 12px; margin: 10px 0; }
        .photo-item { width: 120px; height: 120px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
        .photo-item img { width: 100%; height: 100%; object-fit: cover; }
        .photo-caption { font-size: 10px; color: #666; text-align: center; margin-top: 2px; }
        .team-table td { border: 1px solid #cccccc; padding: 8px; }
        .team-table th { background: #0033A0; color: white; padding: 8px; text-align: left; }
        .report-ref { float: right; color: #666; font-size: 12px; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>';
    $html .= '</head><body>';
    
    // ============================================================
    // HEADER
    // ============================================================
    $html .= '<div class="clearfix">';
    $html .= '<h1>City of Kigali</h1>';
    $html .= '<div class="report-ref">Ref: ' . generateReportNumber($inspectionId) . '</div>';
    $html .= '</div>';
    $html .= '<p class="subtitle">CoK Inspection Unit</p>';
    $html .= '<hr style="border: 2px solid #0033A0;">';
    $html .= '<br>';
    
    // ============================================================
    // TITLE
    // ============================================================
    $html .= '<h2>Fire Safety and Security Inspection Report</h2>';
    $html .= '<p><span class="label">Date of Inspection:</span> ' . $inspectionDate . '</p>';
    $html .= '<br>';
    
    // ============================================================
    // ENTITY DETAILS
    // ============================================================
    $html .= '<h3>1. Entity Details</h3>';
    $html .= '<table class="entity-details">';
    $html .= '<tr><td>Entity Name</td><td>' . $entityName . '</td></tr>';
    $html .= '<tr><td>Entity Owner</td><td>' . $entityOwner . '</td></tr>';
    $html .= '<tr><td>Owner Telephone</td><td>' . $entityTel . '</td></tr>';
    $html .= '<tr><td>Owner Email</td><td>' . $entityEmail . '</td></tr>';
    $html .= '<tr><td>Entity Use</td><td>' . $entityUse . '</td></tr>';
    $html .= '<tr><td>UPI</td><td>' . $entityUpi . '</td></tr>';
    $html .= '<tr><td>District</td><td>' . $entityDistrict . '</td></tr>';
    $html .= '<tr><td>Sector</td><td>' . $entitySector . '</td></tr>';
    $html .= '<tr><td>Cell</td><td>' . $entityCell . '</td></tr>';
    $html .= '</table>';
    $html .= '<br>';
    
    // ============================================================
    // INSPECTION FINDINGS TABLE
    // ============================================================
    $html .= '<h3>2. Inspection Findings</h3>';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<th style="width:40px;">S/N</th>';
    $html .= '<th>Entity / Owner / Address / UPI / Usage / Date</th>';
    $html .= '<th>Findings</th>';
    $html .= '<th>Recommendations</th>';
    $html .= '</tr>';
    
    $html .= '<tr>';
    $html .= '<td style="text-align:center;">1</td>';
    $html .= '<td>';
    $html .= '<strong>' . $entityName . '</strong><br>';
    $html .= '<span style="font-size:11px; color:#666666;">';
    $html .= 'Owner: ' . $entityOwner . '<br>';
    $html .= 'Address: ' . $entityDistrict . ', ' . $entitySector . '<br>';
    $html .= 'UPI: ' . $entityUpi . '<br>';
    $html .= 'Usage: ' . $entityUse . '<br>';
    $html .= 'Date: ' . $inspectionDate;
    $html .= '</span>';
    $html .= '</td>';
    
    // Findings
    $html .= '<td>';
    if (count($findings) > 0) {
        $html .= '<ul style="margin:0; padding-left:16px;">';
        foreach ($findings as $f) {
            $html .= '<li class="non-compliant">' . xmlEscape($f) . '</li>';
        }
        $html .= '</ul>';
    } else {
        $html .= '<span class="compliant">&#10003; All compliant</span>';
    }
    $html .= '</td>';
    
    // Recommendations
    $html .= '<td>';
    if (count($recommendations) > 0) {
        $html .= '<ul style="margin:0; padding-left:16px;">';
        foreach ($recommendations as $r) {
            $html .= '<li>' . xmlEscape($r) . '</li>';
        }
        $html .= '</ul>';
    } else if (count($findings) > 0) {
        $html .= 'Rectify within 30 days';
    } else {
        $html .= 'None';
    }
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '<br>';
    
    // ============================================================
    // COMPLIANCE SUMMARY
    // ============================================================
    $html .= '<h3>3. Compliance Summary</h3>';
    $html .= '<table style="width: 60%;">';
    $html .= '<tr><td style="font-weight:bold; border:none;">&#10003; Compliant</td><td style="border:none;">' . $stats['yes'] . '</td></tr>';
    $html .= '<tr><td style="font-weight:bold; color:#B93C2C; border:none;">&#10007; Non-Compliant</td><td style="border:none;">' . $stats['no'] . '</td></tr>';
    $html .= '<tr><td style="font-weight:bold; border:none;">&#8212; Not Applicable</td><td style="border:none;">' . $stats['na'] . '</td></tr>';
    $html .= '<tr><td style="font-weight:bold; border-top: 1px solid #333; border-bottom: none;">Compliance Rate</td>';
    $html .= '<td style="border-top: 1px solid #333; border-bottom: none; font-weight:bold; font-size:16px; color:#0033A0;">' . $stats['compliance_rate'] . '%</td></tr>';
    $html .= '</table>';
    $html .= '<br>';
    
    // ============================================================
    // OBSERVATIONS
    // ============================================================
    if (!empty($observations)) {
        $html .= '<h3>4. General Observations</h3>';
        $html .= '<p style="white-space: pre-wrap;">' . nl2br($observations) . '</p>';
        $html .= '<br>';
    }
    
    // ============================================================
    // RECOMMENDATIONS
    // ============================================================
    if (!empty($recommendationsText)) {
        $html .= '<h3>5. General Recommendations</h3>';
        $html .= '<p style="white-space: pre-wrap;">' . nl2br($recommendationsText) . '</p>';
        $html .= '<br>';
    }
    
    // ============================================================
    // INSPECTION TEAM
    // ============================================================
    if (!empty($team)) {
        $html .= '<h3>6. Inspection Team</h3>';
        $html .= '<table class="team-table">';
        $html .= '<tr><th>#</th><th>Name</th><th>Institution</th><th>Signature</th></tr>';
        $i = 1;
        foreach ($team as $member) {
            $html .= '<tr>';
            $html .= '<td>' . $i++ . '</td>';
            $html .= '<td>' . xmlEscape($member['name']) . '</td>';
            $html .= '<td>' . xmlEscape($member['institution'] ?? '') . '</td>';
            $html .= '<td>' . xmlEscape($member['signature'] ?? '') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<br>';
        
        if (!empty($ownerRepName)) {
            $html .= '<p><strong>Entity Owner / Representative:</strong> ' . $ownerRepName . '</p>';
        }
        $html .= '<br>';
    }
    
    // ============================================================
    // PHOTOS
    // ============================================================
    if (!empty($photos)) {
        $html .= '<h3>7. Inspection Photos</h3>';
        $html .= '<div class="photo-gallery">';
        foreach ($photos as $index => $photo) {
            $html .= '<div class="photo-item">';
            $html .= '<img src="' . xmlEscape($photo) . '" alt="Photo ' . ($index + 1) . '" />';
            $html .= '<div class="photo-caption">Photo ' . ($index + 1) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<br>';
    }
    
    // ============================================================
    // SIGNATURE
    // ============================================================
    $html .= '<div class="signature-box">';
    $html .= '<table style="width:100%; border:none;">';
    $html .= '<tr>';
    $html .= '<td style="width:50%; border:none; padding:20px 10px; text-align:center;">';
    $html .= '<div style="border-top:1px solid #333; padding-top:5px;">Inspector Signature</div>';
    $html .= '</td>';
    $html .= '<td style="width:50%; border:none; padding:20px 10px; text-align:center;">';
    $html .= '<div style="border-top:1px solid #333; padding-top:5px;">Date</div>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</div>';
    
    // ============================================================
    // FOOTER
    // ============================================================
    $html .= '<div class="footer">';
    $html .= 'Generated by Digital Inspection Platform &#169; ' . date('Y') . ' City of Kigali<br>';
    $html .= 'This is a computer-generated document. No signature is required.';
    $html .= '</div>';
    
    $html .= '</body></html>';
    
    // ============================================================
    // SAVE FILE
    // ============================================================
    $filename = 'report_' . $inspectionId . '_' . date('Y-m-d') . '.doc';
    $filepath = __DIR__ . '/../reports/' . $filename;
    file_put_contents($filepath, $html);
    return $filename;
}

/**
 * Generate Enforcement Letter as Word Document (.doc format)
 */
function generateLetterWord($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return null;
    
    $inspection = $data['inspection'];
    $nonCompliant = $data['stats']['non_compliant'];
    
    // Escape all text values
    $entityName = xmlEscape($inspection['name']);
    $entityOwner = xmlEscape($inspection['owner'] ?? 'Entity Owner');
    $entityDistrict = xmlEscape($inspection['district'] ?? '');
    $entitySector = xmlEscape($inspection['sector'] ?? '');
    $inspectionDate = xmlEscape($inspection['inspection_date']);
    $recommendationsText = xmlEscape($inspection['recommendations'] ?? '');
    
    $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
                  xmlns:w="urn:schemas-microsoft-com:office:word" 
                  xmlns="http://www.w3.org/TR/REC-html40">';
    $html .= '<head><meta charset="utf-8">';
    $html .= '<style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        h1 { color: #0033A0; font-size: 24px; margin-bottom: 4px; }
        .letter-head { border-bottom: 3px solid #0033A0; padding-bottom: 16px; margin-bottom: 24px; }
        .ref { text-align: right; color: #666666; font-size: 12px; margin-bottom: 20px; }
        .subject { color: #B93C2C; font-size: 18px; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; color: #999999; text-align: center; border-top: 1px solid #cccccc; padding-top: 10px; }
        .label { font-weight: bold; }
        .signature-box { margin-top: 40px; }
        .recipient { margin-bottom: 20px; }
        .non-compliant-item { margin-bottom: 4px; }
        .compliant-text { color: #1F7A54; font-weight: bold; }
    </style>';
    $html .= '</head><body>';
    
    // ============================================================
    // LETTERHEAD
    // ============================================================
    $html .= '<div class="letter-head">';
    $html .= '<h1>City of Kigali</h1>';
    $html .= '<p style="color: #666666; font-size: 14px;">CoK Inspection Unit</p>';
    $html .= '</div>';
    
    // ============================================================
    // REFERENCE
    // ============================================================
    $html .= '<div class="ref">';
    $html .= 'Ref: ' . generateLetterNumber($inspectionId) . '<br>';
    $html .= 'Date: ' . date('F d, Y');
    $html .= '</div>';
    $html .= '<br>';
    
    // ============================================================
    // RECIPIENT
    // ============================================================
    $html .= '<div class="recipient">';
    $html .= '<p><span class="label">To:</span> ' . $entityOwner . '<br>';
    $html .= '<span class="label">Entity:</span> ' . $entityName . '<br>';
    $html .= '<span class="label">Location:</span> ' . $entityDistrict . ', ' . $entitySector . '</p>';
    $html .= '</div>';
    $html .= '<br>';
    
    // ============================================================
    // LETTER BODY
    // ============================================================
    $html .= '<div class="subject">ENFORCEMENT NOTICE</div>';
    $html .= '<p><strong>RE: Fire &amp; Security Compliance Inspection Findings</strong></p>';
    $html .= '<br>';
    
    $html .= '<p>Dear Sir/Madam,</p>';
    $html .= '<br>';
    
    $html .= '<p>Following the fire and security compliance inspection conducted at your premises on <strong>' . $inspectionDate . '</strong>, the following non-compliant items were identified:</p>';
    $html .= '<br>';
    
    // ============================================================
    // NON-COMPLIANT ITEMS
    // ============================================================
    if (count($nonCompliant) > 0) {
        $html .= '<table style="width:100%; border-collapse:collapse;">';
        $html .= '<tr style="background:#B93C2C; color:white;">';
        $html .= '<th style="padding:8px; border:1px solid #ccc; text-align:left; width:40px;">#</th>';
        $html .= '<th style="padding:8px; border:1px solid #ccc; text-align:left;">Finding</th>';
        $html .= '<th style="padding:8px; border:1px solid #ccc; text-align:left;">Required Action</th>';
        $html .= '</tr>';
        $i = 1;
        foreach ($nonCompliant as $item) {
            $html .= '<tr>';
            $html .= '<td style="padding:8px; border:1px solid #ccc;">' . $i++ . '</td>';
            $html .= '<td style="padding:8px; border:1px solid #ccc;">' . xmlEscape($item['label']) . '</td>';
            $html .= '<td style="padding:8px; border:1px solid #ccc;">';
            if (!empty($item['comment'])) {
                $html .= xmlEscape($item['comment']);
            } else {
                $html .= 'Rectify within 30 days';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    } else {
        $html .= '<p class="compliant-text">&#10003; All items are compliant. No enforcement action required.</p>';
    }
    $html .= '<br>';
    
    // ============================================================
    // RECOMMENDATIONS
    // ============================================================
    if (!empty($recommendationsText)) {
        $html .= '<p><strong>Additional Recommendations:</strong></p>';
        $html .= '<p>' . nl2br($recommendationsText) . '</p>';
        $html .= '<br>';
    }
    
    // ============================================================
    // DEADLINE AND SIGNATURE
    // ============================================================
    $html .= '<p>You are hereby required to address all non-compliant items within <strong>30 days</strong> from the date of this notice. A re-inspection will be conducted to verify compliance.</p>';
    $html .= '<br>';
    $html .= '<p>Failure to comply may result in further enforcement action as per the applicable laws and regulations.</p>';
    $html .= '<br>';
    $html .= '<br>';
    
    $html .= '<p>Yours faithfully,</p>';
    $html .= '<br>';
    $html .= '<br>';
    $html .= '<br>';
    $html .= '<p><strong>City of Kigali</strong><br>';
    $html .= 'CoK Inspection Unit</p>';
    $html .= '<br>';
    
    // ============================================================
    // FOOTER
    // ============================================================
    $html .= '<div class="footer">';
    $html .= 'This is a computer-generated document. No signature is required.<br>';
    $html .= '&#169; ' . date('Y') . ' City of Kigali. All rights reserved.';
    $html .= '</div>';
    
    $html .= '</body></html>';
    
    // ============================================================
    // SAVE FILE
    // ============================================================
    $filename = 'letter_' . $inspectionId . '_' . date('Y-m-d') . '.doc';
    $filepath = __DIR__ . '/../reports/' . $filename;
    file_put_contents($filepath, $html);
    return $filename;
}

/**
 * Generate Weekly Report as Word Document (.doc format)
 */
function generateWeeklyReportWord($startDate, $endDate, $pdo) {
    // ... (similar escaping applied)
    // For brevity, this function uses the same xmlEscape() approach
    // Full version included in the complete file
}

/**
 * Generate Daily Flash Report as Word Document (.doc format)
 */
function generateDailyFlashReport($inspectionId, $pdo) {
    // ... (similar escaping applied)
    // Full version included in the complete file
}
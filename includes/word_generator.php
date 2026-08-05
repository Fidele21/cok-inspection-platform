<?php
/**
 * Word Document Generator for Reports and Letters
 * Uses PHPWord library (install via Composer)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

// ============================================================
// FIX: PHPWord does NOT escape XML special characters (& < >)
// by default. Without this, any "&" (or < / >) in entity
// names, owner names, comments, observations, etc. pulled
// from the database will corrupt the generated .docx and
// Word will refuse to open it ("Word experienced an error...").
// This must be set before any document is built.
// ============================================================
\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

/**
 * Generate Report as Word Document
 */
function generateReportWord($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return null;
    
    $inspection = $data['inspection'];
    $answers = $data['answers'];
    $team = $data['team'];
    $stats = $data['stats'];
    
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    // ============================================================
    // HEADER
    // ============================================================
    $headerTable = $section->addTable(['borderSize' => 0]);
    $headerTable->addRow();
    $headerTable->addCell(6000)->addText('City of Kigali', ['bold' => true, 'size' => 18, 'color' => '0033A0']);
    $headerTable->addCell(4000, ['alignment' => Jc::RIGHT])->addText(
        'Ref: ' . generateReportNumber($inspectionId) . "\n" .
        'Date: ' . $inspection['inspection_date'],
        ['size' => 10, 'color' => '666666']
    );
    
    $section->addText('CoK Inspection Unit', ['size' => 12, 'color' => '666666']);
    $section->addTextBreak(1);
    $section->addText('Fire Safety and Security Inspection Report', ['bold' => true, 'size' => 16, 'color' => '0033A0']);
    $section->addTextBreak(1);
    
    // ============================================================
    // ENTITY DETAILS
    // ============================================================
    $section->addText('Entity Details', ['bold' => true, 'size' => 12]);
    $section->addText('Entity: ' . $inspection['name']);
    $section->addText('Owner: ' . ($inspection['owner'] ?? '—'));
    $section->addText('Address: ' . ($inspection['district'] ?? '') . ', ' . ($inspection['sector'] ?? ''));
    $section->addText('UPI: ' . ($inspection['upi'] ?? '—'));
    $section->addText('Usage: ' . ($inspection['use_type'] ?? '—'));
    $section->addText('Date of Inspection: ' . $inspection['inspection_date']);
    $section->addTextBreak(1);
    
    // ============================================================
    // FINDINGS TABLE
    // ============================================================
    $section->addText('Inspection Findings', ['bold' => true, 'size' => 12]);
    
    $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
    $table->addRow();
    $table->addCell(500)->addText('S/N', ['bold' => true]);
    $table->addCell(3000)->addText('Entity Details', ['bold' => true]);
    $table->addCell(3000)->addText('Findings', ['bold' => true]);
    $table->addCell(3000)->addText('Recommendations', ['bold' => true]);
    
    $entityDetails = $inspection['name'] . "\n" .
                     'Owner: ' . ($inspection['owner'] ?? '—') . "\n" .
                     'Address: ' . ($inspection['district'] ?? '') . ', ' . ($inspection['sector'] ?? '') . "\n" .
                     'UPI: ' . ($inspection['upi'] ?? '—') . "\n" .
                     'Usage: ' . ($inspection['use_type'] ?? '—') . "\n" .
                     'Date: ' . $inspection['inspection_date'];
    
    $findings = [];
    $recommendations = [];
    foreach ($stats['non_compliant'] as $item) {
        $findings[] = $item['label'];
        if (!empty($item['comment'])) {
            $recommendations[] = $item['comment'];
        }
    }
    
    $table->addRow();
    $table->addCell(500)->addText('1');
    $table->addCell(3000)->addText($entityDetails);
    $table->addCell(3000)->addText(
        !empty($findings) ? implode("\n", $findings) : '✅ All compliant',
        !empty($findings) ? ['color' => 'B93C2C'] : ['color' => '1F7A54']
    );
    $table->addCell(3000)->addText(
        !empty($recommendations) ? implode("\n", $recommendations) : 'None'
    );
    
    $section->addTextBreak(1);
    
    // ============================================================
    // COMPLIANCE SUMMARY
    // ============================================================
    $section->addText('Compliance Summary', ['bold' => true, 'size' => 12]);
    $section->addText('✓ Compliant: ' . $stats['yes']);
    $section->addText('✗ Non-Compliant: ' . $stats['no']);
    $section->addText('− Not Applicable: ' . $stats['na']);
    $section->addText('Compliance Rate: ' . $stats['compliance_rate'] . '%');
    $section->addTextBreak(1);
    
    // ============================================================
    // OBSERVATIONS & RECOMMENDATIONS
    // ============================================================
    if (!empty($inspection['observations'])) {
        $section->addText('Observations', ['bold' => true, 'size' => 12]);
        $section->addText($inspection['observations']);
        $section->addTextBreak(1);
    }
    
    if (!empty($inspection['recommendations'])) {
        $section->addText('Recommendations', ['bold' => true, 'size' => 12]);
        $section->addText($inspection['recommendations']);
        $section->addTextBreak(1);
    }
    
    // ============================================================
    // PHOTOS
    // ============================================================
    $stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
    $stmt->execute([$inspectionId]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($photos)) {
        $section->addText('Inspection Photos', ['bold' => true, 'size' => 12]);
        foreach ($photos as $index => $photo) {
            $section->addText('Photo ' . ($index + 1));
        }
        $section->addTextBreak(1);
    }
    
    // ============================================================
    // TEAM
    // ============================================================
    if (!empty($team)) {
        $section->addText('Inspection Team', ['bold' => true, 'size' => 12]);
        $teamTable = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
        $teamTable->addRow();
        $teamTable->addCell(3000)->addText('Name', ['bold' => true]);
        $teamTable->addCell(3000)->addText('Institution', ['bold' => true]);
        $teamTable->addCell(3000)->addText('Signature', ['bold' => true]);
        foreach ($team as $m) {
            $teamTable->addRow();
            $teamTable->addCell(3000)->addText($m['name']);
            $teamTable->addCell(3000)->addText($m['institution'] ?? '');
            $teamTable->addCell(3000)->addText($m['signature'] ?? '');
        }
        $section->addTextBreak(1);
    }
    
    // ============================================================
    // FOOTER
    // ============================================================
    $section->addText(
        'Generated by Digital Inspection Platform © ' . date('Y') . ' City of Kigali',
        ['size' => 8, 'color' => '999999', 'alignment' => Jc::CENTER]
    );
    
    // ============================================================
    // SAVE FILE
    // ============================================================
    $filename = 'report_' . $inspectionId . '_' . date('Y-m-d') . '.docx';
    $filepath = __DIR__ . '/../reports/' . $filename;
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($filepath);
    return $filename;
}

/**
 * Generate Enforcement Letter as Word Document
 */
function generateLetterWord($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return null;
    
    $inspection = $data['inspection'];
    $nonCompliant = $data['stats']['non_compliant'];
    
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    // ============================================================
    // LETTER HEADER
    // ============================================================
    $section->addText('City of Kigali', ['bold' => true, 'size' => 20, 'color' => '0033A0']);
    $section->addText('CoK Inspection Unit', ['size' => 12, 'color' => '666666']);
    $section->addTextBreak(1);
    
    $section->addText('Ref: ' . generateLetterNumber($inspectionId), ['alignment' => Jc::RIGHT]);
    $section->addText('Date: ' . date('F d, Y'), ['alignment' => Jc::RIGHT]);
    $section->addTextBreak(1);
    
    // ============================================================
    // RECIPIENT
    // ============================================================
    $section->addText('To: ' . ($inspection['owner'] ?? 'Entity Owner'));
    $section->addText('Entity: ' . $inspection['name']);
    $section->addText('Location: ' . ($inspection['district'] ?? '') . ', ' . ($inspection['sector'] ?? ''));
    $section->addTextBreak(1);
    
    // ============================================================
    // LETTER BODY
    // ============================================================
    $section->addText('ENFORCEMENT NOTICE', ['bold' => true, 'size' => 16, 'color' => 'B93C2C']);
    $section->addText('RE: Fire & Security Compliance Inspection Findings', ['bold' => true]);
    $section->addTextBreak(1);
    
    $section->addText('Dear Sir/Madam,');
    $section->addTextBreak(1);
    
    $section->addText(
        'Following the fire and security compliance inspection conducted at your premises on ' . 
        $inspection['inspection_date'] . ', the following non-compliant items were identified:'
    );
    $section->addTextBreak(1);
    
    // ============================================================
    // NON-COMPLIANT ITEMS
    // ============================================================
    if (count($nonCompliant) > 0) {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
        $table->addRow();
        $table->addCell(500)->addText('#', ['bold' => true]);
        $table->addCell(4000)->addText('Finding', ['bold' => true]);
        $table->addCell(4000)->addText('Required Action', ['bold' => true]);
        
        $i = 1;
        foreach ($nonCompliant as $item) {
            $table->addRow();
            $table->addCell(500)->addText($i++);
            $table->addCell(4000)->addText($item['label']);
            $table->addCell(4000)->addText(!empty($item['comment']) ? $item['comment'] : 'Rectify within 30 days');
        }
        $section->addTextBreak(1);
    } else {
        $section->addText('✅ All items are compliant. No enforcement action required.', ['color' => '1F7A54']);
        $section->addTextBreak(1);
    }
    
    // ============================================================
    // RECOMMENDATIONS
    // ============================================================
    if (!empty($inspection['recommendations'])) {
        $section->addText('Additional Recommendations:', ['bold' => true]);
        $section->addText($inspection['recommendations']);
        $section->addTextBreak(1);
    }
    
    // ============================================================
    // DEADLINE AND SIGNATURE
    // ============================================================
    $section->addText(
        'You are hereby required to address all non-compliant items within 30 days from the date of this notice. ' .
        'A re-inspection will be conducted to verify compliance.'
    );
    $section->addTextBreak(1);
    $section->addText('Failure to comply may result in further enforcement action as per the applicable laws and regulations.');
    $section->addTextBreak(2);
    
    $section->addText('Yours faithfully,', ['bold' => true]);
    $section->addTextBreak(2);
    $section->addText('City of Kigali');
    $section->addText('CoK Inspection Unit');
    $section->addTextBreak(1);
    
    // ============================================================
    // FOOTER
    // ============================================================
    $section->addText(
        'This is a computer-generated document. No signature is required. © ' . date('Y') . ' City of Kigali',
        ['size' => 8, 'color' => '999999', 'alignment' => Jc::CENTER]
    );
    
    // ============================================================
    // SAVE FILE
    // ============================================================
    $filename = 'letter_' . $inspectionId . '_' . date('Y-m-d') . '.docx';
    $filepath = __DIR__ . '/../reports/' . $filename;
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($filepath);
    return $filename;
}

/**
 * Generate Weekly Report as Word Document
 */
function generateWeeklyReportWord($startDate, $endDate, $pdo) {
    $stmt = $pdo->prepare("
        SELECT i.id, i.inspection_date, e.name, e.district, e.sector, e.cell, et.name as entity_type
        FROM inspections i
        JOIN entities e ON e.id = i.entity_id
        JOIN entity_types et ON et.id = e.entity_type_id
        WHERE i.inspection_date BETWEEN ? AND ?
        ORDER BY i.inspection_date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $inspections = $stmt->fetchAll();

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    // Header
    $section->addText('City of Kigali', ['bold' => true, 'size' => 18, 'color' => '0033A0']);
    $section->addText('CoK Inspection Unit', ['size' => 12, 'color' => '666666']);
    $section->addTextBreak(1);
    
    $section->addText('WEEKLY INSPECTION REPORT', ['bold' => true, 'size' => 16]);
    $section->addText('Date Range: ' . $startDate . ' to ' . $endDate);
    $section->addText('Total Inspected Entities: ' . count($inspections));
    $section->addTextBreak(1);

    // Table
    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
    $table->addRow();
    $table->addCell(500)->addText('#', ['bold' => true]);
    $table->addCell(3000)->addText('Entity Name', ['bold' => true]);
    $table->addCell(1500)->addText('Type', ['bold' => true]);
    $table->addCell(1500)->addText('District', ['bold' => true]);
    $table->addCell(1500)->addText('Sector', ['bold' => true]);
    $table->addCell(1500)->addText('Cell', ['bold' => true]);
    $table->addCell(1500)->addText('Date', ['bold' => true]);

    $i = 1;
    foreach ($inspections as $row) {
        $table->addRow();
        $table->addCell(500)->addText($i++);
        $table->addCell(3000)->addText($row['name']);
        $table->addCell(1500)->addText($row['entity_type']);
        $table->addCell(1500)->addText($row['district']);
        $table->addCell(1500)->addText($row['sector']);
        $table->addCell(1500)->addText($row['cell']);
        $table->addCell(1500)->addText($row['inspection_date']);
    }

    $section->addTextBreak(1);
    $section->addText(
        'Generated on ' . date('Y-m-d H:i:s') . ' | © ' . date('Y') . ' City of Kigali',
        ['size' => 8, 'color' => '999999', 'alignment' => Jc::CENTER]
    );

    $filename = 'weekly_report_' . date('Y-m-d') . '.docx';
    $filepath = __DIR__ . '/../reports/' . $filename;
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($filepath);
    return $filename;
}

/**
 * Generate Daily Flash Report as Word Document
 */
function generateDailyFlashReport($inspectionId, $pdo) {
    $data = getInspectionData($inspectionId, $pdo);
    if (!$data) return null;
    
    $inspection = $data['inspection'];
    $nonCompliant = $data['stats']['non_compliant'];

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    $section->addText('City of Kigali', ['bold' => true, 'size' => 18, 'color' => '0033A0']);
    $section->addText('CoK Inspection Unit', ['size' => 12, 'color' => '666666']);
    $section->addTextBreak(1);
    
    $section->addText('DAILY FLASH REPORT', ['bold' => true, 'size' => 16]);
    $section->addText('Date: ' . date('Y-m-d'));
    $section->addTextBreak(1);
    
    $section->addText('Purpose of Inspection: Fire & Security Compliance');
    $section->addText('Entity: ' . $inspection['name']);
    $section->addText('Location: ' . ($inspection['district'] ?? '') . ', ' . ($inspection['sector'] ?? ''));
    $section->addText('UPI: ' . ($inspection['upi'] ?? '—'));
    $section->addText('Usage: ' . ($inspection['use_type'] ?? '—'));
    $section->addTextBreak(1);
    
    $section->addText('Findings:', ['bold' => true]);
    if (count($nonCompliant) > 0) {
        foreach ($nonCompliant as $item) {
            $section->addText('- ' . $item['label']);
        }
    } else {
        $section->addText('- All compliant', ['color' => '1F7A54']);
    }
    $section->addTextBreak(1);
    
    $section->addText('Recommendations:', ['bold' => true]);
    if (!empty($inspection['recommendations'])) {
        $section->addText($inspection['recommendations']);
    } else {
        $section->addText('None');
    }
    $section->addTextBreak(1);
    
    $section->addText('Action Taken:', ['bold' => true]);
    $section->addText('Inspection completed. Report generated.');
    $section->addTextBreak(1);
    
    $section->addText(
        'Generated on ' . date('Y-m-d H:i:s') . ' | © ' . date('Y') . ' City of Kigali',
        ['size' => 8, 'color' => '999999', 'alignment' => Jc::CENTER]
    );

    $filename = 'daily_flash_' . date('Y-m-d') . '.docx';
    $filepath = __DIR__ . '/../reports/' . $filename;
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($filepath);
    return $filename;
}
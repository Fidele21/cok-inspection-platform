<?php
/**
 * Petrol Station Report Page
 * GET /reports/report_petrol.php?id=123
 */

require_once '../config/database.php';
require_once '../includes/report_functions.php';
require_once '../includes/doc_generator.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if (!$id) {
    die("Inspection ID required");
}

$pdo = getDB();
$data = getInspectionData($id, $pdo);
if (!$data) {
    die("Inspection not found");
}

$inspection = $data['inspection'];
$stats = $data['stats'];
$nonCompliant = $data['stats']['non_compliant'];
$team = $data['team'];

// Get photos
$stmt = $pdo->prepare("SELECT photo_data FROM inspection_photos WHERE inspection_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate compliance score
$complianceScore = $stats['compliance_rate'];

// Determine decision based on score
function getDecision($score) {
    if ($score >= 81 && $score <= 100) {
        return ['decision' => 'Compliant', 'color' => 'green', 'icon' => '✅', 'message' => 'The facility meets all fire and security requirements.'];
    } elseif ($score >= 61 && $score <= 80) {
        return ['decision' => 'To Improve', 'color' => 'blue', 'icon' => '📈', 'message' => 'The facility has minor deficiencies that need to be addressed.'];
    } elseif ($score >= 50 && $score <= 60) {
        return ['decision' => 'Temporarily Closed', 'color' => 'yellow', 'icon' => '⚠️', 'message' => 'The facility has significant deficiencies requiring immediate action.'];
    } else {
        return ['decision' => 'Closed', 'color' => 'red', 'icon' => '❌', 'message' => 'The facility has critical deficiencies and must be closed until resolved.'];
    }
}

$decision = getDecision($complianceScore);

// ============================================================
// EXPORT AS WORD DOCUMENT
// ============================================================
if ($format === 'word') {
    $filename = generatePetrolReportWord($id, $pdo);
    if ($filename) {
        $filepath = __DIR__ . '/' . $filename;
        if (file_exists($filepath)) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/msword');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($filepath);
            exit;
        }
    }
    die("Failed to generate Word document");
}

// ============================================================
// DISPLAY HTML REPORT
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petrol Station Report - <?= htmlspecialchars($inspection['name']) ?></title>
    <style>
        :root {
            --cok-blue: #0033A0;
            --cok-green: #009A44;
            --cok-red: #EF4135;
            --cok-gold: #FFCD00;
        }
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f5f5; 
            margin: 0; 
            padding: 20px; 
        }
        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
        }
        .toolbar { 
            background: var(--cok-blue); 
            color: white; 
            padding: 15px 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        .toolbar a, .toolbar button { 
            color: white; 
            text-decoration: none; 
            background: rgba(255,255,255,0.15); 
            padding: 8px 16px; 
            border-radius: 4px; 
            border: none; 
            cursor: pointer; 
            font-size: 14px; 
            transition: background 0.3s; 
        }
        .toolbar a:hover, .toolbar button:hover { 
            background: rgba(255,255,255,0.25); 
        }
        .toolbar .btn-success { 
            background: var(--cok-green); 
        }
        .toolbar .btn-danger { 
            background: var(--cok-red); 
        }
        .report-container { 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            padding: 40px; 
        }
        .report-title {
            color: var(--cok-blue);
            font-size: 22px;
            text-align: center;
            margin-bottom: 20px;
        }
        .inspection-date {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: var(--cok-blue);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: top;
        }
        .missing-item {
            color: var(--cok-red);
            font-weight: 500;
        }
        .recommendation-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            min-height: 100px;
        }
        .recommendation-box textarea {
            width: 100%;
            min-height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            resize: vertical;
        }
        .decision-matrix {
            margin: 25px 0;
        }
        .decision-matrix table {
            width: 100%;
            border-collapse: collapse;
        }
        .decision-matrix th {
            background: var(--cok-blue);
            color: white;
            padding: 10px;
        }
        .decision-matrix td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        .decision-result {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        .decision-result.green { background: #d4edda; color: #155724; border: 2px solid var(--cok-green); }
        .decision-result.blue { background: #cce5ff; color: #004085; border: 2px solid #004085; }
        .decision-result.yellow { background: #fff3cd; color: #856404; border: 2px solid var(--cok-gold); }
        .decision-result.red { background: #f8d7da; color: #721c24; border: 2px solid var(--cok-red); }
        .team-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .team-table th {
            background: var(--cok-blue);
            color: white;
            padding: 8px;
        }
        .photo-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 10px 0;
        }
        .photo-item {
            width: 120px;
            height: 120px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-caption {
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .save-btn {
            background: var(--cok-green);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .save-btn:hover {
            background: #007a36;
        }
        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none !important; }
            .report-container { box-shadow: none; padding: 0; }
            .save-btn { display: none; }
            .recommendation-box textarea { border: none; background: transparent; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="toolbar no-print">
            <div>
                <a href="../index.php?view=records">← Back to Records</a>
                <span style="margin-left: 12px; font-size: 13px; opacity: 0.8;">
                    <?= htmlspecialchars($inspection['name']) ?>
                </span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button onclick="saveRecommendation()" class="btn-success">💾 Save Recommendation</button>
                <a href="?id=<?= $id ?>&format=word" class="btn-success">📥 Download Word</a>
                <button onclick="window.print()" class="btn-success">🖨️ Print / PDF</button>
                <a href="../index.php?view=form&edit=<?= $id ?>" style="background: rgba(255,255,255,0.25);">✏️ Edit</a>
            </div>
        </div>

        <div class="report-container" id="report-content">
            <!-- Report Title -->
            <h1 class="report-title">Fire & Security Inspection Report of <?= htmlspecialchars($inspection['name']) ?></h1>
            <p class="inspection-date"><strong>Date of Inspection:</strong> <?= htmlspecialchars($inspection['inspection_date']) ?></p>

            <!-- Main Table -->
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">S/N</th>
                        <th style="width:35%;">Identification</th>
                        <th style="width:35%;">Missing Items</th>
                        <th style="width:20%;">Photos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:center;">1</td>
                        <td>
                            <strong><?= htmlspecialchars($inspection['name']) ?></strong><br>
                            <small>
                                Owner: <?= htmlspecialchars($inspection['owner'] ?? '—') ?><br>
                                Address: <?= htmlspecialchars($inspection['district'] ?? '') ?>, <?= htmlspecialchars($inspection['sector'] ?? '') ?><br>
                                UPI: <?= htmlspecialchars($inspection['upi'] ?? '—') ?>
                            </small>
                        </td>
                        <td>
                            <?php if (count($nonCompliant) > 0): ?>
                                <ul style="margin:0; padding-left:20px;">
                                <?php foreach ($nonCompliant as $item): ?>
                                    <li class="missing-item"><?= htmlspecialchars($item['label']) ?></li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span style="color: var(--cok-green); font-weight: bold;">✅ All items compliant</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($photos)): ?>
                                <div class="photo-gallery">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="photo-item">
                                        <img src="<?= htmlspecialchars($photo) ?>" alt="Photo <?= $index+1 ?>">
                                        <div class="photo-caption">Photo <?= $index+1 ?></div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">No photos</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- General Recommendation -->
            <h3 style="color: var(--cok-blue); margin-top: 30px;">General Recommendation</h3>
            <div class="recommendation-box" id="recommendation-box">
                <textarea id="recommendation-text" placeholder="Enter your recommendations here..."><?= htmlspecialchars($inspection['recommendations'] ?? '') ?></textarea>
                <button class="save-btn" onclick="saveRecommendation()">💾 Save Recommendation</button>
                <span id="save-status" style="margin-left: 10px; color: var(--cok-green); font-weight: bold;"></span>
            </div>

            <!-- Decision Matrix -->
            <div class="decision-matrix">
                <h3 style="color: var(--cok-blue);">Decision Based on Marks</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Mark Range</th>
                            <th>Indicator Color</th>
                            <th>Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>0-49</td><td style="background: var(--cok-red); color: white;">Red</td><td>Closed</td></tr>
                        <tr><td>50-60</td><td style="background: var(--cok-gold);">Yellow</td><td>Temporarily Closed</td></tr>
                        <tr><td>61-80</td><td style="background: #004085; color: white;">Blue</td><td>To Improve</td></tr>
                        <tr><td>81-100</td><td style="background: var(--cok-green); color: white;">Green</td><td>Compliant</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Decision Result -->
            <div class="decision-result <?= $decision['color'] ?>">
                <?= $decision['icon'] ?> Score: <?= $complianceScore ?>% – <strong><?= $decision['decision'] ?></strong>
                <br><span style="font-size: 14px; font-weight: normal;"><?= $decision['message'] ?></span>
            </div>

            <!-- Inspection Team -->
            <h3 style="color: var(--cok-blue); margin-top: 30px;">Inspection Team</h3>
            <table class="team-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Institution</th>
                        <th>Post</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($team)): ?>
                        <?php $i = 1; foreach ($team as $member): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($member['name']) ?></td>
                            <td><?= htmlspecialchars($member['institution'] ?? '') ?></td>
                            <td><?= htmlspecialchars($member['signature'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td>1</td>
                            <td>ITANGISHAKA Fidele</td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function saveRecommendation() {
            const text = document.getElementById('recommendation-text').value;
            const status = document.getElementById('save-status');
            
            // Send to server via AJAX
            fetch('../api/save_recommendation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    inspection_id: <?= $id ?>,
                    recommendations: text
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    status.textContent = '✅ Saved successfully!';
                    status.style.color = 'var(--cok-green)';
                    setTimeout(() => { status.textContent = ''; }, 3000);
                } else {
                    status.textContent = '❌ Error saving';
                    status.style.color = 'var(--cok-red)';
                }
            })
            .catch(error => {
                status.textContent = '❌ Error: ' + error.message;
                status.style.color = 'var(--cok-red)';
            });
        }

        // Auto-save on blur (when user leaves the textarea)
        document.getElementById('recommendation-text').addEventListener('blur', function() {
            saveRecommendation();
        });
    </script>
</body>
</html>
<?php
/**
 * Report Generation Page
 * Filters: Report Type, Date Range, Entity Type, Min Compliance %, District, UPI
 * Status displays decision matrix: P.Closed, T.Closed+Fine, Improve+Fine, Complaint
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDB();
$entityTypes = $pdo->query("SELECT code, name FROM entity_types")->fetchAll();
$districts = $pdo->query("SELECT DISTINCT district FROM entities WHERE district IS NOT NULL AND district != '' ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - City of Kigali</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .filter-group label { font-weight: 600; font-size: 13px; color: #555; display: block; margin-bottom: 4px; }
        .filter-group select, .filter-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #0033A0; color: white; }
        .btn-success { background: #009A44; color: white; }
        .btn-secondary { background: #e9ecef; color: #333; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .results-table th { background: #0033A0; color: white; padding: 10px; text-align: left; }
        .results-table td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
        .results-table tr:hover { background: #f5f5f5; }
        .loading { text-align: center; padding: 40px; color: #666; }
        .spinner { display: inline-block; width: 30px; height: 30px; border: 3px solid #ddd; border-top-color: #0033A0; border-radius: 50%; animation: spin 0.8s linear infinite; vertical-align: middle; margin-right: 10px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .export-buttons { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .hint { font-size: 12px; color: #888; margin-top: 4px; }
        /* Decision matrix colors */
        .status-red { color: #EF4135; font-weight: 600; }
        .status-yellow { color: #B4790E; font-weight: 600; }
        .status-blue { color: #004085; font-weight: 600; }
        .status-green { color: #009A44; font-weight: 600; }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        .status-badge.red { background: #EF4135; color: white; }
        .status-badge.yellow { background: #FFCD00; color: #333; }
        .status-badge.blue { background: #004085; color: white; }
        .status-badge.green { background: #009A44; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="color: #0033A0;">📊 Report Generation</h1>
        <a href="../index.php?view=dashboard" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <form id="report-form">
        <div class="filter-grid">
            <div class="filter-group">
                <label>Report Type</label>
                <select name="report_type" id="report_type">
                    <option value="daily">Daily</option>
                    <option value="weekly" selected>Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annual">Annual</option>
                </select>
            </div>
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="from_date" id="from_date" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
            </div>
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="to_date" id="to_date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="filter-group">
                <label>Entity Type</label>
                <select name="entity_type" id="entity_type">
                    <option value="all">All</option>
                    <?php foreach ($entityTypes as $et): ?>
                        <option value="<?= $et['code'] ?>"><?= $et['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Min Compliance %</label>
                <input type="number" name="min_compliance" id="min_compliance" 
                       value="0" step="any" min="0" max="100" placeholder="e.g. 75.5">
                <div class="hint">Show only inspections with compliance ≥ this value</div>
            </div>
            <div class="filter-group">
                <label>District</label>
                <select name="district" id="district">
                    <option value="">All Districts</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>UPI</label>
                <input type="text" name="upi" id="upi" placeholder="Search by UPI...">
            </div>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">🔍 Generate Report</button>
            <button type="button" class="btn btn-success" onclick="exportCSV()">📥 Export CSV</button>
            <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Print</button>
        </div>
    </form>

    <div id="results-container">
        <div class="loading"><div class="spinner"></div> Applying filters...</div>
    </div>
</div>

<script>
document.getElementById('report-form').addEventListener('submit', function(e) {
    e.preventDefault();
    generateReport();
});

async function generateReport() {
    const container = document.getElementById('results-container');
    container.innerHTML = '<div class="loading"><div class="spinner"></div> Generating report...</div>';

    const formData = new FormData(document.getElementById('report-form'));
    const params = new URLSearchParams(formData);

    try {
        const response = await fetch('../api/generate_report.php?' + params.toString());
        const data = await response.json();
        if (data.success) {
            renderResults(data);
        } else {
            container.innerHTML = `<div style="color: #EF4135;">Error: ${data.error}</div>`;
        }
    } catch (error) {
        container.innerHTML = `<div style="color: #EF4135;">Error: ${error.message}</div>`;
    }
}

/**
 * Get decision matrix status based on compliance rate
 */
function getDecisionMatrix(rate) {
    if (rate === null) {
        return { label: 'Pending', cssClass: 'status-badge', color: '#6c757d' };
    }
    if (rate >= 81 && rate <= 100) {
        return { label: 'Complaint', cssClass: 'status-badge green', color: '#009A44' };
    } else if (rate >= 61 && rate <= 80) {
        return { label: 'Improve+Fine', cssClass: 'status-badge blue', color: '#004085' };
    } else if (rate >= 50 && rate <= 60) {
        return { label: 'T.Closed+Fine', cssClass: 'status-badge yellow', color: '#B4790E' };
    } else if (rate >= 0 && rate <= 49) {
        return { label: 'P.Closed', cssClass: 'status-badge red', color: '#EF4135' };
    }
    return { label: 'Pending', cssClass: 'status-badge', color: '#6c757d' };
}

function renderResults(data) {
    const container = document.getElementById('results-container');
    if (!data.rows || data.rows.length === 0) {
        container.innerHTML = '<div class="empty-state">No records found.</div>';
        return;
    }

    let html = `<div style="overflow-x:auto;"><table class="results-table">
        <thead><tr>
            <th>#</th><th>Entity Name</th><th>Owner</th><th>District</th><th>Entity Type</th>
            <th>Date</th><th>Compliance %</th><th>Decision</th><th>Action</th>
        </tr></thead><tbody>`;
    data.rows.forEach((row, i) => {
        const rate = row.compliance_rate !== null ? row.compliance_rate : null;
        const displayRate = rate !== null ? rate.toFixed(1) + '%' : '—';
        const decision = getDecisionMatrix(rate);
        
        html += `<tr>
            <td>${i+1}</td>
            <td>${escapeHtml(row.entity_name)}</td>
            <td>${escapeHtml(row.owner || '—')}</td>
            <td>${escapeHtml(row.district || '—')}</td>
            <td>${escapeHtml(row.entity_type)}</td>
            <td>${row.inspection_date}</td>
            <td>${displayRate}</td>
            <td><span class="${decision.cssClass}">${decision.label}</span></td>
            <td><a href="../reports/report.php?id=${row.inspection_id}" class="btn btn-secondary btn-sm" target="_blank">View</a></td>
        </tr>`;
    });
    html += `</tbody></table></div>`;
    html += `<div style="margin-top:10px; font-size:13px; color:#666;">Total: ${data.rows.length} records</div>`;
    container.innerHTML = html;
}

function exportCSV() {
    const table = document.querySelector('.results-table');
    if (!table) { alert('No data to export.'); return; }
    let csv = [];
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        csv.push(Array.from(cols).map(c => c.textContent.trim()).join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'report.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

window.onload = function() {
    generateReport();
};
</script>
</body>
</html>
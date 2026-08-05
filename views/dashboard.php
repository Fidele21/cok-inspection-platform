<div class="view active" id="view-dashboard">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
        <div>
            <h2 class="section-title">Dashboard</h2>
            <p class="subtitle" style="margin-bottom:0;">Overview of inspection activity and compliance performance.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="insights.php" class="btn btn-primary btn-sm">Compliance Analysis</a>
            <a href="reports/index.php" class="btn btn-secondary btn-sm">Reports</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="background: var(--cok-blue); color: white; padding: 16px 20px; margin-bottom: 20px;">
        <div style="display:flex; flex-wrap:wrap; gap:15px; align-items:flex-end;">
            <div class="filter-group" style="flex:1; min-width:150px;">
                <label style="color:rgba(255,255,255,0.82); font-size:11px; text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">District</label>
                <select id="filter-district" style="width:100%; padding:8px; border-radius:4px; border:none;">
                    <option value="">All Districts</option>
                </select>
            </div>
            <div class="filter-group" style="flex:1; min-width:150px;">
                <label style="color:rgba(255,255,255,0.82); font-size:11px; text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">Sector</label>
                <select id="filter-sector" style="width:100%; padding:8px; border-radius:4px; border:none;">
                    <option value="">All Sectors</option>
                </select>
            </div>
            <div class="filter-group" style="flex:1; min-width:150px;">
                <label style="color:rgba(255,255,255,0.82); font-size:11px; text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">Cell</label>
                <select id="filter-cell" style="width:100%; padding:8px; border-radius:4px; border:none;">
                    <option value="">All Cells</option>
                </select>
            </div>
            <div class="filter-group" style="flex:0 0 auto; display:flex; gap:8px;">
                <button id="apply-filters" class="btn btn-success" style="background: var(--cok-gold); color: var(--cok-blue); font-weight:700;">Apply Filters</button>
                <button id="reset-filters" class="btn btn-secondary" style="background: rgba(255,255,255,0.18); color:#fff;">Reset</button>
            </div>
        </div>
    </div>

    <!-- Key figures -->
    <div class="stats-grid" id="dashboard-stats">
        <div class="stat-card"><div class="number" id="stat-total">0</div><div class="label">Total Inspections</div></div>
        <div class="stat-card"><div class="number green" id="stat-month">0</div><div class="label">This Month</div></div>
        <div class="stat-card"><div class="number amber" id="stat-week">0</div><div class="label">This Week</div></div>
        <div class="stat-card"><div class="number" id="stat-entities">0</div><div class="label">Premises on Record</div></div>
        <div class="stat-card"><div class="number green" id="stat-compliance">0%</div><div class="label">Mean Compliance</div></div>
        <div class="stat-card"><div class="number" id="stat-compliant-entities">0</div><div class="label">Compliant Premises</div></div>
    </div>

    <!-- Charts -->
    <div class="dashboard-row">
        <div>
            <div class="card">
                <h3>Inspection Volume <span class="badge">By Month</span></h3>
                <div id="monthly-breakdown"><div class="loading"><div class="spinner"></div> Loading</div></div>
            </div>
            <div class="card">
                <h3>Compliance Trend <span class="badge">Monthly Mean</span></h3>
                <div id="monthly-compliance"><div class="loading"><div class="spinner"></div> Loading</div></div>
            </div>
        </div>
        <div>
            <div class="card">
                <h3>District Performance <span class="badge">Ranked</span></h3>
                <div id="district-performance"><div class="loading"><div class="spinner"></div> Loading</div></div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card no-print">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="?view=building" class="btn btn-primary">New Building Inspection</a>
            <a href="?view=petrol" class="btn btn-success">New Petrol Station Inspection</a>
            <a href="?view=records" class="btn btn-secondary">Records</a>
            <a href="insights.php" class="btn btn-secondary">Compliance Analysis</a>
            <a href="reports/index.php" class="btn btn-secondary">Generate Report</a>
        </div>
    </div>

</div>
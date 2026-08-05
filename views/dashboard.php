<div class="view active" id="view-dashboard">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:8px;">
        <div>
            <h2 class="section-title">📊 Dashboard</h2>
            <p class="subtitle" style="margin-bottom:0;">Real-time overview of inspections and compliance.</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
            
            <a href="reports/index.php" class="btn btn-primary btn-sm">📊 Reports</a>
            
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card" style="background: var(--cok-blue); color: white; padding: 16px 20px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <div class="filter-group" style="flex:1; min-width:150px;">
                <label style="color: rgba(255,255,255,0.8); font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">District</label>
                <select id="filter-district" style="width:100%; padding:8px; border-radius:4px; border:none;">
                    <option value="">All Districts</option>
                </select>
            </div>
            <div class="filter-group" style="flex:1; min-width:150px;">
                <label style="color: rgba(255,255,255,0.8); font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Sector</label>
                <select id="filter-sector" style="width:100%; padding:8px; border-radius:4px; border:none;">
                    <option value="">All Sectors</option>
                </select>
            </div>
            <div class="filter-group" style="flex:1; min-width:150px;">
                <label style="color: rgba(255,255,255,0.8); font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Cell</label>
                <select id="filter-cell" style="width:100%; padding:8px; border-radius:4px; border:none;">
                    <option value="">All Cells</option>
                </select>
            </div>
            <div class="filter-group" style="flex:0 0 auto;">
                <button id="apply-filters" class="btn btn-success" style="background: var(--cok-gold); color: var(--cok-blue); font-weight:700;">Apply Filters</button>
                <button id="reset-filters" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color:white;">Reset</button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid" id="dashboard-stats">
        <div class="stat-card"><div class="number" id="stat-total">0</div><div class="label">Total Inspections</div></div>
        <div class="stat-card"><div class="number green" id="stat-month">0</div><div class="label">This Month</div></div>
        <div class="stat-card"><div class="number amber" id="stat-week">0</div><div class="label">This Week</div></div>
        <div class="stat-card"><div class="number" id="stat-entities">0</div><div class="label">Total Entities</div></div>
        <div class="stat-card"><div class="number green" id="stat-compliance">0%</div><div class="label">Avg. Compliance</div></div>
        <div class="stat-card"><div class="number" id="stat-compliant-entities">0</div><div class="label">Compliant Entities</div></div>
    </div>

    <!-- Row: Charts -->
    <div class="dashboard-row">
        <div>
            <div class="card">
                <h3>📊 Monthly Performance <span class="badge">Building vs Petrol</span></h3>
                <div id="monthly-breakdown"><div class="loading"><div class="spinner"></div> Loading...</div></div>
            </div>
            <div class="card">
                <h3>📈 Compliance Trends <span class="badge">Monthly %</span></h3>
                <div id="monthly-compliance"><div class="loading"><div class="spinner"></div> Loading...</div></div>
            </div>
        </div>
        <div>
            <div class="card">
                <h3>📍 District Performance <span class="badge">All Districts</span></h3>
                <div id="district-performance"><div class="loading"><div class="spinner"></div> Loading...</div></div>
            </div>
        </div>
    </div>

    <div class="card no-print">
        <h3>⚡ Quick Actions</h3>
        <div class="action-buttons">
            <a href="?view=building" class="btn btn-primary">🏢 New Building</a>
            <a href="?view=petrol" class="btn btn-success">⛽ New Petrol Station</a>
            <a href="?view=records" class="btn btn-secondary">📁 Records</a>
            <a href="reports/index.php" class="btn btn-primary">📊 Generate Report</a>
        </div>
    </div>
</div>
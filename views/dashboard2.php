<div class="view active" id="view-dashboard">

    <div class="dash-hero">
        <div class="dash-hero-text">
            <h2>Welcome back 👋</h2>
            <p>Here's the compliance overview across all City of Kigali inspections.</p>
        </div>
        <div class="dash-hero-date" id="dash-hero-date">Loading date…</div>
    </div>

    <div class="stats-grid" id="dashboard-stats">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon">📊</div>
            </div>
            <div class="number" id="stat-total">0</div>
            <div class="label">Total Inspections</div>
        </div>
        <div class="stat-card green">
            <div class="stat-top">
                <div class="stat-icon">📅</div>
            </div>
            <div class="number green" id="stat-month">0</div>
            <div class="label">This Month</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-top">
                <div class="stat-icon">🗓️</div>
            </div>
            <div class="number amber" id="stat-week">0</div>
            <div class="label">This Week</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-top">
                <div class="stat-icon">🏢</div>
            </div>
            <div class="number" id="stat-entities">0</div>
            <div class="label">Total Entities</div>
        </div>
        <div class="stat-card green">
            <div class="stat-top">
                <div class="stat-icon">✅</div>
            </div>
            <div class="number green" id="stat-compliance">0%</div>
            <div class="label">Avg. Compliance</div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="card">
            <h3>
                <span class="h3-left">📋 Recent Inspections</span>
                <span class="h3-meta">Latest activity</span>
            </h3>
            <div id="recent-inspections"><div class="loading"><div class="spinner"></div> Loading...</div></div>
        </div>

        <div class="card">
            <h3>
                <span class="h3-left">🏢 District Performance</span>
                <span class="h3-meta">Compliance by district</span>
            </h3>
            <div id="district-performance"><div class="loading"><div class="spinner"></div> Loading...</div></div>
        </div>
    </div>

    <div class="card no-print">
        <h3><span class="h3-left">⚡ Quick Actions</span></h3>
        <div class="quick-actions-grid">
            <a href="?view=building" class="quick-action-card">
                <div class="qa-icon">🏢</div>
                <div class="qa-text">
                    <strong>Building Inspection</strong>
                    <span>Category 4/5 occupied buildings</span>
                </div>
            </a>
            <a href="?view=petrol" class="quick-action-card">
                <div class="qa-icon">⛽</div>
                <div class="qa-text">
                    <strong>Petrol Inspection</strong>
                    <span>Fuel station compliance checklist</span>
                </div>
            </a>
            <a href="?view=records" class="quick-action-card">
                <div class="qa-icon">📁</div>
                <div class="qa-text">
                    <strong>Records</strong>
                    <span>Browse saved inspections</span>
                </div>
            </a>
        </div>
    </div>
</div>

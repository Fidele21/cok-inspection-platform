<div class="view active" id="view-records">
    <h2 class="section-title">📁 Records</h2>
    <p class="subtitle">All saved inspections, searchable and filterable.</p>

    <div class="records-toolbar no-print">
        <input type="text" id="records-search" placeholder="Search by entity, district, owner...">
        <select id="records-sort">
            <option value="date-desc">Newest first</option>
            <option value="date-asc">Oldest first</option>
            <option value="name-asc">Name A–Z</option>
            <option value="score-asc">Lowest compliance first</option>
        </select>
        <button class="btn btn-secondary" onclick="loadInspections()">🔄 Refresh</button>
    </div>
    <div id="records-list"><div class="loading"><div class="spinner"></div> Loading...</div></div>
</div>
<div class="view active" id="view-form">
    <h2 class="section-title" id="form-heading">⛽ Petrol Service Station Inspection</h2>
    <p class="subtitle">Fire & security compliance checklist for petrol stations. <span style="color: var(--cok-red);">* All items are mandatory</span></p>

    <input type="hidden" id="f-entity-type" value="petrol">

    <!-- Entity Details -->
    <div class="card">
        <h3>Entity Details</h3>
        <div class="grid-2">
            <div class="field">
                <label>Petrol Station <span class="required">*</span></label>
                <input type="text" id="f-name" placeholder="Petrol station name">
            </div>
            <div class="field">
                <label>Land Owner <span class="required">*</span></label>
                <input type="text" id="f-owner" placeholder="Owner or representative name">
            </div>
            <div class="field">
                <label>Owner Telephone</label>
                <input type="tel" id="f-tel" placeholder="078...">
            </div>
            <div class="field">
                <label>Owner Email</label>
                <input type="email" id="f-email" placeholder="name@example.com">
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>District</label>
                <select id="f-district"><option value="">Select District</option></select>
            </div>
            <div class="field">
                <label>Sector</label>
                <select id="f-sector"><option value="">Select Sector</option></select>
            </div>
            <div class="field">
                <label>Cell</label>
                <select id="f-cell"><option value="">Select Cell</option></select>
            </div>
            <div class="field">
                <label>Zoning</label>
                <input type="text" id="f-zoning" placeholder="e.g., Commercial, Residential">
            </div>
            <div class="field">
                <label>UPI</label>
                <input type="text" id="f-upi" placeholder="Parcel UPI">
            </div>
        </div>
        <!-- GPS Location -->
        <div style="margin-top: 10px;">
            <div class="grid-2">
                <div class="field">
                    <label>📍 Latitude</label>
                    <input type="text" id="f-latitude" placeholder="e.g. -1.9441" step="any">
                    <small>Decimal degrees</small>
                </div>
                <div class="field">
                    <label>📍 Longitude</label>
                    <input type="text" id="f-longitude" placeholder="e.g. 30.0619" step="any">
                    <small>Decimal degrees</small>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" onclick="getCurrentLocation()" style="margin-top:4px;">
                📍 Get Current Location
            </button>
        </div>
        <div class="field" style="max-width:220px; margin-top:10px;">
            <label>Inspection Date</label>
            <input type="date" id="f-date">
        </div>
    </div>

    <!-- Decision Matrix Preview -->
    <div class="card" style="background: var(--paper);">
        <h3>📊 Decision Matrix</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; font-size: 13px;">
            <div style="background: #EF4135; color: white; padding: 8px; border-radius: 4px; text-align: center; font-weight: bold;">0-49%<br><span style="font-weight: normal;">Red</span><br>P.Closed</div>
            <div style="background: #FFCD00; color: #333; padding: 8px; border-radius: 4px; text-align: center; font-weight: bold;">50-60%<br><span style="font-weight: normal;">Yellow</span><br>T.Closed+Fine</div>
            <div style="background: #004085; color: white; padding: 8px; border-radius: 4px; text-align: center; font-weight: bold;">61-80%<br><span style="font-weight: normal;">Blue</span><br>Improve+Fine</div>
            <div style="background: #009A44; color: white; padding: 8px; border-radius: 4px; text-align: center; font-weight: bold; grid-column: span 3;">81-100%<br><span style="font-weight: normal;">Green</span><br>Complaint</div>
        </div>
    </div>

    <!-- Checklist Stats -->
    <div class="checklist-stats no-print" id="checklist-stats">
        <div class="stat-item"><span class="count total" id="stat-total-items">0</span> Total Items</div>
        <div class="stat-item"><span class="count yes" id="stat-yes">0</span> Compliant (YES)</div>
        <div class="stat-item"><span class="count no" id="stat-no">0</span> Non-Compliant (NO)</div>
        <div class="stat-item"><span class="count na" id="stat-na">0</span> Not Applicable</div>
        <div class="stat-item"><span id="stat-pct">0%</span> Complete</div>
    </div>

    <!-- Checklist Sections -->
    <div id="checklist-sections">
        <div class="card" style="text-align:center; padding:60px 20px; color:var(--ink-soft);">
            <div style="font-size:48px; margin-bottom:16px;">⛽</div>
            <p>Loading petrol station checklist...</p>
        </div>
    </div>

    <!-- ===== PHOTO CAPTURE SECTION (MOVED BEFORE OBSERVATIONS) ===== -->
    <div class="card">
        <h3>📸 Inspection Photos <span class="badge">Max 3 photos</span></h3>
        <div id="photo-container">
            <div id="photo-preview-area" style="display: flex; flex-wrap: wrap; gap: 12px; min-height: 60px; padding: 8px; background: var(--paper); border-radius: 8px; border: 2px dashed var(--line); margin-bottom: 12px;"></div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <button type="button" class="btn btn-primary" id="take-photo-btn" style="background: var(--cok-blue);">
                    📷 Take Photo
                </button>
                <button type="button" class="btn btn-secondary" id="gallery-btn" style="background: var(--cok-green); color: white;">
                    🖼️ Gallery
                </button>
                <input type="file" id="photo-upload-input" accept="image/*" capture="environment" style="display: none;">
                <input type="file" id="gallery-upload-input" accept="image/*" multiple style="display: none;">
                <span id="photo-count" style="font-size: 13px; color: var(--ink-soft);">0 / 3 photos</span>
            </div>
            <div id="photo-error" style="color: var(--cok-red); font-size: 13px; margin-top: 8px; display: none;"></div>
        </div>
    </div>

    <!-- Observations & Recommendations -->
    <div class="card">
        <h3>Observations &amp; Recommendations</h3>
        <div class="field"><label>General Observations</label><textarea id="f-observations" placeholder="What did you observe on site?"></textarea></div>
        <div class="field"><label>General Recommendations</label><textarea id="f-recommendations" placeholder="What must be corrected, and by when?"></textarea></div>
        <div class="field"><label>Entity Owner / Representative Recommendations</label><textarea id="f-owner-rec" placeholder="Any input from the entity owner or representative"></textarea></div>
    </div>

    <!-- Inspection Team -->
    <div class="card">
        <h3>Inspection Team</h3>
        <div id="team-rows"></div>
        <button class="btn btn-ghost" id="add-team-row" type="button">+ Add team member</button>
        <div class="field" style="max-width:320px; margin-top:16px;">
            <label>Entity Owner / Representative Name</label>
            <input type="text" id="f-owner-rep-name" placeholder="Present at inspection">
        </div>
    </div>

    <input type="hidden" id="f-photos" value="">

    <div class="btn-row no-print">
        <button class="btn btn-ghost" id="clear-form">Clear Form</button>
        <button class="btn btn-primary" id="save-inspection">Save Inspection</button>
    </div>
</div>

<script>
function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                document.getElementById('f-latitude').value = pos.coords.latitude.toFixed(7);
                document.getElementById('f-longitude').value = pos.coords.longitude.toFixed(7);
                showToast('📍 Location captured', 'success');
            },
            function(err) {
                showToast('❌ Unable to get location: ' + err.message, 'error');
            }
        );
    } else {
        showToast('❌ Geolocation not supported by your browser', 'error');
    }
}
</script>
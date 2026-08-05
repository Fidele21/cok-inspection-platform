<div class="view active" id="view-form">
    <h2 class="section-title" id="form-heading">🏢 Building Inspection (Category 4/5)</h2>
    <p class="subtitle">Fire & security compliance checklist for occupied buildings.</p>

    <input type="hidden" id="f-entity-type" value="building">

    <!-- Entity Details -->
    <div class="card">
        <h3>Entity Details</h3>
        <div class="grid-2">
            <div class="field"><label>Entity Name <span class="required">*</span></label><input type="text" id="f-name" placeholder="e.g. Kigali Heights Building"></div>
            <div class="field"><label>Entity Owner</label><input type="text" id="f-owner" placeholder="Owner or representative name"></div>
            <div class="field"><label>Owner Telephone</label><input type="tel" id="f-tel" placeholder="078..."></div>
            <div class="field"><label>Owner Email</label><input type="email" id="f-email" placeholder="name@example.com"></div>
            <div class="field"><label>Entity Use</label>
                <select id="f-use"><option value="">Select use type</option></select>
            </div>
            <div class="field"><label>UPI</label><input type="text" id="f-upi" placeholder="Parcel UPI"></div>
        </div>
        <div class="grid-3">
            <div class="field"><label>District</label>
                <select id="f-district"><option value="">Select District</option></select>
            </div>
            <div class="field"><label>Sector</label>
                <select id="f-sector"><option value="">Select Sector</option></select>
            </div>
            <div class="field"><label>Cell</label>
                <select id="f-cell"><option value="">Select Cell</option></select>
            </div>
        </div>
        <div class="field" style="max-width:220px;">
            <label>Inspection Date</label>
            <input type="date" id="f-date">
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

    <!-- Checklist -->
    <div id="checklist-sections">
        <div class="card" style="text-align:center; padding:60px 20px; color:var(--ink-soft);">
            <div style="font-size:48px; margin-bottom:16px;">📋</div>
            <p>Loading building checklist...</p>
        </div>
    </div>

    <!-- ===== PHOTO CAPTURE SECTION ===== -->
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

    <!-- Hidden input to store photo data -->
    <input type="hidden" id="f-photos" value="">

    <div class="btn-row no-print">
        <button class="btn btn-ghost" id="clear-form">Clear Form</button>
        <button class="btn btn-primary" id="save-inspection">Save Inspection</button>
    </div>
</div>

<style>
    #photo-preview-area:empty::before {
        content: 'No photos taken yet';
        color: var(--ink-soft);
        font-size: 14px;
        width: 100%;
        text-align: center;
        padding: 16px 0;
    }
    .photo-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid var(--line);
        box-shadow: var(--shadow);
        background: var(--paper);
    }
    .photo-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-delete-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        background: var(--cok-red);
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
        z-index: 10;
    }
    .photo-delete-btn:hover {
        transform: scale(1.1);
    }
    .photo-number-badge {
        position: absolute;
        bottom: 4px;
        left: 4px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        z-index: 10;
    }
    #take-photo-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
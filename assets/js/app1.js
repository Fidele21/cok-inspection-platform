/**
 * Digital Inspection Platform - Complete Application Logic
 * Handles cascading dropdowns, dashboard filtering, checklist, records, and photo capture.
 */

// ============================================================
// STATE
// ============================================================

const state = {
    currentView: typeof appConfig !== 'undefined' ? appConfig.currentView : 'dashboard',
    currentRecord: null,
    inspections: [],
    entityType: 'building',
    currentChecklist: null,
    isSaving: false,
    answers: {},
    filters: { district: '', sector: '', cell: '' }
};

// ============================================================
// DOM HELPERS
// ============================================================

const $ = (id) => document.getElementById(id);
const $$ = (sel) => document.querySelectorAll(sel);

// ============================================================
// TOAST
// ============================================================

let toastTimeout;

function showToast(message, type = 'info') {
    const toast = $('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toast.classList.remove('show'), 4000);
}

// ============================================================
// UTILITY
// ============================================================

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// ============================================================
// CITY OF KIGALI LOCATIONS – Cascading Dropdowns
// ============================================================

let kigaliData = null;

async function loadKigaliData() {
    if (kigaliData) return kigaliData;
    try {
        const response = await fetch('api/get_all_locations.php');
        const data = await response.json();
        if (data.success) {
            kigaliData = data.data;
            console.log('✅ Kigali location data loaded:', Object.keys(kigaliData).length, 'districts');
            return kigaliData;
        }
    } catch (e) {
        console.error('Error loading Kigali data:', e);
    }
    return null;
}

async function loadDistricts(selectId) {
    const sel = $(selectId);
    if (!sel) return;
    const data = await loadKigaliData();
    if (data) {
        sel.innerHTML = '<option value="">Select District</option>';
        Object.keys(data).forEach(d => {
            const opt = document.createElement('option');
            opt.value = d;
            opt.textContent = d;
            sel.appendChild(opt);
        });
    }
}

async function loadSectors(selectId, district, clearCell = true) {
    const sel = $(selectId);
    if (!sel) return;
    const data = await loadKigaliData();
    if (!data) return;
    
    if (!district || !data[district]) {
        sel.innerHTML = '<option value="">Select Sector</option>';
        if (clearCell) {
            const cellId = selectId.replace('sector', 'cell');
            const cellSel = $(cellId);
            if (cellSel) cellSel.innerHTML = '<option value="">Select Cell</option>';
        }
        return;
    }
    
    sel.innerHTML = '<option value="">Select Sector</option>';
    Object.keys(data[district].sectors).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.textContent = s;
        sel.appendChild(opt);
    });
    
    if (clearCell) {
        const cellId = selectId.replace('sector', 'cell');
        const cellSel = $(cellId);
        if (cellSel) cellSel.innerHTML = '<option value="">Select Cell</option>';
    }
}

async function loadCells(selectId, sector) {
    const sel = $(selectId);
    if (!sel) return;
    const data = await loadKigaliData();
    if (!data) return;
    
    if (!sector) {
        sel.innerHTML = '<option value="">Select Cell</option>';
        return;
    }
    
    let cells = [];
    for (const district of Object.values(data)) {
        if (district.sectors && district.sectors[sector]) {
            cells = district.sectors[sector];
            break;
        }
    }
    
    sel.innerHTML = '<option value="">Select Cell</option>';
    cells.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        sel.appendChild(opt);
    });
}

function setupCascadingDropdowns(prefix = 'f-') {
    const districtSel = $(`${prefix}district`);
    const sectorSel = $(`${prefix}sector`);
    const cellSel = $(`${prefix}cell`);

    if (districtSel) {
        loadDistricts(`${prefix}district`);
        districtSel.addEventListener('change', function() {
            loadSectors(`${prefix}sector`, this.value, true);
        });
    }
    if (sectorSel) {
        sectorSel.addEventListener('change', function() {
            loadCells(`${prefix}cell`, this.value);
        });
    }
}

// ============================================================
// DASHBOARD FILTERS
// ============================================================

function setupDashboardFilters() {
    setupCascadingDropdowns('filter-');
    
    const applyBtn = $('apply-filters');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            state.filters.district = $('filter-district')?.value || '';
            state.filters.sector = $('filter-sector')?.value || '';
            state.filters.cell = $('filter-cell')?.value || '';
            loadDashboard();
        });
    }
    
    const resetBtn = $('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            ['filter-district', 'filter-sector', 'filter-cell'].forEach(id => {
                const el = $(id);
                if (el) el.value = '';
            });
            state.filters = { district: '', sector: '', cell: '' };
            loadDashboard();
        });
    }
}

// ============================================================
// DASHBOARD
// ============================================================

async function loadDashboard() {
    const totalEl = $('stat-total');
    if (!totalEl) {
        console.warn('⚠️ Dashboard containers not found');
        return;
    }
    try {
        const params = new URLSearchParams();
        if (state.filters.district) params.append('district', state.filters.district);
        if (state.filters.sector) params.append('sector', state.filters.sector);
        if (state.filters.cell) params.append('cell', state.filters.cell);
        const url = 'api/get_stats.php?' + params.toString();
        const response = await fetch(url);
        const data = await response.json();
        if (data.success) {
            const stats = data.stats;
            $('stat-total').textContent = stats.total_inspections || 0;
            $('stat-month').textContent = stats.this_month || 0;
            $('stat-week').textContent = stats.this_week || 0;
            $('stat-entities').textContent = stats.total_entities || 0;
            $('stat-compliance').textContent = (stats.avg_compliance || 0) + '%';
            $('stat-compliant-entities').textContent = stats.compliant_count || 0;

            renderMonthlyBreakdown(data.monthly_breakdown || []);
            renderMonthlyCompliance(data.monthly_compliance || []);
            renderDistrictPerformance(data.districts || []);
        }
    } catch (error) {
        console.error('Dashboard error:', error);
    }
}

function renderMonthlyBreakdown(breakdown) {
    const container = $('monthly-breakdown');
    if (!container) return;
    if (!breakdown || breakdown.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="glyph">📊</div><div>No monthly data available</div></div>';
        return;
    }
    const months = {};
    breakdown.forEach(item => {
        if (!months[item.month]) months[item.month] = { building: 0, petrol: 0 };
        if (item.entity_type === 'building') months[item.month].building += parseInt(item.total) || 0;
        else if (item.entity_type === 'petrol') months[item.month].petrol += parseInt(item.total) || 0;
    });
    const monthKeys = Object.keys(months).sort();
    const maxVal = Math.max(...monthKeys.map(m => months[m].building + months[m].petrol), 1);
    const chartHeight = 180;
    let html = '<div class="column-chart">';
    monthKeys.forEach(month => {
        const building = months[month].building || 0;
        const petrol = months[month].petrol || 0;
        const bH = Math.max(4, Math.round((building / maxVal) * chartHeight));
        const pH = Math.max(4, Math.round((petrol / maxVal) * chartHeight));
        html += `
            <div class="column-group">
                <div class="column building" style="height:${bH}px;" title="Building: ${building}"></div>
                <div class="column petrol" style="height:${pH}px;" title="Petrol: ${petrol}"></div>
                <div class="column-label">${month}</div>
            </div>
        `;
    });
    html += '</div>';
    html += `<div class="legend"><span><span class="dot blue"></span> Building</span><span><span class="dot green"></span> Petrol</span></div>`;
    container.innerHTML = html;
}

function renderMonthlyCompliance(compliance) {
    const container = $('monthly-compliance');
    if (!container) return;
    if (!compliance || compliance.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="glyph">📈</div><div>No compliance data available</div></div>';
        return;
    }
    const chartHeight = 180;
    let html = '<div class="column-chart">';
    compliance.forEach(item => {
        const rate = Math.round(parseFloat(item.compliance_rate) || 0);
        const h = Math.max(4, Math.round((rate / 100) * chartHeight));
        let cssClass = 'good';
        if (rate < 50) cssClass = 'poor';
        else if (rate < 70) cssClass = 'average';
        html += `
            <div class="column-group">
                <div class="compliance-column ${cssClass}" style="height:${h}px;" title="${rate}%"></div>
                <div class="column-label">${item.month}</div>
                <div class="column-value">${rate}%</div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function renderDistrictPerformance(districts) {
    const container = $('district-performance');
    if (!container) return;
    if (!districts || districts.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="glyph">📍</div><div>No district data available</div></div>';
        return;
    }
    const required = ['Gasabo', 'Kicukiro', 'Nyarugenge'];
    const existing = districts.map(d => d.district);
    required.forEach(d => {
        if (!existing.includes(d)) {
            districts.push({ district: d, total_inspections: 0, avg_compliance: 0 });
        }
    });
    const maxInspections = Math.max(...districts.map(d => parseInt(d.total_inspections) || 0), 1);
    let html = '';
    districts.forEach(d => {
        const total = parseInt(d.total_inspections) || 0;
        const pct = Math.max(4, Math.round((total / maxInspections) * 100));
        const comp = Math.round(parseFloat(d.avg_compliance) || 0);
        let color = 'var(--cok-green)';
        if (comp < 50) color = 'var(--cok-red)';
        else if (comp < 70) color = 'var(--cok-gold)';
        html += `
            <div style="margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span><strong>${escapeHtml(d.district)}</strong> <span style="color:var(--ink-soft);">(${total} inspections)</span></span>
                    <span style="font-weight:600; color:${color};">${comp}% compliance</span>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <div style="flex:1; height:10px; background:var(--paper); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:${pct}%; background:var(--cok-blue); border-radius:4px; transition:width 0.8s;"></div>
                    </div>
                    <span style="font-size:12px; color:var(--ink-soft); min-width:40px;">${total}</span>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

// ============================================================
// POPULATE USE TYPES
// ============================================================

async function populateDropdowns() {
    try {
        const response = await fetch('api/get_filter_options.php');
        const data = await response.json();
        if (data.success) {
            const useSelect = $('f-use');
            if (useSelect) {
                useSelect.innerHTML = '<option value="">Select use type</option>';
                (data.use_types || []).forEach(val => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    useSelect.appendChild(opt);
                });
            }
        }
    } catch (error) {
        console.error('Error populating dropdowns:', error);
    }
}

// ============================================================
// CHECKLIST FUNCTIONS
// ============================================================

async function loadChecklist(type) {
    const container = $('checklist-sections');
    if (!container) return;
    state.entityType = type;
    container.innerHTML = `<div class="loading"><div class="spinner"></div> Loading checklist...</div>`;
    try {
        const response = await fetch(`api/entity_types.php?type=${type}`);
        const data = await response.json();
        if (data.success && data.sections && data.sections.length > 0) {
            state.currentChecklist = data;
            renderChecklist(data.sections);
            updateStats();
        } else {
            throw new Error(data.error || 'No checklist data found');
        }
    } catch (error) {
        console.error('Error loading checklist:', error);
        container.innerHTML = `
            <div class="card" style="text-align:center; padding:40px; color:var(--cok-red);">
                <div style="font-size:36px; margin-bottom:12px;">⚠️</div>
                <p>Failed to load checklist: ${error.message}</p>
                <p style="font-size:12px; color:var(--ink-soft); margin-top:8px;">
                    Make sure the database is seeded. Visit <a href="api/seed_complete.php" target="_blank">api/seed_complete.php</a>
                </p>
            </div>
        `;
    }
}

function renderChecklist(sections) {
    const container = $('checklist-sections');
    if (!container) return;
    container.innerHTML = '';
    let totalItems = 0;

    sections.forEach((section, secIdx) => {
        const card = document.createElement('div');
        card.className = 'card';
        card.dataset.section = secIdx;

        const header = document.createElement('div');
        header.className = 'section-header';
        header.innerHTML = `
            <div><span class="badge-num">${section.number}</span> ${section.title}</div>
            <div class="section-count">${section.items.length} items</div>
        `;
        card.appendChild(header);

        const progressWrap = document.createElement('div');
        progressWrap.className = 'section-progress';
        progressWrap.innerHTML = `<div class="progress-bar" id="section-progress-${secIdx}" style="width:0%;"></div>`;
        card.appendChild(progressWrap);

        section.items.forEach((item, itemIdx) => {
            const row = document.createElement('div');
            row.className = 'checklist-item';
            row.dataset.itemId = item.id;
            const ans = state.answers[item.id] || { status: '', comment: '' };
            const itemNumber = totalItems + itemIdx + 1;

            row.innerHTML = `
                <div class="item-label">
                    <span class="item-number">${String(itemNumber).padStart(2, '0')}.</span>
                    <span class="item-text">${escapeHtml(item.label)}</span>
                </div>
                <div class="status-group">
                    <button type="button" class="status-btn ${ans.status === 'yes' ? 'on' : ''}" data-v="yes">✓ YES</button>
                    <button type="button" class="status-btn ${ans.status === 'no' ? 'on' : ''}" data-v="no">✗ NO</button>
                    <button type="button" class="status-btn ${ans.status === 'na' ? 'on' : ''}" data-v="na">— N/A</button>
                </div>
                <div class="comment-input">
                    <input type="text" placeholder="Comment (optional)" class="item-comment" value="${escapeHtml(ans.comment || '')}">
                </div>
            `;

            const statusBtns = row.querySelectorAll('.status-btn');
            statusBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const itemId = parseInt(row.dataset.itemId);
                    const value = btn.dataset.v;
                    if (btn.classList.contains('on')) {
                        btn.classList.remove('on');
                        state.answers[itemId] = { status: '', comment: state.answers[itemId]?.comment || '' };
                    } else {
                        statusBtns.forEach(b => b.classList.remove('on'));
                        btn.classList.add('on');
                        state.answers[itemId] = { status: value, comment: state.answers[itemId]?.comment || '' };
                    }
                    updateStats();
                });
            });

            const commentInput = row.querySelector('.item-comment');
            commentInput.addEventListener('input', () => {
                const itemId = parseInt(row.dataset.itemId);
                if (!state.answers[itemId]) state.answers[itemId] = { status: '', comment: '' };
                state.answers[itemId].comment = commentInput.value;
            });

            card.appendChild(row);
            totalItems++;
        });

        container.appendChild(card);
    });
}

function updateStats() {
    if (!$('stat-total-items')) return;
    const items = document.querySelectorAll('.checklist-item');
    let yes = 0, no = 0, na = 0, blank = 0;
    items.forEach(row => {
        const active = row.querySelector('.status-btn.on');
        if (active) {
            const val = active.dataset.v;
            if (val === 'yes') yes++;
            else if (val === 'no') no++;
            else if (val === 'na') na++;
        } else blank++;
    });
    const total = yes + no + na + blank;
    const complete = total > 0 ? Math.round(((yes + no + na) / total) * 100) : 0;
    $('stat-total-items').textContent = total;
    $('stat-yes').textContent = yes;
    $('stat-no').textContent = no;
    $('stat-na').textContent = na;
    $('stat-pct').textContent = complete + '%';

    document.querySelectorAll('.card').forEach((card, idx) => {
        const itemsInSection = card.querySelectorAll('.checklist-item');
        const answered = card.querySelectorAll('.status-btn.on');
        const pct = itemsInSection.length > 0 ? Math.round((answered.length / itemsInSection.length) * 100) : 0;
        const bar = card.querySelector('.progress-bar');
        if (bar) bar.style.width = pct + '%';
    });
}

// ============================================================
// TEAM ROWS
// ============================================================

function addTeamRow(name = '', institution = '', signature = '') {
    const container = $('team-rows');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'team-row';
    const idx = container.children.length;
    row.innerHTML = `
        <input type="text" placeholder="Name" value="${escapeHtml(name)}" data-team-field="name" data-idx="${idx}">
        <input type="text" placeholder="Institution" value="${escapeHtml(institution)}" data-team-field="institution" data-idx="${idx}">
        <input type="text" placeholder="Signature (typed)" value="${escapeHtml(signature)}" data-team-field="signature" data-idx="${idx}">
        <button type="button" class="icon-btn" data-remove-team="${idx}" title="Remove member">×</button>
    `;
    container.appendChild(row);
    row.querySelector('[data-remove-team]').addEventListener('click', () => {
        if (container.children.length <= 1) {
            showToast('At least one team member is required', 'error');
            return;
        }
        row.remove();
        reindexTeamRows();
    });
}

function reindexTeamRows() {
    const container = $('team-rows');
    if (!container) return;
    container.querySelectorAll('.team-row').forEach((row, idx) => {
        row.querySelectorAll('[data-idx]').forEach(el => el.dataset.idx = idx);
    });
}

function getTeamData() {
    const container = $('team-rows');
    if (!container) return [];
    const team = [];
    container.querySelectorAll('.team-row').forEach(row => {
        const name = row.querySelector('[data-team-field="name"]')?.value || '';
        const institution = row.querySelector('[data-team-field="institution"]')?.value || '';
        const signature = row.querySelector('[data-team-field="signature"]')?.value || '';
        if (name.trim()) team.push({ name, institution, signature });
    });
    return team;
}

// ============================================================
// PHOTO CAPTURE – Complete Working Version
// ============================================================

let inspectionPhotos = [];
const MAX_PHOTOS = 3;

function initPhotoCapture() {
    console.log('📸 initPhotoCapture() called');
    
    const takeBtn = document.getElementById('take-photo-btn');
    const galleryBtn = document.getElementById('gallery-btn');
    const fileInput = document.getElementById('photo-upload-input');
    const galleryInput = document.getElementById('gallery-upload-input');
    const previewArea = document.getElementById('photo-preview-area');
    const photoCount = document.getElementById('photo-count');
    const photoError = document.getElementById('photo-error');

    if (!takeBtn && !galleryBtn) {
        console.warn('⚠️ Photo buttons not found');
        return;
    }

    // === TAKE PHOTO (Camera) ===
    if (takeBtn) {
        takeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('📷 Take Photo clicked');
            if (inspectionPhotos.length >= MAX_PHOTOS) {
                showPhotoError('Maximum 3 photos allowed');
                return;
            }
            const input = document.getElementById('photo-upload-input');
            if (input) input.click();
        });
    }

    // === GALLERY UPLOAD ===
    if (galleryBtn) {
        galleryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖼️ Gallery clicked');
            if (inspectionPhotos.length >= MAX_PHOTOS) {
                showPhotoError('Maximum 3 photos allowed');
                return;
            }
            const input = document.getElementById('gallery-upload-input');
            if (input) input.click();
        });
    }

    // === FILE INPUT HANDLERS ===
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                handleFile(this.files[0]);
                this.value = '';
            }
        });
    }

    if (galleryInput) {
        galleryInput.addEventListener('change', function(e) {
            if (this.files) {
                const remaining = MAX_PHOTOS - inspectionPhotos.length;
                const toProcess = Math.min(this.files.length, remaining);
                for (let i = 0; i < toProcess; i++) {
                    handleFile(this.files[i]);
                }
                this.value = '';
            }
        });
    }

    // === PASTE FROM CLIPBOARD ===
    document.addEventListener('paste', function(e) {
        if (inspectionPhotos.length >= MAX_PHOTOS) {
            showPhotoError('Maximum 3 photos allowed');
            return;
        }
        const items = e.clipboardData.items;
        for (let item of items) {
            if (item.type.startsWith('image/')) {
                const file = item.getAsFile();
                const reader = new FileReader();
                reader.onload = function(event) {
                    addPhoto(event.target.result);
                };
                reader.readAsDataURL(file);
                break;
            }
        }
    });

    // === HANDLE FILE ===
    function handleFile(file) {
        if (!file.type.startsWith('image/')) {
            showPhotoError('Please select an image file');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showPhotoError('Photo size exceeds 5MB limit');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(event) {
            // Compress large images
            let dataUrl = event.target.result;
            if (dataUrl.length > 300000) {
                compressImage(dataUrl, function(compressed) {
                    addPhoto(compressed);
                });
            } else {
                addPhoto(dataUrl);
            }
            hidePhotoError();
        };
        reader.onerror = function() {
            showPhotoError('Failed to read file');
        };
        reader.readAsDataURL(file);
    }

    // === COMPRESS IMAGE ===
    function compressImage(dataUrl, callback) {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const maxWidth = 800;
            const maxHeight = 800;
            let width = img.width;
            let height = img.height;
            if (width > height) {
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width = Math.round((width * maxHeight) / height);
                    height = maxHeight;
                }
            }
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);
            callback(canvas.toDataURL('image/jpeg', 0.75));
        };
        img.src = dataUrl;
    }

    // === ADD PHOTO ===
    function addPhoto(dataUrl) {
        if (inspectionPhotos.length >= MAX_PHOTOS) {
            showPhotoError('Maximum 3 photos allowed');
            return;
        }
        inspectionPhotos.push(dataUrl);
        renderPhotos();
        updatePhotoCount();
        updatePhotoHiddenInput();
        hidePhotoError();
        console.log('📸 Photos now:', inspectionPhotos.length);
    }

    // === RENDER PHOTOS ===
    function renderPhotos() {
        const previewEl = document.getElementById('photo-preview-area');
        if (!previewEl) return;
        previewEl.innerHTML = '';
        if (inspectionPhotos.length === 0) return;
        
        inspectionPhotos.forEach((dataUrl, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'photo-wrapper';
            
            const img = document.createElement('img');
            img.src = dataUrl;
            img.alt = `Photo ${index + 1}`;
            wrapper.appendChild(img);
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'photo-delete-btn';
            deleteBtn.innerHTML = '×';
            deleteBtn.title = 'Delete photo';
            deleteBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (confirm('Delete this photo?')) {
                    inspectionPhotos.splice(index, 1);
                    renderPhotos();
                    updatePhotoCount();
                    updatePhotoHiddenInput();
                }
            });
            wrapper.appendChild(deleteBtn);
            
            const badge = document.createElement('span');
            badge.className = 'photo-number-badge';
            badge.textContent = `#${index + 1}`;
            wrapper.appendChild(badge);
            
            previewEl.appendChild(wrapper);
        });
    }

    // === UPDATE COUNT ===
    function updatePhotoCount() {
        const countEl = document.getElementById('photo-count');
        const btn = document.getElementById('take-photo-btn');
        if (countEl) countEl.textContent = `${inspectionPhotos.length} / ${MAX_PHOTOS} photos`;
        if (btn) {
            btn.disabled = inspectionPhotos.length >= MAX_PHOTOS;
            btn.style.opacity = inspectionPhotos.length >= MAX_PHOTOS ? '0.5' : '1';
        }
    }

    // === UPDATE HIDDEN INPUT ===
    function updatePhotoHiddenInput() {
        const hiddenInput = document.getElementById('f-photos');
        if (hiddenInput) {
            try {
                hiddenInput.value = JSON.stringify(inspectionPhotos);
            } catch (e) {
                console.warn('Failed to stringify photos:', e);
            }
        }
    }

    // === ERROR DISPLAY ===
    function showPhotoError(message) {
        const errorEl = document.getElementById('photo-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 4000);
        } else {
            console.warn('Photo error:', message);
        }
    }

    function hidePhotoError() {
        const errorEl = document.getElementById('photo-error');
        if (errorEl) errorEl.style.display = 'none';
    }

    // === LOAD PHOTOS FOR EDITING ===
    window.loadPhotos = function(photos) {
        if (Array.isArray(photos)) {
            inspectionPhotos = photos.slice(0, MAX_PHOTOS);
            renderPhotos();
            updatePhotoCount();
            updatePhotoHiddenInput();
            console.log('📸 Loaded', inspectionPhotos.length, 'photos for editing');
        }
    };

    // Initial setup
    updatePhotoCount();
    console.log('📸 Photo capture initialized – max', MAX_PHOTOS, 'photos');
}

// ============================================================
// FORM OPERATIONS
// ============================================================

function getFormData() {
    return {
        entityType: $('f-entity-type')?.value || 'building',
        name: $('f-name')?.value.trim() || '',
        owner: $('f-owner')?.value.trim() || '',
        tel: $('f-tel')?.value.trim() || '',
        email: $('f-email')?.value.trim() || '',
        use: $('f-use')?.value || '',
        upi: $('f-upi')?.value.trim() || '',
        district: $('f-district')?.value || '',
        sector: $('f-sector')?.value || '',
        cell: $('f-cell')?.value || '',
        date: $('f-date')?.value || new Date().toISOString().slice(0, 10),
        observations: $('f-observations')?.value.trim() || '',
        recommendations: $('f-recommendations')?.value.trim() || '',
        ownerRec: $('f-owner-rec')?.value.trim() || '',
        ownerRepName: $('f-owner-rep-name')?.value.trim() || '',
        answers: { ...state.answers },
        team: getTeamData(),
        inspector: 'City of Kigali Inspector',
        photos: inspectionPhotos
    };
}

function clearForm() {
    if (!confirm('Clear this form? Unsaved changes will be lost.')) return;
    ['f-name', 'f-owner', 'f-tel', 'f-email', 'f-upi', 'f-observations', 'f-recommendations', 'f-owner-rec', 'f-owner-rep-name'].forEach(id => {
        const el = $(id);
        if (el) el.value = '';
    });
    ['f-use', 'f-district', 'f-sector', 'f-cell'].forEach(id => {
        const el = $(id);
        if (el) el.selectedIndex = 0;
    });
    if ($('f-date')) $('f-date').value = new Date().toISOString().slice(0, 10);
    state.answers = {};
    const container = $('team-rows');
    if (container) { container.innerHTML = ''; addTeamRow(); }
    state.currentRecord = null;
    if ($('form-heading')) $('form-heading').textContent = 'New Inspection';
    
    // Clear photos
    inspectionPhotos = [];
    const previewArea = document.getElementById('photo-preview-area');
    if (previewArea) previewArea.innerHTML = '';
    const photoCount = document.getElementById('photo-count');
    if (photoCount) photoCount.textContent = '0 / 3 photos';
    const hiddenInput = document.getElementById('f-photos');
    if (hiddenInput) hiddenInput.value = '';
    const takeBtn = document.getElementById('take-photo-btn');
    if (takeBtn) { takeBtn.disabled = false; takeBtn.style.opacity = '1'; }
    
    loadChecklist(state.entityType);
    showToast('Form cleared');
}

// ============================================================
// SAVE INSPECTION
// ============================================================

async function saveInspection() {
    if (state.isSaving) return;
    const data = getFormData();
    if (!data.name) { showToast('Please enter an entity name', 'error'); if ($('f-name')) $('f-name').focus(); return; }
    if (!data.date) { showToast('Please select an inspection date', 'error'); if ($('f-date')) $('f-date').focus(); return; }
    const hasResponse = Object.values(data.answers).some(a => a.status);
    if (!hasResponse) { showToast('Please complete at least one checklist item', 'error'); return; }

    state.isSaving = true;
    const saveBtn = $('save-inspection');
    if (saveBtn) { saveBtn.textContent = 'Saving...'; saveBtn.disabled = true; }

    try {
        const formattedAnswers = {};
        Object.keys(data.answers).forEach(key => {
            const dbId = parseInt(key);
            if (!isNaN(dbId) && dbId > 0) {
                formattedAnswers[dbId] = { status: data.answers[key].status || '', comment: data.answers[key].comment || '' };
            }
        });
        data.answers = formattedAnswers;
        if (state.currentRecord && state.currentRecord.id) {
            data.inspectionId = parseInt(state.currentRecord.id);
        }

        const response = await fetch('api/save_inspection.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showToast('✅ Inspection saved successfully!', 'success');
            state.currentRecord = result;
            if ($('form-heading')) $('form-heading').textContent = `Editing — ${data.name}`;
            await loadInspections();
            await loadDashboard();
            if (confirm('Inspection saved! Would you like to view the report?')) {
                window.open(`reports/report.php?id=${result.inspectionId}`, '_blank');
            }
        } else {
            throw new Error(result.error || 'Failed to save');
        }
    } catch (error) {
        console.error('Save error:', error);
        showToast('Error saving: ' + error.message, 'error');
    } finally {
        state.isSaving = false;
        if (saveBtn) { saveBtn.textContent = 'Save Inspection'; saveBtn.disabled = false; }
    }
}

// ============================================================
// RECORDS MANAGEMENT
// ============================================================

async function loadInspections() {
    const list = $('records-list');
    if (!list) return;
    try {
        const search = $('records-search')?.value || '';
        const sort = $('records-sort')?.value || 'date-desc';
        const response = await fetch(`api/get_inspections.php?search=${encodeURIComponent(search)}&sort=${sort}`);
        const data = await response.json();
        if (data.success) {
            state.inspections = data.inspections;
            renderRecords();
        } else {
            throw new Error(data.error || 'Failed to load inspections');
        }
    } catch (error) {
        console.error('Error loading inspections:', error);
        state.inspections = [];
        renderRecords();
    }
}

function renderRecords() {
    const list = $('records-list');
    if (!list) return;
    const items = state.inspections;
    if (items.length === 0) {
        list.innerHTML = `
            <div class="empty-state"><div class="glyph">📋</div><div>No inspections saved yet.<br>Fill out a checklist and save it to see it here.</div></div>
        `;
        return;
    }

    list.innerHTML = items.map(item => {
        const rate = item.compliance_rate !== null ? `${item.compliance_rate}%` : '—';
        let pillClass = 'badge-secondary';
        if (item.compliance_rate !== null) {
            if (item.compliance_rate >= 80) pillClass = 'badge-success';
            else if (item.compliance_rate >= 50) pillClass = 'badge-warning';
            else pillClass = 'badge-danger';
        }
        const badgeClass = item.entity_type_code === 'petrol' ? 'petrol' : 'building';
        const badgeLabel = item.entity_type_code === 'petrol' ? '⛽ Petrol' : '🏢 Building';
        return `
            <div class="record-item" data-id="${item.inspection_id}">
                <div class="record-main">
                    <h4>${escapeHtml(item.entity_name)} <span class="entity-badge ${badgeClass}">${badgeLabel}</span></h4>
                    <div class="record-meta">${escapeHtml(item.district || '—')}${item.sector ? ', ' + escapeHtml(item.sector) : ''} · ${item.inspection_date || 'No date'}</div>
                </div>
                <span class="score-pill ${pillClass}">${rate}</span>
                <div class="record-actions no-print">
                    <a href="reports/report.php?id=${item.inspection_id}" class="btn btn-secondary btn-sm" target="_blank">📄 Report</a>
                    <a href="reports/letter.php?id=${item.inspection_id}" class="btn btn-danger btn-sm" target="_blank">📨 Letter</a>
                    <button class="btn btn-ghost btn-sm" data-action="edit" data-id="${item.inspection_id}">✏️ Edit</button>
                    <button class="btn btn-danger btn-sm" data-action="delete" data-id="${item.inspection_id}">🗑️</button>
                </div>
            </div>
        `;
    }).join('');

    list.querySelectorAll('[data-action="edit"]').forEach(btn => {
        btn.addEventListener('click', () => editInspection(btn.dataset.id));
    });
    list.querySelectorAll('[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', () => deleteInspection(btn.dataset.id));
    });
}

async function loadSingleInspection(id) {
    try {
        const response = await fetch(`api/get_inspection.php?id=${id}`);
        const data = await response.json();
        if (data.success) return data;
        throw new Error(data.error || 'Failed to load inspection');
    } catch (error) {
        showToast('Error loading inspection: ' + error.message, 'error');
        return null;
    }
}

async function deleteInspection(id) {
    if (!confirm('Delete this inspection? This cannot be undone.')) return;
    try {
        const response = await fetch(`api/delete_inspection.php?id=${id}`, { method: 'DELETE' });
        const data = await response.json();
        if (data.success) {
            showToast('Inspection deleted', 'success');
            await loadInspections();
            await loadDashboard();
        } else {
            throw new Error(data.error || 'Failed to delete');
        }
    } catch (error) {
        showToast('Error deleting: ' + error.message, 'error');
    }
}

async function editInspection(id) {
    const data = await loadSingleInspection(id);
    if (!data) return;
    const insp = data.inspection;
    state.currentRecord = { id: insp.inspection_id };
    const answers = {};
    data.answers.forEach(a => { answers[a.item_id] = { status: a.status || '', comment: a.comment || '' }; });

    const record = {
        id: insp.inspection_id,
        entityType: insp.entity_type_code || 'building',
        name: insp.entity_name,
        owner: insp.owner,
        tel: insp.telephone,
        email: insp.email,
        use: insp.use_type,
        upi: insp.upi,
        district: insp.district,
        sector: insp.sector,
        cell: insp.cell,
        date: insp.inspection_date,
        observations: insp.observations,
        recommendations: insp.recommendations,
        ownerRec: insp.owner_recommendations,
        ownerRepName: insp.owner_rep_name,
        answers: answers,
        team: data.team || []
    };

    state.answers = answers;
    ['name', 'owner', 'tel', 'email', 'upi', 'observations', 'recommendations', 'ownerRec', 'ownerRepName'].forEach(field => {
        const el = $(`f-${field}`);
        if (el) el.value = record[field] || '';
    });
    ['use', 'district', 'sector', 'cell'].forEach(field => {
        const el = $(`f-${field}`);
        if (el) {
            const options = el.options;
            let found = false;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === record[field]) {
                    el.selectedIndex = i;
                    found = true;
                    break;
                }
            }
            if (!found && record[field]) {
                const opt = document.createElement('option');
                opt.value = record[field];
                opt.textContent = record[field];
                el.appendChild(opt);
                el.value = record[field];
            }
        }
    });
    if ($('f-date')) $('f-date').value = record.date || new Date().toISOString().slice(0, 10);
    if ($('f-entity-type')) $('f-entity-type').value = record.entityType;

    state.entityType = record.entityType;
    await loadChecklist(record.entityType);
    const container = $('team-rows');
    if (container) {
        container.innerHTML = '';
        if (record.team && record.team.length > 0) {
            record.team.forEach(m => addTeamRow(m.name, m.institution, m.signature));
        } else {
            addTeamRow();
        }
    }
    
    // Load photos
    if (data.photos && Array.isArray(data.photos)) {
        window.loadPhotos(data.photos);
    }
    
    if ($('form-heading')) $('form-heading').textContent = `Editing — ${record.name}`;
    window.location.href = `?view=${record.entityType === 'petrol' ? 'petrol' : 'building'}`;
}

// ============================================================
// INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    const view = typeof appConfig !== 'undefined' ? appConfig.currentView : 'dashboard';
    const editId = typeof appConfig !== 'undefined' ? (appConfig.editId || 0) : 0;

    // Show welcome message with username
    if (appConfig.username) {
        console.log(`👋 Welcome, ${appConfig.username}!`);
    }

    if ($('f-date')) {
        $('f-date').value = new Date().toISOString().slice(0, 10);
    }

    if (view === 'dashboard') {
        setupDashboardFilters();
        loadDashboard();
        loadInspections();
    } else if (view === 'building' || view === 'petrol') {
        const type = view === 'petrol' ? 'petrol' : 'building';
        state.entityType = type;
        setupCascadingDropdowns('f-');
        populateDropdowns();
        
        // Initialize photo capture with a small delay to ensure DOM is ready
        setTimeout(function() {
            initPhotoCapture();
        }, 300);
        
        if ($('team-rows')) addTeamRow();
        if ($('add-team-row')) $('add-team-row').addEventListener('click', () => addTeamRow());
        if ($('save-inspection')) $('save-inspection').addEventListener('click', saveInspection);
        if ($('clear-form')) $('clear-form').addEventListener('click', clearForm);
        
        if (editId > 0) {
            editInspection(editId);
        } else {
            loadChecklist(type);
        }
    } else if (view === 'records') {
        loadInspections();
        if ($('records-search')) $('records-search').addEventListener('input', loadInspections);
        if ($('records-sort')) $('records-sort').addEventListener('change', loadInspections);
    }

    console.log('📋 Digital Inspection Platform initialized');
    console.log('📍 Current view:', view);
    console.log('🎨 City of Kigali color theme active');
});
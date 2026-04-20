<?php
require_once 'config.php';
$pageTitle = 'Import Excel';
$db = getDB();
$campaigns = $db->query("SELECT id, campaign_id, campaign_name FROM campaigns ORDER BY campaign_id");
include 'includes/header.php';
?>

<div class="row">
<div class="col-xl-10 mx-auto">

<!-- Step 1 -->
<div class="card mb-4" id="step1Card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">1</span> Upload Excel File</h6>
    </div>
    <div class="card-body">
        <div class="border-2 rounded p-5 text-center" id="dropZone"
            style="border:2px dashed #2d9cdb;cursor:pointer;background:#f8fbff;">
            <i class="fa fa-file-excel fa-3x text-success mb-3"></i>
            <h6>Drag &amp; drop your SolidPro Excel file here</h6>
            <p class="text-muted mb-3">Supports master file or individual tracker files (.xlsx / .xls)</p>
            <label class="btn btn-primary">
                <i class="fa fa-upload me-1"></i> Choose File
                <input type="file" id="excelFile" accept=".xlsx,.xls" style="display:none;">
            </label>
            <div id="fileName" class="mt-2 text-muted"></div>
        </div>
        <div class="mt-3 p-3 bg-light rounded small text-muted">
            <strong><i class="fa fa-circle-info text-primary me-1"></i> Expected format:</strong>
            Row 1 = Title, Row 2 = Instructions, <strong>Row 3 = Column Headers</strong>, Row 4+ = Data.
            Sheets detected: <em>Campaign Master, Creatives, Landing Pages, Content Writing, Lead Magnets, Email Sequences, Ad Copy, SEO, Webinars &amp; Events</em>.
        </div>
    </div>
</div>

<!-- Step 2 -->
<div class="card mb-4 d-none" id="step2Card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">2</span> Review &amp; Import</h6>
        <button class="btn btn-sm btn-outline-secondary" onclick="resetUpload()">
            <i class="fa fa-arrow-left me-1"></i> Back
        </button>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label fw-semibold">Default Campaign (for sheets without Campaign ID column)</label>
            <select class="form-select" id="defaultCampaign" style="max-width:380px;">
                <option value="">-- None --</option>
                <?php while ($cp = $campaigns->fetch_assoc()): ?>
                    <option value="<?= $cp['id'] ?>"><?= htmlspecialchars($cp['campaign_id'].' — '.$cp['campaign_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div id="sheetSummary"></div>

        <div class="mt-4">
            <h6 class="fw-semibold">Data Preview <span class="text-muted fw-normal" id="previewInfo"></span></h6>
            <div id="sheetTabs" class="mb-2"></div>
            <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                <table class="table table-sm table-bordered mb-0" id="previewTable">
                    <thead class="table-light"><tr id="previewHead"></tr></thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Step 3 -->
<div class="card d-none" id="step3Card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><span class="badge bg-success me-2">3</span> Import Complete</h6>
    </div>
    <div class="card-body text-center py-5">
        <i class="fa fa-circle-check fa-4x text-success mb-3" id="resultIcon"></i>
        <h5 id="resultMsg">Import Successful!</h5>
        <div id="resultDetails" class="text-muted mb-4"></div>
        <div class="d-flex gap-2 justify-content-center">
            <a href="index.php" class="btn btn-primary"><i class="fa fa-gauge me-1"></i> Dashboard</a>
            <a href="campaigns.php" class="btn btn-outline-secondary">View Campaigns</a>
            <button class="btn btn-outline-secondary" onclick="resetUpload()">Import Another</button>
        </div>
    </div>
</div>

<div id="importActions" class="d-none text-end mt-3">
    <button class="btn btn-success btn-lg" id="importBtn" onclick="doImport()">
        <i class="fa fa-database me-1"></i> Import to Database
    </button>
</div>

</div><!-- col -->
</div><!-- row -->

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
// Sheet name → asset type mapping
const SHEET_MAP = {
    'campaign master': 'campaigns',
    'creatives':        'creative',
    'landing pages':    'landing_page',
    'content writing':  'content_writing',
    'lead magnets':     'lead_magnet',
    'email sequences':  'email_sequence',
    'ad copy':          'ad_copy',
    'seo':              'seo',
    'webinars & events':'webinar_event',
    'webinars events':  'webinar_event',
    'webinars and events':'webinar_event',
};

const TYPE_ICONS = {
    campaigns: '🗂', creative:'🎨', landing_page:'🌐', content_writing:'✍',
    lead_magnet:'🧲', email_sequence:'📧', ad_copy:'📣', seo:'🔍', webinar_event:'🎤'
};

let parsedSheets = [];   // [{name, type, headers, rows}]
let activeSheetIdx = 0;

const fileInput = document.getElementById('excelFile');
const dropZone  = document.getElementById('dropZone');

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.background='#e8f4fc'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.background='#f8fbff'; });
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.style.background='#f8fbff';
    if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', () => { if (fileInput.files[0]) handleFile(fileInput.files[0]); });

function handleFile(file) {
    document.getElementById('fileName').textContent = file.name;
    const reader = new FileReader();
    reader.onload = function(e) {
        const wb = XLSX.read(e.target.result, { type:'array', cellDates:true });
        parsedSheets = [];
        wb.SheetNames.forEach(name => {
            const cleanName = name.replace(/[^\w\s&]/g,'').trim().toLowerCase();
            const type = Object.keys(SHEET_MAP).find(k => cleanName.includes(k));
            if (!type) return;  // skip Dashboard, How To Use etc.
            const mappedType = SHEET_MAP[type];

            const ws = wb.Sheets[name];
            const raw = XLSX.utils.sheet_to_json(ws, { header:1, defval:'', raw:false });
            if (raw.length < 4) return;

            // Row 3 (index 2) = headers, rows 4+ = data
            const headers = raw[2].map(h => String(h).trim());
            const dataRows = raw.slice(3).filter(r => r.some(c => String(c).trim() !== ''));
            if (dataRows.length === 0) return;

            parsedSheets.push({ name, type: mappedType, headers, rows: dataRows });
        });

        if (parsedSheets.length === 0) {
            alert('No recognised sheets found. Please upload a SolidPro Campaign Tracker file.');
            return;
        }
        showStep2();
    };
    reader.readAsArrayBuffer(file);
}

function showStep2() {
    document.getElementById('step1Card').classList.add('d-none');
    document.getElementById('step2Card').classList.remove('d-none');
    document.getElementById('importActions').classList.remove('d-none');
    renderSummary();
    showSheetPreview(0);
}

function renderSummary() {
    const html = parsedSheets.map((s, i) =>
        `<span class="badge me-2 mb-1" style="background:#e8f4fc;color:#1a5276;font-size:.82rem;cursor:pointer;padding:6px 12px;"
            onclick="showSheetPreview(${i})">
            ${TYPE_ICONS[s.type] || '📋'} ${escHtml(s.name)} <span class="text-success">(${s.rows.length} rows)</span>
        </span>`
    ).join('');
    document.getElementById('sheetSummary').innerHTML =
        `<div class="p-3 bg-light rounded mb-3">
            <strong>Detected Sheets:</strong><br><div class="mt-2">${html}</div>
        </div>`;
}

function showSheetPreview(idx) {
    activeSheetIdx = idx;
    const s = parsedSheets[idx];
    const head = document.getElementById('previewHead');
    head.innerHTML = s.headers.map(h => `<th>${escHtml(h)}</th>`).join('');
    const body = document.getElementById('previewBody');
    body.innerHTML = s.rows.slice(0,8).map(row =>
        `<tr>${s.headers.map((_,i) => `<td>${escHtml(String(row[i] || ''))}</td>`).join('')}</tr>`
    ).join('');
    document.getElementById('previewInfo').textContent =
        `— ${escHtml(s.name)} (${TYPE_ICONS[s.type]} ${s.type.replace(/_/g,' ')}) — showing first ${Math.min(8,s.rows.length)} of ${s.rows.length} rows`;
}

function doImport() {
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';

    const payload = {
        defaultCampaignId: document.getElementById('defaultCampaign').value,
        sheets: parsedSheets.map(s => ({
            type: s.type,
            name: s.name,
            headers: s.headers,
            rows: s.rows.map(r => s.headers.reduce((acc, h, i) => { acc[h] = String(r[i] || '').trim(); return acc; }, {}))
        }))
    };

    const fd = new FormData();
    fd.append('payload', JSON.stringify(payload));

    fetch('api/save_import.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            document.getElementById('step2Card').classList.add('d-none');
            document.getElementById('importActions').classList.add('d-none');
            document.getElementById('step3Card').classList.remove('d-none');
            const details = document.getElementById('resultDetails');
            if (res.success) {
                document.getElementById('resultMsg').textContent = 'Import Successful!';
                let html = '<ul class="list-unstyled">';
                (res.results || []).forEach(r => {
                    html += `<li><strong>${escHtml(r.sheet)}:</strong> ${r.imported} imported${r.skipped ? ', '+r.skipped+' skipped' : ''}</li>`;
                });
                html += '</ul>';
                details.innerHTML = html;
            } else {
                document.getElementById('resultIcon').className = 'fa fa-circle-xmark fa-4x text-danger mb-3';
                document.getElementById('resultMsg').textContent = 'Import Failed';
                details.textContent = res.message || 'Unknown error';
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-database me-1"></i> Import to Database';
            alert('Network error. Please try again.');
        });
}

function resetUpload() {
    fileInput.value = '';
    document.getElementById('fileName').textContent = '';
    parsedSheets = [];
    document.getElementById('step1Card').classList.remove('d-none');
    document.getElementById('step2Card').classList.add('d-none');
    document.getElementById('step3Card').classList.add('d-none');
    document.getElementById('importActions').classList.add('d-none');
    const btn = document.getElementById('importBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-database me-1"></i> Import to Database';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

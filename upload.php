<?php
require_once 'config.php';
$pageTitle = 'Import Excel';
$db = getDB();
$projects = $db->query("SELECT id, name FROM projects ORDER BY name");
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-10 mx-auto">
        <!-- Step 1: Upload -->
        <div class="card mb-4" id="step1Card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">1</span> Upload Excel File</h6>
            </div>
            <div class="card-body">
                <div class="border-2 border-dashed rounded p-5 text-center" id="dropZone"
                    style="border: 2px dashed #2d9cdb; cursor:pointer; background:#f8fbff;">
                    <i class="fa fa-file-excel fa-3x text-success mb-3"></i>
                    <h6>Drag &amp; drop your Excel file here</h6>
                    <p class="text-muted mb-3">Supports .xlsx and .xls files</p>
                    <label class="btn btn-primary">
                        <i class="fa fa-upload me-1"></i> Choose File
                        <input type="file" id="excelFile" accept=".xlsx,.xls" style="display:none;">
                    </label>
                    <div id="fileName" class="mt-2 text-muted"></div>
                </div>
                <div class="mt-3 p-3 bg-light rounded">
                    <strong><i class="fa fa-circle-info text-primary me-1"></i> Excel Format Tips:</strong>
                    <ul class="mb-0 mt-1 small text-muted">
                        <li>First row should be the header row (column names)</li>
                        <li>Common columns: Task Name, Assignee, Status, Priority, Due Date, Start Date, Notes</li>
                        <li>You can map any column names to the right fields in Step 2</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Step 2: Map Columns -->
        <div class="card mb-4 d-none" id="step2Card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">2</span> Map Columns &amp; Preview</h6>
                <button class="btn btn-sm btn-outline-secondary" onclick="resetUpload()">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4" id="columnMapper">
                    <!-- filled by JS -->
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Assign to Project</label>
                    <select class="form-select" id="importProject" style="max-width:300px;">
                        <option value="">-- No Project --</option>
                        <?php while ($pr = $projects->fetch_assoc()): ?>
                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <h6 class="fw-semibold mt-4 mb-2">Preview <span class="text-muted fw-normal" id="previewCount"></span></h6>
                <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
                    <table class="table table-sm table-bordered" id="previewTable">
                        <thead class="table-light"><tr id="previewHead"></tr></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Step 3: Import -->
        <div class="card d-none" id="step3Card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><span class="badge bg-success me-2">3</span> Import Complete</h6>
            </div>
            <div class="card-body text-center py-5">
                <i class="fa fa-circle-check fa-4x text-success mb-3"></i>
                <h5 id="importResultMsg">Import successful!</h5>
                <p class="text-muted" id="importResultDetail"></p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <a href="tasks.php" class="btn btn-primary"><i class="fa fa-list-check me-1"></i> View Tasks</a>
                    <button class="btn btn-outline-secondary" onclick="resetUpload()">Import Another</button>
                </div>
            </div>
        </div>

        <div id="importActions" class="d-none text-end mt-3">
            <button class="btn btn-success btn-lg" id="importBtn" onclick="doImport()">
                <i class="fa fa-database me-1"></i> Import to Database
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
const FIELD_MAP = {
    task_name:  ['task name','task','name','title','activity','work item','description'],
    assignee:   ['assignee','assigned to','owner','responsible','person','resource','team member'],
    status:     ['status','state','progress status'],
    priority:   ['priority','urgency','importance'],
    start_date: ['start date','start','begin date','from date'],
    due_date:   ['due date','due','end date','deadline','target date','finish date'],
    progress:   ['progress','% complete','completion','done %','percent'],
    notes:      ['notes','comments','remarks','description','details']
};

let parsedRows = [];
let headers = [];

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
        const wb = XLSX.read(e.target.result, { type: 'array', cellDates: true });
        const ws = wb.Sheets[wb.SheetNames[0]];
        const data = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
        if (data.length < 2) { alert('File appears empty or has no data rows.'); return; }
        headers = data[0].map(h => String(h).trim());
        parsedRows = data.slice(1).filter(r => r.some(c => c !== ''));
        showStep2();
    };
    reader.readAsArrayBuffer(file);
}

function guessMapping(header) {
    const h = header.toLowerCase().trim();
    for (const [field, synonyms] of Object.entries(FIELD_MAP)) {
        if (synonyms.includes(h)) return field;
    }
    return '';
}

function showStep2() {
    document.getElementById('step1Card').classList.add('d-none');
    document.getElementById('step2Card').classList.remove('d-none');
    document.getElementById('importActions').classList.remove('d-none');

    const mapper = document.getElementById('columnMapper');
    mapper.innerHTML = '';
    headers.forEach((h, i) => {
        const guess = guessMapping(h);
        mapper.innerHTML += `
        <div class="col-md-4 col-sm-6">
            <div class="p-3 bg-light rounded">
                <div class="text-muted small mb-1">Excel Column</div>
                <div class="fw-semibold mb-2">${escHtml(h)}</div>
                <select class="form-select form-select-sm" id="map_${i}">
                    <option value="">-- Skip --</option>
                    <option value="task_name" ${guess==='task_name'?'selected':''}>Task Name</option>
                    <option value="assignee" ${guess==='assignee'?'selected':''}>Assignee</option>
                    <option value="status" ${guess==='status'?'selected':''}>Status</option>
                    <option value="priority" ${guess==='priority'?'selected':''}>Priority</option>
                    <option value="start_date" ${guess==='start_date'?'selected':''}>Start Date</option>
                    <option value="due_date" ${guess==='due_date'?'selected':''}>Due Date</option>
                    <option value="progress" ${guess==='progress'?'selected':''}>Progress %</option>
                    <option value="notes" ${guess==='notes'?'selected':''}>Notes</option>
                </select>
            </div>
        </div>`;
    });

    renderPreview();
    document.querySelectorAll('[id^="map_"]').forEach(s => s.addEventListener('change', renderPreview));
}

function renderPreview() {
    const mapping = headers.map((h, i) => document.getElementById('map_' + i).value);
    const activeFields = mapping.filter(Boolean);

    const head = document.getElementById('previewHead');
    head.innerHTML = activeFields.map(f => `<th>${fieldLabel(f)}</th>`).join('');

    const body = document.getElementById('previewBody');
    const preview = parsedRows.slice(0, 10);
    body.innerHTML = preview.map(row => {
        const cells = mapping.map((f, i) => f ? `<td>${escHtml(formatCell(f, row[i]))}</td>` : '').join('');
        return `<tr>${cells}</tr>`;
    }).join('');

    document.getElementById('previewCount').textContent =
        `(showing ${Math.min(10, parsedRows.length)} of ${parsedRows.length} rows)`;
}

function formatCell(field, val) {
    if (val === null || val === undefined || val === '') return '';
    if (field === 'start_date' || field === 'due_date') {
        if (val instanceof Date) return val.toISOString().split('T')[0];
        if (typeof val === 'number') {
            const d = XLSX.SSF.parse_date_code(val);
            return `${d.y}-${String(d.m).padStart(2,'0')}-${String(d.d).padStart(2,'0')}`;
        }
    }
    return String(val);
}

function doImport() {
    const mapping = headers.map((h, i) => document.getElementById('map_' + i).value);
    if (!mapping.includes('task_name')) {
        alert('Please map at least one column to "Task Name" before importing.');
        return;
    }

    const tasks = parsedRows.map(row => {
        const t = {};
        mapping.forEach((f, i) => { if (f) t[f] = formatCell(f, row[i]); });
        return t;
    }).filter(t => t.task_name);

    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';

    const fd = new FormData();
    fd.append('tasks', JSON.stringify(tasks));
    fd.append('project_id', document.getElementById('importProject').value);

    fetch('api/save_import.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            document.getElementById('step2Card').classList.add('d-none');
            document.getElementById('importActions').classList.add('d-none');
            document.getElementById('step3Card').classList.remove('d-none');
            if (res.success) {
                document.getElementById('importResultMsg').textContent = 'Import Successful!';
                document.getElementById('importResultDetail').textContent =
                    res.imported + ' tasks imported' + (res.skipped ? ', ' + res.skipped + ' skipped' : '') + '.';
            } else {
                document.querySelector('#step3Card .fa-circle-check').className = 'fa fa-circle-xmark fa-4x text-danger mb-3';
                document.getElementById('importResultMsg').textContent = 'Import Failed';
                document.getElementById('importResultDetail').textContent = res.message || 'Unknown error';
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
    parsedRows = []; headers = [];
    document.getElementById('step1Card').classList.remove('d-none');
    document.getElementById('step2Card').classList.add('d-none');
    document.getElementById('step3Card').classList.add('d-none');
    document.getElementById('importActions').classList.add('d-none');
    const btn = document.getElementById('importBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-database me-1"></i> Import to Database';
}

function fieldLabel(f) {
    const labels = { task_name:'Task Name', assignee:'Assignee', status:'Status',
        priority:'Priority', start_date:'Start Date', due_date:'Due Date',
        progress:'Progress%', notes:'Notes' };
    return labels[f] || f;
}

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

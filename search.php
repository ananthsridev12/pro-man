<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'Search';
include 'includes/header.php';
$q = htmlspecialchars(trim($_GET['q'] ?? ''));
?>

<div class="card mb-4">
    <div class="card-body" style="padding:16px 18px;">
        <form id="searchForm" method="get" style="display:flex;gap:10px;">
            <input type="text" name="q" id="searchInput" class="form-control" placeholder="Search campaigns, assets, tasks…"
                value="<?= $q ?>" autocomplete="off" autofocus style="font-size:.95rem;">
            <button type="submit" class="btn btn-primary px-4">
                <i class="fa fa-magnifying-glass me-1"></i>Search
            </button>
        </form>
    </div>
</div>

<div id="searchResults">
<?php if ($q): ?>
<div class="text-center text-muted py-4" id="loadingMsg">
    <i class="fa fa-spinner fa-spin me-2"></i>Searching…
</div>
<?php else: ?>
<div class="text-center text-muted py-5" style="font-size:.88rem;">
    <i class="fa fa-magnifying-glass fa-2x mb-3 d-block" style="color:#d1d5db;"></i>
    Type something above to search across campaigns, assets and tasks.
</div>
<?php endif; ?>
</div>

<script>
const ASSET_TYPES_LABELS = <?= json_encode(array_map(fn($v)=>['label'=>$v['label'],'color'=>$v['color']], $ASSET_TYPES)) ?>;

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function renderResults(data) {
    const el = document.getElementById('searchResults');
    const {campaigns, assets, tasks} = data;
    if (!campaigns.length && !assets.length && !tasks.length) {
        el.innerHTML = '<div class="text-center text-muted py-5" style="font-size:.88rem;">No results found.</div>';
        return;
    }
    let html = '';

    if (campaigns.length) {
        html += `<div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fa fa-layer-group me-2 text-primary"></i>Campaigns (${campaigns.length})</h6></div>
            <div class="card-body p-0"><table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Campaign ID</th><th>Name</th><th class="d-none d-md-table-cell">Vertical</th><th>Status</th><th>Owner</th></tr></thead>
            <tbody>`;
        campaigns.forEach(c => {
            html += `<tr style="cursor:pointer;" onclick="window.location='campaign-detail.php?id=${c.id}'">
                <td><code class="id-code">${esc(c.campaign_id||'')}</code></td>
                <td class="fw-semibold">${esc(c.campaign_name)}</td>
                <td class="d-none d-md-table-cell"><span class="vertical-chip">${esc(c.vertical||'-')}</span></td>
                <td><span class="badge-pill cs-${(c.campaign_status||'').replace(/[ /]/g,'_')}">${esc(c.campaign_status)}</span></td>
                <td class="text-muted">${esc(c.campaign_owner||'-')}</td>
            </tr>`;
        });
        html += '</tbody></table></div></div>';
    }

    if (assets.length) {
        html += `<div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fa fa-folder-open me-2 text-purple" style="color:#8b5cf6;"></i>Assets (${assets.length})</h6></div>
            <div class="card-body p-0"><table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Asset ID</th><th>Name</th><th>Type</th><th class="d-none d-md-table-cell">Campaign</th><th>Status</th></tr></thead>
            <tbody>`;
        assets.forEach(a => {
            const tc = ASSET_TYPES_LABELS[a.asset_type] || {label:a.asset_type,color:'#6b7280'};
            html += `<tr style="cursor:pointer;" onclick="window.location='assets.php?type=${esc(a.asset_type)}'">
                <td><code class="id-code">${esc(a.asset_id||'')}</code></td>
                <td class="fw-semibold">${esc(a.asset_name)}</td>
                <td><span style="display:inline-flex;align-items:center;gap:5px;font-size:.8rem;">
                    <span style="width:7px;height:7px;border-radius:50%;background:${tc.color};display:inline-block;flex-shrink:0;"></span>
                    ${esc(tc.label)}</span></td>
                <td class="d-none d-md-table-cell text-muted">${esc(a.campaign_name||'-')}</td>
                <td><span class="badge-pill st-${(a.status||'').replace(/[ /]/g,'_')}">${esc(a.status)}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div></div>';
    }

    if (tasks.length) {
        html += `<div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fa fa-list-check me-2 text-success"></i>Tasks (${tasks.length})</h6></div>
            <div class="card-body p-0"><table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Asset</th><th>Priority</th><th>Status</th><th>Due</th></tr></thead>
            <tbody>`;
        tasks.forEach(t => {
            html += `<tr style="cursor:pointer;" onclick="window.location='my-work.php'">
                <td class="fw-semibold">${esc(t.title)}</td>
                <td><code class="id-code">${esc(t.a_code||'')}</code></td>
                <td><span class="badge-pill pri-${esc(t.priority)}">${esc(t.priority)}</span></td>
                <td>${esc(t.status)}</td>
                <td class="text-muted">${t.due_date ? t.due_date : '—'}</td>
            </tr>`;
        });
        html += '</tbody></table></div></div>';
    }
    el.innerHTML = html;
}

<?php if ($q): ?>
fetch('api/search_api.php?q=' + encodeURIComponent(<?= json_encode($q) ?>))
    .then(r => r.json())
    .then(res => {
        if (res.success) renderResults(res.data);
        else document.getElementById('searchResults').innerHTML = '<div class="alert alert-danger">Search error.</div>';
    })
    .catch(() => {
        document.getElementById('searchResults').innerHTML = '<div class="alert alert-danger">Network error.</div>';
    });
<?php endif; ?>

// Live search as user types
let debounce;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(debounce);
    const q = this.value.trim();
    if (q.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    debounce = setTimeout(() => {
        fetch('api/search_api.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(res => { if (res.success) renderResults(res.data); });
    }, 280);
});

// '/' keyboard shortcut focuses search from any page
document.addEventListener('keydown', function(e) {
    if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
});
</script>

<?php include 'includes/footer.php'; ?>

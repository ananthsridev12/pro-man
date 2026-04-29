<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'Campaign Master';
$db = getDB();

$campaigns = $db->query("
    SELECT c.*,
        COUNT(a.id) total_assets,
        SUM(a.status='Published / Live') live_assets,
        SUM(a.status='In Revision') revision_assets
    FROM campaigns c
    LEFT JOIN assets a ON a.campaign_ref=c.id AND a.archived=0
    GROUP BY c.id ORDER BY c.created_at DESC
");
$activeUsers = $db->query("SELECT id, name, role FROM users WHERE status='active' ORDER BY name ASC");
$projects    = $db->query("SELECT id, code, name, color FROM projects WHERE status='active' ORDER BY name ASC");
$projectList = [];
while ($p = $projects->fetch_assoc()) $projectList[] = $p;

include 'includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#campaignModal">
        <i class="fa fa-plus me-1"></i> New Campaign
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="hide-xs">Campaign ID</th><th>Campaign Name</th>
                        <th class="d-none d-md-table-cell">Vertical</th>
                        <th class="d-none d-lg-table-cell">Type</th>
                        <th class="d-none d-lg-table-cell">Assigned To</th>
                        <th class="d-none d-md-table-cell">Go-Live</th>
                        <th>Priority</th><th>Status</th>
                        <th class="d-none d-sm-table-cell">Requirements</th>
                        <th style="min-width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($campaigns->num_rows === 0): ?>
                    <tr><td colspan="12" class="text-center text-muted py-5">
                        No campaigns yet. Click "New Campaign" to add one, or <a href="upload.php">import from Excel</a>.
                    </td></tr>
                <?php else: while ($c = $campaigns->fetch_assoc()): ?>
                    <tr>
                        <td class="hide-xs"><code class="id-code"><?= htmlspecialchars($c['campaign_id'] ?? '') ?></code></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($c['campaign_name']) ?></div>
                            <small class="text-muted hide-xs"><?= htmlspecialchars($c['vertical'] ?? '') ?><?= $c['vertical'] && $c['campaign_type'] ? ' · ' : '' ?><?= htmlspecialchars($c['campaign_type'] ?? '') ?></small>
                        </td>
                        <td class="d-none d-md-table-cell"><span class="vertical-chip"><?= htmlspecialchars($c['vertical'] ?? '-') ?></span></td>
                        <td class="d-none d-lg-table-cell text-muted"><?= htmlspecialchars($c['campaign_type'] ?? '-') ?></td>
                        <td class="d-none d-lg-table-cell"><?= htmlspecialchars($c['campaign_owner'] ?? '-') ?></td>
                        <td class="d-none d-md-table-cell text-muted"><?= $c['go_live_date'] ? date('d M Y', strtotime($c['go_live_date'])) : '-' ?></td>
                        <td><span class="badge-pill pri-<?= $c['priority'] ?>"><?= $c['priority'] ?></span></td>
                        <td><span class="badge-pill cs-<?= str_replace([' ','/'],'_',$c['campaign_status']) ?>"><?= $c['campaign_status'] ?></span></td>
                        <td class="d-none d-sm-table-cell">
                            <span class="text-success fw-semibold"><?= $c['live_assets'] ?></span>/<?= $c['total_assets'] ?>
                        </td>
                        <td>
                            <a href="campaign-detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="View">
                                <i class="fa fa-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="editCampaign(<?= htmlspecialchars(json_encode($c)) ?>)">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteCampaign(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['campaign_name'])) ?>')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Campaign Modal -->
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaignModalTitle">New Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="campaignForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="cId">

                    <!-- ID Preview -->
                    <div class="id-preview-bar mb-4" id="idPreviewBar">
                        <span class="id-preview-label">Campaign ID Preview:</span>
                        <code class="id-preview-value" id="idPreview">—</code>
                        <small class="text-muted ms-2">(auto-generated on save)</small>
                    </div>

                    <div class="row g-3">
                        <!-- Row 1: Core identity -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="campaign_name" id="cName" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Project / Vertical <span class="text-danger">*</span></label>
                            <select class="form-select" name="vertical" id="cVertical" onchange="updateIdPreview()">
                                <option value="">-- Select --</option>
                                <?php foreach ($projectList as $p): ?>
                                    <option value="<?= htmlspecialchars($p['code']) ?>"
                                        data-project-id="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['code']) ?> — <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php foreach ($VERTICALS as $code => $label): if (array_filter($projectList, fn($p)=>$p['code']===$code)) continue; ?>
                                    <option value="<?= $code ?>"><?= $code ?> — <?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="project_id" id="cProjectId">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Goal Code <span class="text-danger">*</span></label>
                            <select class="form-select" name="goal_code" id="cGoalCode" onchange="updateIdPreview()">
                                <option value="">-- Select --</option>
                                <?php foreach ($GOAL_CODES as $code => $label): ?>
                                    <option value="<?= $code ?>"><?= $code ?> — <?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Descriptor
                                <span class="text-muted fw-normal" style="font-size:.8rem;">(2–3 words, e.g. PLM-LAUNCH)</span>
                            </label>
                            <input type="text" class="form-control" name="descriptor" id="cDescriptor"
                                placeholder="e.g. PLM-LAUNCH" oninput="updateIdPreview()" style="text-transform:uppercase;">
                        </div>

                        <!-- Row 2 -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Type</label>
                            <select class="form-select" name="campaign_type" id="cType">
                                <option value="">-- Select --</option>
                                <?php foreach ($CAMPAIGN_TYPES as $t): ?>
                                    <option value="<?= $t ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Goal</label>
                            <input type="text" class="form-control" name="campaign_goal" id="cGoal" placeholder="e.g. Lead Capture, Demo Request">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Geography</label>
                            <select class="form-select" name="geography" id="cGeo">
                                <option value="">-- Select --</option>
                                <?php foreach ($GEOGRAPHIES as $g): ?>
                                    <option value="<?= $g ?>"><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 3 -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Audience</label>
                            <input type="text" class="form-control" name="target_audience" id="cAudience">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign Start</label>
                            <input type="date" class="form-control" name="campaign_start" id="cStart">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign End</label>
                            <input type="date" class="form-control" name="campaign_end" id="cEnd">
                        </div>

                        <!-- Row 4 -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Go-Live Date</label>
                            <input type="date" class="form-control" name="go_live_date" id="cGoLive">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" name="priority" id="cPriority">
                                <?php foreach ($PRIORITIES as $p): ?>
                                    <option value="<?= $p ?>" <?= $p==='Medium'?'selected':'' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Assigned To <span class="text-danger">*</span></label>
                            <select class="form-select" name="campaign_owner" id="cOwner" required>
                                <option value="">-- Select User --</option>
                                <?php $activeUsers->data_seek(0); while ($u = $activeUsers->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign Status</label>
                            <select class="form-select" name="campaign_status" id="cStatus">
                                <?php foreach ($CAMPAIGN_STATUSES as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Brief</label>
                            <textarea class="form-control" name="notes" id="cNotes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const MONTHS = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];

function updateIdPreview() {
    const sel = document.getElementById('cVertical');
    const v   = sel.value;
    const opt = sel.options[sel.selectedIndex];
    const pid = opt ? (opt.dataset.projectId || '') : '';
    document.getElementById('cProjectId').value = pid;
    const g = document.getElementById('cGoalCode').value;
    const d = document.getElementById('cDescriptor').value.trim().toUpperCase().replace(/[^A-Z0-9\-]/g,'').replace(/\s+/g,'-');
    const now = new Date();
    const mon = MONTHS[now.getMonth()];
    const yr  = String(now.getFullYear()).slice(-2);
    const parts = [v||'??', g||'??', d||'DESCRIPTOR', mon+yr];
    document.getElementById('idPreview').textContent = parts.join('-');
}

function editCampaign(c) {
    document.getElementById('campaignModalTitle').textContent = 'Edit Campaign';
    document.getElementById('cId').value          = c.id;
    document.getElementById('cName').value         = c.campaign_name;
    document.getElementById('cVertical').value     = c.vertical || '';
    document.getElementById('cProjectId').value    = c.project_id || '';
    document.getElementById('cGoalCode').value     = c.goal_code || '';
    document.getElementById('cDescriptor').value   = c.descriptor || '';
    document.getElementById('cType').value         = c.campaign_type || '';
    document.getElementById('cGoal').value         = c.campaign_goal || '';
    document.getElementById('cGeo').value          = c.geography || '';
    document.getElementById('cAudience').value     = c.target_audience || '';
    document.getElementById('cStart').value        = c.campaign_start || '';
    document.getElementById('cEnd').value          = c.campaign_end || '';
    document.getElementById('cGoLive').value       = c.go_live_date || '';
    document.getElementById('cPriority').value     = c.priority || 'Medium';
    document.getElementById('cOwner').value        = c.campaign_owner || '';
    document.getElementById('cStatus').value       = c.campaign_status || 'Planning';
    document.getElementById('cNotes').value        = c.notes || '';
    // Show existing ID in preview bar
    document.getElementById('idPreview').textContent = c.campaign_id || '—';
    new bootstrap.Modal(document.getElementById('campaignModal')).show();
}

document.getElementById('campaignModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('campaignForm').reset();
    document.getElementById('cId').value = '';
    document.getElementById('campaignModalTitle').textContent = 'New Campaign';
    document.getElementById('idPreview').textContent = '—';
});

document.getElementById('campaignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/campaign_crud.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
});

function deleteCampaign(id, name) {
    if (!confirm('Delete campaign "' + name + '"? All linked assets will be unlinked.')) return;
    const fd = new FormData();
    fd.append('action', 'delete'); fd.append('id', id);
    fetch('api/campaign_crud.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

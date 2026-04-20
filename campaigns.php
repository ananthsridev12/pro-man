<?php
require_once 'config.php';
$pageTitle = 'Campaign Master';
$db = getDB();

$campaigns = $db->query("
    SELECT c.*,
        COUNT(a.id) total_assets,
        SUM(a.status='Live') live_assets,
        SUM(a.status='In Review') review_assets
    FROM campaigns c
    LEFT JOIN assets a ON a.campaign_ref=c.id AND a.archived=0
    GROUP BY c.id ORDER BY c.created_at DESC
");

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
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>Campaign ID</th><th>Campaign Name</th><th>Vertical</th>
                        <th>Campaign Type</th><th>Owner</th><th>Go-Live</th>
                        <th>Priority</th><th>Status</th>
                        <th>Ph Appr.</th><th>Mgr Appr.</th>
                        <th>Assets</th><th style="min-width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($campaigns->num_rows === 0): ?>
                    <tr><td colspan="12" class="text-center text-muted py-5">
                        No campaigns yet. Click "New Campaign" to add one, or <a href="upload.php">import from Excel</a>.
                    </td></tr>
                <?php else: while ($c = $campaigns->fetch_assoc()): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($c['campaign_id'] ?? '') ?></code></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($c['campaign_name']) ?></div>
                            <?php if ($c['campaign_goal']): ?>
                                <small class="text-muted"><?= htmlspecialchars(substr($c['campaign_goal'],0,50)) ?><?= strlen($c['campaign_goal'])>50?'...':'' ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['vertical'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['campaign_type'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['campaign_owner'] ?? '-') ?></td>
                        <td><?= $c['go_live_date'] ? date('d M Y', strtotime($c['go_live_date'])) : '-' ?></td>
                        <td><span class="badge-pill pri-<?= $c['priority'] ?>"><?= $c['priority'] ?></span></td>
                        <td><span class="badge-pill cs-<?= str_replace(' ','-',$c['campaign_status']) ?>"><?= $c['campaign_status'] ?></span></td>
                        <td>
                            <span class="badge-pill <?= $c['approved_project_head']==='Yes'?'bg-success text-white':($c['approved_project_head']==='No'?'bg-danger text-white':'bg-secondary text-white') ?>">
                                <?= $c['approved_project_head'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-pill <?= $c['approved_manager']==='Yes'?'bg-success text-white':($c['approved_manager']==='No'?'bg-danger text-white':'bg-secondary text-white') ?>">
                                <?= $c['approved_manager'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-success fw-semibold"><?= $c['live_assets'] ?></span> live /
                            <span class="text-muted"><?= $c['total_assets'] ?></span> total
                        </td>
                        <td>
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
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="campaign_name" id="cName" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Vertical</label>
                            <input type="text" class="form-control" name="vertical" id="cVertical" placeholder="e.g. DT, EV, HR">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Type</label>
                            <input type="text" class="form-control" name="campaign_type" id="cType" placeholder="e.g. Lead Gen, Brand Awareness">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Goal Code</label>
                            <input type="text" class="form-control" name="goal_code" id="cGoalCode" placeholder="e.g. LG, BA, NU">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Campaign Goal</label>
                            <input type="text" class="form-control" name="campaign_goal" id="cGoal">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Audience</label>
                            <input type="text" class="form-control" name="target_audience" id="cAudience">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Geography</label>
                            <input type="text" class="form-control" name="geography" id="cGeo" placeholder="e.g. India, Pan-India, Metro">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign Start</label>
                            <input type="date" class="form-control" name="campaign_start" id="cStart">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign End</label>
                            <input type="date" class="form-control" name="campaign_end" id="cEnd">
                        </div>
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
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Owner</label>
                            <input type="text" class="form-control" name="campaign_owner" id="cOwner">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Status</label>
                            <select class="form-select" name="campaign_status" id="cStatus">
                                <?php foreach ($CAMPAIGN_STATUSES as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Approved by PH</label>
                            <select class="form-select" name="approved_project_head" id="cApprPH">
                                <?php foreach ($APPROVAL_OPTS as $o): ?>
                                    <option value="<?= $o ?>"><?= $o ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Approved by Mgr</label>
                            <select class="form-select" name="approved_manager" id="cApprMgr">
                                <?php foreach ($APPROVAL_OPTS as $o): ?>
                                    <option value="<?= $o ?>"><?= $o ?></option>
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
function editCampaign(c) {
    document.getElementById('campaignModalTitle').textContent = 'Edit Campaign';
    document.getElementById('cId').value          = c.id;
    document.getElementById('cName').value         = c.campaign_name;
    document.getElementById('cVertical').value     = c.vertical || '';
    document.getElementById('cType').value         = c.campaign_type || '';
    document.getElementById('cGoalCode').value     = c.goal_code || '';
    document.getElementById('cGoal').value         = c.campaign_goal || '';
    document.getElementById('cAudience').value     = c.target_audience || '';
    document.getElementById('cGeo').value          = c.geography || '';
    document.getElementById('cStart').value        = c.campaign_start || '';
    document.getElementById('cEnd').value          = c.campaign_end || '';
    document.getElementById('cGoLive').value       = c.go_live_date || '';
    document.getElementById('cPriority').value     = c.priority || 'Medium';
    document.getElementById('cOwner').value        = c.campaign_owner || '';
    document.getElementById('cStatus').value       = c.campaign_status || 'Planning';
    document.getElementById('cApprPH').value       = c.approved_project_head || 'Pending';
    document.getElementById('cApprMgr').value      = c.approved_manager || 'Pending';
    document.getElementById('cNotes').value        = c.notes || '';
    new bootstrap.Modal(document.getElementById('campaignModal')).show();
}

document.getElementById('campaignModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('campaignForm').reset();
    document.getElementById('cId').value = '';
    document.getElementById('campaignModalTitle').textContent = 'New Campaign';
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

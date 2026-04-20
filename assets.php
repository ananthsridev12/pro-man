<?php
require_once 'config.php';

$type = $_GET['type'] ?? 'creative';
if (!array_key_exists($type, $ASSET_TYPES)) {
    header('Location: assets.php?type=creative'); exit;
}

$cfg       = $ASSET_TYPES[$type];
$pageTitle = $cfg['label'];
$db        = getDB();

// Filters
$filterCampaign = isset($_GET['campaign']) ? (int)$_GET['campaign'] : 0;
$filterStatus   = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';

$where = "a.asset_type='" . $db->real_escape_string($type) . "' AND a.archived=0";
if ($filterCampaign) $where .= " AND a.campaign_ref=$filterCampaign";
if ($filterStatus)   $where .= " AND a.status='" . $db->real_escape_string($filterStatus) . "'";
if ($filterPriority) $where .= " AND a.priority='" . $db->real_escape_string($filterPriority) . "'";

$assets = $db->query("
    SELECT a.*, c.campaign_name, c.campaign_id as c_campaign_id
    FROM assets a
    LEFT JOIN campaigns c ON c.id = a.campaign_ref
    WHERE $where ORDER BY a.due_date ASC, a.priority DESC, a.created_at DESC
");

$campaigns  = $db->query("SELECT id, campaign_id, campaign_name FROM campaigns ORDER BY campaign_id");
$activeUsers = $db->query("SELECT id, name, role FROM users WHERE status='active' ORDER BY name ASC");
$userOpts = [];
if ($activeUsers) { while ($u = $activeUsers->fetch_assoc()) $userOpts[] = $u; }

include 'includes/header.php';
?>

<style>
    .type-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:.82rem;font-weight:500;
        background: <?= $cfg['color'] ?>20; color: <?= $cfg['color'] ?>; }
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 w-100">
        <span class="type-badge flex-shrink-0"><i class="fa <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?></span>
        <form class="d-flex filter-form gap-2 flex-wrap mb-0 w-100" method="GET">
            <input type="hidden" name="type" value="<?= $type ?>">
            <select name="campaign" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Campaigns</option>
                <?php $campaigns->data_seek(0); while ($cp = $campaigns->fetch_assoc()): ?>
                    <option value="<?= $cp['id'] ?>" <?= $filterCampaign==$cp['id']?'selected':'' ?>>
                        <?= htmlspecialchars($cp['campaign_id'].' — '.$cp['campaign_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <?php foreach ($ASSET_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Priority</option>
                <?php foreach ($PRIORITIES as $p): ?>
                    <option value="<?= $p ?>" <?= $filterPriority===$p?'selected':'' ?>><?= $p ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($filterCampaign || $filterStatus || $filterPriority): ?>
                <a href="assets.php?type=<?= $type ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <button class="btn btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#assetModal">
        <i class="fa fa-plus me-1"></i> <span class="d-none d-sm-inline">New <?= rtrim($cfg['label'],'s') ?></span><span class="d-sm-none">Add</span>
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr>
                        <th class="hide-xs">Asset ID</th><th>Asset Name</th><th class="hide-xs">Campaign</th>
                        <th class="d-none d-md-table-cell">Vertical</th><th class="d-none d-lg-table-cell">Owner</th>
                        <?php
                        $tableExtras = array_slice($cfg['extra'], 0, 3, true);
                        foreach ($tableExtras as $k => $label): ?>
                            <th class="d-none d-xl-table-cell"><?= $label ?></th>
                        <?php endforeach; ?>
                        <th class="d-none d-md-table-cell">Due Date</th><th>Priority</th><th>Status</th>
                        <th class="d-none d-lg-table-cell">PH</th><th class="d-none d-lg-table-cell">Mgr</th>
                        <th style="min-width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($assets->num_rows === 0): ?>
                    <tr><td colspan="20" class="text-center text-muted py-5">
                        No <?= strtolower($cfg['label']) ?> yet.
                        <a href="upload.php">Import from Excel</a> or add manually.
                    </td></tr>
                <?php else: while ($a = $assets->fetch_assoc()):
                    $extra = json_decode($a['extra_data'] ?? '{}', true) ?: [];
                    $overdue = $a['due_date'] && strtotime($a['due_date']) < time()
                        && !in_array($a['status'],['Live','Approved','Cancelled','Archived']);
                ?>
                    <tr <?= $overdue?'class="table-danger"':'' ?>>
                        <td class="hide-xs"><code style="font-size:.75rem;"><?= htmlspecialchars($a['asset_id'] ?? '') ?></code></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($a['asset_name']) ?></div>
                            <?php if ($a['feedback_notes']): ?>
                                <small class="text-muted d-none d-sm-inline"><?= htmlspecialchars(substr($a['feedback_notes'],0,40)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td class="hide-xs"><small class="text-muted"><?= htmlspecialchars($a['c_campaign_id'] ?? ($a['campaign_id_text'] ?? '-')) ?></small></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($a['vertical'] ?? '-') ?></td>
                        <td class="d-none d-lg-table-cell"><?= htmlspecialchars($a['owner'] ?? '-') ?></td>
                        <?php foreach (array_keys($tableExtras) as $k): ?>
                            <td class="d-none d-xl-table-cell"><small><?= htmlspecialchars($extra[$k] ?? '-') ?></small></td>
                        <?php endforeach; ?>
                        <td class="d-none d-md-table-cell">
                            <?= $a['due_date'] ? date('d M Y', strtotime($a['due_date'])) : '-' ?>
                            <?php if ($overdue): ?><br><small class="text-danger fw-bold">Overdue</small><?php endif; ?>
                        </td>
                        <td><span class="badge-pill pri-<?= $a['priority'] ?>"><?= $a['priority'] ?></span></td>
                        <td><span class="badge-pill st-<?= str_replace(' ','-',$a['status']) ?>"><?= $a['status'] ?></span></td>
                        <td class="d-none d-lg-table-cell"><small class="<?= $a['approved_project_head']==='Yes'?'text-success':($a['approved_project_head']==='No'?'text-danger':'text-muted') ?>"><?= $a['approved_project_head'] ?></small></td>
                        <td class="d-none d-lg-table-cell"><small class="<?= $a['approved_manager']==='Yes'?'text-success':($a['approved_manager']==='No'?'text-danger':'text-muted') ?>"><?= $a['approved_manager'] ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick='editAsset(<?= htmlspecialchars(json_encode($a)) ?>)'>
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteAsset(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['asset_name'])) ?>')">
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

<!-- Asset Modal -->
<div class="modal fade" id="assetModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetModalTitle">New <?= rtrim($cfg['label'],'s') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assetForm">
                <input type="hidden" name="asset_type" value="<?= $type ?>">
                <input type="hidden" name="id" id="aId">
                <div class="modal-body">
                    <!-- Common Fields -->
                    <h6 class="fw-semibold mb-3" style="color:<?= $cfg['color'] ?>;">
                        <i class="fa <?= $cfg['icon'] ?> me-1"></i> Common Fields
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="asset_name" id="aName" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign</label>
                            <select class="form-select" name="campaign_ref" id="aCampaign">
                                <option value="">-- None --</option>
                                <?php $campaigns->data_seek(0); while ($cp = $campaigns->fetch_assoc()): ?>
                                    <option value="<?= $cp['id'] ?>"><?= htmlspecialchars($cp['campaign_id'].' — '.$cp['campaign_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Vertical</label>
                            <input type="text" class="form-control" name="vertical" id="aVertical">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Owner</label>
                            <select class="form-select" name="owner" id="aOwner">
                                <option value="">-- Select --</option>
                                <?php foreach ($userOpts as $u): ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Support</label>
                            <select class="form-select" name="support" id="aSupport">
                                <option value="">-- Select --</option>
                                <?php foreach ($userOpts as $u): ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Requested By</label>
                            <select class="form-select" name="requested_by" id="aReqBy">
                                <option value="">-- Select --</option>
                                <?php foreach ($userOpts as $u): ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Brief Date</label>
                            <input type="date" class="form-control" name="brief_date" id="aBriefDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" id="aDueDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Pub / Go-Live Date</label>
                            <input type="date" class="form-control" name="pub_date" id="aPubDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" name="priority" id="aPriority">
                                <?php foreach ($PRIORITIES as $p): ?>
                                    <option value="<?= $p ?>" <?= $p==='Medium'?'selected':'' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="aStatus">
                                <?php foreach ($ASSET_STATUSES as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Approved by PH</label>
                            <select class="form-select" name="approved_project_head" id="aApprPH">
                                <?php foreach ($APPROVAL_OPTS as $o): ?>
                                    <option><?= $o ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Approved by Mgr</label>
                            <select class="form-select" name="approved_manager" id="aApprMgr">
                                <?php foreach ($APPROVAL_OPTS as $o): ?>
                                    <option><?= $o ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Revision #</label>
                            <input type="number" class="form-control" name="revision_no" id="aRevNo" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Final File / URL</label>
                            <input type="text" class="form-control" name="final_file_url" id="aFinalUrl">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Feedback / Notes</label>
                            <textarea class="form-control" name="feedback_notes" id="aFeedback" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Type-Specific Fields -->
                    <h6 class="fw-semibold mb-3" style="color:<?= $cfg['color'] ?>;">
                        <i class="fa <?= $cfg['icon'] ?> me-1"></i> <?= $cfg['label'] ?> Details
                    </h6>
                    <div class="row g-3">
                    <?php foreach ($cfg['extra'] as $key => $label): ?>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= htmlspecialchars($label) ?></label>
                            <?php if (in_array($key, ['content_writer','designer','dev_owner','speakers'])): ?>
                                <select class="form-select form-select-sm" name="extra[<?= $key ?>]" id="extra_<?= $key ?>">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($userOpts as $u): ?>
                                        <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif (in_array($key, ['brief_desc','body_copy','on_page_changes','topic'])): ?>
                                <textarea class="form-control form-control-sm" name="extra[<?= $key ?>]" id="extra_<?= $key ?>" rows="2"></textarea>
                            <?php elseif (in_array($key, ['seo_optimised','cms_published','gating','ab_test','tracking_impl','results_defined','followup_sent'])): ?>
                                <select class="form-select form-select-sm" name="extra[<?= $key ?>]" id="extra_<?= $key ?>">
                                    <option value="">--</option>
                                    <option>Yes</option><option>No</option>
                                </select>
                            <?php elseif (in_array($key, ['run_start','run_end','event_date'])): ?>
                                <input type="date" class="form-control form-control-sm" name="extra[<?= $key ?>]" id="extra_<?= $key ?>">
                            <?php elseif (in_array($key, ['word_count','email_num','duration_mins','expected_att','actual_att','current_rank','target_rank','search_vol','num_pages'])): ?>
                                <input type="number" class="form-control form-control-sm" name="extra[<?= $key ?>]" id="extra_<?= $key ?>" min="0">
                            <?php else: ?>
                                <input type="text" class="form-control form-control-sm" name="extra[<?= $key ?>]" id="extra_<?= $key ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAsset(a) {
    document.getElementById('assetModalTitle').textContent = 'Edit Asset';
    document.getElementById('aId').value           = a.id;
    document.getElementById('aName').value          = a.asset_name;
    document.getElementById('aCampaign').value      = a.campaign_ref || '';
    document.getElementById('aVertical').value      = a.vertical || '';
    document.getElementById('aOwner').value         = a.owner || '';
    document.getElementById('aSupport').value       = a.support || '';
    document.getElementById('aReqBy').value         = a.requested_by || '';
    document.getElementById('aBriefDate').value     = a.brief_date || '';
    document.getElementById('aDueDate').value       = a.due_date || '';
    document.getElementById('aPubDate').value       = a.pub_date || '';
    document.getElementById('aPriority').value      = a.priority || 'Medium';
    document.getElementById('aStatus').value        = a.status || 'Not Started';
    document.getElementById('aApprPH').value        = a.approved_project_head || 'Pending';
    document.getElementById('aApprMgr').value       = a.approved_manager || 'Pending';
    document.getElementById('aRevNo').value         = a.revision_no || 0;
    document.getElementById('aFinalUrl').value      = a.final_file_url || '';
    document.getElementById('aFeedback').value      = a.feedback_notes || '';
    // Extra fields
    const extra = typeof a.extra_data === 'string' ? JSON.parse(a.extra_data || '{}') : (a.extra_data || {});
    for (const [k, v] of Object.entries(extra)) {
        const el = document.getElementById('extra_' + k);
        if (el) el.value = v || '';
    }
    new bootstrap.Modal(document.getElementById('assetModal')).show();
}

document.getElementById('assetModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('assetForm').reset();
    document.getElementById('aId').value = '';
    document.getElementById('assetModalTitle').textContent = 'New Asset';
});

document.getElementById('assetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/asset_crud.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
});

function deleteAsset(id, name) {
    if (!confirm('Delete "' + name + '"?')) return;
    const fd = new FormData();
    fd.append('action', 'delete'); fd.append('id', id);
    fetch('api/asset_crud.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

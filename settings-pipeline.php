<?php
require_once 'config.php';
require_once 'includes/auth.php';
if ($_SESSION['user_role'] !== 'Admin') { header('Location: index.php'); exit; }
$pageTitle = 'Approval Pipeline';
$db = getDB();

$pipelines = $db->query("
    SELECT p.*, COUNT(s.id) AS stage_count
    FROM approval_pipelines p
    LEFT JOIN approval_stages s ON s.pipeline_id = p.id
    GROUP BY p.id ORDER BY p.is_default DESC, p.name ASC
");
$pipelineList = [];
if ($pipelines) while ($p = $pipelines->fetch_assoc()) $pipelineList[] = $p;

$activeUsers = $db->query("SELECT id, name, role FROM users WHERE status='active' ORDER BY name ASC");
$userList = [];
if ($activeUsers) while ($u = $activeUsers->fetch_assoc()) $userList[] = $u;

$USER_ROLES = ['Admin','Project Head','Manager','Campaign Manager','Designer',
               'Content Writer','Developer','SEO Specialist','Event Manager','Team Member'];

include 'includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" onclick="openPipelineModal(null)">
        <i class="fa fa-plus me-1"></i> New Pipeline
    </button>
</div>

<?php if (empty($pipelineList)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        No pipelines yet. Click "New Pipeline" to create one.
        <br><small class="mt-1 d-block">Tip: Create a pipeline with sequential stages (e.g. Manager → Project Head) and set it as default.</small>
    </div>
</div>
<?php else: ?>

<?php foreach ($pipelineList as $pl):
    $stages = $db->query("
        SELECT s.*, u.name AS approver_name
        FROM approval_stages s
        LEFT JOIN users u ON u.id = s.approver_user_id
        WHERE s.pipeline_id={$pl['id']} ORDER BY s.stage_order ASC
    ");
    $stageList = [];
    if ($stages) while ($s = $stages->fetch_assoc()) $stageList[] = $s;
?>
<div class="card mb-3" id="pipeline-<?= $pl['id'] ?>">
    <div class="card-header bg-white py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold"><?= htmlspecialchars($pl['name']) ?></span>
                <?php if ($pl['is_default']): ?>
                    <span class="badge bg-primary">Default</span>
                <?php endif; ?>
                <?php if (!$pl['is_active']): ?>
                    <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
                <span class="text-muted" style="font-size:.8rem;"><?= count($stageList) ?> stage<?= count($stageList)!=1?'s':'' ?></span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary"
                    onclick="openPipelineModal(<?= htmlspecialchars(json_encode($pl)) ?>)">
                    <i class="fa fa-pen"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger"
                    onclick="deletePipeline(<?= $pl['id'] ?>, '<?= htmlspecialchars(addslashes($pl['name'])) ?>')">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
        <?php if ($pl['description']): ?>
            <p class="text-muted mb-0 mt-1" style="font-size:.82rem;"><?= htmlspecialchars($pl['description']) ?></p>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($stageList)): ?>
            <div class="text-center text-muted py-3" style="font-size:.84rem;">No stages yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.85rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Stage Name</th>
                    <th>Approver</th>
                    <th style="min-width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stageList as $s): ?>
            <tr>
                <td class="text-muted fw-semibold"><?= $s['stage_order'] ?></td>
                <td><?= htmlspecialchars($s['stage_name']) ?></td>
                <td>
                    <?php if ($s['approver_user_id'] && $s['approver_name']): ?>
                        <i class="fa fa-user me-1 text-muted"></i><?= htmlspecialchars($s['approver_name']) ?>
                    <?php elseif ($s['approver_role']): ?>
                        <i class="fa fa-users me-1 text-muted"></i><?= htmlspecialchars($s['approver_role']) ?> <span class="text-muted">(role)</span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-xs btn-outline-secondary me-1"
                        onclick="moveStage(<?= $s['id'] ?>, 'up', <?= $pl['id'] ?>)"
                        title="Move up" <?= $s['stage_order'] == 1 ? 'disabled' : '' ?>>
                        <i class="fa fa-arrow-up"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-secondary me-1"
                        onclick="moveStage(<?= $s['id'] ?>, 'down', <?= $pl['id'] ?>)"
                        title="Move down" <?= $s['stage_order'] == count($stageList) ? 'disabled' : '' ?>>
                        <i class="fa fa-arrow-down"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-primary me-1"
                        onclick="openStageModal(<?= $pl['id'] ?>, <?= htmlspecialchars(json_encode($s)) ?>)">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger"
                        onclick="deleteStage(<?= $s['id'] ?>, <?= $pl['id'] ?>, '<?= htmlspecialchars(addslashes($s['stage_name'])) ?>')">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <button class="btn btn-sm btn-outline-primary" onclick="openStageModal(<?= $pl['id'] ?>, null)">
            <i class="fa fa-plus me-1"></i> Add Stage
        </button>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Pipeline Modal -->
<div class="modal fade" id="pipelineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pipelineModalTitle">New Pipeline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="pipelineForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_pipeline">
                    <input type="hidden" name="id" id="pId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pipeline Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="pName" required
                            placeholder="e.g. Campaign Approval">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" id="pDesc" rows="2"
                            placeholder="Optional description"></textarea>
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" id="pDefault" value="1">
                            <label class="form-check-label fw-semibold" for="pDefault">Set as Default</label>
                            <div class="text-muted" style="font-size:.78rem;">Used when submitting campaigns for approval</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="pActive" value="1" checked>
                            <label class="form-check-label fw-semibold" for="pActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Pipeline</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stage Modal -->
<div class="modal fade" id="stageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stageModalTitle">Add Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stageForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_stage">
                    <input type="hidden" name="id" id="sId">
                    <input type="hidden" name="pipeline_id" id="sPipelineId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stage Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="stage_name" id="sStageName" required
                            placeholder="e.g. Manager Review">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Approver Type</label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="approver_type" id="typeRole" value="role" checked onchange="toggleApproverType()">
                                <label class="form-check-label" for="typeRole">By Role</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="approver_type" id="typeUser" value="user" onchange="toggleApproverType()">
                                <label class="form-check-label" for="typeUser">Specific User</label>
                            </div>
                        </div>
                    </div>
                    <div id="roleSelect" class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select class="form-select" name="approver_role" id="sRole">
                            <option value="">-- Select Role --</option>
                            <?php foreach ($USER_ROLES as $r): ?>
                                <option value="<?= $r ?>"><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Any user with this role can approve</div>
                    </div>
                    <div id="userSelect" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">User</label>
                        <select class="form-select" name="approver_user_id" id="sUserId">
                            <option value="">-- Select User --</option>
                            <?php foreach ($userList as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Stage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.btn-xs { padding: 2px 8px; font-size: .75rem; }
</style>

<script>
function openPipelineModal(p) {
    document.getElementById('pipelineModalTitle').textContent = p ? 'Edit Pipeline' : 'New Pipeline';
    document.getElementById('pId').value    = p ? p.id : '';
    document.getElementById('pName').value  = p ? p.name : '';
    document.getElementById('pDesc').value  = p ? (p.description || '') : '';
    document.getElementById('pDefault').checked = p ? p.is_default == 1 : false;
    document.getElementById('pActive').checked  = p ? p.is_active == 1 : true;
    new bootstrap.Modal(document.getElementById('pipelineModal')).show();
}

document.getElementById('pipelineForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/pipeline_crud.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
});

function deletePipeline(id, name) {
    if (!confirm('Delete pipeline "' + name + '"?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_pipeline'); fd.append('id', id);
    fetch('api/pipeline_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message); });
}

function openStageModal(pipelineId, s) {
    document.getElementById('stageModalTitle').textContent = s ? 'Edit Stage' : 'Add Stage';
    document.getElementById('sId').value         = s ? s.id : '';
    document.getElementById('sPipelineId').value = pipelineId;
    document.getElementById('sStageName').value  = s ? s.stage_name : '';

    if (s && s.approver_user_id) {
        document.getElementById('typeUser').checked = true;
        document.getElementById('sUserId').value = s.approver_user_id;
        document.getElementById('sRole').value = '';
    } else {
        document.getElementById('typeRole').checked = true;
        document.getElementById('sRole').value = s ? (s.approver_role || '') : '';
        document.getElementById('sUserId').value = '';
    }
    toggleApproverType();
    new bootstrap.Modal(document.getElementById('stageModal')).show();
}

function toggleApproverType() {
    const byUser = document.getElementById('typeUser').checked;
    document.getElementById('roleSelect').style.display = byUser ? 'none' : '';
    document.getElementById('userSelect').style.display = byUser ? '' : 'none';
    if (byUser) {
        document.getElementById('sRole').value = '';
    } else {
        document.getElementById('sUserId').value = '';
    }
}

document.getElementById('stageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/pipeline_crud.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
});

function deleteStage(id, pipelineId, name) {
    if (!confirm('Delete stage "' + name + '"?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_stage'); fd.append('id', id); fd.append('pipeline_id', pipelineId);
    fetch('api/pipeline_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message); });
}

function moveStage(id, direction, pipelineId) {
    const fd = new FormData();
    fd.append('action', 'move_stage'); fd.append('id', id); fd.append('direction', direction);
    fetch('api/pipeline_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

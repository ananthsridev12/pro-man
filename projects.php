<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'Projects';
$db = getDB();

$projects = $db->query("
    SELECT p.*, u.name AS owner_name,
        (SELECT COUNT(*) FROM campaigns c WHERE c.project_id = p.id) AS campaign_count
    FROM projects p
    LEFT JOIN users u ON u.id = p.owner_id
    ORDER BY p.status ASC, p.name ASC
");

$users = $db->query("SELECT id, name FROM users WHERE status='active' ORDER BY name");
$userList = [];
while ($u = $users->fetch_assoc()) $userList[] = $u;

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:.85rem;">Manage verticals / project containers</p>
    <button class="btn btn-primary" onclick="openProjectModal()">
        <i class="fa fa-plus me-1"></i> Add Project
    </button>
</div>

<div class="row g-3" id="projectGrid">
<?php if ($projects->num_rows === 0): ?>
    <div class="col-12 text-center text-muted py-5">No projects yet.</div>
<?php else: while ($p = $projects->fetch_assoc()): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100" style="border-left:4px solid <?= htmlspecialchars($p['color']) ?>;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:36px;height:36px;border-radius:8px;background:<?= htmlspecialchars($p['color']) ?>1a;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa <?= htmlspecialchars($p['icon']) ?>" style="color:<?= htmlspecialchars($p['color']) ?>;font-size:.9rem;"></i>
                        </span>
                        <div>
                            <div class="fw-semibold" style="font-size:.9rem;"><?= htmlspecialchars($p['name']) ?></div>
                            <code style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($p['code']) ?></code>
                        </div>
                    </div>
                    <span class="badge-pill <?= $p['status']==='active'?'bg-success':'bg-secondary' ?> text-white" style="font-size:.7rem;">
                        <?= ucfirst($p['status']) ?>
                    </span>
                </div>
                <?php if ($p['description']): ?>
                    <p class="text-muted mb-2" style="font-size:.8rem;"><?= htmlspecialchars($p['description']) ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:.8rem;color:var(--text-muted);">
                    <span><i class="fa fa-layer-group me-1"></i><?= $p['campaign_count'] ?> campaign<?= $p['campaign_count']!=1?'s':'' ?></span>
                    <span><?= $p['owner_name'] ? '<i class="fa fa-user me-1"></i>'.htmlspecialchars($p['owner_name']) : '' ?></span>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-secondary flex-fill"
                        onclick='editProject(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>
                        <i class="fa fa-pen"></i> Edit
                    </button>
                    <?php if ($p['campaign_count'] == 0): ?>
                    <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteProject(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')">
                        <i class="fa fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endwhile; endif; ?>
</div>

<!-- Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="projectModalTitle">Add Project</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="projectForm">
                    <input type="hidden" id="pId" name="id" value="">
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pCode" name="code"
                                placeholder="e.g. DT" maxlength="10" style="text-transform:uppercase;" required>
                            <div class="form-text">Short unique key</div>
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pName" name="name"
                                placeholder="e.g. Digital Transformation" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="pDesc" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Colour</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color" id="pColor" name="color" value="#3b82f6">
                                <span id="pColorHex" style="font-size:.8rem;color:var(--text-muted);">#3b82f6</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Owner</label>
                            <select class="form-select" id="pOwner" name="owner_id">
                                <option value="">— None —</option>
                                <?php foreach ($userList as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="pStatus" name="status">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProjectBtn" onclick="saveProject()">Save Project</button>
            </div>
        </div>
    </div>
</div>

<script>
const projectModal = new bootstrap.Modal(document.getElementById('projectModal'));

document.getElementById('pColor').addEventListener('input', function() {
    document.getElementById('pColorHex').textContent = this.value;
});
document.getElementById('pCode').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

function openProjectModal() {
    document.getElementById('projectModalTitle').textContent = 'Add Project';
    document.getElementById('projectForm').reset();
    document.getElementById('pId').value = '';
    document.getElementById('pColor').value = '#3b82f6';
    document.getElementById('pColorHex').textContent = '#3b82f6';
    projectModal.show();
}

function editProject(p) {
    document.getElementById('projectModalTitle').textContent = 'Edit Project';
    document.getElementById('pId').value    = p.id;
    document.getElementById('pCode').value  = p.code;
    document.getElementById('pName').value  = p.name;
    document.getElementById('pDesc').value  = p.description || '';
    document.getElementById('pColor').value = p.color || '#3b82f6';
    document.getElementById('pColorHex').textContent = p.color || '#3b82f6';
    document.getElementById('pOwner').value  = p.owner_id || '';
    document.getElementById('pStatus').value = p.status || 'active';
    projectModal.show();
}

function saveProject() {
    const btn = document.getElementById('saveProjectBtn');
    btn.disabled = true;
    const fd = new FormData(document.getElementById('projectForm'));
    fetch('api/project_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) { projectModal.hide(); location.reload(); }
            else { alert(res.message || 'Error saving project.'); }
        })
        .catch(() => alert('Network error.'))
        .finally(() => btn.disabled = false);
}

function deleteProject(id, name) {
    if (!confirm('Delete project "' + name + '"? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('api/project_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) location.reload();
            else alert(res.message || 'Error deleting project.');
        });
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config.php';
$pageTitle = 'Projects';
$db = getDB();
$projects = $db->query("
    SELECT p.*, COUNT(t.id) as total_tasks,
        SUM(t.status='completed') as done_tasks
    FROM projects p
    LEFT JOIN tasks t ON t.project_id = p.id
    GROUP BY p.id ORDER BY p.created_at DESC
");
include 'includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
        <i class="fa fa-plus me-1"></i> New Project
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Project Name</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Tasks</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($projects->num_rows === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No projects yet. Click "New Project" to add one, or <a href="upload.php">import from Excel</a>.</td></tr>
                <?php else: ?>
                    <?php $i=1; while ($p = $projects->fetch_assoc()):
                        $pct = $p['total_tasks'] > 0 ? round(($p['done_tasks'] / $p['total_tasks']) * 100) : 0;
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                            <?php if ($p['description']): ?>
                                <small class="text-muted"><?= htmlspecialchars(substr($p['description'],0,60)) ?><?= strlen($p['description'])>60?'...':'' ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge rounded-pill
                                <?= $p['status']==='active'?'bg-success':($p['status']==='completed'?'bg-primary':($p['status']==='on_hold'?'bg-warning text-dark':'bg-secondary')) ?>">
                                <?= ucfirst(str_replace('_',' ',$p['status'])) ?>
                            </span>
                        </td>
                        <td><?= $p['start_date'] ? date('d M Y', strtotime($p['start_date'])) : '-' ?></td>
                        <td><?= $p['end_date'] ? date('d M Y', strtotime($p['end_date'])) : '-' ?></td>
                        <td><?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?></td>
                        <td style="min-width:100px;">
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $pct ?>%</small>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="editProject(<?= htmlspecialchars(json_encode($p)) ?>)">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteProject(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="projectForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="projectId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="projectName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" id="projectDesc" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="projectStatus">
                                <option value="active">Active</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="projectStart">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" name="end_date" id="projectEnd">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProject(p) {
    document.getElementById('modalTitle').textContent = 'Edit Project';
    document.getElementById('projectId').value = p.id;
    document.getElementById('projectName').value = p.name;
    document.getElementById('projectDesc').value = p.description || '';
    document.getElementById('projectStatus').value = p.status;
    document.getElementById('projectStart').value = p.start_date || '';
    document.getElementById('projectEnd').value = p.end_date || '';
    new bootstrap.Modal(document.getElementById('addProjectModal')).show();
}

document.getElementById('addProjectModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('projectForm').reset();
    document.getElementById('projectId').value = '';
    document.getElementById('modalTitle').textContent = 'New Project';
});

document.getElementById('projectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch('api/project_crud.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) location.reload();
            else alert(res.message || 'Error saving project');
        });
});

function deleteProject(id, name) {
    if (!confirm('Delete project "' + name + '"? All tasks in this project will be unlinked.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('api/project_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

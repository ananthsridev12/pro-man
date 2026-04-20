<?php
require_once 'config.php';
$pageTitle = 'Tasks';
$db = getDB();

$filterProject = isset($_GET['project']) ? (int)$_GET['project'] : 0;
$filterStatus  = isset($_GET['status'])  ? $_GET['status']  : '';
$filterPriority= isset($_GET['priority'])? $_GET['priority'] : '';

$where = '1=1';
if ($filterProject) $where .= " AND t.project_id = $filterProject";
if ($filterStatus)  $where .= " AND t.status = '" . $db->real_escape_string($filterStatus) . "'";
if ($filterPriority)$where .= " AND t.priority = '" . $db->real_escape_string($filterPriority) . "'";

$tasks = $db->query("
    SELECT t.*, p.name as project_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    WHERE $where
    ORDER BY
        FIELD(t.priority,'critical','high','medium','low'),
        t.due_date ASC
");
$projects = $db->query("SELECT id, name FROM projects ORDER BY name");
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <select name="project" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Projects</option>
            <?php $projects->data_seek(0); while ($pr = $projects->fetch_assoc()): ?>
                <option value="<?= $pr['id'] ?>" <?= $filterProject == $pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <?php foreach(['not_started','in_progress','completed','on_hold','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Priority</option>
            <?php foreach(['critical','high','medium','low'] as $pr): ?>
                <option value="<?= $pr ?>" <?= $filterPriority===$pr?'selected':'' ?>><?= ucfirst($pr) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterProject || $filterStatus || $filterPriority): ?>
            <a href="tasks.php" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
        <i class="fa fa-plus me-1"></i> New Task
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Task Name</th>
                        <th>Project</th>
                        <th>Assignee</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($tasks->num_rows === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">No tasks found. <a href="upload.php">Import from Excel</a> or add manually.</td></tr>
                <?php else: ?>
                    <?php $i=1; while ($t = $tasks->fetch_assoc()):
                        $overdue = $t['due_date'] && strtotime($t['due_date']) < time() && !in_array($t['status'],['completed','cancelled']);
                    ?>
                    <tr <?= $overdue ? 'class="table-danger"' : '' ?>>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($t['task_name']) ?></div>
                            <?php if ($t['notes']): ?>
                                <small class="text-muted"><?= htmlspecialchars(substr($t['notes'],0,50)) ?><?= strlen($t['notes'])>50?'...':'' ?></small>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($t['project_name'] ?? '-') ?></small></td>
                        <td><?= htmlspecialchars($t['assignee'] ?? '-') ?></td>
                        <td><span class="badge-status status-<?= $t['status'] ?>"><?= ucwords(str_replace('_',' ',$t['status'])) ?></span></td>
                        <td><span class="badge-status priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                        <td>
                            <?php if ($t['due_date']): ?>
                                <?= date('d M Y', strtotime($t['due_date'])) ?>
                                <?php if ($overdue): ?><br><small class="text-danger fw-bold">Overdue</small><?php endif; ?>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td style="min-width:80px;">
                            <div class="progress" style="height:5px;">
                                <div class="progress-bar" style="width:<?= $t['progress'] ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $t['progress'] ?>%</small>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="editTask(<?= htmlspecialchars(json_encode($t)) ?>)">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteTask(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['task_name'])) ?>')">
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

<!-- Add/Edit Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskModalTitle">New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="taskForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="taskId">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Task Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="task_name" id="taskName" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Project</label>
                            <select class="form-select" name="project_id" id="taskProject">
                                <option value="">-- None --</option>
                                <?php $projects->data_seek(0); while ($pr = $projects->fetch_assoc()): ?>
                                    <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Assignee</label>
                            <input type="text" class="form-control" name="assignee" id="taskAssignee">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="taskStatus">
                                <option value="not_started">Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="on_hold">On Hold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" name="priority" id="taskPriority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="taskStart">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Due Date</label>
                            <input type="date" class="form-control" name="due_date" id="taskDue">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Progress (%)</label>
                            <input type="number" class="form-control" name="progress" id="taskProgress" min="0" max="100" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control" name="notes" id="taskNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTask(t) {
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    document.getElementById('taskId').value = t.id;
    document.getElementById('taskName').value = t.task_name;
    document.getElementById('taskProject').value = t.project_id || '';
    document.getElementById('taskAssignee').value = t.assignee || '';
    document.getElementById('taskStatus').value = t.status;
    document.getElementById('taskPriority').value = t.priority;
    document.getElementById('taskStart').value = t.start_date || '';
    document.getElementById('taskDue').value = t.due_date || '';
    document.getElementById('taskProgress').value = t.progress || 0;
    document.getElementById('taskNotes').value = t.notes || '';
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

document.getElementById('addTaskModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('taskModalTitle').textContent = 'New Task';
});

document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch('api/task_crud.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) location.reload();
            else alert(res.message || 'Error saving task');
        });
});

function deleteTask(id, name) {
    if (!confirm('Delete task "' + name + '"?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('api/task_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

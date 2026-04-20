<?php
require_once 'config.php';
$pageTitle = 'Dashboard';
$db = getDB();

$totalProjects = $db->query("SELECT COUNT(*) as c FROM projects")->fetch_assoc()['c'];
$activeProjects = $db->query("SELECT COUNT(*) as c FROM projects WHERE status='active'")->fetch_assoc()['c'];
$totalTasks = $db->query("SELECT COUNT(*) as c FROM tasks")->fetch_assoc()['c'];
$completedTasks = $db->query("SELECT COUNT(*) as c FROM tasks WHERE status='completed'")->fetch_assoc()['c'];
$inProgressTasks = $db->query("SELECT COUNT(*) as c FROM tasks WHERE status='in_progress'")->fetch_assoc()['c'];
$overdueTasks = $db->query("SELECT COUNT(*) as c FROM tasks WHERE due_date < CURDATE() AND status NOT IN ('completed','cancelled')")->fetch_assoc()['c'];

$recentTasks = $db->query("
    SELECT t.*, p.name as project_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    ORDER BY t.created_at DESC LIMIT 10
");

$projectProgress = $db->query("
    SELECT p.id, p.name, p.status,
        COUNT(t.id) as total,
        SUM(t.status='completed') as done
    FROM projects p
    LEFT JOIN tasks t ON t.project_id = p.id
    GROUP BY p.id ORDER BY p.created_at DESC LIMIT 5
");

include 'includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#2d9cdb,#1a7bbf);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size:2rem;font-weight:700;"><?= $totalProjects ?></div>
                    <div style="opacity:.85;">Total Projects</div>
                </div>
                <i class="fa fa-folder-open fa-2x" style="opacity:.5;"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#27ae60,#1e8449);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size:2rem;font-weight:700;"><?= $completedTasks ?></div>
                    <div style="opacity:.85;">Tasks Completed</div>
                </div>
                <i class="fa fa-circle-check fa-2x" style="opacity:.5;"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#f39c12,#d68910);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size:2rem;font-weight:700;"><?= $inProgressTasks ?></div>
                    <div style="opacity:.85;">In Progress</div>
                </div>
                <i class="fa fa-spinner fa-2x" style="opacity:.5;"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#e74c3c,#c0392b);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size:2rem;font-weight:700;"><?= $overdueTasks ?></div>
                    <div style="opacity:.85;">Overdue Tasks</div>
                </div>
                <i class="fa fa-triangle-exclamation fa-2x" style="opacity:.5;"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold">Recent Tasks</h6>
                <a href="tasks.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Task</th>
                                <th>Project</th>
                                <th>Assignee</th>
                                <th>Status</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentTasks->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No tasks yet. <a href="upload.php">Import from Excel</a></td></tr>
                        <?php else: ?>
                            <?php while ($t = $recentTasks->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['task_name']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($t['project_name'] ?? '-') ?></small></td>
                                <td><small><?= htmlspecialchars($t['assignee'] ?? '-') ?></small></td>
                                <td><span class="badge-status status-<?= $t['status'] ?>"><?= ucwords(str_replace('_',' ',$t['status'])) ?></span></td>
                                <td><span class="badge-status priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold">Project Progress</h6>
                <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if ($projectProgress->num_rows === 0): ?>
                    <p class="text-muted text-center py-3">No projects yet. <a href="projects.php">Add one</a></p>
                <?php else: ?>
                    <?php while ($p = $projectProgress->fetch_assoc()):
                        $pct = $p['total'] > 0 ? round(($p['done'] / $p['total']) * 100) : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold" style="font-size:.9rem;"><?= htmlspecialchars($p['name']) ?></span>
                            <span class="text-muted" style="font-size:.8rem;"><?= $p['done'] ?>/<?= $p['total'] ?> tasks &bull; <?= $pct ?>%</span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body text-center py-4">
                <i class="fa fa-file-excel fa-3x text-success mb-3"></i>
                <h6 class="fw-semibold">Import from Excel</h6>
                <p class="text-muted small">Upload your Excel file to import projects and tasks instantly.</p>
                <a href="upload.php" class="btn btn-success">
                    <i class="fa fa-upload me-1"></i> Upload Excel
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; $db->close(); ?>

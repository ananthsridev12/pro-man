<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'My Work';
$db  = getDB();
$uid = (int)$_SESSION['user_id'];

// My tasks
$tasks = $db->query("
    SELECT t.*, a.asset_name, a.asset_id AS a_code, a.asset_type
    FROM tasks t
    LEFT JOIN assets a ON a.id = t.asset_id
    WHERE t.assigned_to = $uid
    ORDER BY FIELD(t.status,'To Do','In Progress','Blocked','Done'), t.due_date ASC
");

$byStatus = ['To Do'=>[], 'In Progress'=>[], 'Blocked'=>[], 'Done'=>[]];
while ($row = $tasks->fetch_assoc()) {
    $s = $row['status'] ?? 'To Do';
    if (!isset($byStatus[$s])) $byStatus[$s] = [];
    $byStatus[$s][] = $row;
}

// Overdue assets I own
$overdue = $db->query("
    SELECT a.id, a.asset_id AS a_code, a.asset_name, a.asset_type, a.due_date, a.status, a.priority
    FROM assets a
    WHERE a.owner_id = $uid
      AND a.due_date < CURDATE()
      AND a.status NOT IN ('Published / Live','Cancelled')
    ORDER BY a.due_date ASC
    LIMIT 20
");

// Pending approvals — pipeline-based
$role = $_SESSION['user_role'] ?? '';
$roleEsc = addslashes($role);
$pending = $db->query("
    SELECT ai.id AS instance_id, ai.current_stage_order, ast.stage_name,
           c.id AS camp_id, c.campaign_name, c.campaign_id AS camp_code,
           ai.initiated_at
    FROM approval_instances ai
    JOIN approval_stages ast
        ON ast.pipeline_id = ai.pipeline_id
        AND ast.stage_order = ai.current_stage_order
    JOIN campaigns c ON c.id = ai.entity_id
    WHERE ai.status = 'pending'
      AND (ast.approver_user_id = $uid
           OR (ast.approver_user_id IS NULL AND ast.approver_role = '$roleEsc'))
    ORDER BY ai.initiated_at ASC
    LIMIT 20
");

include 'includes/header.php';

$statusColor = ['To Do'=>'#6b7280','In Progress'=>'#3b82f6','Blocked'=>'#ef4444','Done'=>'#10b981'];
$priorityBadge = ['High'=>'danger','Medium'=>'warning','Low'=>'secondary','Critical'=>'danger'];
?>

<div class="row g-3 mb-4">
    <?php
    $counts = array_map('count', $byStatus);
    $cards  = [
        ['To Do',       $counts['To Do'],       '#6b7280','fa-circle-dot'],
        ['In Progress', $counts['In Progress'],  '#3b82f6','fa-spinner'],
        ['Blocked',     $counts['Blocked'],       '#ef4444','fa-ban'],
        ['Done',        $counts['Done'],          '#10b981','fa-circle-check'],
    ];
    foreach ($cards as [$lbl,$cnt,$col,$ico]):
    ?>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div style="font-size:1.5rem;color:<?= $col ?>;">
                <i class="fa <?= $ico ?>"></i>
            </div>
            <div style="font-size:1.6rem;font-weight:700;color:<?= $col ?>;"><?= $cnt ?></div>
            <div style="font-size:.78rem;color:var(--text-muted);"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Task Columns -->
<div class="row g-3 mb-4">
<?php foreach (['To Do','In Progress','Blocked','Done'] as $col): ?>
<div class="col-12 col-md-6 col-xl-3">
    <div class="card h-100">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
            <span class="fw-semibold" style="font-size:.85rem;color:<?= $statusColor[$col] ?>;">
                <i class="fa fa-circle fa-xs me-1"></i><?= $col ?>
            </span>
            <span class="badge rounded-pill" style="background:<?= $statusColor[$col] ?>20;color:<?= $statusColor[$col] ?>;">
                <?= count($byStatus[$col]) ?>
            </span>
        </div>
        <div class="card-body p-2" style="max-height:420px;overflow-y:auto;">
        <?php if (empty($byStatus[$col])): ?>
            <p class="text-center text-muted py-3" style="font-size:.8rem;">No tasks</p>
        <?php else: foreach ($byStatus[$col] as $t): ?>
            <div class="task-card mb-2 p-2" data-id="<?= $t['id'] ?>">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="fw-semibold" style="font-size:.82rem;line-height:1.3;">
                        <?= htmlspecialchars($t['title']) ?>
                    </span>
                    <span class="badge bg-<?= $priorityBadge[$t['priority']] ?? 'secondary' ?> ms-1"
                        style="font-size:.65rem;flex-shrink:0;">
                        <?= htmlspecialchars($t['priority']) ?>
                    </span>
                </div>
                <?php if ($t['a_code']): ?>
                <div style="font-size:.73rem;color:var(--text-muted);" class="mb-1">
                    <i class="fa fa-paperclip me-1"></i><?= htmlspecialchars($t['a_code']) ?>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <?php if ($t['due_date']): ?>
                    <span style="font-size:.72rem;color:<?= $t['due_date'] < date('Y-m-d') && $col!=='Done' ? '#ef4444' : 'var(--text-muted)' ?>;">
                        <i class="fa fa-calendar me-1"></i><?= date('d M', strtotime($t['due_date'])) ?>
                    </span>
                    <?php else: ?><span></span><?php endif; ?>
                    <select class="task-status-sel" data-id="<?= $t['id'] ?>"
                        style="font-size:.7rem;border:1px solid var(--border);border-radius:5px;padding:1px 4px;background:#fff;cursor:pointer;">
                        <?php foreach (['To Do','In Progress','Blocked','Done'] as $s): ?>
                        <option <?= $t['status']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Overdue Assets -->
<?php if ($overdue->num_rows > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-white py-2">
        <span class="fw-semibold text-danger" style="font-size:.88rem;">
            <i class="fa fa-triangle-exclamation me-1"></i> Overdue Assets I Own
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr><th>Asset</th><th>Type</th><th>Due</th><th>Status</th><th>Priority</th></tr>
                </thead>
                <tbody>
                <?php while ($a = $overdue->fetch_assoc()): ?>
                <tr>
                    <td><span class="fw-semibold"><?= htmlspecialchars($a['asset_name']) ?></span>
                        <br><code style="font-size:.72rem;"><?= htmlspecialchars($a['a_code']) ?></code></td>
                    <td><?= htmlspecialchars($a['asset_type']) ?></td>
                    <td class="text-danger fw-semibold"><?= date('d M Y', strtotime($a['due_date'])) ?></td>
                    <td><?= htmlspecialchars($a['status']) ?></td>
                    <td><span class="badge bg-<?= $priorityBadge[$a['priority']] ?? 'secondary' ?>"><?= htmlspecialchars($a['priority']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Pending Approvals -->
<?php if ($pending && $pending->num_rows > 0): ?>
<div class="card">
    <div class="card-header bg-white py-2">
        <span class="fw-semibold" style="font-size:.88rem;">
            <i class="fa fa-clock me-1 text-warning"></i> Campaigns Awaiting My Approval
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr><th>Campaign</th><th>Stage</th><th>Submitted</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php while ($a = $pending->fetch_assoc()): ?>
                <tr>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars($a['campaign_name']) ?></span>
                        <br><code style="font-size:.72rem;"><?= htmlspecialchars($a['camp_code']) ?></code>
                    </td>
                    <td>
                        <span style="font-size:.78rem;background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:10px;">
                            Stage <?= $a['current_stage_order'] ?>: <?= htmlspecialchars($a['stage_name']) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= date('d M Y', strtotime($a['initiated_at'])) ?></td>
                    <td>
                        <a href="campaign-detail.php?id=<?= $a['camp_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-eye me-1"></i> Review
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.task-card {
    background: #f9fafb; border: 1px solid var(--border);
    border-radius: 8px; transition: box-shadow .15s;
}
.task-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
</style>

<script>
document.querySelectorAll('.task-status-sel').forEach(function(sel) {
    sel.addEventListener('change', function() {
        const id = this.dataset.id;
        const status = this.value;
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('id', id);
        fd.append('status', status);
        fetch('api/task_crud.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => { if (res.success) location.reload(); });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

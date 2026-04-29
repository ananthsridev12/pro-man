<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'Kanban';
$db = getDB();

$type = $_GET['type'] ?? array_key_first($ASSET_TYPES);
if (!isset($ASSET_TYPES[$type])) $type = array_key_first($ASSET_TYPES);
$cfg = $ASSET_TYPES[$type];

$uid     = (int)$_SESSION['user_id'];
$isAdmin = in_array($_SESSION['user_role'] ?? '', ['Admin','Project Head','Manager']);
$campFilter = (int)($_GET['campaign'] ?? 0);

$where = "a.archived=0 AND a.asset_type='" . $db->real_escape_string($type) . "'";
if (!$isAdmin) $where .= " AND (a.owner_id=$uid OR a.created_by=$uid OR a.support_id=$uid)";
if ($campFilter) $where .= " AND a.campaign_ref=$campFilter";

$assets = $db->query("
    SELECT a.id, a.asset_id, a.asset_name, a.status, a.priority, a.due_date,
           u.name AS owner_name_disp, c.campaign_name
    FROM assets a
    LEFT JOIN users u ON u.id = a.owner_id
    LEFT JOIN campaigns c ON c.id = a.campaign_ref
    WHERE $where
    ORDER BY a.due_date ASC, a.priority DESC
");

$byStatus = [];
foreach ($ASSET_STATUSES as $s) $byStatus[$s] = [];
while ($a = $assets->fetch_assoc()) {
    $s = $a['status'];
    if (!isset($byStatus[$s])) $byStatus[$s] = [];
    $byStatus[$s][] = $a;
}

$campaigns = $db->query("SELECT id, campaign_name FROM campaigns ORDER BY campaign_name ASC");
include 'includes/header.php';

$priColors = ['Low'=>'#10b981','Medium'=>'#f59e0b','High'=>'#ef4444','Critical'=>'#7c3aed'];
$colColors = [
    'Briefed'             => '#6b7280',
    'In Progress'         => '#3b82f6',
    'In Revision'         => '#f59e0b',
    'Approved by PH'      => '#10b981',
    'Approved by Manager' => '#059669',
    'Published / Live'    => '#065f46',
    'On Hold'             => '#92400e',
    'Cancelled'           => '#9ca3af',
];
?>

<!-- Type Tabs -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
    <?php foreach ($ASSET_TYPES as $t => $c): ?>
    <a href="kanban.php?type=<?= $t ?><?= $campFilter ? '&campaign='.$campFilter : '' ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;
              background:<?= $t===$type ? $c['color'].'22' : '#f3f4f6' ?>;
              color:<?= $t===$type ? $c['color'] : '#6b7280' ?>;
              border:1.5px solid <?= $t===$type ? $c['color'] : '#e5e7eb' ?>;">
        <span style="width:7px;height:7px;border-radius:50%;background:<?= $c['color'] ?>;display:inline-block;flex-shrink:0;"></span>
        <?= $c['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Campaign Filter -->
<div class="d-flex align-items-center gap-2 mb-3" style="flex-wrap:wrap;">
    <form method="get" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="type" value="<?= $type ?>">
        <select name="campaign" class="form-select form-select-sm" style="width:auto;min-width:200px;" onchange="this.form.submit()">
            <option value="">All Campaigns</option>
            <?php while ($cmp = $campaigns->fetch_assoc()): ?>
                <option value="<?= $cmp['id'] ?>" <?= $campFilter==(int)$cmp['id']?'selected':'' ?>>
                    <?= htmlspecialchars($cmp['campaign_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
    <span style="font-size:.8rem;color:var(--text-muted);">
        <?= array_sum(array_map('count', $byStatus)) ?> card<?= array_sum(array_map('count', $byStatus))!=1?'s':'' ?>
    </span>
</div>

<!-- Kanban Board -->
<div id="kanbanBoard" style="display:flex;gap:12px;overflow-x:auto;padding-bottom:16px;align-items:flex-start;min-height:calc(100vh - 280px);">
<?php foreach ($ASSET_STATUSES as $status):
    $col  = $colColors[$status] ?? '#6b7280';
    $cards = $byStatus[$status] ?? [];
?>
<div class="kb-col" data-status="<?= htmlspecialchars($status) ?>"
     style="min-width:220px;flex:0 0 220px;background:#f9fafb;border-radius:10px;border:1px solid var(--border);"
     ondragover="event.preventDefault()" ondrop="onDrop(event, '<?= htmlspecialchars($status) ?>')">
    <!-- Column Header -->
    <div style="padding:10px 12px;border-bottom:2px solid <?= $col ?>;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:.78rem;font-weight:700;color:<?= $col ?>;"><?= htmlspecialchars($status) ?></span>
        <span style="font-size:.7rem;background:<?= $col ?>22;color:<?= $col ?>;border-radius:10px;padding:1px 7px;font-weight:600;">
            <?= count($cards) ?>
        </span>
    </div>
    <!-- Cards -->
    <div style="padding:8px;display:flex;flex-direction:column;gap:6px;min-height:60px;" class="kb-drop-zone">
    <?php if (empty($cards)): ?>
        <div style="font-size:.75rem;color:#d1d5db;text-align:center;padding:16px 8px;">Drop here</div>
    <?php else: foreach ($cards as $a):
        $isOver  = $a['due_date'] && strtotime($a['due_date']) < time() && !in_array($status, ['Published / Live','Cancelled']);
        $priCol  = $priColors[$a['priority']] ?? '#6b7280';
        $initials = strtoupper(substr($a['owner_name_disp'] ?? '?', 0, 2));
    ?>
    <div class="kb-card" draggable="true" data-id="<?= $a['id'] ?>"
         ondragstart="onDragStart(event, <?= $a['id'] ?>, '<?= htmlspecialchars($status) ?>')"
         style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:10px 11px;cursor:grab;
                border-left:3px solid <?= $priCol ?>;<?= $isOver ? 'background:#fef2f2!important;' : '' ?>">
        <div style="font-size:.8rem;font-weight:600;color:#111827;line-height:1.35;margin-bottom:5px;">
            <?= htmlspecialchars($a['asset_name']) ?>
        </div>
        <?php if ($a['asset_id']): ?>
        <div style="font-size:.68rem;color:#9ca3af;font-family:monospace;margin-bottom:5px;"><?= htmlspecialchars($a['asset_id']) ?></div>
        <?php endif; ?>
        <?php if ($a['campaign_name']): ?>
        <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <i class="fa fa-layer-group" style="font-size:.6rem;"></i> <?= htmlspecialchars($a['campaign_name']) ?>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
            <span style="font-size:.68rem;font-weight:600;color:<?= $priCol ?>;"><?= $a['priority'] ?></span>
            <div style="display:flex;align-items:center;gap:6px;">
                <?php if ($a['due_date']): ?>
                <span style="font-size:.68rem;color:<?= $isOver?'#ef4444':'#9ca3af' ?>;">
                    <i class="fa fa-calendar fa-xs"></i> <?= date('d M', strtotime($a['due_date'])) ?>
                </span>
                <?php endif; ?>
                <?php if ($a['owner_name_disp']): ?>
                <div title="<?= htmlspecialchars($a['owner_name_disp']) ?>"
                     style="width:20px;height:20px;border-radius:50%;background:var(--accent);color:#fff;
                            font-size:.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <?= $initials ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Drag ghost indicator -->
<div id="dragGhost" style="display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);
    background:#111827;color:#fff;font-size:.8rem;padding:6px 14px;border-radius:8px;z-index:9999;">
    Moving…
</div>

<script>
let dragId = null, dragFrom = null;

function onDragStart(e, id, fromStatus) {
    dragId   = id;
    dragFrom = fromStatus;
    e.dataTransfer.effectAllowed = 'move';
    document.getElementById('dragGhost').style.display = 'block';
}

document.addEventListener('dragend', () => {
    document.getElementById('dragGhost').style.display = 'none';
});

function onDrop(e, toStatus) {
    e.preventDefault();
    document.getElementById('dragGhost').style.display = 'none';
    if (!dragId || toStatus === dragFrom) return;
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('asset_id', dragId);
    fd.append('status', toStatus);
    fetch('api/kanban_status.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

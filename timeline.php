<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'Timeline';
$db = getDB();

$uid     = (int)$_SESSION['user_id'];
$isAdmin = in_array($_SESSION['user_role'] ?? '', ['Admin','Project Head','Manager']);
$campFilter = (int)($_GET['campaign'] ?? 0);
$typeFilter = $_GET['type'] ?? '';

$where = "a.archived=0 AND (a.due_date IS NOT NULL OR a.brief_date IS NOT NULL)";
if (!$isAdmin) $where .= " AND (a.owner_id=$uid OR a.created_by=$uid OR a.support_id=$uid)";
if ($campFilter) $where .= " AND a.campaign_ref=$campFilter";
if ($typeFilter && isset($ASSET_TYPES[$typeFilter])) {
    $where .= " AND a.asset_type='" . $db->real_escape_string($typeFilter) . "'";
}

$assets = $db->query("
    SELECT a.id, a.asset_id, a.asset_name, a.asset_type, a.status, a.priority,
           a.brief_date, a.due_date, u.name AS owner_name_disp, c.campaign_name
    FROM assets a
    LEFT JOIN users u ON u.id = a.owner_id
    LEFT JOIN campaigns c ON c.id = a.campaign_ref
    WHERE $where
    ORDER BY COALESCE(a.brief_date, a.due_date) ASC
");
$rows = [];
while ($a = $assets->fetch_assoc()) $rows[] = $a;

// Calculate date range
$rangeStart = $rangeEnd = null;
foreach ($rows as $a) {
    $dates = array_filter([$a['brief_date'], $a['due_date']]);
    foreach ($dates as $d) {
        if (!$rangeStart || $d < $rangeStart) $rangeStart = $d;
        if (!$rangeEnd   || $d > $rangeEnd)   $rangeEnd   = $d;
    }
}
// Default: today ± 3 months
$today = date('Y-m-d');
if (!$rangeStart) $rangeStart = date('Y-m-d', strtotime('-1 month'));
if (!$rangeEnd)   $rangeEnd   = date('Y-m-d', strtotime('+3 months'));
// Add 1-week padding
$rangeStart = date('Y-m-d', strtotime($rangeStart . ' -7 days'));
$rangeEnd   = date('Y-m-d', strtotime($rangeEnd   . ' +7 days'));

$totalDays = max(1, (strtotime($rangeEnd) - strtotime($rangeStart)) / 86400);

function dayPct($date, $rangeStart, $totalDays) {
    $pct = (strtotime($date) - strtotime($rangeStart)) / ($totalDays * 86400) * 100;
    return max(0, min(100, $pct));
}

// Build month headers
$months = [];
$cur = strtotime(date('Y-m-01', strtotime($rangeStart)));
$end = strtotime($rangeEnd);
while ($cur <= $end) {
    $label = date('M Y', $cur);
    $pct   = dayPct(date('Y-m-d', $cur), $rangeStart, $totalDays);
    $months[] = ['label'=>$label, 'pct'=>$pct];
    $cur = strtotime('+1 month', $cur);
}

$todayPct = dayPct($today, $rangeStart, $totalDays);

$campaigns = $db->query("SELECT id, campaign_name FROM campaigns ORDER BY campaign_name ASC");
include 'includes/header.php';
?>

<!-- Filters -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select name="campaign" class="form-select form-select-sm" style="width:auto;min-width:190px;" onchange="this.form.submit()">
            <option value="">All Campaigns</option>
            <?php while ($cmp = $campaigns->fetch_assoc()): ?>
                <option value="<?= $cmp['id'] ?>" <?= $campFilter==$cmp['id']?'selected':'' ?>>
                    <?= htmlspecialchars($cmp['campaign_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="type" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach ($ASSET_TYPES as $t => $c): ?>
                <option value="<?= $t ?>" <?= $typeFilter===$t?'selected':'' ?>><?= $c['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <span style="font-size:.8rem;color:var(--text-muted);"><?= count($rows) ?> asset<?= count($rows)!=1?'s':'' ?></span>
</div>

<?php if (empty($rows)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">No assets with dates to display.</div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <div style="overflow-x:auto;">
            <div style="min-width:700px;">

                <!-- Month Header -->
                <div style="display:flex;border-bottom:1px solid var(--border);">
                    <div style="width:220px;flex-shrink:0;padding:8px 12px;font-size:.72rem;font-weight:700;color:var(--text-muted);border-right:1px solid var(--border);">
                        Asset
                    </div>
                    <div style="flex:1;position:relative;height:32px;">
                        <?php foreach ($months as $m): ?>
                        <div style="position:absolute;left:<?= round($m['pct'],2) ?>%;top:0;height:100%;border-left:1px dashed #e5e7eb;
                            padding:8px 0 0 4px;font-size:.68rem;font-weight:600;color:var(--text-muted);white-space:nowrap;overflow:hidden;">
                            <?= $m['label'] ?>
                        </div>
                        <?php endforeach; ?>
                        <!-- Today line -->
                        <?php if ($todayPct > 0 && $todayPct < 100): ?>
                        <div style="position:absolute;left:<?= round($todayPct,2) ?>%;top:0;bottom:0;width:2px;background:#ef4444;z-index:2;"
                             title="Today">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Asset Rows -->
                <?php foreach ($rows as $a):
                    $aCfg  = $ASSET_TYPES[$a['asset_type']] ?? ['color'=>'#6b7280','label'=>$a['asset_type']];
                    $color = $aCfg['color'];
                    $start = $a['brief_date'] ?: $a['due_date'];
                    $end   = $a['due_date']   ?: $a['brief_date'];
                    if ($end < $start) $end = $start;
                    // Ensure bar has minimum width (1 day = 1/totalDays * 100%)
                    if ($start === $end) $end = date('Y-m-d', strtotime($start . ' +2 days'));
                    $left  = round(dayPct($start, $rangeStart, $totalDays), 3);
                    $width = round(dayPct($end, $rangeStart, $totalDays) - $left, 3);
                    $width = max(0.5, $width);
                    $isOver = $a['due_date'] && $a['due_date'] < $today
                        && !in_array($a['status'], ['Published / Live','Cancelled','Archived']);
                ?>
                <div style="display:flex;border-bottom:1px solid #f3f4f6;align-items:center;min-height:40px;">
                    <!-- Asset label -->
                    <div style="width:220px;flex-shrink:0;padding:8px 10px;font-size:.78rem;border-right:1px solid #f3f4f6;">
                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#111827;">
                            <?= htmlspecialchars($a['asset_name']) ?>
                        </div>
                        <div style="font-size:.68rem;color:var(--text-muted);display:flex;align-items:center;gap:5px;">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?= $color ?>;display:inline-block;flex-shrink:0;"></span>
                            <?= $aCfg['label'] ?>
                            <?php if ($a['owner_name_disp']): ?>
                                · <?= htmlspecialchars($a['owner_name_disp']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Bar area -->
                    <div style="flex:1;position:relative;height:40px;">
                        <!-- Today line (overlay) -->
                        <?php if ($todayPct > 0 && $todayPct < 100): ?>
                        <div style="position:absolute;left:<?= round($todayPct,2) ?>%;top:0;bottom:0;width:2px;background:#ef444422;z-index:1;pointer-events:none;"></div>
                        <?php endif; ?>
                        <!-- Asset bar -->
                        <div title="<?= htmlspecialchars($a['asset_name']) ?> | <?= $a['brief_date'] ?: '—' ?> → <?= $a['due_date'] ?: '—' ?> | <?= $a['status'] ?>"
                             style="position:absolute;left:<?= $left ?>%;width:<?= $width ?>%;top:50%;transform:translateY(-50%);
                                    height:20px;border-radius:4px;z-index:2;
                                    background:<?= $isOver ? '#fca5a5' : $color ?>33;
                                    border:1.5px solid <?= $isOver ? '#ef4444' : $color ?>;
                                    display:flex;align-items:center;padding:0 5px;min-width:4px;overflow:hidden;">
                            <?php if ($width > 3): ?>
                            <span style="font-size:.64rem;font-weight:600;color:<?= $isOver ? '#991b1b' : $color ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($a['due_date'] ? date('d M', strtotime($a['due_date'])) : '') ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Legend -->
                <div style="padding:10px 12px;border-top:1px solid var(--border);display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                    <span style="font-size:.72rem;color:var(--text-muted);">Legend:</span>
                    <?php foreach ($ASSET_TYPES as $t => $c): if (empty(array_filter($rows, fn($r)=>$r['asset_type']===$t))) continue; ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:.72rem;">
                        <span style="width:12px;height:12px;border-radius:3px;background:<?= $c['color'] ?>33;border:1.5px solid <?= $c['color'] ?>;display:inline-block;"></span>
                        <?= $c['label'] ?>
                    </span>
                    <?php endforeach; ?>
                    <?php if ($todayPct > 0 && $todayPct < 100): ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:.72rem;">
                        <span style="width:12px;height:3px;background:#ef4444;display:inline-block;border-radius:2px;"></span>
                        Today
                    </span>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; $db->close(); ?>

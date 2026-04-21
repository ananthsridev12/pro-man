<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'Dashboard';
$db = getDB();

$totalCampaigns  = $db->query("SELECT COUNT(*) c FROM campaigns")->fetch_assoc()['c'];
$activeCampaigns = $db->query("SELECT COUNT(*) c FROM campaigns WHERE campaign_status='Active'")->fetch_assoc()['c'];
$totalAssets     = $db->query("SELECT COUNT(*) c FROM assets WHERE archived=0")->fetch_assoc()['c'];
$liveAssets      = $db->query("SELECT COUNT(*) c FROM assets WHERE status='Published / Live' AND archived=0")->fetch_assoc()['c'];
$inReview        = $db->query("SELECT COUNT(*) c FROM assets WHERE status IN ('In Revision','In Review') AND archived=0")->fetch_assoc()['c'];
$overdue         = $db->query("SELECT COUNT(*) c FROM assets WHERE due_date < CURDATE() AND status NOT IN ('Published / Live','Live','Approved by Manager','Cancelled','Archived') AND archived=0")->fetch_assoc()['c'];
$pendingAppr     = $db->query("SELECT COUNT(*) c FROM assets WHERE (approved_project_head='Pending' OR approved_manager='Pending') AND status NOT IN ('Cancelled','Archived') AND archived=0")->fetch_assoc()['c'];
$dueThisWeek     = $db->query("SELECT COUNT(*) c FROM assets WHERE due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status NOT IN ('Published / Live','Live','Cancelled','Archived') AND archived=0")->fetch_assoc()['c'];

$byType = $db->query("SELECT asset_type, COUNT(*) c FROM assets WHERE archived=0 GROUP BY asset_type");

$recentAssets = $db->query("
    SELECT a.*, c.campaign_name
    FROM assets a
    LEFT JOIN campaigns c ON c.id = a.campaign_ref
    WHERE a.archived=0
    ORDER BY a.created_at DESC LIMIT 12
");

$campaigns = $db->query("
    SELECT c.*,
        COUNT(a.id) total_assets,
        SUM(a.status='Published / Live') live_assets
    FROM campaigns c
    LEFT JOIN assets a ON a.campaign_ref=c.id AND a.archived=0
    GROUP BY c.id ORDER BY c.created_at DESC LIMIT 6
");

include 'includes/header.php';
?>

<!-- KPI Stats -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['num'=>$totalCampaigns,  'label'=>'Total Campaigns',    'icon'=>'fa-layer-group',    'color'=>'#3b82f6', 'bg'=>'#eff6ff'],
        ['num'=>$activeCampaigns, 'label'=>'Active Campaigns',   'icon'=>'fa-rocket',         'color'=>'#10b981', 'bg'=>'#ecfdf5'],
        ['num'=>$totalAssets,     'label'=>'Total Assets',       'icon'=>'fa-folder-open',    'color'=>'#8b5cf6', 'bg'=>'#f5f3ff'],
        ['num'=>$liveAssets,      'label'=>'Published / Live',   'icon'=>'fa-circle-check',   'color'=>'#059669', 'bg'=>'#d1fae5'],
        ['num'=>$inReview,        'label'=>'In Revision',        'icon'=>'fa-rotate',         'color'=>'#f59e0b', 'bg'=>'#fef3c7'],
        ['num'=>$overdue,         'label'=>'Overdue',            'icon'=>'fa-triangle-exclamation','color'=>'#ef4444','bg'=>'#fee2e2'],
        ['num'=>$pendingAppr,     'label'=>'Pending Approvals',  'icon'=>'fa-clock',          'color'=>'#6366f1', 'bg'=>'#eef2ff'],
        ['num'=>$dueThisWeek,     'label'=>'Due This Week',      'icon'=>'fa-calendar-check', 'color'=>'#0891b2', 'bg'=>'#ecfeff'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;">
                <i class="fa <?= $s['icon'] ?>"></i>
            </div>
            <div>
                <div class="stat-num" style="color:<?= $s['color'] ?>;"><?= $s['num'] ?></div>
                <div class="stat-label"><?= $s['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Assets by Tracker -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Assets by Tracker</h6>
            </div>
            <div class="card-body" style="padding:16px 18px;">
                <?php
                $typeCount = [];
                while ($r = $byType->fetch_assoc()) $typeCount[$r['asset_type']] = $r['c'];
                foreach ($ASSET_TYPES as $navType => $navCfg):
                    $cnt = $typeCount[$navType] ?? 0;
                    $pct = $totalAssets > 0 ? round(($cnt / $totalAssets) * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-size:.82rem;font-weight:500;display:flex;align-items:center;gap:7px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?= $navCfg['color'] ?>;display:inline-block;flex-shrink:0;"></span>
                            <?= $navCfg['label'] ?>
                        </span>
                        <span style="font-size:.78rem;font-weight:600;color:#374151;"><?= $cnt ?></span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:4px;">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $navCfg['color'] ?>;border-radius:4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Campaign Summary -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Campaigns</h6>
                <a href="campaigns.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Campaign ID</th><th>Name</th>
                                <th class="d-none d-md-table-cell">Owner</th>
                                <th>Status</th>
                                <th class="d-none d-sm-table-cell">Go-Live</th>
                                <th>Assets</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($campaigns->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                No campaigns yet. <a href="campaigns.php">Add one</a> or <a href="upload.php">import</a>.
                            </td></tr>
                        <?php else: while ($c = $campaigns->fetch_assoc()): ?>
                            <tr>
                                <td><code class="id-code"><?= htmlspecialchars($c['campaign_id'] ?? '') ?></code></td>
                                <td class="fw-semibold"><?= htmlspecialchars($c['campaign_name']) ?></td>
                                <td class="d-none d-md-table-cell text-muted"><?= htmlspecialchars($c['campaign_owner'] ?? '-') ?></td>
                                <td><span class="badge-pill cs-<?= str_replace([' ','/'],'_',$c['campaign_status']) ?>"><?= $c['campaign_status'] ?></span></td>
                                <td class="d-none d-sm-table-cell text-muted"><?= $c['go_live_date'] ? date('d M Y', strtotime($c['go_live_date'])) : '-' ?></td>
                                <td><?= $c['live_assets'] ?><span class="text-muted">/<?= $c['total_assets'] ?></span></td>
                            </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Assets -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Recent Assets</h6>
        <a href="assets.php?type=creative" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;">Browse by Type</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="hide-xs">Asset ID</th><th>Name</th><th>Type</th>
                        <th class="d-none d-md-table-cell">Campaign</th>
                        <th class="d-none d-lg-table-cell">Owner</th>
                        <th class="d-none d-md-table-cell">Due Date</th>
                        <th>Priority</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recentAssets->num_rows === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        No assets yet. <a href="upload.php">Import from Excel</a>.
                    </td></tr>
                <?php else: while ($a = $recentAssets->fetch_assoc()):
                    $aCfg   = $ASSET_TYPES[$a['asset_type']] ?? ['label'=>$a['asset_type'],'color'=>'#6b7280','icon'=>'fa-file'];
                    $overdue = $a['due_date'] && strtotime($a['due_date']) < time()
                        && !in_array($a['status'],['Published / Live','Live','Approved by Manager','Cancelled','Archived']);
                ?>
                    <tr <?= $overdue?'class="table-danger"':'' ?>>
                        <td class="hide-xs"><code class="id-code"><?= htmlspecialchars($a['asset_id'] ?? '') ?></code></td>
                        <td class="fw-semibold"><?= htmlspecialchars($a['asset_name']) ?></td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:.8rem;">
                                <span style="width:8px;height:8px;border-radius:50%;background:<?= $aCfg['color'] ?>;flex-shrink:0;"></span>
                                <?= $aCfg['label'] ?>
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell text-muted"><small><?= htmlspecialchars($a['campaign_name'] ?? '-') ?></small></td>
                        <td class="d-none d-lg-table-cell text-muted"><?= htmlspecialchars($a['owner'] ?? '-') ?></td>
                        <td class="d-none d-md-table-cell text-muted">
                            <?= $a['due_date'] ? date('d M Y', strtotime($a['due_date'])) : '-' ?>
                            <?php if ($overdue): ?> <span class="badge-pill" style="background:#fee2e2;color:#991b1b;">Overdue</span><?php endif; ?>
                        </td>
                        <td><span class="badge-pill pri-<?= $a['priority'] ?>"><?= $a['priority'] ?></span></td>
                        <td><span class="badge-pill st-<?= str_replace([' ','/'],'_',$a['status']) ?>"><?= $a['status'] ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; $db->close(); ?>

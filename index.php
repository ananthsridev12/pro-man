<?php
require_once 'config.php';
$pageTitle = 'Dashboard';
$db = getDB();

$totalCampaigns  = $db->query("SELECT COUNT(*) c FROM campaigns")->fetch_assoc()['c'];
$activeCampaigns = $db->query("SELECT COUNT(*) c FROM campaigns WHERE campaign_status='Active'")->fetch_assoc()['c'];
$totalAssets     = $db->query("SELECT COUNT(*) c FROM assets WHERE archived=0")->fetch_assoc()['c'];
$liveAssets      = $db->query("SELECT COUNT(*) c FROM assets WHERE status='Live' AND archived=0")->fetch_assoc()['c'];
$inReview        = $db->query("SELECT COUNT(*) c FROM assets WHERE status='In Review' AND archived=0")->fetch_assoc()['c'];
$overdue         = $db->query("SELECT COUNT(*) c FROM assets WHERE due_date < CURDATE() AND status NOT IN ('Live','Approved','Cancelled','Archived') AND archived=0")->fetch_assoc()['c'];

// Assets by type
$byType = $db->query("SELECT asset_type, COUNT(*) c FROM assets WHERE archived=0 GROUP BY asset_type");

// Recent assets
$recentAssets = $db->query("
    SELECT a.*, c.campaign_name
    FROM assets a
    LEFT JOIN campaigns c ON c.id = a.campaign_ref
    WHERE a.archived=0
    ORDER BY a.created_at DESC LIMIT 12
");

// Campaign summary
$campaigns = $db->query("
    SELECT c.*,
        COUNT(a.id) total_assets,
        SUM(a.status='Live') live_assets
    FROM campaigns c
    LEFT JOIN assets a ON a.campaign_ref=c.id AND a.archived=0
    GROUP BY c.id ORDER BY c.created_at DESC LIMIT 6
");

include 'includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="card h-100 text-center p-3" style="border-top:3px solid var(--accent);">
            <div style="font-size:1.8rem;font-weight:700;color:var(--accent);"><?= $totalCampaigns ?></div>
            <div class="text-muted small">Total Campaigns</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card h-100 text-center p-3" style="border-top:3px solid #27ae60;">
            <div style="font-size:1.8rem;font-weight:700;color:#27ae60;"><?= $activeCampaigns ?></div>
            <div class="text-muted small">Active Campaigns</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card h-100 text-center p-3" style="border-top:3px solid #8e44ad;">
            <div style="font-size:1.8rem;font-weight:700;color:#8e44ad;"><?= $totalAssets ?></div>
            <div class="text-muted small">Total Assets</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card h-100 text-center p-3" style="border-top:3px solid #16a085;">
            <div style="font-size:1.8rem;font-weight:700;color:#16a085;"><?= $liveAssets ?></div>
            <div class="text-muted small">Live Assets</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card h-100 text-center p-3" style="border-top:3px solid #f39c12;">
            <div style="font-size:1.8rem;font-weight:700;color:#f39c12;"><?= $inReview ?></div>
            <div class="text-muted small">In Review</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card h-100 text-center p-3" style="border-top:3px solid #e74c3c;">
            <div style="font-size:1.8rem;font-weight:700;color:#e74c3c;"><?= $overdue ?></div>
            <div class="text-muted small">Overdue</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Assets by Tracker -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Assets by Tracker</h6>
            </div>
            <div class="card-body">
                <?php
                $typeCount = [];
                while ($r = $byType->fetch_assoc()) $typeCount[$r['asset_type']] = $r['c'];
                foreach ($ASSET_TYPES as $type => $cfg):
                    $cnt = $typeCount[$type] ?? 0;
                    $pct = $totalAssets > 0 ? round(($cnt / $totalAssets) * 100) : 0;
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.85rem;">
                            <i class="fa <?= $cfg['icon'] ?> me-1" style="color:<?= $cfg['color'] ?>;width:16px;"></i>
                            <?= $cfg['label'] ?>
                        </span>
                        <span class="text-muted" style="font-size:.82rem;"><?= $cnt ?></span>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $cfg['color'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Campaign Status -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold">Campaigns</h6>
                <a href="campaigns.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr><th>Campaign ID</th><th>Name</th><th>Owner</th><th>Status</th><th>Go-Live</th><th>Assets</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($campaigns->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No campaigns yet. <a href="campaigns.php">Add one</a> or <a href="upload.php">import from Excel</a>.</td></tr>
                        <?php else: while ($c = $campaigns->fetch_assoc()): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($c['campaign_id'] ?? '') ?></code></td>
                                <td class="fw-semibold"><?= htmlspecialchars($c['campaign_name']) ?></td>
                                <td><?= htmlspecialchars($c['campaign_owner'] ?? '-') ?></td>
                                <td><span class="badge-pill cs-<?= str_replace(' ','-',$c['campaign_status']) ?>"><?= $c['campaign_status'] ?></span></td>
                                <td><?= $c['go_live_date'] ? date('d M Y', strtotime($c['go_live_date'])) : '-' ?></td>
                                <td><?= $c['live_assets'] ?>/<?= $c['total_assets'] ?> live</td>
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
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-semibold">Recent Assets</h6>
        <a href="assets.php?type=creative" class="btn btn-sm btn-outline-secondary">Browse by Type</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.84rem;">
                <thead class="table-light">
                    <tr><th>Asset ID</th><th>Name</th><th>Type</th><th>Campaign</th><th>Owner</th><th>Due Date</th><th>Priority</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if ($recentAssets->num_rows === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No assets yet. <a href="upload.php">Import from Excel</a>.</td></tr>
                <?php else: while ($a = $recentAssets->fetch_assoc()):
                    $cfg = $ASSET_TYPES[$a['asset_type']];
                    $overdue = $a['due_date'] && strtotime($a['due_date']) < time() && !in_array($a['status'],['Live','Approved','Cancelled','Archived']);
                ?>
                    <tr <?= $overdue?'class="table-danger"':'' ?>>
                        <td><code><?= htmlspecialchars($a['asset_id'] ?? '') ?></code></td>
                        <td><?= htmlspecialchars($a['asset_name']) ?></td>
                        <td>
                            <span style="color:<?= $cfg['color'] ?>;font-size:.8rem;">
                                <i class="fa <?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($a['campaign_name'] ?? '-') ?></small></td>
                        <td><?= htmlspecialchars($a['owner'] ?? '-') ?></td>
                        <td><?= $a['due_date'] ? date('d M Y', strtotime($a['due_date'])) : '-' ?></td>
                        <td><span class="badge-pill pri-<?= $a['priority'] ?>"><?= $a['priority'] ?></span></td>
                        <td><span class="badge-pill st-<?= str_replace(' ','-',$a['status']) ?>"><?= $a['status'] ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; $db->close(); ?>

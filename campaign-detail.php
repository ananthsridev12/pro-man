<?php
require_once 'config.php';
require_once 'includes/auth.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: campaigns.php'); exit; }

$campaign = $db->query("SELECT * FROM campaigns WHERE id=$id")->fetch_assoc();
if (!$campaign) { header('Location: campaigns.php'); exit; }

$pageTitle = htmlspecialchars($campaign['campaign_name']);

// All assets for this campaign
$assets = $db->query("
    SELECT a.*, u.name AS owner_name_disp, s.name AS support_name_disp
    FROM assets a
    LEFT JOIN users u ON u.id = a.owner_id
    LEFT JOIN users s ON s.id = a.support_id
    WHERE a.campaign_ref = $id AND a.archived = 0
    ORDER BY a.asset_type, a.created_at DESC
");
$byType = [];
while ($a = $assets->fetch_assoc()) {
    $byType[$a['asset_type']][] = $a;
}

$stats = $db->query("
    SELECT COUNT(*) total,
        SUM(status='Published / Live') live,
        SUM(status='In Progress') in_progress,
        SUM(status='In Revision') in_revision,
        SUM(due_date < CURDATE() AND status NOT IN ('Published / Live','Cancelled')) overdue
    FROM assets WHERE campaign_ref=$id AND archived=0
")->fetch_assoc();

$uid    = (int)$_SESSION['user_id'];
$isAdmin = in_array($_SESSION['user_role'] ?? '', ['Admin','Project Head','Manager']);
$activeUsers = $db->query("SELECT id, name, role FROM users WHERE status='active' ORDER BY name");

// Lead times
$leadTimes = [];
$ltRes = $db->query("SELECT asset_type, days_before_golive FROM asset_lead_times");
if ($ltRes) while ($lt = $ltRes->fetch_assoc()) $leadTimes[$lt['asset_type']] = (int)$lt['days_before_golive'];

// Approval state
$approvalStatus   = $campaign['approval_status'] ?? 'Draft';
$approvalInstance = null;
$approvalStages   = [];
$canSubmit        = false;
$canApprove       = false;
$pendingAction    = null;

$inst = $db->query("SELECT * FROM approval_instances WHERE entity_type='campaign' AND entity_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($inst) {
    $approvalInstance = $inst;
    $iid = (int)$inst['id'];
    $stagesRes = $db->query("
        SELECT s.*, u.name AS approver_name,
               aa.action AS act, aa.id AS action_id,
               aa.comments AS act_comments, aa.acted_at,
               ua.name AS actor_name
        FROM approval_stages s
        LEFT JOIN approval_actions aa ON aa.instance_id=$iid AND aa.stage_id=s.id
        LEFT JOIN users ua ON ua.id = aa.approver_user_id
        LEFT JOIN users u  ON u.id  = s.approver_user_id
        WHERE s.pipeline_id={$inst['pipeline_id']}
        ORDER BY s.stage_order ASC
    ");
    if ($stagesRes) while ($s = $stagesRes->fetch_assoc()) $approvalStages[] = $s;

    if ($inst['status'] === 'pending') {
        foreach ($approvalStages as $s) {
            if ((int)$s['stage_order'] === (int)$inst['current_stage_order'] && ($s['act'] ?? 'pending') === 'pending') {
                if ((int)$s['approver_user_id'] === $uid || (!$s['approver_user_id'] && $s['approver_role'] === ($_SESSION['user_role'] ?? ''))) {
                    $canApprove  = true;
                    $pendingAction = $s;
                }
                break;
            }
        }
    }
}

if (in_array($approvalStatus, ['Draft','Rejected'])) {
    $canSubmit = $isAdmin || ($campaign['campaign_owner'] === ($_SESSION['user_name'] ?? ''));
}

include 'includes/header.php';
?>

<!-- Campaign Header -->
<div class="card mb-4">
    <div class="card-body" style="padding:20px 24px;">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="campaigns.php" class="text-muted" style="font-size:.8rem;text-decoration:none;">
                        <i class="fa fa-arrow-left me-1"></i>Campaigns
                    </a>
                    <span class="text-muted" style="font-size:.8rem;">/</span>
                    <code class="id-code"><?= htmlspecialchars($campaign['campaign_id'] ?? '') ?></code>
                </div>
                <h4 class="fw-bold mb-1" style="font-size:1.2rem;"><?= htmlspecialchars($campaign['campaign_name']) ?></h4>
                <div class="d-flex flex-wrap gap-2 align-items-center" style="font-size:.82rem;color:var(--text-muted);">
                    <?php if ($campaign['vertical']): ?>
                        <span class="vertical-chip"><?= htmlspecialchars($campaign['vertical']) ?></span>
                    <?php endif; ?>
                    <?php if ($campaign['campaign_type']): ?>
                        <span><i class="fa fa-tag me-1"></i><?= htmlspecialchars($campaign['campaign_type']) ?></span>
                    <?php endif; ?>
                    <?php if ($campaign['campaign_owner']): ?>
                        <span><i class="fa fa-user me-1"></i><?= htmlspecialchars($campaign['campaign_owner']) ?></span>
                    <?php endif; ?>
                    <?php if ($campaign['go_live_date']): ?>
                        <span><i class="fa fa-calendar-check me-1"></i>Go-Live: <?= date('d M Y', strtotime($campaign['go_live_date'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-shrink-0">
                <span class="badge-pill cs-<?= str_replace([' ','/'],'_',$campaign['campaign_status']) ?>">
                    <?= htmlspecialchars($campaign['campaign_status']) ?>
                </span>
                <span class="badge-pill pri-<?= $campaign['priority'] ?>">
                    <?= htmlspecialchars($campaign['priority']) ?>
                </span>
                <button class="btn btn-sm btn-outline-secondary"
                    onclick="editCampaign(<?= htmlspecialchars(json_encode($campaign)) ?>)">
                    <i class="fa fa-pen"></i> Edit
                </button>
            </div>
        </div>

        <?php if ($campaign['notes']): ?>
        <div class="mt-3 p-3" style="background:#f9fafb;border-radius:8px;font-size:.84rem;color:var(--text-muted);">
            <?= nl2br(htmlspecialchars($campaign['notes'])) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['Total Requirements', $stats['total'],       '#3b82f6','#eff6ff','fa-folder-open'],
        ['Published / Live',   $stats['live'],        '#059669','#d1fae5','fa-circle-check'],
        ['In Progress',        $stats['in_progress'], '#f59e0b','#fef3c7','fa-spinner'],
        ['Overdue',            $stats['overdue'],     '#ef4444','#fee2e2','fa-triangle-exclamation'],
    ];
    foreach ($kpis as [$lbl,$num,$col,$bg,$ico]):
    ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $col ?>;"><i class="fa <?= $ico ?>"></i></div>
            <div>
                <div class="stat-num" style="color:<?= $col ?>;"><?= (int)$num ?></div>
                <div class="stat-label"><?= $lbl ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Approval Status Bar -->
<?php
$apBg    = ['Draft'=>'#f3f4f6','Submitted'=>'#dbeafe','Approved'=>'#dcfce7','Rejected'=>'#fee2e2'];
$apColor = ['Draft'=>'#6b7280','Submitted'=>'#1d4ed8','Approved'=>'#15803d','Rejected'=>'#dc2626'];
$apStatusKey = preg_replace('/\s*\(.*\)/','',$approvalStatus); // strip "(Stage N of M)"
?>
<div class="card mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span style="font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;">Approval</span>
                <span style="background:<?= $apBg[$apStatusKey] ?? '#f3f4f6' ?>;color:<?= $apColor[$apStatusKey] ?? '#6b7280' ?>;
                    padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:600;">
                    <?= htmlspecialchars($approvalStatus) ?>
                </span>

                <?php if (!empty($approvalStages) && $approvalInstance): ?>
                <div class="d-flex align-items-center gap-1 ms-1">
                    <?php foreach ($approvalStages as $i => $s):
                        $act = $s['act'] ?? 'pending';
                        $isCurrent = (int)$s['stage_order'] === (int)($approvalInstance['current_stage_order'] ?? 1);
                        $dotBg  = $act==='approved' ? '#dcfce7' : ($act==='rejected' ? '#fee2e2' : ($isCurrent ? '#dbeafe' : '#f3f4f6'));
                        $dotCol = $act==='approved' ? '#15803d' : ($act==='rejected' ? '#dc2626' : ($isCurrent ? '#2563eb' : '#9ca3af'));
                    ?>
                    <?php if ($i > 0): ?><span style="width:18px;height:2px;background:#e5e7eb;flex-shrink:0;"></span><?php endif; ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:2px;" title="<?= htmlspecialchars($s['stage_name']) ?>">
                        <div style="width:26px;height:26px;border-radius:50%;background:<?= $dotBg ?>;color:<?= $dotCol ?>;
                            display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;
                            border:2px solid <?= $dotCol ?>;">
                            <?php if ($act==='approved'): ?><i class="fa fa-check"></i>
                            <?php elseif ($act==='rejected'): ?><i class="fa fa-xmark"></i>
                            <?php else: ?><?= $s['stage_order'] ?><?php endif; ?>
                        </div>
                        <span style="font-size:.58rem;color:var(--text-muted);white-space:nowrap;max-width:56px;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($s['stage_name']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($approvalStatus === 'Rejected' && !empty($approvalStages)):
                    foreach (array_reverse($approvalStages) as $s):
                        if ($s['act'] === 'rejected' && $s['act_comments']): ?>
                <span class="ms-2" style="font-size:.79rem;color:#dc2626;">
                    <i class="fa fa-comment-slash me-1"></i><?= htmlspecialchars(mb_substr($s['act_comments'],0,70)) ?><?= mb_strlen($s['act_comments'])>70?'…':'' ?>
                </span>
                <?php break; endif; endforeach; endif; ?>
            </div>

            <?php if ($canSubmit): ?>
            <button class="btn btn-sm btn-primary" onclick="submitForApproval()">
                <i class="fa fa-paper-plane me-1"></i>
                <?= $approvalStatus === 'Rejected' ? 'Re-submit for Approval' : 'Submit for Approval' ?>
            </button>
            <?php endif; ?>
        </div>

        <?php if ($canApprove && $pendingAction): ?>
        <div class="mt-3 pt-3" style="border-top:1px solid var(--border);">
            <p class="fw-semibold mb-2" style="font-size:.84rem;">
                <i class="fa fa-gavel me-1 text-primary"></i>
                Stage <?= $pendingAction['stage_order'] ?>: <?= htmlspecialchars($pendingAction['stage_name']) ?> — Your approval is needed
            </p>
            <textarea id="approvalComment" class="form-control form-control-sm mb-2" rows="2"
                placeholder="Comments (optional for approval, required for rejection)"></textarea>
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-sm" onclick="doApprovalAction('approve')">
                    <i class="fa fa-check me-1"></i> Approve
                </button>
                <button class="btn btn-danger btn-sm" onclick="doApprovalAction('reject')">
                    <i class="fa fa-xmark me-1"></i> Reject
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($approvalStages)): ?>
        <div class="mt-3 pt-2" style="border-top:1px solid var(--border);">
            <button class="btn btn-link btn-sm p-0 text-muted text-decoration-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#approvalHistory" style="font-size:.8rem;">
                <i class="fa fa-history me-1"></i> View Approval History
            </button>
            <div class="collapse mt-2" id="approvalHistory">
                <div class="border rounded p-3" style="background:#f9fafb;font-size:.82rem;">
                    <?php foreach ($approvalStages as $s):
                        $act     = $s['act'] ?? 'pending';
                        $actCls  = $act==='approved' ? 'text-success' : ($act==='rejected' ? 'text-danger' : 'text-muted');
                        $actIcon = $act==='approved' ? 'fa-circle-check' : ($act==='rejected' ? 'fa-circle-xmark' : 'fa-clock');
                    ?>
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="fa <?= $actIcon ?> <?= $actCls ?> mt-1" style="flex-shrink:0;"></i>
                        <div>
                            <strong>Stage <?= $s['stage_order'] ?>: <?= htmlspecialchars($s['stage_name']) ?></strong>
                            <?php if ($s['approver_role']): ?>
                                <span class="text-muted">(<?= htmlspecialchars($s['approver_role']) ?>)</span>
                            <?php elseif ($s['approver_name']): ?>
                                <span class="text-muted">(<?= htmlspecialchars($s['approver_name']) ?>)</span>
                            <?php endif; ?>
                            — <span class="<?= $actCls ?>"><?= ucfirst($act) ?></span>
                            <?php if ($s['acted_at']): ?>
                                <span class="text-muted">· <?= date('d M Y, H:i', strtotime($s['acted_at'])) ?></span>
                            <?php endif; ?>
                            <?php if ($s['actor_name']): ?>
                                <span class="text-muted">by <?= htmlspecialchars($s['actor_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($s['act_comments']): ?>
                                <div class="text-muted mt-1 fst-italic">"<?= htmlspecialchars($s['act_comments']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Requirements by Type -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-semibold mb-0">Requirements</h6>
    <div class="d-flex gap-2">
        <!-- Type selector for adding new requirement -->
        <select id="newReqType" class="form-select form-select-sm" style="width:auto;">
            <?php foreach ($ASSET_TYPES as $t => $cfg): ?>
                <option value="<?= $t ?>"><?= $cfg['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" onclick="openAddRequirement()">
            <i class="fa fa-plus me-1"></i>Add Requirement
        </button>
    </div>
</div>

<?php if (empty($byType)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        No requirements yet. Click "Add Requirement" to get started.
    </div>
</div>
<?php else: ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="reqTabs">
    <?php $first = true; foreach ($byType as $type => $items): $cfg = $ASSET_TYPES[$type] ?? ['label'=>$type,'color'=>'#6b7280']; ?>
    <li class="nav-item">
        <button class="nav-link <?= $first?'active':'' ?>" data-type="<?= $type ?>"
            onclick="showTab('<?= $type ?>', this)">
            <span style="display:inline-flex;align-items:center;gap:6px;">
                <span style="width:7px;height:7px;border-radius:50%;background:<?= $cfg['color'] ?>;display:inline-block;flex-shrink:0;"></span>
                <?= $cfg['label'] ?> <span class="badge rounded-pill bg-secondary ms-1" style="font-size:.65rem;"><?= count($items) ?></span>
            </span>
        </button>
    </li>
    <?php $first = false; endforeach; ?>
</ul>

<?php foreach ($byType as $type => $items): $cfg = $ASSET_TYPES[$type] ?? ['label'=>$type,'color'=>'#6b7280']; $first = array_key_first($byType); ?>
<div class="tab-pane-custom" id="tab-<?= $type ?>" style="<?= $type !== $first ? 'display:none;' : '' ?>">
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="hide-xs">Asset ID</th>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">Assigned To</th>
                            <th class="d-none d-md-table-cell">Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $a):
                        $isOverdue = $a['due_date'] && strtotime($a['due_date']) < time()
                            && !in_array($a['status'], ['Published / Live','Cancelled','Archived']);
                    ?>
                        <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                            <td class="hide-xs"><code class="id-code"><?= htmlspecialchars($a['asset_id'] ?? '') ?></code></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($a['asset_name']) ?></div>
                                <?php if ($a['support_name_disp']): ?>
                                    <small class="text-muted">Support: <?= htmlspecialchars($a['support_name_disp']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($a['owner_name_disp'] ?? '-') ?></td>
                            <td class="d-none d-md-table-cell text-muted">
                                <?= $a['due_date'] ? date('d M Y', strtotime($a['due_date'])) : '-' ?>
                                <?php if ($isOverdue): ?><br><span class="badge-pill" style="background:#fee2e2;color:#991b1b;font-size:.65rem;">Overdue</span><?php endif; ?>
                            </td>
                            <td><span class="badge-pill pri-<?= $a['priority'] ?>"><?= $a['priority'] ?></span></td>
                            <td><span class="badge-pill st-<?= str_replace([' ','/'],'_',$a['status']) ?>"><?= $a['status'] ?></span></td>
                            <?php if ($isAdmin || (int)$a['created_by'] === $uid): ?>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    onclick='editAsset(<?= htmlspecialchars(json_encode($a)) ?>)'>
                                    <i class="fa fa-pen"></i>
                                </button>
                            </td>
                            <?php elseif ($isAdmin): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ========== Campaign Edit Modal (reused from campaigns.php) ========== -->
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="campaignForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="cId">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="campaign_name" id="cName" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Vertical</label>
                            <select class="form-select" name="vertical" id="cVertical">
                                <option value="">-- Select --</option>
                                <?php foreach ($VERTICALS as $code => $label): ?>
                                    <option value="<?= $code ?>"><?= $code ?> — <?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Goal Code</label>
                            <select class="form-select" name="goal_code" id="cGoalCode">
                                <option value="">-- Select --</option>
                                <?php foreach ($GOAL_CODES as $code => $label): ?>
                                    <option value="<?= $code ?>"><?= $code ?> — <?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Descriptor</label>
                            <input type="text" class="form-control" name="descriptor" id="cDescriptor" style="text-transform:uppercase;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Type</label>
                            <select class="form-select" name="campaign_type" id="cType">
                                <option value="">-- Select --</option>
                                <?php foreach ($CAMPAIGN_TYPES as $t): ?>
                                    <option value="<?= $t ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Campaign Goal</label>
                            <input type="text" class="form-control" name="campaign_goal" id="cGoal">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Geography</label>
                            <select class="form-select" name="geography" id="cGeo">
                                <option value="">-- Select --</option>
                                <?php foreach ($GEOGRAPHIES as $g): ?>
                                    <option value="<?= $g ?>"><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Audience</label>
                            <input type="text" class="form-control" name="target_audience" id="cAudience">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign Start</label>
                            <input type="date" class="form-control" name="campaign_start" id="cStart">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campaign End</label>
                            <input type="date" class="form-control" name="campaign_end" id="cEnd">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Go-Live Date</label>
                            <input type="date" class="form-control" name="go_live_date" id="cGoLive">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" name="priority" id="cPriority">
                                <?php foreach ($PRIORITIES as $p): ?>
                                    <option value="<?= $p ?>"><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Assigned To <span class="text-danger">*</span></label>
                            <select class="form-select" name="campaign_owner" id="cOwner" required>
                                <option value="">-- Select User --</option>
                                <?php $activeUsers->data_seek(0); while ($u = $activeUsers->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="campaign_status" id="cStatus">
                                <?php foreach ($CAMPAIGN_STATUSES as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Brief</label>
                            <textarea class="form-control" name="notes" id="cNotes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== Asset / Requirement Modal ========== -->
<div class="modal fade" id="assetModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetModalTitle">Add Requirement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assetForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="aId">
                    <input type="hidden" name="asset_type" id="aType">
                    <input type="hidden" name="campaign_ref" value="<?= $id ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Requirement Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="asset_name" id="aName" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Assign To <span class="text-danger">*</span></label>
                            <select class="form-select" name="owner_id" id="aOwner" required>
                                <option value="">-- Select User --</option>
                                <?php $activeUsers->data_seek(0); while ($u = $activeUsers->fetch_assoc()): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Support</label>
                            <select class="form-select" name="support_id" id="aSupport">
                                <option value="">-- None --</option>
                                <?php $activeUsers->data_seek(0); while ($u = $activeUsers->fetch_assoc()): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Brief Date</label>
                            <input type="date" class="form-control" name="brief_date" id="aBriefDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Due Date</label>
                            <input type="date" class="form-control" name="due_date" id="aDueDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" name="priority" id="aPriority">
                                <?php foreach ($PRIORITIES as $p): ?>
                                    <option value="<?= $p ?>" <?= $p==='Medium'?'selected':'' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="aStatus">
                                <?php foreach ($ASSET_STATUSES as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="extraFields" class="col-12">
                            <div class="row g-3" id="extraFieldsInner"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="assetSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.nav-tabs .nav-link { font-size:.82rem; padding:8px 14px; }
.nav-tabs .nav-link.active { font-weight:600; }
</style>

<script>
const ASSET_TYPES_JS = <?= json_encode(array_map(fn($v)=>['label'=>$v['label'],'extra'=>$v['extra'] ?? []], $ASSET_TYPES)) ?>;
const LEAD_TIMES = <?= json_encode($leadTimes) ?>;
const GO_LIVE    = '<?= $campaign['go_live_date'] ?? '' ?>';
const CAMPAIGN_ID = <?= $id ?>;
const ACTIVE_USERS_JS = <?php
    $activeUsers->data_seek(0);
    $ul = [];
    while ($u = $activeUsers->fetch_assoc()) $ul[] = ['id'=>$u['id'],'name'=>$u['name']];
    echo json_encode($ul);
?>;

function showTab(type, btn) {
    document.querySelectorAll('.tab-pane-custom').forEach(el => el.style.display = 'none');
    document.querySelectorAll('#reqTabs .nav-link').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + type).style.display = '';
    btn.classList.add('active');
}

function openAddRequirement() {
    const type = document.getElementById('newReqType').value;
    document.getElementById('assetModalTitle').textContent = 'Add ' + ASSET_TYPES_JS[type].label + ' Requirement';
    document.getElementById('assetForm').reset();
    document.getElementById('aId').value = '';
    document.getElementById('aType').value = type;
    // Auto-calculate due date from lead times
    if (GO_LIVE && LEAD_TIMES[type]) {
        const d = new Date(GO_LIVE + 'T00:00:00');
        d.setDate(d.getDate() - LEAD_TIMES[type]);
        document.getElementById('aDueDate').value = d.toISOString().split('T')[0];
    }
    buildExtraFields(type, {});
    new bootstrap.Modal(document.getElementById('assetModal')).show();
}

function editAsset(a) {
    document.getElementById('assetModalTitle').textContent = 'Edit Requirement';
    document.getElementById('aId').value         = a.id;
    document.getElementById('aType').value        = a.asset_type;
    document.getElementById('aName').value        = a.asset_name;
    document.getElementById('aOwner').value       = a.owner_id || '';
    document.getElementById('aSupport').value     = a.support_id || '';
    document.getElementById('aBriefDate').value   = a.brief_date || '';
    document.getElementById('aDueDate').value     = a.due_date || '';
    document.getElementById('aPriority').value    = a.priority || 'Medium';
    document.getElementById('aStatus').value      = a.status || 'Briefed';
    const extra = a.extra_data ? JSON.parse(a.extra_data) : {};
    buildExtraFields(a.asset_type, extra);
    new bootstrap.Modal(document.getElementById('assetModal')).show();
}

function buildExtraFields(type, vals) {
    const cfg = ASSET_TYPES_JS[type] || {};
    const extra = cfg.extra || {};
    let html = '';
    for (const [k, f] of Object.entries(extra)) {
        const val = vals[k] || '';
        html += '<div class="col-md-4"><label class="form-label fw-semibold">' + f.label + '</label>';
        if (f.textarea) {
            html += '<textarea class="form-control form-control-sm" name="extra[' + k + ']" rows="2">' + esc(val) + '</textarea>';
        } else if (f.options) {
            html += '<select class="form-select form-select-sm" name="extra[' + k + ']"><option value="">-- Select --</option>';
            f.options.forEach(o => { html += '<option ' + (val===o?'selected':'') + '>' + esc(o) + '</option>'; });
            html += '</select>';
        } else if (f.date) {
            html += '<input type="date" class="form-control form-control-sm" name="extra[' + k + ']" value="' + esc(val) + '">';
        } else if (f.number) {
            html += '<input type="number" class="form-control form-control-sm" name="extra[' + k + ']" value="' + esc(val) + '">';
        } else {
            html += '<input type="text" class="form-control form-control-sm" name="extra[' + k + ']" value="' + esc(val) + '">';
        }
        html += '</div>';
    }
    document.getElementById('extraFieldsInner').innerHTML = html;
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

document.getElementById('assetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('assetSaveBtn');
    btn.disabled = true;
    fetch('api/asset_crud.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            if (res.success) location.reload();
            else alert(res.message || 'Error saving requirement.');
        })
        .catch(() => alert('Network error.'))
        .finally(() => btn.disabled = false);
});

function editCampaign(c) {
    document.getElementById('cId').value        = c.id;
    document.getElementById('cName').value       = c.campaign_name;
    document.getElementById('cVertical').value   = c.vertical || '';
    document.getElementById('cGoalCode').value   = c.goal_code || '';
    document.getElementById('cDescriptor').value = c.descriptor || '';
    document.getElementById('cType').value        = c.campaign_type || '';
    document.getElementById('cGoal').value        = c.campaign_goal || '';
    document.getElementById('cGeo').value         = c.geography || '';
    document.getElementById('cAudience').value    = c.target_audience || '';
    document.getElementById('cStart').value       = c.campaign_start || '';
    document.getElementById('cEnd').value         = c.campaign_end || '';
    document.getElementById('cGoLive').value      = c.go_live_date || '';
    document.getElementById('cPriority').value    = c.priority || 'Medium';
    document.getElementById('cOwner').value       = c.campaign_owner || '';
    document.getElementById('cStatus').value      = c.campaign_status || 'Planning';
    document.getElementById('cNotes').value       = c.notes || '';
    new bootstrap.Modal(document.getElementById('campaignModal')).show();
}

function submitForApproval() {
    if (!confirm('Submit this campaign for approval?')) return;
    const fd = new FormData();
    fd.append('action', 'submit');
    fd.append('campaign_id', CAMPAIGN_ID);
    fetch('api/approval_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error submitting for approval.'); });
}

function doApprovalAction(action) {
    const comment = document.getElementById('approvalComment').value.trim();
    if (action === 'reject' && !comment) { alert('Please add a comment explaining the rejection.'); return; }
    if (!confirm(action === 'approve' ? 'Approve this stage?' : 'Reject this campaign?')) return;
    const fd = new FormData();
    fd.append('action', action);
    fd.append('campaign_id', CAMPAIGN_ID);
    fd.append('comment', comment);
    fetch('api/approval_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
}

document.getElementById('campaignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/campaign_crud.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
});
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

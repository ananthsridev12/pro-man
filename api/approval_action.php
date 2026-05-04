<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db   = getDB();
$uid  = (int)$_SESSION['user_id'];
$role = $db->real_escape_string($_SESSION['user_role'] ?? '');

$action = $_POST['action'] ?? '';
$campId = (int)($_POST['campaign_id'] ?? 0);

if (!$campId) { echo json_encode(['success'=>false,'message'=>'Campaign required']); exit; }

$campaign = $db->query("SELECT * FROM campaigns WHERE id=$campId")->fetch_assoc();
if (!$campaign) { echo json_encode(['success'=>false,'message'=>'Campaign not found']); exit; }

// ── SUBMIT ────────────────────────────────────────────────────────────────────
if ($action === 'submit') {
    $pipeline = $db->query("SELECT * FROM approval_pipelines WHERE is_default=1 AND is_active=1 LIMIT 1")->fetch_assoc();
    if (!$pipeline) {
        echo json_encode(['success'=>false,'message'=>'No active default approval pipeline configured. Ask your admin to set one up in Settings → Approval Pipeline.']);
        exit;
    }
    $pid = $pipeline['id'];
    $stagesRes = $db->query("SELECT * FROM approval_stages WHERE pipeline_id=$pid ORDER BY stage_order ASC");
    if (!$stagesRes || $stagesRes->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Pipeline has no stages configured.']); exit;
    }
    $allStages = [];
    while ($s = $stagesRes->fetch_assoc()) $allStages[] = $s;

    // Supersede any existing pending instance
    $db->query("UPDATE approval_instances SET status='superseded' WHERE entity_type='campaign' AND entity_id=$campId AND status='pending'");

    // Create instance
    $db->query("INSERT INTO approval_instances (pipeline_id, entity_type, entity_id, current_stage_order, status, initiated_by)
        VALUES ($pid,'campaign',$campId,1,'pending',$uid)");
    $instanceId = $db->insert_id;

    // Create pending action rows for all stages
    foreach ($allStages as $s) {
        $db->query("INSERT INTO approval_actions (instance_id, stage_id, action) VALUES ($instanceId,{$s['id']},'pending')");
    }

    // Update campaign
    $db->query("UPDATE campaigns SET approval_status='Submitted' WHERE id=$campId");

    // Notify stage-1 approvers
    $stage1  = $allStages[0];
    $cname   = $db->real_escape_string($campaign['campaign_name']);
    $stName1 = $db->real_escape_string($stage1['stage_name']);
    $msg     = "Campaign \"$cname\" requires your approval at stage: $stName1";
    notifyApprovers($db, $stage1, $campId, 'approval_needed', "Approval Required: $cname", $msg);

    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

// ── APPROVE / REJECT ──────────────────────────────────────────────────────────
if ($action === 'approve' || $action === 'reject') {
    $inst = $db->query("SELECT * FROM approval_instances WHERE entity_type='campaign' AND entity_id=$campId AND status='pending' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if (!$inst) { echo json_encode(['success'=>false,'message'=>'No active approval instance found']); exit; }

    $iid      = $inst['id'];
    $pid      = $inst['pipeline_id'];
    $curOrder = (int)$inst['current_stage_order'];

    $stage = $db->query("SELECT * FROM approval_stages WHERE pipeline_id=$pid AND stage_order=$curOrder LIMIT 1")->fetch_assoc();
    if (!$stage) { echo json_encode(['success'=>false,'message'=>'Stage not found']); exit; }

    // Authorisation check
    $auth = false;
    if ((int)$stage['approver_user_id'] === $uid) $auth = true;
    if (!$stage['approver_user_id'] && $stage['approver_role'] === $_SESSION['user_role']) $auth = true;
    if (!$auth) { echo json_encode(['success'=>false,'message'=>'You are not the designated approver for this stage']); exit; }

    $comment = $db->real_escape_string(trim($_POST['comment'] ?? ''));
    $now     = date('Y-m-d H:i:s');
    $cname   = $db->real_escape_string($campaign['campaign_name']);

    // Record action
    $db->query("UPDATE approval_actions
        SET action='$action', approver_user_id=$uid, comments='$comment', acted_at='$now'
        WHERE instance_id=$iid AND stage_id={$stage['id']}");

    if ($action === 'reject') {
        $db->query("UPDATE approval_instances SET status='rejected', completed_at='$now' WHERE id=$iid");
        $db->query("UPDATE campaigns SET approval_status='Rejected' WHERE id=$campId");
        // Notify initiator
        $initiator = (int)$inst['initiated_by'];
        if ($initiator) {
            $db->query("INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id)
                VALUES ($initiator,'approval_rejected','Campaign Rejected: $cname',
                    'Your campaign \"$cname\" was rejected. Check the approval history for details.','campaign',$campId)");
        }
    } else {
        // Check for next stage
        $nextStage = $db->query("SELECT * FROM approval_stages WHERE pipeline_id=$pid AND stage_order=" . ($curOrder+1) . " LIMIT 1")->fetch_assoc();
        if ($nextStage) {
            $newOrder   = $curOrder + 1;
            $totalStages = (int)$db->query("SELECT COUNT(*) c FROM approval_stages WHERE pipeline_id=$pid")->fetch_assoc()['c'];
            $db->query("UPDATE approval_instances SET current_stage_order=$newOrder WHERE id=$iid");
            $newStatus = $db->real_escape_string("In Review (Stage $newOrder of $totalStages)");
            $db->query("UPDATE campaigns SET approval_status='$newStatus' WHERE id=$campId");
            // Notify next approvers
            $stName = $db->real_escape_string($nextStage['stage_name']);
            $msg    = "Campaign \"$cname\" is pending your approval at stage: $stName";
            notifyApprovers($db, $nextStage, $campId, 'approval_needed', "Approval Required: $cname", $msg);
        } else {
            // All stages approved
            $db->query("UPDATE approval_instances SET status='approved', completed_at='$now' WHERE id=$iid");
            $db->query("UPDATE campaigns SET approval_status='Approved', campaign_status='Active' WHERE id=$campId");
            // Notify initiator
            $initiator = (int)$inst['initiated_by'];
            if ($initiator) {
                $db->query("INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id)
                    VALUES ($initiator,'approval_approved','Campaign Approved: $cname',
                        'Your campaign \"$cname\" has been fully approved and is now Active.','campaign',$campId)");
            }
        }
    }

    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

function notifyApprovers($db, $stage, $campId, $type, $title, $message) {
    $type_e    = $db->real_escape_string($type);
    $title_e   = $db->real_escape_string($title);
    $message_e = $db->real_escape_string($message);
    if ($stage['approver_user_id']) {
        $uid = (int)$stage['approver_user_id'];
        $db->query("INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id)
            VALUES ($uid,'$type_e','$title_e','$message_e','campaign',$campId)");
    } elseif ($stage['approver_role']) {
        $r    = $db->real_escape_string($stage['approver_role']);
        $res  = $db->query("SELECT id FROM users WHERE role='$r' AND status='active'");
        while ($u = $res->fetch_assoc()) {
            $uid = $u['id'];
            $db->query("INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id)
                VALUES ($uid,'$type_e','$title_e','$message_e','campaign',$campId)");
        }
    }
}

<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SESSION['user_role'] !== 'Admin') { echo json_encode(['success'=>false,'message'=>'Admin only']); exit; }
$db = getDB();

$action = $_POST['action'] ?? '';

if ($action === 'save_pipeline') {
    $pid  = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Pipeline name required']); exit; }
    $name_e  = $db->real_escape_string($name);
    $desc_e  = $db->real_escape_string(trim($_POST['description'] ?? ''));
    $isDef   = isset($_POST['is_default']) ? 1 : 0;
    $isAct   = isset($_POST['is_active'])  ? 1 : 0;

    if ($isDef) $db->query("UPDATE approval_pipelines SET is_default=0");

    if ($pid) {
        $db->query("UPDATE approval_pipelines SET name='$name_e', description='$desc_e', is_default=$isDef, is_active=$isAct WHERE id=$pid");
    } else {
        $db->query("INSERT INTO approval_pipelines (name, description, is_default, is_active) VALUES ('$name_e','$desc_e',$isDef,$isAct)");
        $pid = $db->insert_id;
    }
    echo json_encode(['success'=>true, 'id'=>$pid]);
    $db->close(); exit;
}

if ($action === 'delete_pipeline') {
    $pid = (int)($_POST['id'] ?? 0);
    $active = $db->query("SELECT COUNT(*) c FROM approval_instances WHERE pipeline_id=$pid AND status='pending'")->fetch_assoc()['c'];
    if ((int)$active > 0) { echo json_encode(['success'=>false,'message'=>'Pipeline has active approvals in progress.']); exit; }
    $db->query("DELETE FROM approval_pipelines WHERE id=$pid");
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

if ($action === 'save_stage') {
    $sid    = (int)($_POST['id'] ?? 0);
    $pid    = (int)($_POST['pipeline_id'] ?? 0);
    $sname  = trim($_POST['stage_name'] ?? '');
    $role   = trim($_POST['approver_role'] ?? '');
    $userId = (int)($_POST['approver_user_id'] ?? 0);
    if (!$sname || !$pid) { echo json_encode(['success'=>false,'message'=>'Stage name and pipeline required']); exit; }
    $sname_e  = $db->real_escape_string($sname);
    $role_sql = ($role && !$userId) ? "'" . $db->real_escape_string($role) . "'" : 'NULL';
    $user_sql = $userId ?: 'NULL';

    if ($sid) {
        $db->query("UPDATE approval_stages SET stage_name='$sname_e', approver_role=$role_sql, approver_user_id=$user_sql WHERE id=$sid");
    } else {
        $row = $db->query("SELECT COALESCE(MAX(stage_order),0)+1 n FROM approval_stages WHERE pipeline_id=$pid")->fetch_assoc();
        $order = (int)$row['n'];
        $db->query("INSERT INTO approval_stages (pipeline_id, stage_order, stage_name, approver_role, approver_user_id)
            VALUES ($pid,$order,'$sname_e',$role_sql,$user_sql)");
        $sid = $db->insert_id;
    }
    echo json_encode(['success'=>true,'id'=>$sid]);
    $db->close(); exit;
}

if ($action === 'delete_stage') {
    $sid = (int)($_POST['id'] ?? 0);
    $pid = (int)($_POST['pipeline_id'] ?? 0);
    $db->query("DELETE FROM approval_stages WHERE id=$sid");
    $stages = $db->query("SELECT id FROM approval_stages WHERE pipeline_id=$pid ORDER BY stage_order ASC");
    $i = 1; while ($s = $stages->fetch_assoc()) { $db->query("UPDATE approval_stages SET stage_order=$i WHERE id={$s['id']}"); $i++; }
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

if ($action === 'move_stage') {
    $sid = (int)($_POST['id'] ?? 0);
    $dir = $_POST['direction'] ?? '';
    $row = $db->query("SELECT * FROM approval_stages WHERE id=$sid")->fetch_assoc();
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    $pid = $row['pipeline_id']; $cur = $row['stage_order'];
    $targetOrder = $dir === 'up' ? $cur - 1 : $cur + 1;
    $swap = $db->query("SELECT id FROM approval_stages WHERE pipeline_id=$pid AND stage_order=$targetOrder LIMIT 1")->fetch_assoc();
    if ($swap) {
        $db->query("UPDATE approval_stages SET stage_order=$targetOrder WHERE id=$sid");
        $db->query("UPDATE approval_stages SET stage_order=$cur WHERE id={$swap['id']}");
    }
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

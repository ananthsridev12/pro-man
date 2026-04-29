<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$uid     = (int)$_SESSION['user_id'];
$isAdmin = in_array($_SESSION['user_role'] ?? '', ['Admin','Project Head','Manager']);

if (($_POST['action'] ?? '') !== 'update_status') {
    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}

$assetId = (int)($_POST['asset_id'] ?? 0);
$status  = trim($_POST['status'] ?? '');

if (!$assetId || !$status) { echo json_encode(['success'=>false,'message'=>'Missing params']); exit; }

// Non-admins can only update their own assets
$ownerCheck = $isAdmin ? "" : " AND (owner_id=$uid OR created_by=$uid)";
$old = $db->query("SELECT status FROM assets WHERE id=$assetId$ownerCheck LIMIT 1")->fetch_assoc();
if (!$old) { echo json_encode(['success'=>false,'message'=>'Asset not found or permission denied']); exit; }

$status_e = $db->real_escape_string($status);
$db->query("UPDATE assets SET status='$status_e' WHERE id=$assetId");

// Log to activity_log
$ov = $db->real_escape_string($old['status']);
$db->query("INSERT INTO activity_log (entity_type, entity_id, user_id, action, old_value, new_value)
    VALUES ('asset',$assetId,$uid,'status changed','$ov','$status_e')");

echo json_encode(['success'=>true]);
$db->close();

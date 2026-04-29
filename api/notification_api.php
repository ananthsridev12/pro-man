<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'count') {
    $row = $db->query("SELECT COUNT(*) c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc();
    echo json_encode(['success'=>true, 'count'=>(int)($row['c'] ?? 0)]);
    $db->close(); exit;
}

if ($action === 'list') {
    $rows = $db->query("
        SELECT id, type, title, message, entity_type, entity_id, is_read, created_at
        FROM notifications WHERE user_id=$uid
        ORDER BY created_at DESC LIMIT 20
    ");
    $out = [];
    while ($r = $rows->fetch_assoc()) $out[] = $r;
    echo json_encode(['success'=>true, 'data'=>$out]);
    $db->close(); exit;
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db->query("UPDATE notifications SET is_read=1 WHERE id=$id AND user_id=$uid");
    } else {
        $db->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    }
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

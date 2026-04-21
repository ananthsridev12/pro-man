<?php
require_once '../config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$action = $_POST['action'] ?? '';

if ($action === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['To Do', 'In Progress', 'Done', 'Blocked'];
    if (!$id || !in_array($status, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Invalid data']); exit;
    }
    $completed = $status === 'Done' ? 'NOW()' : 'NULL';
    $s = $db->real_escape_string($status);
    $db->query("UPDATE tasks SET status='$s', completed_at=$completed WHERE id=$id");
    echo json_encode(['success' => true]);
    $db->close(); exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("DELETE FROM tasks WHERE id=$id AND created_by=" . (int)$_SESSION['user_id']);
    echo json_encode(['success' => $db->affected_rows > 0]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

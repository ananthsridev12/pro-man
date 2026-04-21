<?php
require_once '../config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $db->query("DELETE FROM users WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    $db->close(); exit;
}

$id   = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'Team Member');
$dept = trim($_POST['department'] ?? '');
$status = $_POST['status'] ?? 'active';

if (!$name) { echo json_encode(['success'=>false,'message'=>'Name is required']); exit; }

$name   = $db->real_escape_string($name);
$email  = $db->real_escape_string($email);
$role   = $db->real_escape_string($role);
$dept   = $db->real_escape_string($dept);
$status = $status === 'active' ? 'active' : 'inactive';

if ($id) {
    $db->query("UPDATE users SET name='$name', email='$email', role='$role', department='$dept', status='$status' WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows >= 0, 'id' => $id]);
} else {
    $db->query("INSERT INTO users (name, email, role, department, status) VALUES ('$name','$email','$role','$dept','$status')");
    echo json_encode(['success' => $db->insert_id > 0, 'id' => $db->insert_id]);
}

$db->close();

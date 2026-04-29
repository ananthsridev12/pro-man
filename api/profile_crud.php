<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'message'=>'Name is required']); exit; }
    $name_e  = $db->real_escape_string($name);
    $email_e = $db->real_escape_string($email);
    $db->query("UPDATE users SET name='$name_e', email='$email_e' WHERE id=$uid");
    if ($db->error) { echo json_encode(['success'=>false,'message'=>$db->error]); exit; }
    $_SESSION['user_name'] = $name;
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    if (!$current || !$new) { echo json_encode(['success'=>false,'message'=>'All fields are required']); exit; }
    if (strlen($new) < 6) { echo json_encode(['success'=>false,'message'=>'New password must be at least 6 characters']); exit; }
    $row = $db->query("SELECT password_hash FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
    if (!$row || !password_verify($current, $row['password_hash'] ?? '')) {
        echo json_encode(['success'=>false,'message'=>'Current password is incorrect']); exit;
    }
    $hash = $db->real_escape_string(password_hash($new, PASSWORD_DEFAULT));
    $db->query("UPDATE users SET password_hash='$hash' WHERE id=$uid");
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

<?php
require_once '../config.php';
header('Content-Type: application/json');
$db = getDB();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$e   = $db->real_escape_string($email);
$row = $db->query("SELECT id, name, email, role, department, status, password_hash FROM users WHERE email='$e' LIMIT 1")->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    $db->close(); exit;
}
if ($row['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Your account is inactive. Contact an administrator.']);
    $db->close(); exit;
}
if (!password_verify($password, $row['password_hash'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    $db->close(); exit;
}

// Set session
$_SESSION['user_id']   = $row['id'];
$_SESSION['user_name'] = $row['name'];
$_SESSION['user_role'] = $row['role'];
$_SESSION['user_email']= $row['email'];

// Update last_login
$db->query("UPDATE users SET last_login=NOW() WHERE id=" . (int)$row['id']);
$db->close();

echo json_encode(['success' => true, 'name' => $row['name'], 'role' => $row['role']]);

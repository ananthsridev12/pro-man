<?php
require_once '../config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $check = $db->query("SELECT COUNT(*) AS n FROM campaigns WHERE project_id=$id")->fetch_assoc();
    if ($check['n'] > 0) {
        echo json_encode(['success'=>false,'message'=>'Cannot delete — project has campaigns linked to it.']);
        $db->close(); exit;
    }
    $db->query("DELETE FROM projects WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    $db->close(); exit;
}

// Save (insert or update)
$id          = (int)($_POST['id'] ?? 0);
$code        = strtoupper(trim($_POST['code'] ?? ''));
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$color       = trim($_POST['color'] ?? '#3b82f6');
$owner_id    = (int)($_POST['owner_id'] ?? 0) ?: 'NULL';
$status      = in_array($_POST['status'] ?? '', ['active','archived']) ? $_POST['status'] : 'active';

if (!$code || !$name) {
    echo json_encode(['success'=>false,'message'=>'Code and Name are required.']);
    $db->close(); exit;
}

$code        = $db->real_escape_string($code);
$name        = $db->real_escape_string($name);
$description = $db->real_escape_string($description);
$color       = $db->real_escape_string($color);
$ownerSql    = $owner_id === 'NULL' ? 'NULL' : (int)$owner_id;

if ($id) {
    $db->query("UPDATE projects SET code='$code', name='$name', description='$description',
        color='$color', owner_id=$ownerSql, status='$status' WHERE id=$id");
    if ($db->error) { echo json_encode(['success'=>false,'message'=>$db->error]); $db->close(); exit; }
} else {
    $db->query("INSERT INTO projects (code, name, description, color, owner_id, status)
        VALUES ('$code','$name','$description','$color',$ownerSql,'$status')");
    if ($db->error) { echo json_encode(['success'=>false,'message'=>$db->error]); $db->close(); exit; }
}

echo json_encode(['success' => true]);
$db->close();

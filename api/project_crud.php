<?php
require_once '../config.php';
header('Content-Type: application/json');
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $db->query("DELETE FROM projects WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    exit;
}

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status      = $_POST['status'] ?? 'active';
$start_date  = $_POST['start_date'] ?? null;
$end_date    = $_POST['end_date'] ?? null;

if (!$name) { echo json_encode(['success'=>false,'message'=>'Project name is required']); exit; }

$validStatus = ['active','on_hold','completed','cancelled'];
if (!in_array($status, $validStatus)) $status = 'active';

$name        = $db->real_escape_string($name);
$description = $db->real_escape_string($description);
$start_date  = $start_date ? "'" . $db->real_escape_string($start_date) . "'" : 'NULL';
$end_date    = $end_date   ? "'" . $db->real_escape_string($end_date) . "'"   : 'NULL';

if ($id) {
    $db->query("UPDATE projects SET name='$name', description='$description', status='$status',
        start_date=$start_date, end_date=$end_date WHERE id=$id");
} else {
    $db->query("INSERT INTO projects (name, description, status, start_date, end_date)
        VALUES ('$name', '$description', '$status', $start_date, $end_date)");
}

echo json_encode(['success' => true, 'id' => $db->insert_id ?: $id]);
$db->close();

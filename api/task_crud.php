<?php
require_once '../config.php';
header('Content-Type: application/json');
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $db->query("DELETE FROM tasks WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    exit;
}

$id         = (int)($_POST['id'] ?? 0);
$task_name  = trim($_POST['task_name'] ?? '');
$project_id = (int)($_POST['project_id'] ?? 0);
$assignee   = trim($_POST['assignee'] ?? '');
$status     = $_POST['status'] ?? 'not_started';
$priority   = $_POST['priority'] ?? 'medium';
$start_date = $_POST['start_date'] ?? null;
$due_date   = $_POST['due_date'] ?? null;
$progress   = min(100, max(0, (int)($_POST['progress'] ?? 0)));
$notes      = trim($_POST['notes'] ?? '');

if (!$task_name) { echo json_encode(['success'=>false,'message'=>'Task name is required']); exit; }

$validStatus   = ['not_started','in_progress','completed','on_hold','cancelled'];
$validPriority = ['low','medium','high','critical'];
if (!in_array($status, $validStatus)) $status = 'not_started';
if (!in_array($priority, $validPriority)) $priority = 'medium';

$task_name  = $db->real_escape_string($task_name);
$assignee   = $db->real_escape_string($assignee);
$notes      = $db->real_escape_string($notes);
$pid        = $project_id ?: 'NULL';
$start_date = $start_date ? "'" . $db->real_escape_string($start_date) . "'" : 'NULL';
$due_date   = $due_date   ? "'" . $db->real_escape_string($due_date) . "'"   : 'NULL';

if ($id) {
    $db->query("UPDATE tasks SET task_name='$task_name', project_id=$pid, assignee='$assignee',
        status='$status', priority='$priority', start_date=$start_date, due_date=$due_date,
        progress=$progress, notes='$notes' WHERE id=$id");
} else {
    $db->query("INSERT INTO tasks (task_name, project_id, assignee, status, priority, start_date, due_date, progress, notes)
        VALUES ('$task_name', $pid, '$assignee', '$status', '$priority', $start_date, $due_date, $progress, '$notes')");
}

echo json_encode(['success' => true, 'id' => $db->insert_id ?: $id]);
$db->close();

<?php
require_once '../config.php';
header('Content-Type: application/json');
$db = getDB();

$tasksJson  = $_POST['tasks'] ?? '[]';
$project_id = (int)($_POST['project_id'] ?? 0);
$tasks      = json_decode($tasksJson, true);

if (!is_array($tasks) || empty($tasks)) {
    echo json_encode(['success'=>false,'message'=>'No tasks to import']);
    exit;
}

$validStatus   = ['not_started','in_progress','completed','on_hold','cancelled'];
$validPriority = ['low','medium','high','critical'];

$statusMap = [
    'not started'=>'not_started','todo'=>'not_started','new'=>'not_started','open'=>'not_started',
    'in progress'=>'in_progress','in-progress'=>'in_progress','doing'=>'in_progress','active'=>'in_progress','wip'=>'in_progress',
    'done'=>'completed','complete'=>'completed','finished'=>'completed','closed'=>'completed',
    'on hold'=>'on_hold','hold'=>'on_hold','pending'=>'on_hold','deferred'=>'on_hold',
    'cancelled'=>'cancelled','canceled'=>'cancelled','dropped'=>'cancelled'
];
$priorityMap = [
    'critical'=>'critical','urgent'=>'critical','p0'=>'critical',
    'high'=>'high','p1'=>'high',
    'medium'=>'medium','med'=>'medium','normal'=>'medium','p2'=>'medium',
    'low'=>'low','minor'=>'low','p3'=>'low'
];

$imported = 0;
$skipped  = 0;

foreach ($tasks as $t) {
    $task_name = trim($t['task_name'] ?? '');
    if (!$task_name) { $skipped++; continue; }

    $assignee   = $db->real_escape_string(trim($t['assignee'] ?? ''));
    $notes      = $db->real_escape_string(trim($t['notes'] ?? ''));
    $progress   = min(100, max(0, (int)($t['progress'] ?? 0)));

    $rawStatus   = strtolower(trim($t['status'] ?? ''));
    $status      = $statusMap[$rawStatus] ?? (in_array($rawStatus, $validStatus) ? $rawStatus : 'not_started');

    $rawPriority = strtolower(trim($t['priority'] ?? ''));
    $priority    = $priorityMap[$rawPriority] ?? (in_array($rawPriority, $validPriority) ? $rawPriority : 'medium');

    $start_date = sanitizeDate($t['start_date'] ?? '');
    $due_date   = sanitizeDate($t['due_date'] ?? '');

    $task_name = $db->real_escape_string($task_name);
    $pid       = $project_id ?: 'NULL';
    $sd        = $start_date ? "'$start_date'" : 'NULL';
    $dd        = $due_date   ? "'$due_date'"   : 'NULL';

    $db->query("INSERT INTO tasks (task_name, project_id, assignee, status, priority, start_date, due_date, progress, notes)
        VALUES ('$task_name', $pid, '$assignee', '$status', '$priority', $sd, $dd, $progress, '$notes')");

    if ($db->insert_id) $imported++;
    else $skipped++;
}

echo json_encode(['success'=>true, 'imported'=>$imported, 'skipped'=>$skipped]);
$db->close();

function sanitizeDate($val) {
    if (!$val) return null;
    $val = trim($val);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
    $ts = strtotime($val);
    if ($ts) return date('Y-m-d', $ts);
    return null;
}

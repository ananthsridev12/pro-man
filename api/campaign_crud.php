<?php
require_once '../config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $db->query("DELETE FROM campaigns WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    $db->close(); exit;
}

$id   = (int)($_POST['id'] ?? 0);
$name = trim($_POST['campaign_name'] ?? '');
if (!$name) { echo json_encode(['success'=>false,'message'=>'Campaign name is required']); exit; }

$fields = ['vertical','goal_code','campaign_goal','target_audience','geography','campaign_type',
           'campaign_start','campaign_end','go_live_date','priority','campaign_owner',
           'campaign_status','notes','descriptor'];

$sets = ["campaign_name='" . $db->real_escape_string($name) . "'"];
foreach ($fields as $f) {
    $val = trim($_POST[$f] ?? '');
    if (in_array($f, ['campaign_start','campaign_end','go_live_date'])) {
        $sets[] = "$f=" . ($val ? "'" . $db->real_escape_string($val) . "'" : 'NULL');
    } else {
        $sets[] = "$f='" . $db->real_escape_string($val) . "'";
    }
}

if ($id) {
    $db->query("UPDATE campaigns SET " . implode(',', $sets) . " WHERE id=$id");
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    // Build Campaign ID: [VERTICAL]-[GOAL_CODE]-[DESCRIPTOR]-[MON][YY]
    $vertical   = strtoupper(trim($_POST['vertical']   ?? 'XX'));
    $goalCode   = strtoupper(trim($_POST['goal_code']  ?? 'LG'));
    $raw        = strtoupper(str_replace(' ', '-', trim($_POST['descriptor'] ?? '')));
    $descriptor = preg_replace('/[^A-Z0-9\-]/', '', $raw);
    if (!$descriptor) $descriptor = 'CAMP';
    $monYY = strtoupper(date('MY')); // e.g. APR26

    $baseId    = $vertical . '-' . $goalCode . '-' . $descriptor . '-' . $monYY;
    $candidate = $db->real_escape_string($baseId);

    // Ensure uniqueness — append -2, -3 etc. if collision
    $suffix = 2;
    while ($db->query("SELECT id FROM campaigns WHERE campaign_id='$candidate' LIMIT 1")->num_rows > 0) {
        $candidate = $db->real_escape_string($baseId . '-' . $suffix);
        $suffix++;
    }

    $sets[] = "campaign_id='$candidate'";
    $db->query("INSERT INTO campaigns SET " . implode(',', $sets));
    echo json_encode(['success' => true, 'id' => $db->insert_id, 'campaign_id' => $candidate]);
}

$db->close();

<?php
require_once '../config.php';
header('Content-Type: application/json');
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $db->query("DELETE FROM campaigns WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    exit;
}

$id            = (int)($_POST['id'] ?? 0);
$name          = trim($_POST['campaign_name'] ?? '');
if (!$name) { echo json_encode(['success'=>false,'message'=>'Campaign name is required']); exit; }

$fields = ['vertical','goal_code','campaign_goal','target_audience','geography','campaign_type',
           'campaign_start','campaign_end','go_live_date','priority','campaign_owner',
           'campaign_status','approved_project_head','approved_manager','notes'];

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
    // Auto-generate campaign_id: CAMP-001, CAMP-002 ...
    $last = $db->query("SELECT campaign_id FROM campaigns ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $num  = 1;
    if ($last && preg_match('/CAMP-(\d+)/', $last['campaign_id'] ?? '', $m)) $num = (int)$m[1] + 1;
    $campId = 'CAMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    $sets[] = "campaign_id='" . $db->real_escape_string($campId) . "'";
    $db->query("INSERT INTO campaigns SET " . implode(',', $sets));
    echo json_encode(['success' => true, 'id' => $db->insert_id, 'campaign_id' => $campId]);
}

$db->close();

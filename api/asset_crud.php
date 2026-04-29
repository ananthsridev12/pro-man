<?php
require_once '../config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $db->query("DELETE FROM assets WHERE id=$id");
    echo json_encode(['success' => $db->affected_rows > 0]);
    $db->close(); exit;
}

$id         = (int)($_POST['id'] ?? 0);
$asset_type = $_POST['asset_type'] ?? '';
$asset_name = trim($_POST['asset_name'] ?? '');

if (!$asset_name) { echo json_encode(['success'=>false,'message'=>'Asset name is required']); $db->close(); exit; }
if (!array_key_exists($asset_type, $ASSET_TYPES)) { echo json_encode(['success'=>false,'message'=>'Invalid asset type']); $db->close(); exit; }

$campaign_ref = (int)($_POST['campaign_ref'] ?? 0) ?: 'NULL';
$vertical     = $db->real_escape_string(trim($_POST['vertical'] ?? ''));
$owner_id     = (int)($_POST['owner_id'] ?? 0);
$support_id   = (int)($_POST['support_id'] ?? 0);
$requested_by = $db->real_escape_string(trim($_POST['requested_by'] ?? ''));
$priority     = $db->real_escape_string($_POST['priority'] ?? 'Medium');
$status       = $db->real_escape_string($_POST['status'] ?? 'Briefed');
$appr_ph      = $db->real_escape_string($_POST['approved_project_head'] ?? 'Pending');
$appr_mgr     = $db->real_escape_string($_POST['approved_manager'] ?? 'Pending');
$revision_no  = (int)($_POST['revision_no'] ?? 0);
$final_url    = $db->real_escape_string(trim($_POST['final_file_url'] ?? ''));
$feedback     = $db->real_escape_string(trim($_POST['feedback_notes'] ?? ''));
$asset_name_e = $db->real_escape_string($asset_name);
$asset_type_e = $db->real_escape_string($asset_type);
$owner_id_sql   = $owner_id   ?: 'NULL';
$support_id_sql = $support_id ?: 'NULL';

// Resolve names from IDs
$owner_name   = '';
$support_name = '';
if ($owner_id) {
    $r = $db->query("SELECT name FROM users WHERE id=$owner_id LIMIT 1")->fetch_assoc();
    $owner_name = $db->real_escape_string($r['name'] ?? '');
}
if ($support_id) {
    $r = $db->query("SELECT name FROM users WHERE id=$support_id LIMIT 1")->fetch_assoc();
    $support_name = $db->real_escape_string($r['name'] ?? '');
}

$brief_date = ($_POST['brief_date'] ?? '') ? "'" . $db->real_escape_string($_POST['brief_date']) . "'" : 'NULL';
$due_date   = ($_POST['due_date']   ?? '') ? "'" . $db->real_escape_string($_POST['due_date'])   . "'" : 'NULL';
$pub_date   = ($_POST['pub_date']   ?? '') ? "'" . $db->real_escape_string($_POST['pub_date'])   . "'" : 'NULL';

// Extra fields
$extraRaw = $_POST['extra'] ?? [];
$extra = [];
if (is_array($extraRaw)) {
    foreach ($extraRaw as $k => $v) {
        $extra[preg_replace('/[^a-z0-9_]/','',$k)] = trim($v);
    }
}
$extra_json = $db->real_escape_string(json_encode($extra));
$uid = (int)$_SESSION['user_id'];

if ($id) {
    $old = $db->query("SELECT status, approved_project_head, approved_manager, owner_id FROM assets WHERE id=$id")->fetch_assoc();

    $db->query("UPDATE assets SET
        asset_type='$asset_type_e', asset_name='$asset_name_e',
        campaign_ref=$campaign_ref, vertical='$vertical',
        owner='$owner_name', owner_id=$owner_id_sql,
        support='$support_name', support_id=$support_id_sql,
        requested_by='$requested_by',
        brief_date=$brief_date, due_date=$due_date, pub_date=$pub_date,
        priority='$priority', status='$status',
        approved_project_head='$appr_ph', approved_manager='$appr_mgr',
        revision_no=$revision_no, final_file_url='$final_url',
        feedback_notes='$feedback', extra_data='$extra_json'
        WHERE id=$id");

    if ($db->error) { echo json_encode(['success'=>false,'message'=>$db->error]); $db->close(); exit; }

    // Activity log
    $changes = [
        'status'                => [$old['status'], $status],
        'approved_project_head' => [$old['approved_project_head'], $appr_ph],
        'approved_manager'      => [$old['approved_manager'], $appr_mgr],
        'owner_id'              => [$old['owner_id'], $owner_id ?: null],
    ];
    foreach ($changes as $field => [$oldVal, $newVal]) {
        if ((string)$oldVal !== (string)$newVal) {
            $f  = $db->real_escape_string($field);
            $ov = $db->real_escape_string($oldVal ?? '');
            $nv = $db->real_escape_string($newVal ?? '');
            $db->query("INSERT INTO activity_log (entity_type, entity_id, user_id, action, old_value, new_value)
                VALUES ('asset',$id,$uid,'$f changed','$ov','$nv')");
        }
    }

    syncTask($db, $id, $asset_name, $owner_id, $due_date, $priority, $uid);
    echo json_encode(['success' => true]);

} else {
    $cfg      = $ASSET_TYPES[$asset_type];
    $prefix   = $cfg['prefix'];
    $campCode = '';
    if ($campaign_ref !== 'NULL') {
        $row = $db->query("SELECT campaign_id FROM campaigns WHERE id=$campaign_ref")->fetch_assoc();
        $campCode = $row['campaign_id'] ?? '';
    }
    $baseId = ($campCode ? $campCode . '-' : '') . $prefix;
    $last   = $db->query("SELECT asset_id FROM assets WHERE asset_id LIKE '" . $db->real_escape_string($baseId) . "-%' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $num    = 1;
    if ($last && preg_match('/-(\d+)$/', $last['asset_id'] ?? '', $m)) $num = (int)$m[1] + 1;
    $assetId   = $baseId . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    $assetId_e = $db->real_escape_string($assetId);

    $db->query("INSERT INTO assets
        (asset_type, asset_name, asset_id, campaign_ref, vertical,
         owner, owner_id, support, support_id, requested_by,
         brief_date, due_date, pub_date, priority, status,
         approved_project_head, approved_manager, revision_no,
         final_file_url, feedback_notes, extra_data, created_by)
        VALUES
        ('$asset_type_e','$asset_name_e','$assetId_e',$campaign_ref,'$vertical',
         '$owner_name',$owner_id_sql,'$support_name',$support_id_sql,'$requested_by',
         $brief_date,$due_date,$pub_date,'$priority','$status',
         '$appr_ph','$appr_mgr',$revision_no,
         '$final_url','$feedback','$extra_json',$uid)");

    if ($db->error) { echo json_encode(['success'=>false,'message'=>$db->error]); $db->close(); exit; }

    $newId = $db->insert_id;
    syncTask($db, $newId, $asset_name, $owner_id, $due_date, $priority, $uid);
    echo json_encode(['success' => true, 'id' => $newId, 'asset_id' => $assetId]);
}

$db->close();

function syncTask($db, $asset_id, $title, $owner_id, $due_date, $priority, $created_by) {
    if (!$owner_id) return;
    $title_e    = $db->real_escape_string($title);
    $priority_e = $db->real_escape_string($priority);
    $due        = (is_string($due_date) && strlen($due_date) > 6) ? $due_date : 'NULL';

    $existing = $db->query("SELECT id, assigned_to FROM tasks WHERE asset_id=$asset_id LIMIT 1")->fetch_assoc();
    if ($existing) {
        if ((int)$existing['assigned_to'] !== $owner_id) {
            $db->query("UPDATE tasks SET assigned_to=$owner_id, title='$title_e',
                due_date=$due, priority='$priority_e' WHERE id={$existing['id']}");
        }
    } else {
        $db->query("INSERT INTO tasks (asset_id, title, assigned_to, created_by, due_date, priority, status)
            VALUES ($asset_id,'$title_e',$owner_id,$created_by,$due,'$priority_e','To Do')");
    }
}

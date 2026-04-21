<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Fetch comments + activity for an asset
if ($action === 'fetch') {
    $id = (int)($_GET['id'] ?? 0);
    $comments = $db->query("
        SELECT c.id, c.comment_text, c.created_at, u.name AS user_name
        FROM comments c
        LEFT JOIN users u ON u.id = c.user_id
        WHERE c.entity_type='asset' AND c.entity_id=$id
        ORDER BY c.created_at ASC
    ");
    $activity = $db->query("
        SELECT a.action, a.old_value, a.new_value, a.created_at, u.name AS user_name
        FROM activity_log a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.entity_type='asset' AND a.entity_id=$id
        ORDER BY a.created_at DESC
        LIMIT 30
    ");
    $out = ['comments'=>[], 'activity'=>[]];
    while ($r = $comments->fetch_assoc()) $out['comments'][] = $r;
    while ($r = $activity->fetch_assoc()) $out['activity'][] = $r;
    echo json_encode(['success'=>true, 'data'=>$out]);
    $db->close(); exit;
}

// Add comment
if ($action === 'add') {
    $id   = (int)($_POST['entity_id'] ?? 0);
    $text = trim($_POST['comment_text'] ?? '');
    if (!$id || !$text) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }
    $uid  = (int)$_SESSION['user_id'];
    $t    = $db->real_escape_string($text);
    $db->query("INSERT INTO comments (entity_type, entity_id, user_id, comment_text)
        VALUES ('asset', $id, $uid, '$t')");
    echo json_encode(['success' => true, 'id' => $db->insert_id]);
    $db->close(); exit;
}

// Delete own comment
if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $uid = (int)$_SESSION['user_id'];
    $db->query("DELETE FROM comments WHERE id=$id AND user_id=$uid");
    echo json_encode(['success' => $db->affected_rows > 0]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

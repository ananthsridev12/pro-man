<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['success'=>true,'data'=>['campaigns'=>[],'assets'=>[],'tasks'=>[]]]); exit; }

$uid     = (int)$_SESSION['user_id'];
$isAdmin = in_array($_SESSION['user_role'] ?? '', ['Admin','Project Head','Manager']);
$qe = $db->real_escape_string($q);

// Campaigns
$campaigns = [];
$r = $db->query("
    SELECT id, campaign_id, campaign_name, campaign_status, campaign_owner, vertical
    FROM campaigns
    WHERE campaign_name LIKE '%$qe%' OR campaign_id LIKE '%$qe%'
    ORDER BY campaign_name LIMIT 8
");
while ($row = $r->fetch_assoc()) $campaigns[] = $row;

// Assets
$assetWhere = "archived=0 AND (asset_name LIKE '%$qe%' OR asset_id LIKE '%$qe%')";
if (!$isAdmin) $assetWhere .= " AND (owner_id=$uid OR created_by=$uid OR support_id=$uid)";
$assets = [];
$r = $db->query("
    SELECT a.id, a.asset_id, a.asset_name, a.asset_type, a.status, a.campaign_ref, c.campaign_name
    FROM assets a
    LEFT JOIN campaigns c ON c.id = a.campaign_ref
    WHERE $assetWhere
    ORDER BY a.asset_name LIMIT 10
");
while ($row = $r->fetch_assoc()) $assets[] = $row;

// Tasks
$taskWhere = "t.title LIKE '%$qe%'";
if (!$isAdmin) $taskWhere .= " AND t.assigned_to=$uid";
$tasks = [];
$r = $db->query("
    SELECT t.id, t.title, t.status, t.priority, t.due_date, a.asset_id AS a_code, a.asset_type
    FROM tasks t
    LEFT JOIN assets a ON a.id = t.asset_id
    WHERE $taskWhere
    ORDER BY t.title LIMIT 6
");
while ($row = $r->fetch_assoc()) $tasks[] = $row;

echo json_encode(['success'=>true,'data'=>['campaigns'=>$campaigns,'assets'=>$assets,'tasks'=>$tasks]]);
$db->close();

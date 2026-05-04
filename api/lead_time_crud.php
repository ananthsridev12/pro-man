<?php
ob_start();
require_once '../config.php';
ob_clean();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
if ($_SESSION['user_role'] !== 'Admin') { echo json_encode(['success'=>false,'message'=>'Admin only']); exit; }
$db = getDB();

if (($_POST['action'] ?? '') === 'save_all') {
    $data = $_POST['lead_times'] ?? [];
    foreach ($data as $type => $days) {
        $type_e = $db->real_escape_string($type);
        $days   = max(1, (int)$days);
        $db->query("INSERT INTO asset_lead_times (asset_type, days_before_golive)
            VALUES ('$type_e', $days)
            ON DUPLICATE KEY UPDATE days_before_golive=$days");
    }
    echo json_encode(['success'=>true]);
    $db->close(); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
$db->close();

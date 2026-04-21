<?php
require_once '../config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$db = getDB();

$payloadJson = $_POST['payload'] ?? '{}';
$payload = json_decode($payloadJson, true);
if (!$payload || empty($payload['sheets'])) {
    echo json_encode(['success'=>false,'message'=>'No data to import']); exit;
}

$defaultCampaignId = (int)($payload['defaultCampaignId'] ?? 0);
$results = [];
$campaignIdCache = [];  // campaign_id_text → db id

// Helper: sanitize date
function parseDate($val) {
    if (!$val || trim($val) === '') return null;
    $val = trim($val);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d', $ts) : null;
}

// Helper: normalize status
function normStatus($val) {
    $map = [
        'not started'=>'Not Started','new'=>'Not Started','todo'=>'Not Started','open'=>'Not Started',
        'briefed'=>'Briefed',
        'in progress'=>'In Progress','in-progress'=>'In Progress','wip'=>'In Progress','doing'=>'In Progress',
        'in review'=>'In Review','review'=>'In Review','under review'=>'In Review',
        'approved'=>'Approved','sign off'=>'Approved',
        'scheduled'=>'Scheduled',
        'live'=>'Live','published'=>'Live','done'=>'Live','complete'=>'Live','completed'=>'Live',
        'amends'=>'Amends','revision'=>'Amends',
        'on hold'=>'On Hold','hold'=>'On Hold','paused'=>'On Hold',
        'cancelled'=>'Cancelled','canceled'=>'Cancelled',
        'archived'=>'Archived',
    ];
    return $map[strtolower(trim($val))] ?? (trim($val) ?: 'Not Started');
}

function normPriority($val) {
    $map = ['critical'=>'Critical','urgent'=>'Critical','low'=>'Low','minor'=>'Low',
            'medium'=>'Medium','normal'=>'Medium','med'=>'Medium','high'=>'High'];
    return $map[strtolower(trim($val))] ?? (trim($val) ?: 'Medium');
}

function normApproval($val) {
    $v = strtolower(trim($val));
    if ($v === 'yes' || $v === 'y') return 'Yes';
    if ($v === 'no' || $v === 'n')  return 'No';
    return 'Pending';
}

// Get campaign DB id by campaign_id_text
function getCampaignRef($db, $campIdText, &$cache) {
    if (!$campIdText) return null;
    if (isset($cache[$campIdText])) return $cache[$campIdText];
    $esc = $db->real_escape_string($campIdText);
    $row = $db->query("SELECT id FROM campaigns WHERE campaign_id='$esc'")->fetch_assoc();
    $cache[$campIdText] = $row ? (int)$row['id'] : null;
    return $cache[$campIdText];
}

global $ASSET_TYPES;

foreach ($payload['sheets'] as $sheet) {
    $type    = $sheet['type'];
    $sName   = $sheet['name'];
    $rows    = $sheet['rows'];
    $imported = 0; $skipped = 0;

    if ($type === 'campaigns') {
        foreach ($rows as $row) {
            $name = trim($row['Campaign Name *'] ?? $row['Campaign Name'] ?? '');
            if (!$name) { $skipped++; continue; }

            $campIdText  = trim($row['Campaign ID *'] ?? $row['Campaign ID'] ?? '');
            $vertical    = trim($row['Vertical *'] ?? $row['Vertical'] ?? '');
            $goal_code   = trim($row['Goal Code'] ?? '');
            $camp_goal   = trim($row['Campaign Goal'] ?? '');
            $target_aud  = trim($row['Target Audience'] ?? '');
            $geography   = trim($row['Geography'] ?? '');
            $camp_type   = trim($row['Campaign Type'] ?? '');
            $start       = parseDate($row['Campaign Start *'] ?? $row['Campaign Start'] ?? '');
            $end         = parseDate($row['Campaign End *'] ?? $row['Campaign End'] ?? '');
            $go_live     = parseDate($row['Go-Live Date *'] ?? $row['Go-Live Date'] ?? '');
            $priority    = normPriority($row['Priority'] ?? 'Medium');
            $owner       = trim($row['Campaign Owner *'] ?? $row['Campaign Owner'] ?? '');
            $status      = trim($row['Campaign Status'] ?? 'Planning');
            $appr_ph     = normApproval($row['Approved by Project Head'] ?? '');
            $appr_mgr    = normApproval($row['Approved by Manager'] ?? '');
            $notes       = trim($row['Notes / Brief'] ?? '');

            // Skip if Campaign ID already exists
            if ($campIdText) {
                $esc = $db->real_escape_string($campIdText);
                $exists = $db->query("SELECT id FROM campaigns WHERE campaign_id='$esc'")->fetch_assoc();
                if ($exists) { $skipped++; continue; }
            } else {
                // Auto-generate
                $last = $db->query("SELECT campaign_id FROM campaigns ORDER BY id DESC LIMIT 1")->fetch_assoc();
                $num  = 1;
                if ($last && preg_match('/CAMP-(\d+)/', $last['campaign_id'] ?? '', $m)) $num = (int)$m[1] + 1;
                $campIdText = 'CAMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }

            $e = function($v) use ($db) { return $db->real_escape_string((string)$v); };
            $db->query("INSERT INTO campaigns
                (campaign_id, campaign_name, vertical, goal_code, campaign_goal, target_audience,
                 geography, campaign_type, campaign_start, campaign_end, go_live_date,
                 priority, campaign_owner, campaign_status, approved_project_head, approved_manager, notes)
                VALUES
                ('{$e($campIdText)}','{$e($name)}','{$e($vertical)}','{$e($goal_code)}','{$e($camp_goal)}',
                 '{$e($target_aud)}','{$e($geography)}','{$e($camp_type)}',
                 " . ($start ? "'$start'" : 'NULL') . "," . ($end ? "'$end'" : 'NULL') . "," . ($go_live ? "'$go_live'" : 'NULL') . ",
                 '{$e($priority)}','{$e($owner)}','{$e($status)}','{$e($appr_ph)}','{$e($appr_mgr)}','{$e($notes)}')");

            if ($db->insert_id) { $campaignIdCache[$campIdText] = $db->insert_id; $imported++; }
            else $skipped++;
        }
    } else {
        // Asset sheet
        if (!array_key_exists($type, $ASSET_TYPES)) { continue; }
        $cfg    = $ASSET_TYPES[$type];
        $prefix = $cfg['prefix'];

        // Build reverse map of extra field labels → keys
        $extraLabelMap = [];
        foreach ($cfg['extra'] as $k => $label) $extraLabelMap[strtolower($label)] = $k;

        foreach ($rows as $row) {
            $assetName = trim(
                $row['Asset Name / Title *'] ?? $row['Asset Name / Title'] ??
                $row['Page Name / Title *'] ?? $row['Page Name / Title'] ??
                $row['Content Title *'] ?? $row['Lead Magnet Name *'] ??
                $row['Sequence Name *'] ?? $row['Event Name *'] ??
                $row['SEO Task Name *'] ?? ''
            );
            if (!$assetName) { $skipped++; continue; }

            $campIdText  = trim($row['Campaign ID *'] ?? $row['Campaign ID'] ?? '');
            $campaignRef = getCampaignRef($db, $campIdText, $campaignIdCache) ?? $defaultCampaignId ?: 'NULL';
            $assetId     = trim($row['Asset ID'] ?? '');
            $vertical    = trim($row['Vertical *'] ?? $row['Vertical'] ?? '');
            $owner       = trim($row['Owner *'] ?? $row['Owner'] ?? '');
            $support     = trim($row['Support'] ?? '');
            $req_by      = trim($row['Requested By'] ?? '');
            $brief_date  = parseDate($row['Brief Date'] ?? '');
            $due_date    = parseDate($row['Due Date *'] ?? $row['Due Date'] ?? '');
            $pub_date    = parseDate($row['Pub / Post Date *'] ?? $row['Pub / Go-Live Date *'] ?? $row['Pub / Post Date'] ?? '');
            $priority    = normPriority($row['Priority'] ?? 'Medium');
            $status      = normStatus($row['Status'] ?? 'Not Started');
            $appr_ph     = normApproval($row['Approved by Project Head'] ?? '');
            $appr_mgr    = normApproval($row['Approved by Manager'] ?? '');
            $final_url   = trim($row['Final File / URL Link'] ?? $row['Final URL'] ?? '');
            $rev_no      = (int)($row['Revision #'] ?? 0);
            $feedback    = trim($row['Feedback / Notes'] ?? '');

            // Auto-generate asset_id if not present
            if (!$assetId) {
                $campCode = $campIdText ?: '';
                $baseId   = ($campCode ? $campCode . '-' : '') . $prefix;
                $last     = $db->query("SELECT asset_id FROM assets WHERE asset_type='{$db->real_escape_string($type)}' ORDER BY id DESC LIMIT 1")->fetch_assoc();
                $num      = 1;
                if ($last && preg_match('/-(\d+)$/', $last['asset_id'] ?? '', $m)) $num = (int)$m[1] + 1;
                $assetId  = $baseId . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }

            // Collect extra fields
            $extra = [];
            foreach ($row as $colName => $colVal) {
                $colLower = strtolower(trim($colName));
                if (isset($extraLabelMap[$colLower]) && trim($colVal) !== '') {
                    $extra[$extraLabelMap[$colLower]] = trim($colVal);
                }
            }

            $e = function($v) use ($db) { return $db->real_escape_string((string)$v); };
            $db->query("INSERT INTO assets
                (asset_type, asset_name, asset_id, campaign_ref, campaign_id_text, vertical,
                 owner, support, requested_by, brief_date, due_date, pub_date,
                 priority, status, approved_project_head, approved_manager,
                 revision_no, final_file_url, feedback_notes, extra_data)
                VALUES
                ('{$e($type)}','{$e($assetName)}','{$e($assetId)}',$campaignRef,'{$e($campIdText)}','{$e($vertical)}',
                 '{$e($owner)}','{$e($support)}','{$e($req_by)}',
                 " . ($brief_date ? "'$brief_date'" : 'NULL') . "," . ($due_date ? "'$due_date'" : 'NULL') . "," . ($pub_date ? "'$pub_date'" : 'NULL') . ",
                 '{$e($priority)}','{$e($status)}','{$e($appr_ph)}','{$e($appr_mgr)}',
                 $rev_no,'{$e($final_url)}','{$e($feedback)}','{$e(json_encode($extra))}')");

            if ($db->insert_id) $imported++; else $skipped++;
        }
    }

    $results[] = ['sheet' => $sName, 'imported' => $imported, 'skipped' => $skipped];
}

echo json_encode(['success' => true, 'results' => $results]);
$db->close();

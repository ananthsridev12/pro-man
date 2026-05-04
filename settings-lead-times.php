<?php
require_once 'config.php';
require_once 'includes/auth.php';
if ($_SESSION['user_role'] !== 'Admin') { header('Location: index.php'); exit; }
$pageTitle = 'Asset Lead Times';
$db = getDB();

$rows = $db->query("SELECT * FROM asset_lead_times ORDER BY id ASC");
$leadTimes = [];
if ($rows) while ($r = $rows->fetch_assoc()) $leadTimes[$r['asset_type']] = (int)$r['days_before_golive'];

include 'includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="fw-semibold mb-1"><i class="fa fa-clock me-2 text-primary"></i>Asset Lead Times</h6>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            Define how many days before campaign go-live each asset type must be ready.
            When you add a requirement to a campaign that has a go-live date set, the due date is auto-calculated.
        </p>
    </div>
    <div class="card-body p-0">
        <form id="leadTimeForm">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Asset Type</th>
                    <th style="width:190px;">Days Before Go-Live</th>
                    <th class="d-none d-md-table-cell">Example (go-live = today)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ASSET_TYPES as $key => $cfg):
                $days = $leadTimes[$key] ?? 7;
            ?>
            <tr>
                <td>
                    <span class="d-flex align-items-center gap-2">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?= $cfg['color'] ?>;flex-shrink:0;"></span>
                        <i class="fa <?= $cfg['icon'] ?> fa-fw" style="color:<?= $cfg['color'] ?>;"></i>
                        <span class="fw-semibold"><?= $cfg['label'] ?></span>
                    </span>
                </td>
                <td>
                    <div class="input-group input-group-sm" style="max-width:150px;">
                        <input type="number" class="form-control" name="lead_times[<?= $key ?>]"
                            value="<?= $days ?>" min="1" max="365"
                            data-type="<?= $key ?>" oninput="updateExample('<?= $key ?>')">
                        <span class="input-group-text">days</span>
                    </div>
                </td>
                <td class="d-none d-md-table-cell text-muted" id="ex_<?= $key ?>" style="font-size:.82rem;">
                    <?php
                    $ex = (new DateTime())->modify("-{$days} days")->format('d M Y');
                    echo "Due by <strong>$ex</strong>";
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </form>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <button class="btn btn-primary" id="saveBtn" onclick="saveAll()">
            <i class="fa fa-save me-1"></i> Save All
        </button>
    </div>
</div>

</div>
</div>

<script>
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function updateExample(type) {
    const inp = document.querySelector('[data-type="' + type + '"]');
    const days = parseInt(inp.value) || 7;
    const d = new Date();
    d.setDate(d.getDate() - days);
    const label = d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
    const el = document.getElementById('ex_' + type);
    if (el) el.innerHTML = 'Due by <strong>' + label + '</strong>';
}

function saveAll() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    const fd = new FormData(document.getElementById('leadTimeForm'));
    fd.append('action', 'save_all');
    fetch('api/lead_time_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                btn.textContent = 'Saved!';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-save me-1"></i> Save All';
                }, 1800);
            } else {
                alert(res.message || 'Error saving');
                btn.disabled = false;
            }
        })
        .catch(() => { alert('Network error'); btn.disabled = false; });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

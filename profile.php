<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = 'My Profile';
$db = getDB();
$uid  = (int)$_SESSION['user_id'];
$user = $db->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:var(--accent);color:#fff;
                    display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                </div>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                    <div style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars($user['role'] ?? '') ?> · <?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- Profile Info -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Profile Information</h6></div>
            <div class="card-body">
                <div id="profileAlert"></div>
                <form id="profileForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['role'] ?? '') ?>" disabled>
                        <div class="form-text">Role is managed by administrators.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Change Password</h6></div>
            <div class="card-body">
                <div id="passwordAlert"></div>
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" minlength="6" required>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Update Password</button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function showAlert(el, msg, type) {
    document.getElementById(el).innerHTML =
        `<div class="alert alert-${type} alert-dismissible py-2" role="alert">
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>`;
}

document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'update_profile');
    fetch('api/profile_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) showAlert('profileAlert', 'Profile updated successfully.', 'success');
            else showAlert('profileAlert', res.message || 'Error saving profile.', 'danger');
        })
        .catch(() => showAlert('profileAlert', 'Network error.', 'danger'));
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    if (fd.get('new_password') !== fd.get('confirm_password')) {
        showAlert('passwordAlert', 'New passwords do not match.', 'danger');
        return;
    }
    fd.append('action', 'change_password');
    fetch('api/profile_crud.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) { showAlert('passwordAlert', 'Password changed successfully.', 'success'); this.reset(); }
            else showAlert('passwordAlert', res.message || 'Error changing password.', 'danger');
        })
        .catch(() => showAlert('passwordAlert', 'Network error.', 'danger'));
});
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

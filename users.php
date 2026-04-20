<?php
require_once 'config.php';
$pageTitle = 'Users & Roles';
$db = getDB();

$USER_ROLES = ['Admin','Project Head','Manager','Campaign Manager','Designer',
               'Content Writer','Developer','SEO Specialist','Event Manager','Team Member'];
$DEPARTMENTS = ['Marketing','Design','Development','Content','SEO','Events','Management'];

$users = $db->query("SELECT * FROM users ORDER BY status ASC, name ASC");
include 'includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="fa fa-user-plus me-1"></i> Add User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.86rem;">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th class="hide-xs">Email</th>
                        <th>Role</th>
                        <th class="d-none d-md-table-cell">Department</th>
                        <th>Status</th>
                        <th style="min-width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($users->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        No users yet. Click "Add User" to get started.
                    </td></tr>
                <?php else: $i=1; while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:<?= '#' . substr(md5($u['name']),0,6) ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:600;flex-shrink:0;">
                                    <?= strtoupper(substr($u['name'],0,1)) ?>
                                </div>
                                <span class="fw-semibold"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                        </td>
                        <td class="hide-xs text-muted"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                        <td>
                            <span class="badge-pill" style="background:#e8f4fc;color:#1a5276;">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell text-muted"><?= htmlspecialchars($u['department'] ?? '-') ?></td>
                        <td>
                            <span class="badge-pill <?= $u['status']==='active'?'bg-success text-white':'bg-secondary text-white' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="uId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="uName" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="uStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" id="uEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" id="uRole">
                                <?php foreach ($USER_ROLES as $r): ?>
                                    <option value="<?= $r ?>"><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <select class="form-select" name="department" id="uDept">
                                <option value="">-- Select --</option>
                                <?php foreach ($DEPARTMENTS as $d): ?>
                                    <option value="<?= $d ?>"><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(u) {
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('uId').value     = u.id;
    document.getElementById('uName').value   = u.name;
    document.getElementById('uEmail').value  = u.email || '';
    document.getElementById('uRole').value   = u.role;
    document.getElementById('uDept').value   = u.department || '';
    document.getElementById('uStatus').value = u.status;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('userForm').reset();
    document.getElementById('uId').value = '';
    document.getElementById('userModalTitle').textContent = 'Add User';
});
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/user_crud.php', { method:'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => { if (res.success) location.reload(); else alert(res.message || 'Error'); });
});
function deleteUser(id, name) {
    if (!confirm('Delete user "' + name + '"?')) return;
    const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
    fetch('api/user_crud.php', { method:'POST', body:fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>

<?php include 'includes/footer.php'; $db->close(); ?>

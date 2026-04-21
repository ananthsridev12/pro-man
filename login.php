<?php
require_once 'config.php';
// Already logged in → redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}
$next = htmlspecialchars($_GET['next'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolidPro — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --accent: #3b82f6; }
        * { box-sizing: border-box; }
        body {
            background: #f3f4f6;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .login-wrap { width: 100%; max-width: 420px; padding: 20px; }
        .login-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 40px 36px;
        }
        .brand { text-align: center; margin-bottom: 28px; }
        .brand-icon {
            width: 52px; height: 52px; border-radius: 14px;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin: 0 auto 14px;
        }
        .brand h1 { font-size: 1.3rem; font-weight: 700; color: #111827; margin: 0; }
        .brand p  { font-size: .85rem; color: #6b7280; margin: 4px 0 0; }
        .form-label { font-size: .82rem; font-weight: 500; }
        .form-control {
            border-radius: 8px; border-color: #e5e7eb;
            padding: 10px 14px; font-size: .9rem;
        }
        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
        .btn-login {
            background: var(--accent); border: none; border-radius: 8px;
            width: 100%; padding: 11px; font-size: .95rem; font-weight: 600;
            color: #fff; cursor: pointer; transition: background .15s;
        }
        .btn-login:hover { background: #2563eb; }
        .btn-login:disabled { opacity: .7; cursor: not-allowed; }
        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
            border-radius: 8px; padding: 10px 14px; font-size: .85rem; margin-bottom: 16px;
        }
        .footer-note { text-align: center; margin-top: 20px; font-size: .78rem; color: #9ca3af; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="brand">
            <div class="brand-icon"><i class="fa fa-diagram-project"></i></div>
            <h1>SolidPro</h1>
            <p>Marketing Campaign Manager</p>
        </div>

        <div id="errMsg" class="alert-error" style="display:none;"></div>

        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" class="form-control" name="email" id="email"
                    placeholder="you@solidpro.in" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password"
                    placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login" id="loginBtn">Sign In</button>
        </form>

        <div class="footer-note">
            Default: <strong>admin@solidpro.in</strong> / <strong>admin123</strong><br>
            Change your password after first login.
        </div>
    </div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    const err = document.getElementById('errMsg');
    btn.disabled = true;
    btn.textContent = 'Signing in…';
    err.style.display = 'none';

    fetch('api/auth.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.href = '<?= $next ?>';
            } else {
                err.textContent = res.message;
                err.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Sign In';
            }
        })
        .catch(() => {
            err.textContent = 'Network error. Please try again.';
            err.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Sign In';
        });
});
</script>
</body>
</html>

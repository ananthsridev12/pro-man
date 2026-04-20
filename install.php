<?php
/**
 * SolidPro — One-time installer
 * Visit once to create DB tables and write config.php, then it self-deletes.
 */

$error   = '';
$success = false;
$step    = isset($_POST['step']) ? (int)$_POST['step'] : 1;

if ($step === 2) {
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = $_POST['db_pass'] ?? '';
    $dbname = trim($_POST['db_name'] ?? '');

    if (!$user || !$dbname) {
        $error = 'DB Username and DB Name are required.';
        $step  = 1;
    } else {
        $conn = @new mysqli($host, $user, $pass, $dbname);
        if ($conn->connect_error) {
            $error = 'Connection failed: ' . htmlspecialchars($conn->connect_error)
                . '<br><small>Make sure you created the database + user in cPanel → MySQL Databases first.</small>';
            $step = 1;
        } else {
            $conn->set_charset('utf8mb4');

            $queries = [
                "CREATE TABLE IF NOT EXISTS campaigns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    campaign_id VARCHAR(20) UNIQUE,
                    campaign_name VARCHAR(255) NOT NULL,
                    vertical VARCHAR(100),
                    goal_code VARCHAR(50),
                    campaign_goal TEXT,
                    target_audience TEXT,
                    geography VARCHAR(100),
                    campaign_type VARCHAR(100),
                    campaign_start DATE,
                    campaign_end DATE,
                    go_live_date DATE,
                    priority VARCHAR(20) DEFAULT 'Medium',
                    campaign_owner VARCHAR(150),
                    campaign_status VARCHAR(50) DEFAULT 'Planning',
                    approved_project_head VARCHAR(20) DEFAULT 'Pending',
                    approved_manager VARCHAR(20) DEFAULT 'Pending',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS assets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    campaign_ref INT,
                    campaign_id_text VARCHAR(20),
                    asset_id VARCHAR(50),
                    asset_type VARCHAR(30) NOT NULL,
                    vertical VARCHAR(100),
                    asset_name VARCHAR(255) NOT NULL,
                    owner VARCHAR(150),
                    support VARCHAR(150),
                    requested_by VARCHAR(150),
                    brief_date DATE,
                    due_date DATE,
                    pub_date DATE,
                    priority VARCHAR(20) DEFAULT 'Medium',
                    status VARCHAR(50) DEFAULT 'Not Started',
                    approved_project_head VARCHAR(20) DEFAULT 'Pending',
                    approved_manager VARCHAR(20) DEFAULT 'Pending',
                    final_file_url TEXT,
                    revision_no INT DEFAULT 0,
                    feedback_notes TEXT,
                    archived TINYINT DEFAULT 0,
                    extra_data TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (campaign_ref) REFERENCES campaigns(id) ON DELETE SET NULL
                )"
            ];

            $tableErrors = [];
            foreach ($queries as $q) {
                if (!$conn->query($q)) {
                    $tableErrors[] = $conn->error;
                }
            }
            $conn->close();

            if ($tableErrors) {
                $error = 'Table error: ' . implode('; ', $tableErrors);
                $step  = 1;
            } else {
                // Write clean config.php — just 4 defines + getDB + include asset_types
                $config = <<<PHP
<?php
define('DB_HOST', '{$host}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass}');
define('DB_NAME', '{$dbname}');

function getDB() {
    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (\$conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'DB connection failed: ' . \$conn->connect_error]));
    }
    \$conn->set_charset('utf8mb4');
    return \$conn;
}

require_once __DIR__ . '/asset_types.php';
PHP;
                file_put_contents(__DIR__ . '/config.php', $config);
                $success = true;
                @unlink(__FILE__);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolidPro — Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .wrap { max-width: 560px; margin: 60px auto; }
        .brand { font-size: 1.4rem; font-weight: 700; color: #1e2a3a; }
        .brand span { color: #2d9cdb; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="text-center mb-4">
        <div class="brand">Solid<span>Pro</span> Campaign Manager</div>
        <p class="text-muted mb-0">Database Installer</p>
    </div>

    <?php if ($success): ?>
    <div class="card shadow-sm text-center py-5">
        <div class="card-body">
            <div style="font-size:3rem;">✅</div>
            <h5 class="mt-3 fw-semibold">Installation Complete!</h5>
            <p class="text-muted">Tables created and <code>config.php</code> updated.<br>
            This file has been deleted for security.</p>
            <a href="index.php" class="btn btn-primary mt-2 px-4">Open SolidPro &rarr;</a>
        </div>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold">Database Configuration</h6>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="alert alert-info small mb-4">
                <strong>Before running:</strong><br>
                1. Go to <strong>cPanel → MySQL Databases</strong><br>
                2. Create a database — e.g. <code>de2shrnx_proman</code><br>
                3. Create a user and assign <strong>ALL PRIVILEGES</strong> to that database<br>
                4. Fill in the details below and click Install
            </div>

            <form method="POST">
                <input type="hidden" name="step" value="2">
                <div class="mb-3">
                    <label class="form-label fw-semibold">DB Host</label>
                    <input type="text" class="form-control" name="db_host"
                        value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">DB Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="db_name"
                        value="<?= htmlspecialchars($_POST['db_name'] ?? 'de2shrnx_proman') ?>" required>
                    <div class="form-text">cPanel prefixes DB names with your cPanel username.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">DB Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="db_user"
                        value="<?= htmlspecialchars($_POST['db_user'] ?? 'de2shrnx_') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">DB Password</label>
                    <input type="password" class="form-control" name="db_pass">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    Install Database &amp; Configure
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center text-muted small mt-3">
        SolidPro &bull; <code>pro.easi7.in</code>
    </p>
</div>
</body>
</html>

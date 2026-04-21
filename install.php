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
                "CREATE TABLE IF NOT EXISTS projects (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(10) UNIQUE NOT NULL,
                    name VARCHAR(150) NOT NULL,
                    description TEXT,
                    color VARCHAR(7) DEFAULT '#3b82f6',
                    icon VARCHAR(50) DEFAULT 'fa-folder',
                    owner_id INT DEFAULT NULL,
                    status VARCHAR(20) DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    email VARCHAR(200) UNIQUE,
                    password_hash VARCHAR(255),
                    role VARCHAR(50) DEFAULT 'Team Member',
                    department VARCHAR(100),
                    project_scope TEXT,
                    status VARCHAR(10) DEFAULT 'active',
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS campaigns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    project_id INT DEFAULT NULL,
                    campaign_id VARCHAR(100) UNIQUE,
                    campaign_name VARCHAR(255) NOT NULL,
                    vertical VARCHAR(20),
                    goal_code VARCHAR(10),
                    descriptor VARCHAR(100),
                    campaign_goal VARCHAR(100),
                    target_audience TEXT,
                    geography VARCHAR(100),
                    campaign_type VARCHAR(100),
                    campaign_start DATE,
                    campaign_end DATE,
                    go_live_date DATE,
                    priority VARCHAR(20) DEFAULT 'Medium',
                    campaign_owner VARCHAR(150),
                    campaign_status VARCHAR(50) DEFAULT 'Planning',
                    approved_project_head VARCHAR(30) DEFAULT 'Pending',
                    approved_manager VARCHAR(30) DEFAULT 'Pending',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS assets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    campaign_ref INT DEFAULT NULL,
                    project_id INT DEFAULT NULL,
                    campaign_id_text VARCHAR(100),
                    asset_id VARCHAR(100),
                    asset_type VARCHAR(50) NOT NULL,
                    vertical VARCHAR(100),
                    asset_name VARCHAR(255) NOT NULL,
                    owner VARCHAR(150),
                    owner_id INT DEFAULT NULL,
                    support VARCHAR(150),
                    requested_by VARCHAR(150),
                    brief_date DATE,
                    due_date DATE,
                    pub_date DATE,
                    priority VARCHAR(20) DEFAULT 'Medium',
                    status VARCHAR(50) DEFAULT 'Briefed',
                    approved_project_head VARCHAR(30) DEFAULT 'Pending',
                    approved_manager VARCHAR(30) DEFAULT 'Pending',
                    final_file_url TEXT,
                    revision_no INT DEFAULT 0,
                    feedback_notes TEXT,
                    archived TINYINT DEFAULT 0,
                    extra_data TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    asset_id INT DEFAULT NULL,
                    campaign_id INT DEFAULT NULL,
                    project_id INT DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    assigned_to INT DEFAULT NULL,
                    created_by INT DEFAULT NULL,
                    due_date DATE,
                    priority VARCHAR(20) DEFAULT 'Medium',
                    status VARCHAR(30) DEFAULT 'To Do',
                    completed_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    entity_type VARCHAR(20) NOT NULL,
                    entity_id INT NOT NULL,
                    user_id INT DEFAULT NULL,
                    comment_text TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    entity_type VARCHAR(20) NOT NULL,
                    entity_id INT NOT NULL,
                    user_id INT DEFAULT NULL,
                    action VARCHAR(80) NOT NULL,
                    old_value TEXT,
                    new_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    type VARCHAR(50),
                    title VARCHAR(255),
                    message TEXT,
                    entity_type VARCHAR(20),
                    entity_id INT,
                    is_read TINYINT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                // Default admin (password: admin123)
                "INSERT IGNORE INTO users (name, email, password_hash, role, status) VALUES
                    ('Admin', 'admin@solidpro.in',
                     '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
                     'Admin', 'active')",
                // Seed projects
                "INSERT IGNORE INTO projects (code, name, color) VALUES
                    ('DT','Digital Transformation','#3b82f6'),
                    ('IG','Industrial Goods','#10b981'),
                    ('SE','Structural Engineering','#f59e0b'),
                    ('MT','MedTech','#ef4444'),
                    ('EU','Energy & Utilities','#8b5cf6'),
                    ('SUS','Sustainability','#059669'),
                    ('BFSI','BFSI','#0891b2'),
                    ('PI','Product Innovation','#d97706'),
                    ('AI','SolidPro AI','#6366f1'),
                    ('XX','Other / Cross-Vertical','#6b7280')"
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

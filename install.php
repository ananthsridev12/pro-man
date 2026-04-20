<?php
/**
 * SolidPro — One-time installer
 * Visit this page once to set up the database, then it self-deletes.
 */

$error   = '';
$success = '';
$step    = isset($_POST['step']) ? (int)$_POST['step'] : 1;

// ── Step 2: Run installation ───────────────────────────────────────────────
if ($step === 2) {
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = $_POST['db_pass'] ?? '';
    $dbname = trim($_POST['db_name'] ?? '');

    if (!$user || !$dbname) {
        $error = 'DB Username and DB Name are required.';
        $step  = 1;
    } else {
        // Test connection (db must already exist on cPanel — created via cPanel > MySQL Databases)
        $conn = @new mysqli($host, $user, $pass, $dbname);
        if ($conn->connect_error) {
            $error = 'Connection failed: ' . $conn->connect_error
                . '<br><small>Make sure you created the database and user in cPanel → MySQL Databases first.</small>';
            $step = 1;
        } else {
            $conn->set_charset('utf8mb4');

            // Create tables
            $sql = "
            CREATE TABLE IF NOT EXISTS campaigns (
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
                priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
                campaign_owner VARCHAR(150),
                campaign_status VARCHAR(50) DEFAULT 'Planning',
                approved_project_head VARCHAR(20) DEFAULT 'Pending',
                approved_manager VARCHAR(20) DEFAULT 'Pending',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS assets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                campaign_ref INT,
                campaign_id_text VARCHAR(20),
                asset_id VARCHAR(50),
                asset_type ENUM('creative','landing_page','content_writing','lead_magnet','email_sequence','ad_copy','seo','webinar_event') NOT NULL,
                vertical VARCHAR(100),
                asset_name VARCHAR(255) NOT NULL,
                owner VARCHAR(150),
                support VARCHAR(150),
                requested_by VARCHAR(150),
                brief_date DATE,
                due_date DATE,
                pub_date DATE,
                priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
                status VARCHAR(50) DEFAULT 'Not Started',
                approved_project_head VARCHAR(20) DEFAULT 'Pending',
                approved_manager VARCHAR(20) DEFAULT 'Pending',
                final_file_url TEXT,
                revision_no INT DEFAULT 0,
                feedback_notes TEXT,
                archived TINYINT DEFAULT 0,
                extra_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (campaign_ref) REFERENCES campaigns(id) ON DELETE SET NULL
            );
            ";

            $tableErrors = [];
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
                if ($query && !$conn->query($query)) {
                    $tableErrors[] = $conn->error;
                }
            }

            if ($tableErrors) {
                $error = 'Table creation error: ' . implode(', ', $tableErrors);
                $step  = 1;
            } else {
                // Write config.php
                $configContent = '<?php' . "\n"
                    . 'define(\'DB_HOST\', \'' . addslashes($host) . '\');' . "\n"
                    . 'define(\'DB_USER\', \'' . addslashes($user) . '\');' . "\n"
                    . 'define(\'DB_PASS\', \'' . addslashes($pass) . '\');' . "\n"
                    . 'define(\'DB_NAME\', \'' . addslashes($dbname) . '\');' . "\n\n";

                // Append rest of config.php (the $ASSET_TYPES etc.)
                $existingConfig = file_get_contents(__DIR__ . '/config.php');
                // Remove the old define() lines
                $existingConfig = preg_replace("/^<\?php\s*/", '', $existingConfig);
                $existingConfig = preg_replace("/define\('DB_(HOST|USER|PASS|NAME)'.*?\n/", '', $existingConfig);
                $existingConfig = preg_replace("/^function getDB.*?\}\n/ms", '', $existingConfig);

                $configContent .= "function getDB() {\n"
                    . "    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n"
                    . "    if (\$conn->connect_error) {\n"
                    . "        die(json_encode(['success' => false, 'message' => 'DB connection failed: ' . \$conn->connect_error]));\n"
                    . "    }\n"
                    . "    \$conn->set_charset('utf8mb4');\n"
                    . "    return \$conn;\n"
                    . "}\n"
                    . $existingConfig;

                file_put_contents(__DIR__ . '/config.php', $configContent);

                $conn->close();
                $success = true;

                // Self-delete
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
        .installer-card { max-width: 560px; margin: 60px auto; }
        .brand { font-size: 1.4rem; font-weight: 700; color: #1e2a3a; }
        .brand span { color: #2d9cdb; }
    </style>
</head>
<body>
<div class="installer-card">
    <div class="text-center mb-4">
        <div class="brand">Solid<span>Pro</span> Campaign Manager</div>
        <div class="text-muted">Database Installer</div>
    </div>

    <?php if ($success): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fa fa-circle-check" style="font-size:3rem;color:#27ae60;"></i>
            <h5 class="mt-3 fw-semibold">Installation Complete!</h5>
            <p class="text-muted">Database tables created and <code>config.php</code> updated successfully.<br>
            This installer file has been deleted for security.</p>
            <a href="index.php" class="btn btn-primary mt-2">
                Open SolidPro &rarr;
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold">Step 1 — Database Configuration</h6>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="alert alert-info small">
                <strong>Before running this installer:</strong><br>
                1. Go to <strong>cPanel → MySQL Databases</strong><br>
                2. Create a new database (e.g. <code>de2shrnx_proman</code>)<br>
                3. Create a database user and assign <strong>ALL PRIVILEGES</strong><br>
                4. Then fill in the details below.
            </div>

            <form method="POST">
                <input type="hidden" name="step" value="2">
                <div class="mb-3">
                    <label class="form-label fw-semibold">DB Host</label>
                    <input type="text" class="form-control" name="db_host"
                        value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                    <div class="form-text">Usually <code>localhost</code> on cPanel.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">DB Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="db_name"
                        value="<?= htmlspecialchars($_POST['db_name'] ?? 'de2shrnx_proman') ?>" required
                        placeholder="de2shrnx_proman">
                    <div class="form-text">cPanel prefixes DB names with your account username.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">DB Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="db_user"
                        value="<?= htmlspecialchars($_POST['db_user'] ?? 'de2shrnx_') ?>" required
                        placeholder="de2shrnx_proman">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">DB Password</label>
                    <input type="password" class="form-control" name="db_pass"
                        value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    Install Database &amp; Configure
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-3 text-muted small">
        SolidPro Campaign Manager &bull; Hosting: <code>pro.easi7.in</code>
    </div>
</div>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>

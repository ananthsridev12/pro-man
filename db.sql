CREATE DATABASE IF NOT EXISTS pro_man CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pro_man;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3b82f6',
    icon VARCHAR(50) DEFAULT 'fa-folder',
    owner_id INT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
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
);

CREATE TABLE IF NOT EXISTS campaigns (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS assets (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_ref) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tasks (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL,
    entity_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL,
    entity_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    action VARCHAR(80) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    entity_type VARCHAR(20),
    entity_id INT,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default admin user (password: admin123)
INSERT IGNORE INTO users (name, email, password_hash, role, status)
VALUES ('Admin', 'admin@solidpro.in',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    'Admin', 'active');

-- Seed projects from default verticals
INSERT IGNORE INTO projects (code, name, color) VALUES
('DT',   'Digital Transformation',             '#3b82f6'),
('IG',   'Industrial Goods',                   '#10b981'),
('SE',   'Structural Engineering',             '#f59e0b'),
('MT',   'MedTech',                            '#ef4444'),
('EU',   'Energy & Utilities',                 '#8b5cf6'),
('SUS',  'Sustainability',                     '#059669'),
('BFSI', 'BFSI',                               '#0891b2'),
('PI',   'Product Innovation',                 '#d97706'),
('AI',   'SolidPro AI',                        '#6366f1'),
('XX',   'Other / Cross-Vertical',             '#6b7280');

-- Upgrade notes (run these if updating an existing installation):
-- ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) AFTER email;
-- ALTER TABLE users ADD COLUMN project_scope TEXT AFTER department;
-- ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
-- ALTER TABLE campaigns ADD COLUMN project_id INT DEFAULT NULL FIRST;
-- ALTER TABLE assets ADD COLUMN project_id INT DEFAULT NULL AFTER campaign_ref;
-- ALTER TABLE assets ADD COLUMN owner_id INT DEFAULT NULL AFTER owner;

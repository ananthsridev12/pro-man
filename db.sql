CREATE DATABASE IF NOT EXISTS pro_man CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pro_man;

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

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(200),
    role VARCHAR(50) DEFAULT 'Team Member',
    department VARCHAR(100),
    status ENUM('active','inactive') DEFAULT 'active',
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

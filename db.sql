CREATE DATABASE IF NOT EXISTS pro_man CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pro_man;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active','on_hold','completed','cancelled') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    task_name VARCHAR(255) NOT NULL,
    assignee VARCHAR(150),
    status ENUM('not_started','in_progress','completed','on_hold','cancelled') DEFAULT 'not_started',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    start_date DATE,
    due_date DATE,
    progress INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

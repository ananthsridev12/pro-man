<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ProMan - Project Manager' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e2a3a;
            --sidebar-active: #2d9cdb;
            --accent: #2d9cdb;
        }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: 240px; min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 100;
        }
        .sidebar .brand {
            padding: 20px 24px; font-size: 1.3rem; font-weight: 700;
            color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand span { color: var(--accent); }
        .sidebar nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 24px; color: rgba(255,255,255,0.7);
            text-decoration: none; font-size: 0.92rem; transition: all 0.2s;
        }
        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(45,156,219,0.15); color: #fff;
            border-left: 3px solid var(--accent);
        }
        .sidebar nav a i { width: 18px; }
        .main-content { margin-left: 240px; padding: 24px; }
        .topbar {
            background: #fff; border-radius: 10px; padding: 14px 24px;
            margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar h4 { margin: 0; font-weight: 600; color: #1e2a3a; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-radius: 10px; }
        .stat-card { border-radius: 12px; padding: 20px; color: #fff; }
        .badge-status { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; }
        .priority-high { background: #fde8e8; color: #c0392b; }
        .priority-medium { background: #fef3cd; color: #856404; }
        .priority-low { background: #d1e7dd; color: #0f5132; }
        .priority-critical { background: #f8d7da; color: #842029; }
        .status-not_started { background: #e2e8f0; color: #475569; }
        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-on_hold { background: #fef9c3; color: #854d0e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #1a7bbf; border-color: #1a7bbf; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <div class="brand"><i class="fa fa-diagram-project"></i> Pro<span>Man</span></div>
    <nav>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="fa fa-gauge"></i> Dashboard
        </a>
        <a href="projects.php" class="<?= $currentPage === 'projects.php' ? 'active' : '' ?>">
            <i class="fa fa-folder-open"></i> Projects
        </a>
        <a href="tasks.php" class="<?= $currentPage === 'tasks.php' ? 'active' : '' ?>">
            <i class="fa fa-list-check"></i> Tasks
        </a>
        <a href="upload.php" class="<?= $currentPage === 'upload.php' ? 'active' : '' ?>">
            <i class="fa fa-file-excel"></i> Import Excel
        </a>
    </nav>
</div>
<div class="main-content">
    <div class="topbar">
        <h4><?= $pageTitle ?? 'Dashboard' ?></h4>
        <div class="text-muted" style="font-size:0.85rem;">
            <i class="fa fa-calendar me-1"></i><?= date('d M Y') ?>
        </div>
    </div>

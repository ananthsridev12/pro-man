<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SolidPro — Campaign Manager') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 248px;
            --sidebar-bg: #111827;
            --sidebar-hover: rgba(255,255,255,.06);
            --sidebar-active-bg: rgba(59,130,246,.12);
            --sidebar-active-border: #3b82f6;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --body-bg: #f3f4f6;
            --card-bg: #ffffff;
            --card-radius: 12px;
            --card-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 12px rgba(0,0,0,.04);
            --text-primary: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--body-bg);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            margin: 0;
            color: var(--text-primary);
            font-size: .9rem;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            height: 100%;
            background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 1050;
            overflow-y: auto; overflow-x: hidden;
            transition: transform .26s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        .sidebar .brand {
            padding: 18px 20px 16px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .sidebar .brand-inner { display: flex; align-items: center; gap: 10px; }
        .sidebar .brand-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; color: #fff; flex-shrink: 0;
        }
        .sidebar .brand-text { font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: -.2px; }
        .sidebar .brand-text span { color: #93c5fd; }

        .sidebar-section {
            padding: 18px 16px 6px;
            font-size: .68rem; font-weight: 600;
            color: rgba(255,255,255,.28); text-transform: uppercase; letter-spacing: 1.2px;
        }
        .sidebar-divider { height: 1px; background: rgba(255,255,255,.07); margin: 8px 16px; }

        .sidebar nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 16px 9px 14px;
            color: rgba(255,255,255,.6);
            text-decoration: none; font-size: .85rem; font-weight: 500;
            transition: all .15s ease;
            border-left: 3px solid transparent;
            border-radius: 0 6px 6px 0;
            margin: 1px 8px 1px 0;
        }
        .sidebar nav a:hover  { color: rgba(255,255,255,.9); background: var(--sidebar-hover); }
        .sidebar nav a.active { color: #fff; background: var(--sidebar-active-bg); border-left-color: var(--sidebar-active-border); }
        .sidebar nav a .nav-icon { width: 18px; text-align: center; flex-shrink: 0; font-size: .85rem; }
        .sidebar nav a .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── Overlay ── */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 1040;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.show { display: block; }

        /* ── Main content ── */
        .main-content {
            margin-left: var(--sidebar-w);
            padding: 20px 24px 60px;
            min-height: 100vh;
            transition: margin-left .26s cubic-bezier(.4,0,.2,1);
        }

        /* ── Topbar ── */
        .topbar {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 0 20px;
            height: 60px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 12px; z-index: 100;
        }
        .topbar-left  { display: flex; align-items: center; gap: 12px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .topbar h5 { margin: 0; font-weight: 700; color: var(--text-primary); font-size: .98rem; }

        .btn-hamburger {
            display: none; background: none; border: none; padding: 6px 8px;
            color: var(--text-primary); font-size: 1.1rem; cursor: pointer;
            border-radius: 6px; transition: background .15s;
        }
        .btn-hamburger:hover { background: var(--body-bg); }

        .topbar-date { font-size: .78rem; color: var(--text-muted); white-space: nowrap; display: flex; align-items: center; gap: 5px; }

        .topbar-bell {
            width: 34px; height: 34px; border-radius: 8px;
            background: var(--body-bg); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); cursor: pointer; font-size: .9rem;
            transition: all .15s; position: relative;
        }
        .topbar-bell:hover { background: #e5e7eb; color: var(--text-primary); }
        .topbar-bell .bell-dot {
            width: 7px; height: 7px; border-radius: 50%; background: #ef4444;
            position: absolute; top: 5px; right: 5px; border: 2px solid var(--card-bg);
        }
        .topbar-user {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 4px;
            background: var(--body-bg); border: 1px solid var(--border);
            border-radius: 8px; cursor: pointer; font-size: .82rem;
            font-weight: 500; color: var(--text-primary); transition: all .15s;
        }
        .topbar-user:hover { background: #e5e7eb; }
        .user-avatar-sm {
            width: 26px; height: 26px; border-radius: 6px;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: .7rem; font-weight: 700; flex-shrink: 0;
        }

        /* ── Cards ── */
        .card { border: 1px solid var(--border); box-shadow: var(--card-shadow); border-radius: var(--card-radius); background: var(--card-bg); }
        .card-header {
            border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
            background: var(--card-bg) !important;
            border-bottom: 1px solid var(--border) !important;
            padding: 14px 18px !important;
        }
        .card-header h6 { font-size: .88rem; font-weight: 600; }

        /* ── Buttons ── */
        .btn-primary {
            background: var(--accent); border-color: var(--accent); border-radius: 8px; font-weight: 500;
            box-shadow: 0 1px 2px rgba(59,130,246,.2);
        }
        .btn-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        .btn-secondary { border-radius: 8px; font-weight: 500; }
        .btn-outline-primary { border-radius: 6px; }
        .btn-outline-danger  { border-radius: 6px; }
        .btn-sm { border-radius: 6px !important; font-size: .78rem !important; }

        /* ── Form controls ── */
        .form-control, .form-select {
            border-color: var(--border); border-radius: 8px; font-size: .875rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }
        .form-label { font-size: .8rem; margin-bottom: 5px; color: var(--text-primary); font-weight: 500; }

        /* ── Tables ── */
        .table { font-size: .875rem; }
        .table > tbody > tr > td { padding: 10px 14px; vertical-align: middle; border-color: #f3f4f6; }
        .table > thead > tr > th {
            padding: 10px 14px; font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .6px;
            color: var(--text-muted); background: #f9fafb;
            border-bottom: 1px solid var(--border);
        }
        .table > tbody > tr:hover > td { background: #f9fafb; }
        .table > tbody > tr.table-danger > td { background: #fef2f2 !important; }
        .sticky-top { position: sticky; top: 0; z-index: 2; }

        /* ── Badges ── */
        .badge-pill {
            font-size: .7rem; padding: 3px 8px; border-radius: 20px;
            font-weight: 600; display: inline-block; white-space: nowrap;
        }
        /* Priority */
        .pri-Low      { background:#dcfce7;color:#166534; }
        .pri-Medium   { background:#fef9c3;color:#854d0e; }
        .pri-High     { background:#ffedd5;color:#9a3412; }
        .pri-Critical { background:#fee2e2;color:#991b1b; }
        /* Asset statuses — PRD lifecycle */
        .st-Briefed             { background:#ebf5fb;color:#1a5276; }
        .st-In_Progress         { background:#fef9e7;color:#7d6608; }
        .st-In_Revision         { background:#fdebd0;color:#a04000; }
        .st-Approved_by_PH      { background:#d5f5e3;color:#1e8449; }
        .st-Approved_by_Manager { background:#d1fae5;color:#065f46; }
        .st-Published___Live    { background:#bbf7d0;color:#14532d; }
        .st-On_Hold             { background:#fef3c7;color:#92400e; }
        .st-Cancelled           { background:#fee2e2;color:#991b1b; }
        /* Legacy compatibility */
        .st-Not_Started { background:#e2e8f0;color:#475569; }
        .st-In_Review   { background:#fef9c3;color:#854d0e; }
        .st-Approved    { background:#d5f5e3;color:#1e8449; }
        .st-Live        { background:#bbf7d0;color:#14532d; }
        .st-Archived    { background:#f1f5f9;color:#64748b; }
        /* Campaign statuses */
        .cs-Planning  { background:#eff6ff;color:#1d4ed8; }
        .cs-Active    { background:#dcfce7;color:#166534; }
        .cs-Paused    { background:#fef9c3;color:#854d0e; }
        .cs-Completed { background:#d1fae5;color:#065f46; }
        .cs-Cancelled { background:#fee2e2;color:#991b1b; }
        /* Approval statuses */
        .appr-Pending           { background:#f3f4f6;color:#6b7280; }
        .appr-Approved          { background:#d1fae5;color:#065f46; }
        .appr-Changes-Requested { background:#ffedd5;color:#9a3412; }
        .appr-Rejected          { background:#fee2e2;color:#991b1b; }
        .appr-Yes { background:#d1fae5;color:#065f46; }
        .appr-No  { background:#fee2e2;color:#991b1b; }

        /* ── Vertical chip ── */
        .vertical-chip {
            display: inline-block; padding: 2px 8px; border-radius: 6px;
            background: #eff6ff; color: #1d4ed8;
            font-size: .7rem; font-weight: 700; letter-spacing: .3px;
        }

        /* ── ID code ── */
        .id-code {
            font-size: .7rem; background: #f3f4f6; color: #374151;
            padding: 2px 6px; border-radius: 4px;
            font-family: 'SF Mono','Fira Code',monospace;
        }

        /* ── Campaign ID preview bar ── */
        .id-preview-bar {
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;
            padding: 10px 14px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        .id-preview-label { font-size: .78rem; font-weight: 600; color: #1d4ed8; }
        .id-preview-value {
            font-family: 'SF Mono','Fira Code',monospace; font-size: .88rem;
            color: #1e40af; background: #dbeafe; padding: 2px 10px; border-radius: 6px; font-weight: 600;
        }

        /* ── Modal ── */
        .modal-content { border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 20px 60px rgba(0,0,0,.15); }
        .modal-header { padding: 16px 20px; }
        .modal-body   { padding: 20px; }
        .modal-footer { padding: 14px 20px; background: #f9fafb; border-top: 1px solid var(--border); border-radius: 0 0 14px 14px; }
        .modal-section-title {
            font-size: .7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--text-muted);
            margin: 0 0 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border);
        }

        /* ── Stat cards ── */
        .stat-card {
            border-radius: var(--card-radius); padding: 16px 18px;
            background: var(--card-bg); border: 1px solid var(--border);
            box-shadow: var(--card-shadow); height: 100%;
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon {
            width: 46px; height: 46px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }
        .stat-num   { font-size: 1.65rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: .76rem; color: var(--text-muted); font-weight: 500; margin-top: 3px; }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 12px 14px 60px; }
            .btn-hamburger { display: inline-flex; align-items: center; }
            .topbar { border-radius: 10px; top: 8px; }
            .filter-form { flex-direction: column !important; align-items: stretch !important; }
            .filter-form select, .filter-form a { width: 100% !important; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 8px 10px 60px; }
            .topbar { padding: 0 14px; margin-bottom: 14px; }
            .topbar h5 { font-size: .9rem; }
            .hide-xs { display: none !important; }
            .modal-dialog { margin: 0; max-width: 100%; }
            .modal-content { border-radius: 0; min-height: 100vh; }
            .modal-body { padding: 14px; }
            .topbar-user span { display: none; }
        }
        @media (min-width: 992px) {
            .sidebar-overlay { display: none !important; }
        }
    </style>
</head>
<body>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentType = $_GET['type'] ?? '';
?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-inner">
            <div class="brand-icon"><i class="fa fa-diagram-project"></i></div>
            <div class="brand-text">Solid<span>Pro</span></div>
        </div>
        <button onclick="closeSidebar()" id="sidebarClose"
            style="display:none;background:none;border:none;color:rgba(255,255,255,.4);font-size:1.1rem;padding:0;cursor:pointer;line-height:1;">
            <i class="fa fa-xmark"></i>
        </button>
    </div>

    <nav>
        <div class="sidebar-section">Main</div>
        <a href="index.php" class="<?= $currentPage==='index.php'?'active':'' ?>" onclick="closeSidebar()">
            <span class="nav-icon"><i class="fa fa-gauge-high fa-fw"></i></span> Dashboard
        </a>
        <a href="campaigns.php" class="<?= $currentPage==='campaigns.php'?'active':'' ?>" onclick="closeSidebar()">
            <span class="nav-icon"><i class="fa fa-layer-group fa-fw"></i></span> Campaign Master
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section">Asset Trackers</div>
        <?php foreach ($ASSET_TYPES as $navType => $navCfg): ?>
        <a href="assets.php?type=<?= $navType ?>"
           class="<?= ($currentPage==='assets.php' && $currentType===$navType)?'active':'' ?>"
           onclick="closeSidebar()">
            <span class="dot" style="background:<?= $navCfg['color'] ?>;"></span>
            <?= $navCfg['label'] ?>
        </a>
        <?php endforeach; ?>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section">Settings</div>
        <a href="users.php" class="<?= $currentPage==='users.php'?'active':'' ?>" onclick="closeSidebar()">
            <span class="nav-icon"><i class="fa fa-users fa-fw"></i></span> Users &amp; Roles
        </a>
        <a href="upload.php" class="<?= $currentPage==='upload.php'?'active':'' ?>" onclick="closeSidebar()">
            <span class="nav-icon"><i class="fa fa-file-import fa-fw"></i></span> Import Excel
        </a>
    </nav>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn-hamburger" onclick="openSidebar()" aria-label="Open menu">
                <i class="fa fa-bars"></i>
            </button>
            <h5><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h5>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="fa fa-calendar-days"></i><?= date('d M Y') ?>
            </div>
            <div class="topbar-bell" title="Notifications">
                <i class="fa fa-bell"></i>
                <span class="bell-dot"></span>
            </div>
            <div class="topbar-user">
                <div class="user-avatar-sm">SP</div>
                <span>SolidPro</span>
            </div>
        </div>
    </div>


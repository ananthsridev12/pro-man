<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SolidPro — Campaign Manager') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-w: 230px; --sidebar-bg: #0f1923; --accent: #2d9cdb; --topbar-h: 58px; }
        * { box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; margin: 0; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            height: 100%;
            background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 1050;
            overflow-y: auto; overflow-x: hidden;
            transition: transform .28s ease;
            display: flex; flex-direction: column;
        }
        .sidebar .brand {
            padding: 16px 20px 14px; font-size: 1.1rem; font-weight: 700;
            color: #fff; border-bottom: 1px solid rgba(255,255,255,.08);
            letter-spacing: .5px; flex-shrink: 0;
        }
        .sidebar .brand span { color: var(--accent); }
        .sidebar .nav-section {
            padding: 10px 16px 4px; font-size: .66rem; font-weight: 600;
            color: rgba(255,255,255,.3); text-transform: uppercase; letter-spacing: 1px;
        }
        .sidebar nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 20px; color: rgba(255,255,255,.65);
            text-decoration: none; font-size: .85rem; transition: all .18s;
            border-left: 3px solid transparent;
        }
        .sidebar nav a:hover  { color: #fff; background: rgba(255,255,255,.05); }
        .sidebar nav a.active { color: #fff; background: rgba(45,156,219,.15); border-left-color: var(--accent); }
        .sidebar nav a .dot   { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── Overlay (mobile) ── */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 1040;
        }
        .sidebar-overlay.show { display: block; }

        /* ── Main content ── */
        .main-content {
            margin-left: var(--sidebar-w);
            padding: 20px 20px 40px;
            min-height: 100vh;
            transition: margin-left .28s ease;
        }

        /* ── Topbar ── */
        .topbar {
            background: #fff; border-radius: 10px; padding: 10px 16px;
            margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 12px; z-index: 100;
        }
        .topbar h5 { margin: 0; font-weight: 600; color: #1a2533; font-size: 1rem; }
        .btn-hamburger {
            display: none; background: none; border: none; padding: 4px 8px;
            color: #1a2533; font-size: 1.2rem; cursor: pointer; margin-right: 10px;
        }

        /* ── Cards / general ── */
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-radius: 10px; }
        .card-header { border-radius: 10px 10px 0 0 !important; }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #1a85c2; border-color: #1a85c2; }

        /* ── Badges ── */
        .badge-pill { font-size: .72rem; padding: 3px 9px; border-radius: 20px; font-weight: 500; display: inline-block; }
        .pri-Low      { background:#d1fae5;color:#065f46; }
        .pri-Medium   { background:#fef3c7;color:#92400e; }
        .pri-High     { background:#fee2e2;color:#991b1b; }
        .pri-Critical { background:#fce7f3;color:#9d174d; }
        .st-Not-Started  { background:#e2e8f0;color:#475569; }
        .st-Briefed      { background:#e0f2fe;color:#0369a1; }
        .st-In-Progress  { background:#dbeafe;color:#1d4ed8; }
        .st-In-Review    { background:#fef9c3;color:#854d0e; }
        .st-Approved     { background:#dcfce7;color:#166534; }
        .st-Scheduled    { background:#ede9fe;color:#5b21b6; }
        .st-Live         { background:#bbf7d0;color:#14532d; }
        .st-Amends       { background:#ffedd5;color:#9a3412; }
        .st-On-Hold      { background:#fef3c7;color:#92400e; }
        .st-Cancelled    { background:#fee2e2;color:#991b1b; }
        .st-Archived     { background:#f1f5f9;color:#64748b; }
        .cs-Planning     { background:#e0f2fe;color:#0369a1; }
        .cs-Active       { background:#dcfce7;color:#166534; }
        .cs-Paused       { background:#fef9c3;color:#854d0e; }
        .cs-Completed    { background:#d1fae5;color:#065f46; }
        .cs-Cancelled    { background:#fee2e2;color:#991b1b; }
        .table > tbody > tr:hover { background: #f8fafc; }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 12px 12px 40px; }
            .btn-hamburger { display: inline-flex; align-items: center; }
            .topbar { border-radius: 8px; top: 8px; }
            /* Stack filter forms vertically */
            .filter-form { flex-direction: column !important; align-items: stretch !important; }
            .filter-form select, .filter-form a { width: 100% !important; }
            /* Smaller table font on mobile */
            .table { font-size: .78rem; }
            /* Action buttons smaller */
            .btn-sm { padding: 3px 7px; font-size: .75rem; }
        }

        @media (max-width: 575.98px) {
            .main-content { padding: 8px 8px 40px; }
            .topbar { padding: 8px 12px; margin-bottom: 12px; }
            .topbar h5 { font-size: .9rem; }
            .card { border-radius: 8px; }
            /* Hide less important table columns on small phones */
            .hide-xs { display: none !important; }
            /* Modals full screen on phone */
            .modal-dialog { margin: 0; max-width: 100%; }
            .modal-content { border-radius: 0; min-height: 100vh; }
            .modal-body { padding: 12px; }
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

<!-- Sidebar overlay (mobile tap to close) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="brand">
        <i class="fa fa-diagram-project me-1"></i> Solid<span>Pro</span>
        <!-- Close button (mobile only) -->
        <button onclick="closeSidebar()"
            style="display:none;float:right;background:none;border:none;color:rgba(255,255,255,.5);font-size:1.1rem;padding:0;cursor:pointer;"
            id="sidebarClose">
            <i class="fa fa-xmark"></i>
        </button>
    </div>
    <nav>
        <div class="nav-section">Main</div>
        <a href="index.php" class="<?= $currentPage==='index.php'?'active':'' ?>">
            <i class="fa fa-gauge fa-fw"></i> Dashboard
        </a>
        <a href="campaigns.php" class="<?= $currentPage==='campaigns.php'?'active':'' ?>">
            <i class="fa fa-folder-open fa-fw"></i> Campaign Master
        </a>

        <div class="nav-section mt-2">Asset Trackers</div>
        <?php foreach ($ASSET_TYPES as $type => $cfg): ?>
        <a href="assets.php?type=<?= $type ?>"
           class="<?= ($currentPage==='assets.php' && $currentType===$type)?'active':'' ?>"
           onclick="closeSidebar()">
            <span class="dot" style="background:<?= $cfg['color'] ?>;"></span>
            <?= $cfg['label'] ?>
        </a>
        <?php endforeach; ?>

        <div class="nav-section mt-2">Tools</div>
        <a href="upload.php" class="<?= $currentPage==='upload.php'?'active':'' ?>">
            <i class="fa fa-file-excel fa-fw"></i> Import Excel
        </a>
    </nav>
</div>

<!-- Main content wrapper -->
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center">
            <button class="btn-hamburger" onclick="openSidebar()" aria-label="Open menu">
                <i class="fa fa-bars"></i>
            </button>
            <h5><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h5>
        </div>
        <div class="text-muted" style="font-size:.8rem; white-space:nowrap;">
            <i class="fa fa-calendar-days me-1"></i><?= date('d M Y') ?>
        </div>
    </div>

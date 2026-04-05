<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$admin_name = $_SESSION['admin_username'] ?? '管理员';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>导航管理后台</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
    background: #f0f2f5;
    color: #1a1a1a;
    min-height: 100vh;
}
.admin-layout {
    display: flex;
    min-height: 100vh;
}
.sidebar {
    width: 220px;
    background: #1a1a2e;
    color: #fff;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
}
.sidebar-logo {
    padding: 24px 20px;
    font-size: 16px;
    font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: #c9a227;
    letter-spacing: 0.05em;
}
.sidebar-nav { flex: 1; padding: 12px 0; }
.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    font-size: 14px;
    transition: background 0.2s, color 0.2s;
}
.sidebar-nav a:hover { background: rgba(255,255,255,0.06); color: #fff; }
.sidebar-nav a.active {
    background: rgba(201,162,39,0.15);
    color: #c9a227;
    border-right: 3px solid #c9a227;
}
.sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
    font-size: 12px;
    color: rgba(255,255,255,0.4);
}
.sidebar-footer .logout {
    display: inline-block;
    margin-top: 8px;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    font-size: 12px;
}
.sidebar-footer .logout:hover { color: #e03e3e; }
.main-content { flex: 1; display: flex; flex-direction: column; }
.topbar {
    background: #fff;
    padding: 0 24px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e8e8e8;
}
.topbar-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
}
.topbar-user { font-size: 13px; color: #6b6b6b; }
.page-content { padding: 24px; flex: 1; }
.card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: #1a1a1a;
    border-radius: 6px;
    transition: background 0.2s;
}
.menu-toggle:hover { background: #f0f0f0; }
.menu-toggle svg { display: block; }

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 998;
}

@media (max-width: 767px) {
    .admin-layout {
        transform: scale(0.85);
        transform-origin: top left;
        width: 133.33%;
        height: 133.33%;
        min-height: 133.33vh;
    }

    .menu-toggle { display: flex; align-items: center; justify-content: center; }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 999;
        transform: translateX(-100%);
        transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay.show { display: block; }

    .topbar { padding: 0 16px; }
    .topbar-title { font-size: 15px; }
    .page-content { padding: 16px; }

    .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .stat-card { padding: 14px 10px; }
    .stat-num { font-size: 22px; }
    .stat-label { font-size: 11px; }
}

@media (max-width: 480px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .page-content { padding: 12px; }
}
</style>
</head>
<body>
<div class="admin-layout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">导航管理后台</div>
        <nav class="sidebar-nav">
            <a href="index.php" class="<?= $current_page === 'index' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                仪表盘
            </a>
            <a href="feeds.php" class="<?= $current_page === 'feeds' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                发现管理
            </a>
            <a href="carousel.php" class="<?= $current_page === 'carousel' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                轮播图管理
            </a>
            <a href="nav.php" class="<?= $current_page === 'nav' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                导航管理
            </a>
            <a href="profile.php" class="<?= $current_page === 'profile' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                修改密码
            </a>
            <a href="lottery.php" class="<?= $current_page === 'lottery' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                抽奖设置
            </a>
            <a href="contacts.php" class="<?= $current_page === 'contacts' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                联系方式
            </a>
        </nav>
        <div class="sidebar-footer">
            <?= htmlspecialchars($admin_name) ?>
            <a href="logout.php" class="logout">退出登录</a>
        </div>
    </aside>
    <div class="main-content">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="打开菜单">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="topbar-title"><?= $page_title ?? '仪表盘' ?></span>
            <span class="topbar-user"><?= htmlspecialchars($admin_name) ?></span>
        </header>
        <div class="page-content">
<script>
(function() {
    var toggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // 点击侧边栏链接后自动关闭
    sidebar.querySelectorAll('a').forEach(function(a) {
        a.addEventListener('click', closeSidebar);
    });
})();
</script>

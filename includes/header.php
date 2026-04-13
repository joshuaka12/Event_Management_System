<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../auth/auth.php';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Campus EMS') ?> — Campus Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ========== COMPLETE STYLES ========== */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#f8f6f2; color:#1e1b18; line-height:1.65; min-height:100vh; display:flex; flex-direction:column; }
        h1,h2,h3,h4 { font-family:'DM Serif Display',Georgia,serif; font-weight:500; }
        .container { max-width:1240px; margin:0 auto; padding:0 1.5rem; }
        .main-content { flex:1; padding-top:80px; padding-bottom:3rem; }
        .navbar { position:fixed; top:0; left:0; right:0; height:70px; background:white; border-bottom:1px solid #e8e2dc; z-index:1000; }
        .nav-container { display:flex; align-items:center; justify-content:space-between; height:100%; max-width:1240px; margin:0 auto; padding:0 1.5rem; }
        .nav-brand { font-family:'DM Serif Display',serif; font-size:1.5rem; color:#1e1b18; text-decoration:none; }
        .brand-icon, .brand-accent { color:#c13b2b; }
        .nav-links { display:flex; align-items:center; gap:0.5rem; list-style:none; }
        .nav-link { padding:0.4rem 0.8rem; border-radius:30px; color:#4a4540; font-weight:500; font-size:0.9rem; text-decoration:none; }
        .nav-link:hover { background:#f0ede8; color:#1e1b18; }
        .nav-logout { color:#c13b2b; }
        .nav-user { display:flex; align-items:center; gap:0.5rem; margin-left:0.5rem; }
        .user-badge { font-weight:600; font-size:0.85rem; }
        .role-pill { padding:0.2rem 0.6rem; border-radius:40px; font-size:0.7rem; font-weight:700; text-transform:uppercase; }
        .role-admin { background:#fae9e6; color:#c13b2b; }
        .role-organizer { background:#e0f2f1; color:#1e7a6e; }
        .role-student { background:#e8f0fe; color:#1d4e89; }
        .nav-toggle { display:none; background:none; border:none; font-size:1.6rem; cursor:pointer; }
        .btn { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.3rem; border-radius:40px; font-weight:600; font-size:0.9rem; border:2px solid transparent; cursor:pointer; text-decoration:none; }
        .btn-sm { padding:0.35rem 0.9rem; font-size:0.8rem; }
        .btn-primary { background:#c13b2b; color:white; border-color:#c13b2b; }
        .btn-primary:hover { background:#96201a; }
        .btn-outline { background:transparent; color:#c13b2b; border-color:#c13b2b; }
        .btn-outline:hover { background:#c13b2b; color:white; }
        .btn-ghost { background:#f0ede8; color:#4a4540; border-color:#e0dbd4; }
        .hero { background:linear-gradient(135deg,#1e1b18,#3a2c24); padding:4rem 0; text-align:center; }
        .hero h1 { color:white; margin-bottom:1rem; }
        .hero p { color:rgba(255,255,255,0.8); margin-bottom:2rem; }
        .hero-actions { display:flex; gap:1rem; justify-content:center; }
        .search-bar { display:flex; gap:0.75rem; align-items:center; max-width:620px; margin:2rem auto; background:white; padding:0.3rem; border-radius:80px; border:1px solid #e8e2dc; }
        .search-bar input { flex:1; border:none; background:transparent; padding:0.6rem 1rem; outline:none; }
        .events-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1.8rem; margin-top:2rem; }
        .event-card { background:white; border:1px solid #ece5df; border-radius:20px; overflow:hidden; transition:0.25s; }
        .event-card:hover { transform:translateY(-4px); box-shadow:0 16px 28px -8px rgba(0,0,0,0.12); }
        .event-card-body { padding:1.5rem; }
        .event-card-title { font-family:'DM Serif Display',serif; font-size:1.25rem; margin-bottom:0.5rem; }
        .event-meta { display:flex; flex-direction:column; gap:0.4rem; margin:1rem 0; font-size:0.85rem; color:#5e5a55; }
        .event-meta div { display:flex; align-items:center; gap:0.4rem; }
        .event-card-footer { padding:1rem 1.5rem; border-top:1px solid #ece5df; display:flex; justify-content:space-between; background:#fefcf9; }
        .event-badge { display:inline-block; padding:0.2rem 0.8rem; border-radius:30px; font-size:0.7rem; font-weight:700; text-transform:uppercase; }
        .badge-upcoming { background:#e3f5ec; color:#1e7a48; }
        .badge-past { background:#efebe7; color:#6b625c; }
        .badge-today { background:#fee9e6; color:#c13b2b; }
        .flash { position:fixed; top:85px; left:50%; transform:translateX(-50%); z-index:1100; padding:0.9rem 1.5rem; border-radius:60px; display:flex; gap:1rem; background:white; box-shadow:0 8px 20px rgba(0,0,0,0.12); }
        .flash-success { background:#e3f5ec; color:#1e7a48; }
        .flash-error { background:#fee9e6; color:#c13b2b; }
        .flash-close { background:none; border:none; font-size:1.2rem; cursor:pointer; }
        .site-footer { background:#1e1b18; color:#aaa49e; text-align:center; padding:2rem; margin-top:auto; }
        .footer-brand { font-family:'DM Serif Display',serif; font-size:1.3rem; color:white; }
        .form-card { background:white; border:1px solid #ece5df; border-radius:24px; padding:2rem; max-width:520px; margin:0 auto; }
        .form-title { font-family:'DM Serif Display',serif; font-size:1.8rem; text-align:center; margin-bottom:1.5rem; }
        .form-group { margin-bottom:1.2rem; }
        .form-label { display:block; font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; }
        .form-control { width:100%; padding:0.7rem 1rem; border:1.5px solid #e0dbd4; border-radius:14px; background:#fefcf9; }
        .form-control:focus { border-color:#c13b2b; outline:none; }
        .alert { padding:0.9rem 1.25rem; border-radius:16px; margin-bottom:1.25rem; }
        .alert-error { background:#fee9e6; color:#c13b2b; border-left:4px solid #c13b2b; }
        .text-muted { color:#8a837c; }
        .text-center { text-align:center; }
        .mt-2 { margin-top:1rem; }
        .mt-3 { margin-top:1.5rem; }
        .mt-4 { margin-top:2rem; }
        .mb-2 { margin-bottom:1rem; }
        .btn-full { width:100%; justify-content:center; }
        .back-button-wrapper { max-width:1240px; margin:0 auto; padding:0 1.5rem; margin-top:0.5rem; margin-bottom:1rem; }
        .back-button { display:inline-flex; align-items:center; gap:0.4rem; background:white; border:1px solid #e0dbd4; border-radius:40px; padding:0.4rem 1rem; font-family:'DM Sans',sans-serif; font-size:0.85rem; font-weight:500; color:#4a4540; cursor:pointer; transition:all 0.2s ease; }
        .back-button:hover { background:#f0ede8; border-color:#c13b2b; color:#c13b2b; transform:translateX(-3px); }
        @media (max-width:768px) {
            .nav-toggle { display:block; }
            .nav-links { display:none; flex-direction:column; position:absolute; top:70px; left:0; right:0; background:white; padding:1rem; border-bottom:1px solid #ece5df; }
            .nav-links.open { display:flex; }
            .events-grid { grid-template-columns:1fr; }
            .back-button-wrapper { margin-top:0; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= base_url('index.php') ?>" class="nav-brand">
            <span class="brand-icon">◈</span>
            Campus<span class="brand-accent">EMS</span>
        </a>
        <button class="nav-toggle" id="navToggle">☰</button>
        <ul class="nav-links" id="navLinks">
            <li><a href="<?= base_url('index.php') ?>" class="nav-link">Events</a></li>
            <?php if (is_logged_in()): ?>
                <?php if (current_role() === 'admin'): ?>
                    <li><a href="<?= base_url('admin/dashboard.php') ?>" class="nav-link">Admin Panel</a></li>
                <?php elseif (current_role() === 'organizer'): ?>
                    <li><a href="<?= base_url('organizer/dashboard.php') ?>" class="nav-link">Dashboard</a></li>
                    <li><a href="<?= base_url('organizer/create_event.php') ?>" class="nav-link">Create Event</a></li>
                    <li><a href="<?= base_url('organizer/profile.php') ?>" class="nav-link">My Profile</a></li>  <!-- added profile link -->
                <?php else: ?>
                    <li><a href="<?= base_url('student/dashboard.php') ?>" class="nav-link">My Dashboard</a></li>
                <?php endif; ?>
                <li class="nav-user">
                    <span class="user-badge"><?= e(current_user_name()) ?></span>
                    <span class="role-pill role-<?= e(current_role()) ?>"><?= e(current_role()) ?></span>
                </li>
                <li><a href="<?= base_url('logout.php') ?>" class="nav-link nav-logout">Log Out</a></li>
            <?php else: ?>
                <li><a href="<?= base_url('login.php') ?>" class="nav-link">Log In</a></li>
                <li><a href="<?= base_url('register.php') ?>" class="btn btn-primary btn-sm">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>" id="flashMsg">
    <?= e($flash['message']) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>
<main class="main-content">
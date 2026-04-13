<?php
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/config/db.php';

$page_title = 'Browse Events';
$conn = db_connect();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$params = array();
$types  = '';
$sql    = "SELECT e.*, u.name AS organizer_name,
                  (SELECT COUNT(*) FROM registrations r
                   WHERE r.event_id = e.id AND r.status = 'registered') AS reg_count
           FROM events e
           JOIN users u ON u.id = e.created_by";

if ($search !== '') {
    $sql   .= " WHERE (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $like   = "%$search%";
    $params = array($like, $like, $like);
    $types  = 'sss';
}

$sql .= " ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    call_user_func_array(array($stmt, 'bind_param'), array_merge(array($types), $params));
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

function event_status($date) {
    $d = new DateTime($date);
    $now = new DateTime();
    $today_start = clone $now;
    $today_start->setTime(0, 0, 0);
    $today_end = clone $now;
    $today_end->setTime(23, 59, 59);
    if ($d >= $today_start && $d <= $today_end) return array('Today!', 'badge-today');
    if ($d > $now) return array('Upcoming', 'badge-upcoming');
    return array('Past', 'badge-past');
}

// Only declare e() if it doesn't already exist (prevents redeclaration error)
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Campus Event Hub — Browse Events</title>
    <!-- Bootstrap 5 (lightweight, modern, responsive) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (free icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.5;
            scroll-behavior: smooth;
        }

        /* preserve original color tokens – no color changes, only usage refinements */
        :root {
            --primary: #680909f1;
            --primary-dark: #d70b0b;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-700: #495057;
            --gray-900: #212529;
            --badge-today: #d63384;
            --badge-upcoming: #198754;
            --badge-past: #6c757d;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            --transition-default: all 0.25s ease;
        }

        h1, h2, h3, h4 {
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .hero {
            background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
            border-bottom: 1px solid var(--gray-200);
            padding: 3.5rem 0 3rem 0;
            margin-bottom: 2rem;
        }

        .hero-content h1 {
            font-size: 2.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #334155;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.75rem;
        }

        .btn {
            border-radius: 60px;
            padding: 0.5rem 1.4rem;
            font-weight: 500;
            transition: var(--transition-default);
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-200);
            color: #1e293b;
        }
        .btn-outline:hover {
            background-color: var(--gray-100);
            border-color: var(--gray-300);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: transparent;
            border: none;
            color: #5b6e8c;
        }
        .btn-ghost:hover {
            background: rgba(0,0,0,0.04);
            color: #0f172a;
        }

        .btn-sm {
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
        }

        .search-wrapper {
            max-width: 720px;
            margin: 0 auto 2rem auto;
        }
        .search-bar-modern {
            display: flex;
            gap: 0.75rem;
            background: white;
            border-radius: 80px;
            padding: 0.4rem 0.4rem 0.4rem 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03), 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-200);
            transition: var(--transition-default);
        }
        .search-bar-modern:focus-within {
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border-color: var(--primary);
        }
        .search-bar-modern input {
            flex: 1;
            border: none;
            padding: 0.7rem 0;
            font-size: 0.95rem;
            background: transparent;
            outline: none;
        }
        .search-bar-modern button, .search-bar-modern a {
            white-space: nowrap;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.8rem;
            margin: 2rem 0 3rem;
        }

        .event-card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            transition: var(--transition-default);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.12);
            border-color: var(--gray-200);
        }

        .event-card-body {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            flex: 1;
        }

        .event-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.8rem;
            border-radius: 40px;
            margin-bottom: 0.9rem;
            letter-spacing: 0.3px;
            background: #eef2ff;
            color: #1f2b48;
        }
        .badge-today {
            background: var(--badge-today) !important;
            color: white !important;
        }
        .badge-upcoming {
            background: var(--badge-upcoming) !important;
            color: white !important;
        }
        .badge-past {
            background: var(--badge-past) !important;
            color: white !important;
        }

        .event-card-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .event-meta {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            font-size: 0.85rem;
            color: #475569;
            border-top: 1px solid #edf2f7;
            padding-top: 1rem;
        }
        .event-meta div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .event-meta i {
            width: 1.25rem;
            color: #5b6e8c;
        }

        .event-card-footer {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            background: #ffffff;
            border-top: 1px solid #f0f2f5;
        }

        .empty-state-modern {
            text-align: center;
            padding: 3rem 1.5rem;
            background: white;
            border-radius: 2rem;
            box-shadow: var(--card-shadow);
            margin: 2rem 0;
        }
        .empty-state-modern i {
            font-size: 3.5rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .result-counter {
            background: white;
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        footer {
            background: white;
            border-top: 1px solid #eef2ff;
            margin-top: 3rem;
            padding: 2rem 0;
            text-align: center;
            color: #5b6e8c;
        }

        a, button {
            transition: var(--transition-default);
        }
        .btn:active {
            transform: scale(0.97);
        }

        @media (max-width: 768px) {
            .hero-content h1 { font-size: 2rem; }
            .events-grid { gap: 1.2rem; }
            .search-bar-modern { border-radius: 60px; padding: 0.3rem 0.3rem 0.3rem 1.2rem; }
        }
    </style>
</head>
<body>

<!-- Hero section redesigned with modern spacing and preserved colors -->
<div class="hero">
    <div class="container hero-content text-center">
        <h1><i class="fas fa-calendar-alt me-2" style="color: #0d6efd;"></i> Campus Event Hub</h1>
        <p>Discover workshops, seminars, cultural nights, career fairs and more happening across campus.</p>
        <?php if (!is_logged_in()): ?>
        <div class="hero-actions">
            <a href="<?= base_url('register.php') ?>" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Join Us Now — It's Free</a>
            <a href="<?= base_url('login.php') ?>" class="btn btn-outline"><i class="fas fa-sign-in-alt me-1"></i> Log In</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <!-- Modern search bar with micro-interaction & clear button -->
    <div class="search-wrapper">
        <form method="GET" action="">
            <div class="search-bar-modern">
                <input type="text" name="q" placeholder="Search by title, venue, or keyword…" value="<?= e($search) ?>" aria-label="Search events">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Search</button>
                <?php if ($search): ?>
                    <a href="<?= base_url('index.php') ?>" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Search results counter + subtle animation -->
    <?php if ($search): ?>
        <div class="text-center mt-2 mb-3">
            <span class="result-counter">
                <i class="far fa-file-alt me-1"></i> <?= count($events) ?> result<?= count($events) !== 1 ? 's' : '' ?> for "<strong><?= e($search) ?></strong>"
            </span>
        </div>
    <?php endif; ?>

    <?php if (empty($events)): ?>
        <div class="empty-state-modern">
            <i class="fas fa-calendar-times"></i>
            <h3 class="fw-semibold mt-2">No events found</h3>
            <p class="text-muted"><?= $search ? 'Try a different search term or keyword.' : 'Check back soon — exciting events will be posted here.' ?></p>
            <?php if($search): ?>
                <a href="<?= base_url('index.php') ?>" class="btn btn-outline mt-2"><i class="fas fa-arrow-left"></i> Browse all events</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="events-grid mt-3">
            <?php foreach ($events as $ev):
                list($statusLabel, $statusClass) = event_status($ev['event_date']);
                $date_fmt = date('D, d M Y · g:i A', strtotime($ev['event_date']));
            ?>
            <div class="event-card">
                <div class="event-card-body">
                    <span class="event-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
                    <p class="text-secondary small"><?= e(substr($ev['description'], 0, 110)) ?><?= strlen($ev['description']) > 110 ? '…' : '' ?></p>
                    <div class="event-meta">
                        <div><i class="far fa-calendar-alt"></i> <?= e($date_fmt) ?></div>
                        <div><i class="fas fa-map-marker-alt"></i> <?= e($ev['venue']) ?></div>
                        <div><i class="fas fa-users"></i> <span class="fw-semibold"><?= (int)$ev['reg_count'] ?> / <?= (int)$ev['capacity'] ?></span> registered</div>
                        <div><i class="fas fa-chalkboard-user"></i> By <?= e($ev['organizer_name']) ?></div>
                    </div>
                </div>
                <div class="event-card-footer">
                    <a href="<?= base_url('event_details.php?id=' . $ev['id']) ?>" class="btn btn-outline btn-sm">
                        View Details <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                    <?php if (is_logged_in() && current_role() === 'student'): ?>
                        <a href="<?= base_url('student/register_event.php?id=' . $ev['id']) ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-check-circle"></i> Register
                        </a>
                    <?php elseif (!is_logged_in()): ?>
                        <a href="<?= base_url('login.php') ?>" class="btn btn-ghost btn-sm">
                            <i class="fas fa-lock"></i> Login to Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * index.php
 * Landing page – displays approved events, search, calendar, features, about, contact (professional).
 */

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/config/db.php';

$page_title = 'Campus Event Hub';
$conn = db_connect();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$params = [];
$types = '';
$sql = "SELECT e.*, u.name AS organizer_name,
               (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'registered') AS reg_count
        FROM events e
        JOIN users u ON u.id = e.created_by
        WHERE e.deleted_at IS NULL
        AND e.status = 'approved'";

if ($search !== '') {
    $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
    $types = 'sss';
}

$sql .= " ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

function event_status($date) {
    $d = new DateTime($date);
    $now = new DateTime();
    $today_start = (clone $now)->setTime(0, 0, 0);
    $today_end = (clone $now)->setTime(23, 59, 59);
    if ($d >= $today_start && $d <= $today_end) return ['Today!', 'badge-today'];
    if ($d > $now) return ['Upcoming', 'badge-upcoming'];
    return ['Past', 'badge-past'];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ==================== HERO SECTION ==================== -->
<section id="home" class="hero-section">
    <div class="container hero-content">
        <h1 class="hero-title">Campus Event Hub</h1>
        <p class="hero-subtitle">Discover workshops, seminars, cultural nights, career fairs and more happening across campus.</p>
        <?php if (!is_logged_in()): ?>
        <div class="hero-buttons">
            <a href="<?= base_url('register.php') ?>" class="btn btn-primary btn-lg">Join Now — It's Free</a>
            <a href="<?= base_url('login.php') ?>" class="btn btn-outline-light btn-lg">Log In</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <!-- Search & Calendar Bar -->
    <div class="search-calendar-wrapper">
        <form method="GET" action="" class="search-form">
            <input type="text" name="q" placeholder="Search by title, venue, or keyword…" value="<?= e($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="<?= base_url('index.php') ?>" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
        <button id="calendarBtn" class="btn btn-outline calendar-btn">
            <i class="fas fa-calendar-alt"></i> Calendar
        </button>
    </div>

    <?php if ($search): ?>
        <p class="search-results-count"><?= count($events) ?> result<?= count($events) !== 1 ? 's' : '' ?> for "<strong><?= e($search) ?></strong>"</p>
    <?php endif; ?>

    <!-- Events Grid -->
    <?php if (empty($events) && !$search): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times empty-icon"></i>
            <h3>No approved events yet</h3>
            <p>Events will appear here once approved by an administrator.</p>
            <?php if (is_logged_in() && current_role() === 'organizer'): ?>
                <a href="<?= base_url('organizer/create_event.php') ?>" class="btn btn-primary mt-3">Create an Event</a>
            <?php endif; ?>
        </div>
    <?php elseif (empty($events) && $search): ?>
        <div class="empty-state">
            <i class="fas fa-search empty-icon"></i>
            <h3>No matching events found</h3>
            <p>Try a different search term.</p>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $ev):
                list($statusLabel, $statusClass) = event_status($ev['event_date']);
                $date_fmt = date('D, d M Y · g:i A', strtotime($ev['event_date']));
                $poster_url = !empty($ev['poster_image']) ? image_url($ev['poster_image']) : '';
            ?>
            <div class="event-card">
                <?php if ($poster_url): ?>
                    <div class="event-card-image">
                        <img src="<?= $poster_url ?>" alt="<?= e($ev['title']) ?>">
                    </div>
                <?php else: ?>
                    <div class="event-card-image-placeholder">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                <?php endif; ?>
                <div class="event-card-body">
                    <span class="event-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
                    <p class="event-description"><?= e(substr($ev['description'], 0, 100)) ?></p>
                    <div class="event-meta">
                        <div><i class="fas fa-calendar-day"></i> <?= e($date_fmt) ?></div>
                        <div><i class="fas fa-map-marker-alt"></i> <?= e($ev['venue']) ?></div>
                        <div><i class="fas fa-users"></i> <?= (int)$ev['reg_count'] ?> / <?= (int)$ev['capacity'] ?> registered</div>
                        <div><i class="fas fa-user-tie"></i> <?= e($ev['organizer_name']) ?></div>
                    </div>
                </div>
                <div class="event-card-footer">
                    <a href="<?= base_url('event_details.php?id=' . $ev['id']) ?>" class="btn btn-outline btn-sm">View Details →</a>
                    <?php if (is_logged_in() && current_role() === 'student'): ?>
                        <a href="<?= base_url('student/register_event.php?id=' . $ev['id']) ?>" class="btn btn-primary btn-sm">Register</a>
                    <?php elseif (!is_logged_in()): ?>
                        <a href="<?= base_url('login.php') ?>" class="btn btn-ghost btn-sm">Login to Register</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ==================== FEATURES SECTION ==================== -->
<section id="features" class="features-section">
    <div class="container">
        <div class="section-header">
            <h2>Features & Services</h2>
            <p>Everything you need to manage campus events seamlessly</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-calendar-alt feature-icon"></i>
                <h4>Event Management</h4>
                <p>Create, edit, and manage events with rich multimedia support.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-users feature-icon"></i>
                <h4>Registration & Tracking</h4>
                <p>Easy registration, cancellation, and real‑time capacity tracking.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line feature-icon"></i>
                <h4>Analytics & Reports</h4>
                <p>Charts, statistics, and printable reports for informed decisions.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-bell feature-icon"></i>
                <h4>Smart Notifications</h4>
                <p>Automatic alerts for upcoming events and capacity warnings.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-video feature-icon"></i>
                <h4>Multimedia Support</h4>
                <p>Upload posters, galleries, and MP4 videos for each event.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt feature-icon"></i>
                <h4>Secure Roles</h4>
                <p>Admin, Organizer, Student roles with fine‑grained permissions.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== ABOUT SECTION ==================== -->
<section id="about" class="about-section">
    <div class="container">
        <div class="section-header">
            <h2>About CampusEMS</h2>
            <p>A platform built for the campus community</p>
        </div>
        <div class="about-content">
            <div class="about-text">
                <h3>Our Mission</h3>
                <p>Empower students, faculty, and staff with a centralized, easy‑to‑use tool for discovering, managing, and attending campus events – from academic conferences to cultural festivals.</p>
                <h3>Key Features</h3>
                <ul>
                    <li>📅 Event creation & approval workflow</li>
                    <li>🎥 Multimedia support (posters, galleries, videos)</li>
                    <li>👥 Registration tracking with capacity limits</li>
                    <li>🔔 Smart notifications for upcoming events</li>
                    <li>📊 Admin & organizer dashboards with reports</li>
                </ul>
                <p>Built with PHP, MySQL, Bootstrap, and FullCalendar – open source and community driven.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== PROFESSIONAL CONTACT SECTION (NO FORM) ==================== -->
<section id="contact" class="contact-section">
    <div class="container">
        <div class="section-header">
            <h2>Contact Us</h2>
            <p>We’d love to hear from you</p>
        </div>
        <div class="contact-cards">
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                <h4>Email</h4>
                <p><a href="mailto:support@campusems.com">support@campusems.com</a></p>
            </div>
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                <h4>Phone</h4>
                <p><a href="tel:+12345678900">+1 (234) 567-8900</a></p>
            </div>
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h4>Location</h4>
                <p>Main Campus, University Building, Room 101</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon"><i class="fab fa-twitter"></i></div>
                <h4>Social</h4>
                <p>
                    <a href="#">Twitter</a> &nbsp;|&nbsp;
                    <a href="#">Facebook</a> &nbsp;|&nbsp;
                    <a href="#">Instagram</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Calendar Modal (unchanged) -->
<div id="calendarModal" class="modal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.7); z-index:2000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 20px; width: 90%; max-width: 1000px; max-height: 90vh; overflow-y: auto; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-family: 'DM Serif Display', serif;">Event Calendar</h2>
            <button id="closeCalendarBtn" class="btn btn-ghost btn-sm" style="font-size: 1.2rem;">&times; Close</button>
        </div>
        <div id="calendar"></div>
    </div>
</div>

<!-- Styles -->
<style>
    /* Hero Section (unchanged) */
    .hero-section {
        background: linear-gradient(135deg, #1e1b18 0%, #3a2c24 100%);
        padding: 5rem 0;
        text-align: center;
        margin-top: calc(-1 * var(--nav-h));
        padding-top: calc(var(--nav-h) + 4rem);
    }
    .hero-title {
        font-size: 3rem;
        font-weight: 700;
        color: white;
        margin-bottom: 1rem;
    }
    .hero-subtitle {
        font-size: 1.2rem;
        color: rgba(255,255,255,0.85);
        max-width: 600px;
        margin: 0 auto 2rem;
    }
    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn-outline-light {
        border-color: white;
        color: white;
    }
    .btn-outline-light:hover {
        background: white;
        color: #1e1b18;
    }
    .btn-lg {
        padding: 0.75rem 1.8rem;
        font-size: 1rem;
    }

    /* Search & Calendar Bar (unchanged) */
    .search-calendar-wrapper {
        display: flex;
        gap: 1rem;
        align-items: center;
        max-width: 700px;
        margin: 2rem auto;
    }
    .search-form {
        flex: 1;
        display: flex;
        gap: 0.5rem;
        background: white;
        border: 1px solid #e0dbd4;
        border-radius: 60px;
        padding: 0.3rem 0.3rem 0.3rem 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .search-form input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0.6rem 0;
        font-size: 0.95rem;
        outline: none;
    }
    .search-form button {
        border-radius: 40px;
        white-space: nowrap;
    }
    .calendar-btn {
        white-space: nowrap;
    }
    .search-results-count {
        text-align: center;
        margin: 1rem 0;
        color: #6b625c;
    }

    /* Events Grid (unchanged) */
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
        margin: 2rem 0 4rem;
    }
    .event-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        transition: transform 0.25s, box-shadow 0.25s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 30px -12px rgba(0,0,0,0.15);
    }
    .event-card-image {
        height: 180px;
        overflow: hidden;
    }
    .event-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    .event-card:hover .event-card-image img {
        transform: scale(1.05);
    }
    .event-card-image-placeholder {
        height: 180px;
        background: #f0ede8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #c13b2b;
    }
    .event-card-body {
        padding: 1.2rem;
    }
    .event-badge {
        display: inline-block;
        padding: 0.2rem 0.8rem;
        border-radius: 30px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }
    .event-card-title {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }
    .event-description {
        color: #4a4540;
        font-size: 0.85rem;
        line-height: 1.4;
        margin-bottom: 1rem;
    }
    .event-meta {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: #6b625c;
        margin-bottom: 1rem;
    }
    .event-meta i {
        width: 20px;
        color: #c13b2b;
    }
    .event-card-footer {
        padding: 0.8rem 1.2rem;
        border-top: 1px solid #ece5df;
        display: flex;
        justify-content: space-between;
        background: #fefcf9;
    }

    /* Features Section (unchanged) */
    .features-section {
        background: #fefcf9;
        padding: 4rem 0;
    }
    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    .section-header h2 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .section-header p {
        color: #6b625c;
    }
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
    }
    .feature-card {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: 20px;
        transition: transform 0.2s;
    }
    .feature-card:hover {
        transform: translateY(-5px);
    }
    .feature-icon {
        font-size: 2.5rem;
        color: #c13b2b;
        margin-bottom: 1rem;
    }
    .feature-card h4 {
        margin-bottom: 0.5rem;
    }
    .feature-card p {
        color: #6b625c;
        font-size: 0.9rem;
    }

    /* About Section (unchanged) */
    .about-section {
        background: white;
        padding: 4rem 0;
    }
    .about-content {
        max-width: 800px;
        margin: 0 auto;
    }
    .about-text h3 {
        margin: 1.5rem 0 0.5rem;
    }
    .about-text ul {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }
    .about-text li {
        margin: 0.5rem 0;
    }

    /* Professional Contact Section (new, no form) */
    .contact-section {
        background: #f8f6f2;
        padding: 4rem 0;
    }
    .contact-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 2rem;
        max-width: 1000px;
        margin: 0 auto;
    }
    .contact-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .contact-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.08);
    }
    .contact-icon {
        font-size: 2.5rem;
        color: #c13b2b;
        margin-bottom: 1rem;
    }
    .contact-card h4 {
        margin-bottom: 0.5rem;
        font-size: 1.2rem;
    }
    .contact-card p {
        color: #4a4540;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    .contact-card a {
        color: #c13b2b;
        text-decoration: none;
        transition: color 0.2s;
    }
    .contact-card a:hover {
        color: #96201a;
        text-decoration: underline;
    }

    /* Empty State (unchanged) */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 24px;
        margin: 2rem 0;
    }
    .empty-icon {
        font-size: 3rem;
        color: #c13b2b;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .hero-title { font-size: 2rem; }
        .search-calendar-wrapper { flex-direction: column; }
        .contact-cards { grid-template-columns: 1fr; }
        .features-grid { grid-template-columns: 1fr; }
        .events-grid { grid-template-columns: 1fr; }
    }
</style>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<script>
    const calendarBtn = document.getElementById('calendarBtn');
    const calendarModal = document.getElementById('calendarModal');
    const closeCalendarBtn = document.getElementById('closeCalendarBtn');
    let calendar = null;

    calendarBtn.addEventListener('click', function() {
        calendarModal.style.display = 'flex';
        if (!calendar) {
            fetch('<?= base_url('get_calendar_events.php') ?>')
                .then(response => response.json())
                .then(events => {
                    const calendarEl = document.getElementById('calendar');
                    calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,listWeek'
                        },
                        events: events,
                        eventClick: function(info) {
                            if (info.event.url) window.location.href = info.event.url;
                        },
                        height: 'auto',
                        buttonText: {
                            today: 'Today',
                            month: 'Month',
                            listWeek: 'List'
                        }
                    });
                    calendar.render();
                })
                .catch(error => console.error('Error loading events:', error));
        } else {
            calendar.render();
        }
    });

    closeCalendarBtn.addEventListener('click', () => calendarModal.style.display = 'none');
    calendarModal.addEventListener('click', (e) => { if (e.target === calendarModal) calendarModal.style.display = 'none'; });

    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(() => target.scrollIntoView({ behavior: 'smooth' }), 100);
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

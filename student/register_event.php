<?php
/**
 * student/register_event.php
 * Handles event registration AND cancellation for students.
 *
 * Register:  GET  ?id=N       → confirms intent → POST to register
 * Cancel:    GET  ?cancel=N   → confirms via JS → GET redirect
 *
 * Protections:
 *  - Duplicate registration blocked via UNIQUE KEY in DB
 *  - Capacity check before inserting
 *  - Past-event registration blocked
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

require_login('student');

$uid  = current_user_id();
$conn = db_connect();

// ── CANCELLATION ────────────────────────────────────────────────
$cancel_id = (int)($_GET['cancel'] ?? 0);
if ($cancel_id > 0) {
    $stmt = $conn->prepare(
        "UPDATE registrations SET status = 'cancelled'
         WHERE user_id = ? AND event_id = ? AND status = 'registered'"
    );
    $stmt->bind_param('ii', $uid, $cancel_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        set_flash('info', 'Your registration has been cancelled.');
    } else {
        set_flash('error', 'Could not cancel — registration not found.');
    }
    $stmt->close();
    $conn->close();

    // Redirect back to where the user came from
    $ref = $_SERVER['HTTP_REFERER'] ?? base_url('student/dashboard.php');
    header('Location: ' . $ref);
    exit;
}

// ── REGISTER ─────────────────────────────────────────────────────
$event_id = (int)($_GET['id'] ?? 0);
if ($event_id <= 0) {
    set_flash('error', 'Invalid event.');
    header('Location: ' . base_url('index.php'));
    exit;
}

// Fetch event details
$stmt = $conn->prepare(
    "SELECT id, title, event_date, venue, capacity,
            (SELECT COUNT(*) FROM registrations r
             WHERE r.event_id = e.id AND r.status = 'registered') AS reg_count
     FROM events e
     WHERE e.id = ? LIMIT 1"
);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    set_flash('error', 'Event not found.');
    header('Location: ' . base_url('index.php'));
    exit;
}

// Check: past event
if (strtotime($event['event_date']) < time()) {
    set_flash('error', 'Registration is closed — this event has already taken place.');
    header('Location: ' . base_url('event_details.php?id=' . $event_id));
    exit;
}

// Check: already registered
$stmt = $conn->prepare(
    "SELECT id FROM registrations
     WHERE user_id = ? AND event_id = ? AND status = 'registered' LIMIT 1"
);
$stmt->bind_param('ii', $uid, $event_id);
$stmt->execute();
$stmt->store_result();
$already = $stmt->num_rows > 0;
$stmt->close();

if ($already) {
    set_flash('info', 'You are already registered for this event.');
    header('Location: ' . base_url('event_details.php?id=' . $event_id));
    exit;
}

// Check: capacity
$is_full = (int)$event['reg_count'] >= (int)$event['capacity'];

// ── POST → perform registration ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if ($is_full) {
        set_flash('error', 'Sorry, this event is now fully booked.');
        header('Location: ' . base_url('event_details.php?id=' . $event_id));
        exit;
    }

    // Insert with duplicate protection (UNIQUE KEY will throw if race condition)
    $stmt = $conn->prepare(
        "INSERT INTO registrations (user_id, event_id, status) VALUES (?, ?, 'registered')"
    );
    $stmt->bind_param('ii', $uid, $event_id);

    if ($stmt->execute()) {
        set_flash('success', 'You are now registered for "' . $event['title'] . '"! See you there 🎉');
        $stmt->close();
        $conn->close();
        header('Location: ' . base_url('student/dashboard.php'));
        exit;
    } else {
        // Duplicate key error = already registered (race condition)
        if ($conn->errno === 1062) {
            set_flash('info', 'You are already registered for this event.');
        } else {
            set_flash('error', 'Registration failed. Please try again.');
        }
        $stmt->close();
        $conn->close();
        header('Location: ' . base_url('event_details.php?id=' . $event_id));
        exit;
    }
}

$conn->close();

// ── GET → show confirmation page ─────────────────────────────────
$page_title = 'Register for Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:1.5rem;">
    <div class="form-card" style="max-width:560px;">
        <h2 class="form-title">Confirm Registration</h2>

        <?php if ($is_full): ?>
            <div class="alert alert-error">
                <strong>This event is fully booked.</strong><br>
                Unfortunately no spots are available.
            </div>
            <a href="<?= base_url('index.php') ?>" class="btn btn-ghost btn-full">Browse Other Events</a>
        <?php else: ?>

        <!-- Event summary -->
        <div class="event-info-box mb-3" style="background:var(--surface-alt);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;">
            <h3 style="margin-bottom:.75rem;"><?= e($event['title']) ?></h3>
            <div class="event-meta">
                <div class="event-meta-item">
                    <span class="meta-icon">📅</span>
                    <?= date('l, d F Y · g:i A', strtotime($event['event_date'])) ?>
                </div>
                <div class="event-meta-item">
                    <span class="meta-icon">📍</span> <?= e($event['venue']) ?>
                </div>
                <div class="event-meta-item">
                    <span class="meta-icon">👥</span>
                    <?= (int)$event['reg_count'] ?> / <?= (int)$event['capacity'] ?> spots filled
                </div>
            </div>
        </div>

        <p style="margin-bottom:1.5rem;color:var(--ink-2);">
            You are about to register for this event as
            <strong><?= e(current_user_name()) ?></strong>.
            You can cancel at any time from your dashboard.
        </p>

        <form method="POST" action="?id=<?= $event_id ?>">
            <input type="hidden" name="confirm" value="1">
            <div style="display:flex;gap:1rem;">
                <button type="submit" class="btn btn-primary">✓ Confirm Registration</button>
                <a href="<?= base_url('event_details.php?id=' . $event_id) ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

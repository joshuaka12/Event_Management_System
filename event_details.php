<?php
/**
 * event_details.php – Full event information with clean layout.
 * Includes back/forward navigation and organised content sections.
 */

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
$conn = db_connect();

// Fetch event with organizer and registration count
$stmt = $conn->prepare(
    "SELECT e.*, u.name AS organizer_name,
            (SELECT COUNT(*) FROM registrations r
             WHERE r.event_id = e.id AND r.status = 'registered') AS reg_count
     FROM events e
     JOIN users u ON u.id = e.created_by
     WHERE e.id = ? LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    $conn->close();
    set_flash('error', 'Event not found.');
    header('Location: ' . base_url('index.php'));
    exit;
}

// Check if current student is already registered
$already_registered = false;
if (is_logged_in() && current_role() === 'student') {
    $uid = current_user_id();
    $stmt = $conn->prepare(
        "SELECT id FROM registrations WHERE user_id = ? AND event_id = ? AND status = 'registered' LIMIT 1"
    );
    $stmt->bind_param('ii', $uid, $id);
    $stmt->execute();
    $stmt->store_result();
    $already_registered = $stmt->num_rows > 0;
    $stmt->close();
}

// Get next event (for forward button)
$next_id = null;
$stmt = $conn->prepare("SELECT id FROM events WHERE event_date > ? ORDER BY event_date ASC LIMIT 1");
$stmt->bind_param('s', $event['event_date']);
$stmt->execute();
$next = $stmt->get_result()->fetch_assoc();
if ($next) $next_id = $next['id'];
$stmt->close();
$conn->close();

$page_title = $event['title'];
$event_date = new DateTime($event['event_date']);
$is_past = $event_date < new DateTime();
$is_full = (int)$event['reg_count'] >= (int)$event['capacity'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero / Header Section -->
<div class="event-hero" style="background: linear-gradient(135deg, #1e1b18 0%, #3a2c24 100%); color: white; padding: 2rem 0; margin-top: calc(-1 * var(--nav-h)); padding-top: calc(var(--nav-h) + 2rem);">
    <div class="container">
        <div class="event-hero-content">
            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;"><?= e($event['title']) ?></h1>
            <p style="font-size: 1rem; opacity: 0.85;">
                Hosted by <strong><?= e($event['organizer_name']) ?></strong>
                <?php if ($is_past): ?>
                    <span class="event-badge badge-past" style="margin-left: 0.75rem;">Past Event</span>
                <?php else: ?>
                    <span class="event-badge badge-upcoming" style="margin-left: 0.75rem;">Upcoming</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="container event-detail-container" style="padding: 2rem 1.5rem;">
    <!-- Back & Forward Buttons -->
    <div class="nav-button-wrapper" style="margin-bottom: 2rem;">
        <?php back_button('index.php'); ?>
        <?php if ($next_id): ?>
            <?php forward_button('event_details.php?id=' . $next_id, 'Next Event →'); ?>
        <?php endif; ?>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="event-layout" style="display: grid; grid-template-columns: 1fr 360px; gap: 2rem;">
        <!-- LEFT COLUMN: Description & Organizer Info -->
        <div class="event-main">
            <!-- Description Card -->
            <div class="info-card" style="background: white; border-radius: 20px; border: 1px solid #ece5df; overflow: hidden; margin-bottom: 1.5rem;">
                <div style="padding: 1.5rem;">
                    <h3 style="font-family: 'DM Serif Display', serif; font-size: 1.4rem; margin-bottom: 1rem;">About This Event</h3>
                    <div style="line-height: 1.7; color: #4a4540; white-space: pre-wrap;">
                        <?= nl2br(e($event['description'])) ?>
                    </div>
                </div>
            </div>

            <!-- Organizer Info Card (optional) -->
            <div class="info-card" style="background: white; border-radius: 20px; border: 1px solid #ece5df;">
                <div style="padding: 1.5rem;">
                    <h3 style="font-family: 'DM Serif Display', serif; font-size: 1.2rem; margin-bottom: 0.75rem;">Organized by</h3>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: #f0ede8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">🎤</div>
                        <div>
                            <strong style="font-size: 1rem;"><?= e($event['organizer_name']) ?></strong>
                            <p style="color: #6b625c; font-size: 0.85rem;">Event Organizer</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin / Organizer Management Links (only for privileged roles) -->
            <?php if (is_logged_in() && in_array(current_role(), ['admin', 'organizer'])): ?>
            <div class="info-card" style="background: white; border-radius: 20px; border: 1px solid #ece5df; margin-top: 1.5rem;">
                <div style="padding: 1.5rem;">
                    <h3 style="font-family: 'DM Serif Display', serif; font-size: 1.2rem; margin-bottom: 1rem;">⚙ Manage Event</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="<?= base_url('organizer/create_event.php?edit=' . $event['id']) ?>" class="btn btn-ghost btn-sm">✏ Edit Event</a>
                        <a href="<?= base_url('organizer/manage_events.php') ?>" class="btn btn-ghost btn-sm">← My Events</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: Details & Registration -->
        <div class="event-sidebar">
            <!-- Event Details Card -->
            <div class="details-card" style="background: white; border-radius: 20px; border: 1px solid #ece5df; margin-bottom: 1.5rem;">
                <div style="padding: 1.5rem;">
                    <h3 style="font-family: 'DM Serif Display', serif; font-size: 1.2rem; margin-bottom: 1rem;">Event Details</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <span style="font-size: 1.2rem;">📅</span>
                            <div>
                                <div style="font-weight: 600; color: #1a1612;">Date & Time</div>
                                <div style="color: #4a4540;"><?= $event_date->format('l, d F Y') ?></div>
                                <div style="color: #4a4540;"><?= $event_date->format('g:i A') ?></div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <span style="font-size: 1.2rem;">📍</span>
                            <div>
                                <div style="font-weight: 600; color: #1a1612;">Venue</div>
                                <div style="color: #4a4540;"><?= e($event['venue']) ?></div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <span style="font-size: 1.2rem;">👥</span>
                            <div>
                                <div style="font-weight: 600; color: #1a1612;">Registrations</div>
                                <div style="color: #4a4540;">
                                    <strong><?= (int)$event['reg_count'] ?></strong> / <?= (int)$event['capacity'] ?> spots filled
                                    <?php if ($is_full): ?>
                                        <span class="event-badge badge-today" style="margin-left: 0.5rem;">FULL</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <span style="font-size: 1.2rem;">📌</span>
                            <div>
                                <div style="font-weight: 600; color: #1a1612;">Status</div>
                                <div style="color: #4a4540;">
                                    <?= $is_past ? 'Event has ended' : 'Accepting registrations' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Card (call to action) -->
            <div class="registration-card" style="background: white; border-radius: 20px; border: 1px solid #ece5df; text-align: center; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                <?php if (!is_logged_in()): ?>
                    <p class="text-muted" style="margin-bottom: 1rem;">Log in to register for this event.</p>
                    <a href="<?= base_url('login.php') ?>" class="btn btn-primary btn-full">Log In to Register</a>

                <?php elseif (current_role() === 'student'): ?>
                    <?php if ($is_past): ?>
                        <p class="text-muted">This event has already taken place.</p>
                    <?php elseif ($already_registered): ?>
                        <div class="alert alert-success" style="background: #e3f5ec; border-radius: 12px; padding: 0.75rem; margin-bottom: 1rem;">
                            ✅ You are registered for this event!
                        </div>
                        <a href="<?= base_url('student/register_event.php?cancel=' . $event['id']) ?>"
                           class="btn btn-ghost btn-full btn-sm"
                           onclick="return confirm('Cancel your registration for <?= e($event['title']) ?>?')">
                            Cancel Registration
                        </a>
                    <?php elseif ($is_full): ?>
                        <div class="alert alert-error" style="background: #fee9e6; border-radius: 12px; padding: 0.75rem;">
                            This event is fully booked.
                        </div>
                    <?php else: ?>
                        <a href="<?= base_url('student/register_event.php?id=' . $event['id']) ?>"
                           class="btn btn-primary btn-full" style="font-size: 1rem;">
                            ✦ Register for This Event
                        </a>
                        <p style="font-size: 0.8rem; color: #8a837c; margin-top: 1rem;">
                            Hurry! Only <?= (int)$event['capacity'] - (int)$event['reg_count'] ?> spots left.
                        </p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-muted">Registration is available for student accounts only.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Additional responsive adjustments */
    @media (max-width: 768px) {
        .event-layout {
            grid-template-columns: 1fr !important;
        }
        .event-sidebar {
            order: 2;
        }
        .event-main {
            order: 1;
        }
        .event-hero h1 {
            font-size: 1.5rem;
        }
    }
    .info-card, .details-card, .registration-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .info-card:hover, .details-card:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
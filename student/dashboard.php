<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('student');

$uid = current_user_id();
$conn = db_connect();

$stmt = $conn->prepare("SELECT COUNT(*) AS n FROM registrations WHERE user_id=? AND status='registered'");
$stmt->bind_param('i', $uid); $stmt->execute();
$total_regs = (int)$stmt->get_result()->fetch_assoc()['n'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS n FROM registrations r JOIN events e ON e.id=r.event_id WHERE r.user_id=? AND r.status='registered' AND e.event_date>=NOW()");
$stmt->bind_param('i', $uid); $stmt->execute();
$upcoming_regs = (int)$stmt->get_result()->fetch_assoc()['n'];
$stmt->close();

$total_events = (int)$conn->query("SELECT COUNT(*) AS n FROM events")->fetch_assoc()['n'];

$stmt = $conn->prepare("SELECT e.id, e.title, e.event_date, e.venue, e.description, e.capacity, r.registered_at, r.status, (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id=e.id AND r2.status='registered') AS reg_count FROM registrations r JOIN events e ON e.id=r.event_id WHERE r.user_id=? AND r.status='registered' ORDER BY e.event_date ASC");
$stmt->bind_param('i', $uid); $stmt->execute();
$my_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT e.id, e.title, e.event_date, e.venue, e.capacity, (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status='registered') AS reg_count FROM events e WHERE e.event_date>=NOW() AND e.id NOT IN (SELECT event_id FROM registrations WHERE user_id=? AND status='registered') ORDER BY e.event_date ASC LIMIT 6");
$stmt->bind_param('i', $uid); $stmt->execute();
$suggested_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$page_title = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:1rem;">
    <!-- Only back button -->
    <div class="nav-button-wrapper">
        <?php back_button('index.php'); ?>
    </div>

    <div style="margin-bottom:2.5rem; border-bottom:2px solid #ece5df; padding-bottom:1rem;">
        <h1 style="font-size:2rem; font-weight:500;">Hello, <?= e(current_user_name()) ?> 👋</h1>
        <p style="color:#6b625c;">Manage your registrations and discover new events.</p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1.5rem; margin-bottom:3rem;">
        <div style="background:white; border-radius:24px; padding:1.25rem; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.04);"><div style="font-size:2.5rem; font-weight:600;"><?= $total_regs ?></div><div style="color:#8a837c;">Events Joined</div></div>
        <div style="background:white; border-radius:24px; padding:1.25rem; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.04);"><div style="font-size:2.5rem; font-weight:600;"><?= $upcoming_regs ?></div><div style="color:#8a837c;">Upcoming</div></div>
        <div style="background:white; border-radius:24px; padding:1.25rem; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.04);"><div style="font-size:2.5rem; font-weight:600;"><?= $total_events ?></div><div style="color:#8a837c;">Total Events</div></div>
    </div>

    <div style="margin-bottom:3rem;">
        <div style="display:flex; justify-content:space-between; align-items:baseline; flex-wrap:wrap; margin-bottom:1.5rem;"><h2 style="font-size:1.5rem;">📌 My Events</h2><a href="<?= base_url('index.php') ?>" class="btn btn-outline btn-sm">Browse All Events →</a></div>
        <?php if (empty($my_events)): ?>
            <div style="background:#fefcf9; border-radius:28px; padding:3rem 2rem; text-align:center; border:1px solid #ece5df;"><div style="font-size:3.5rem;">🌟</div><h3>No registrations yet</h3><p>Explore upcoming events and join the ones you love.</p><a href="<?= base_url('index.php') ?>" class="btn btn-primary" style="margin-top:1rem;">Find Events</a></div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1.5rem;">
                <?php foreach ($my_events as $ev):
                    $is_past = strtotime($ev['event_date']) < time();
                    $reg_count = (int)$ev['reg_count'];
                    $capacity = (int)$ev['capacity'];
                ?>
                <div style="background:white; border-radius:20px; border:1px solid #ece5df; overflow:hidden;">
                    <div style="padding:1.25rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem;"><span class="event-badge <?= $is_past ? 'badge-past' : 'badge-upcoming' ?>"><?= $is_past ? 'Past' : 'Upcoming' ?></span><?php if (!$is_past && $ev['status'] === 'registered'): ?><a href="<?= base_url('student/register_event.php?cancel=' . $ev['id']) ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Cancel registration?')">Cancel</a><?php endif; ?></div>
                        <h3 style="font-size:1.2rem;"><?= e($ev['title']) ?></h3>
                        <div style="margin:0.75rem 0; font-size:0.85rem; color:#5e5a55;"><div>📅 <?= date('D, d M Y · g:i A', strtotime($ev['event_date'])) ?></div><div>📍 <?= e($ev['venue']) ?></div><div>👥 <?= $reg_count ?> / <?= $capacity ?> registered</div></div>
                        <p style="color:#6b625c; font-size:0.85rem;"><?= e(substr($ev['description'],0,90)) ?>…</p>
                    </div>
                    <div style="padding:0.75rem 1.25rem; border-top:1px solid #ece5df; background:#fefcf9; display:flex; justify-content:space-between;"><a href="<?= base_url('event_details.php?id=' . $ev['id']) ?>" class="btn btn-outline btn-sm">Details →</a><?php if ($is_past): ?><span style="color:#8a837c;">Event ended</span><?php elseif ($reg_count >= $capacity): ?><span class="badge-today">Full</span><?php endif; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($suggested_events)): ?>
    <div><h2 style="font-size:1.5rem; margin-bottom:1.5rem;">🔥 Discover More Events</h2><div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1.5rem;">
        <?php foreach ($suggested_events as $ev):
            $reg_count = (int)$ev['reg_count'];
            $capacity = (int)$ev['capacity'];
            $is_full = $reg_count >= $capacity;
        ?>
        <div style="background:white; border-radius:20px; border:1px solid #ece5df; overflow:hidden;"><div style="padding:1.25rem;"><span class="event-badge badge-upcoming">Upcoming</span><h3 style="font-size:1.2rem; margin:0.5rem 0;"><?= e($ev['title']) ?></h3><div style="margin:0.5rem 0; font-size:0.85rem; color:#5e5a55;"><div>📅 <?= date('D, d M Y · g:i A', strtotime($ev['event_date'])) ?></div><div>📍 <?= e($ev['venue']) ?></div><div>👥 <?= $reg_count ?> / <?= $capacity ?> spots</div></div></div><div style="padding:0.75rem 1.25rem; border-top:1px solid #ece5df; background:#fefcf9; display:flex; justify-content:space-between;"><a href="<?= base_url('event_details.php?id=' . $ev['id']) ?>" class="btn btn-outline btn-sm">Details →</a><?php if (!$is_full): ?><a href="<?= base_url('student/register_event.php?id=' . $ev['id']) ?>" class="btn btn-primary btn-sm">Register</a><?php else: ?><span class="badge-today">Full</span><?php endif; ?></div></div>
        <?php endforeach; ?>
    </div></div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
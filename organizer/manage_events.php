<?php
/**
 * organizer/manage_events.php
 * Lists all events created by this organizer with registration details.
 * Also shows a list of registered students per event.
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

require_login(['organizer', 'admin']);

$uid  = current_user_id();
$conn = db_connect();

// Fetch all events by this organizer
$stmt = $conn->prepare(
    "SELECT e.id, e.title, e.event_date, e.venue, e.capacity,
            (SELECT COUNT(*) FROM registrations r
             WHERE r.event_id = e.id AND r.status = 'registered') AS reg_count
     FROM events e
     WHERE e.created_by = ?
     ORDER BY e.event_date DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$my_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// If ?view_regs=N → fetch registered students for that event
$view_id  = (int)($_GET['view_regs'] ?? 0);
$attendees = [];
$selected_event = null;

if ($view_id > 0) {
    // Verify organizer owns this event
    $stmt = $conn->prepare("SELECT title FROM events WHERE id = ? AND created_by = ? LIMIT 1");
    $stmt->bind_param('ii', $view_id, $uid);
    $stmt->execute();
    $selected_event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($selected_event) {
        $stmt = $conn->prepare(
            "SELECT u.name, u.email, r.registered_at, r.status
             FROM registrations r
             JOIN users u ON u.id = r.user_id
             WHERE r.event_id = ?
             ORDER BY r.registered_at ASC"
        );
        $stmt->bind_param('i', $view_id);
        $stmt->execute();
        $attendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$conn->close();

$page_title = 'Manage Events';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:1.5rem;">
    <div class="dash-header">
        <div>
            <h2 class="dash-title">Manage Events</h2>
            <p class="text-muted">View registrations and manage your events</p>
        </div>
        <a href="<?= base_url('organizer/create_event.php') ?>" class="btn btn-primary">+ Create Event</a>
    </div>

    <!-- Events Table -->
    <?php if (empty($my_events)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No events yet</h3>
            <p><a href="<?= base_url('organizer/create_event.php') ?>">Create your first event</a></p>
        </div>
    <?php else: ?>
    <div class="table-wrapper mb-4">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Registered / Cap</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_events as $ev):
                    $is_past = strtotime($ev['event_date']) < time();
                    $pct     = $ev['capacity'] > 0
                               ? min(100, round($ev['reg_count'] / $ev['capacity'] * 100))
                               : 0;
                ?>
                <tr>
                    <td><strong><?= e($ev['title']) ?></strong></td>
                    <td><?= date('d M Y · g:i A', strtotime($ev['event_date'])) ?></td>
                    <td><?= e($ev['venue']) ?></td>
                    <td>
                        <?= (int)$ev['reg_count'] ?> / <?= (int)$ev['capacity'] ?>
                        <div style="height:4px;background:var(--border);border-radius:2px;margin-top:4px;width:80px;">
                            <div style="height:4px;background:var(--teal);border-radius:2px;width:<?= $pct ?>%;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="event-badge <?= $is_past ? 'badge-past' : 'badge-upcoming' ?>">
                            <?= $is_past ? 'Past' : 'Upcoming' ?>
                        </span>
                    </td>
                    <td>
                        <div class="td-actions">
                            <a href="?view_regs=<?= $ev['id'] ?>" class="btn btn-teal btn-sm">Attendees</a>
                            <a href="<?= base_url('organizer/create_event.php?edit=' . $ev['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                            <a href="<?= base_url('organizer/delete_event.php?id=' . $ev['id']) ?>"
                               class="btn btn-danger btn-sm"
                               data-confirm="Delete '<?= e($ev['title']) ?>'?">
                               Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Attendees sub-table -->
    <?php if ($selected_event): ?>
    <h3 class="section-title mt-3">
        Attendees for: <?= e($selected_event['title']) ?>
    </h3>

    <?php if (empty($attendees)): ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>No registrations yet</h3>
        </div>
    <?php else: ?>
    <div class="table-wrapper mb-4">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Registered At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendees as $i => $att): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($att['name']) ?></td>
                    <td><?= e($att['email']) ?></td>
                    <td><?= date('d M Y g:i A', strtotime($att['registered_at'])) ?></td>
                    <td>
                        <span class="event-badge <?= $att['status'] === 'registered' ? 'badge-upcoming' : 'badge-past' ?>">
                            <?= e($att['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

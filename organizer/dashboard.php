<?php
/**
 * organizer/dashboard.php
 * Professional, modern dashboard for event organizers.
 * Features: stats cards, search/filter, event table, notifications, print report.
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$uid = current_user_id();
$conn = db_connect();

// --- Stats: total events, upcoming, total registrations ---
$stats = [];
$res = $conn->query("SELECT COUNT(*) AS c FROM events WHERE created_by = $uid AND deleted_at IS NULL");
$stats['total_events'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM events WHERE created_by = $uid AND event_date >= NOW() AND deleted_at IS NULL");
$stats['upcoming_events'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM registrations r JOIN events e ON e.id = r.event_id WHERE e.created_by = $uid AND r.status = 'registered'");
$stats['total_registrations'] = $res->fetch_assoc()['c'];

// --- Upcoming notifications (next 7 days) ---
$notifications = [];
$stmt = $conn->prepare("SELECT id, title, event_date FROM events WHERE created_by = ? AND deleted_at IS NULL AND event_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY event_date ASC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Search & Filter parameters ---
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for events list
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations WHERE event_id=e.id AND status='registered') AS reg_count
        FROM events e
        WHERE e.created_by = ? AND e.deleted_at IS NULL";
$params = [$uid];
$types = 'i';

if ($search) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($category) {
    $sql .= " AND e.category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($status_filter) {
    $sql .= " AND e.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($date_from) {
    $sql .= " AND e.event_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to) {
    $sql .= " AND e.event_date <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}
$sql .= " ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get distinct categories for filter dropdown
$categories = [];
$res = $conn->query("SELECT DISTINCT category FROM events WHERE created_by = $uid AND deleted_at IS NULL");
while ($row = $res->fetch_assoc()) $categories[] = $row['category'];
$conn->close();

$page_title = 'Organizer Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
    <!-- Back Button -->
    <div class="nav-button-wrapper">
        <?php back_button('index.php'); ?>
    </div>

    <!-- Header with Create Button -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 1.8rem; margin-bottom: 0.25rem;">Organizer Dashboard</h1>
            <p class="text-muted">Manage your events, track registrations, and grow your impact.</p>
        </div>
        <a href="<?= base_url('organizer/create_event.php') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.2rem;">+</span> Create New Event
        </a>
    </div>

    <!-- Stats Cards (Modern, clean) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; border-radius: 20px; padding: 1.25rem; border: 1px solid #ece5df; transition: 0.2s;">
            <div style="font-size: 2.2rem; font-weight: 700; color: #c13b2b;"><?= $stats['total_events'] ?></div>
            <div style="color: #6b625c; margin-top: 0.5rem;">Total Events</div>
        </div>
        <div class="stat-card" style="background: white; border-radius: 20px; padding: 1.25rem; border: 1px solid #ece5df;">
            <div style="font-size: 2.2rem; font-weight: 700; color: #1e7a6e;"><?= $stats['upcoming_events'] ?></div>
            <div style="color: #6b625c; margin-top: 0.5rem;">Upcoming Events</div>
        </div>
        <div class="stat-card" style="background: white; border-radius: 20px; padding: 1.25rem; border: 1px solid #ece5df;">
            <div style="font-size: 2.2rem; font-weight: 700; color: #1d4e89;"><?= $stats['total_registrations'] ?></div>
            <div style="color: #6b625c; margin-top: 0.5rem;">Total Registrations</div>
        </div>
    </div>

    <!-- Upcoming Notifications (if any) -->
    <?php if (!empty($notifications)): ?>
        <div style="background: #e8f0fe; border-left: 4px solid #1d4e89; border-radius: 16px; padding: 1rem; margin-bottom: 1.5rem;">
            <strong style="display: block; margin-bottom: 0.5rem;">📢 Upcoming Events (next 7 days)</strong>
            <ul style="margin-left: 1.5rem;">
                <?php foreach ($notifications as $notif): ?>
                    <li><?= e($notif['title']) ?> – <strong><?= date('d M Y', strtotime($notif['event_date'])) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Advanced Search & Filter Bar -->
    <div style="background: white; border-radius: 24px; padding: 1.25rem; margin-bottom: 2rem; border: 1px solid #ece5df;">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <div style="flex: 2; min-width: 180px;">
                <label style="display: block; font-size: 0.75rem; margin-bottom: 0.25rem; color: #6b625c;">Search</label>
                <input type="text" name="search" placeholder="Title, description, venue..." value="<?= e($search) ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #e0dbd4; border-radius: 30px;">
            </div>
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 0.75rem; margin-bottom: 0.25rem; color: #6b625c;">Category</label>
                <select name="category" style="width: 100%; padding: 0.6rem; border-radius: 30px; border: 1px solid #e0dbd4;">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 0.75rem; margin-bottom: 0.25rem; color: #6b625c;">Status</label>
                <select name="status" style="width: 100%; padding: 0.6rem; border-radius: 30px; border: 1px solid #e0dbd4;">
                    <option value="">All</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 0.75rem; margin-bottom: 0.25rem; color: #6b625c;">From Date</label>
                <input type="date" name="date_from" value="<?= e($date_from) ?>" style="width: 100%; padding: 0.6rem; border-radius: 30px; border: 1px solid #e0dbd4;">
            </div>
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 0.75rem; margin-bottom: 0.25rem; color: #6b625c;">To Date</label>
                <input type="date" name="date_to" value="<?= e($date_to) ?>" style="width: 100%; padding: 0.6rem; border-radius: 30px; border: 1px solid #e0dbd4;">
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 42px;">Filter</button>
                <a href="<?= base_url('organizer/dashboard.php') ?>" class="btn btn-ghost btn-sm" style="height: 42px;">Clear</a>
            </div>
        </form>
    </div>

    <!-- Events Table (Clean, modern) -->
    <div style="background: white; border-radius: 20px; border: 1px solid #ece5df; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f6f2; border-bottom: 1px solid #ece5df;">
                    <th style="padding: 1rem;">Title</th>
                    <th style="padding: 1rem;">Date & Time</th>
                    <th style="padding: 1rem;">Location</th>
                    <th style="padding: 1rem;">Category</th>
                    <th style="padding: 1rem;">Registrations</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr style="border-bottom: 1px solid #ece5df;">
                    <td style="padding: 1rem;"><strong><?= e($ev['title']) ?></strong></td>
                    <td style="padding: 1rem;"><?= date('d M Y · g:i A', strtotime($ev['event_date'])) ?></td>
                    <td style="padding: 1rem;"><?= e($ev['venue']) ?></td>
                    <td style="padding: 1rem;"><?= e($ev['category']) ?></td>
                    <td style="padding: 1rem;"><?= (int)$ev['reg_count'] ?> / <?= (int)$ev['capacity'] ?></td>
                    <td style="padding: 1rem;">
                        <span class="badge badge-<?= $ev['status'] ?>" style="background: <?= $ev['status'] === 'approved' ? '#e3f5ec' : ($ev['status'] === 'pending' ? '#fef3c7' : '#fee9e6') ?>; color: <?= $ev['status'] === 'approved' ? '#1e7a48' : ($ev['status'] === 'pending' ? '#b45309' : '#c13b2b') ?>; padding: 0.2rem 0.6rem; border-radius: 30px; font-size: 0.7rem;">
                            <?= ucfirst($ev['status']) ?>
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="<?= base_url('event_details.php?id='.$ev['id']) ?>" class="btn btn-ghost btn-sm">View</a>
                            <a href="<?= base_url('organizer/edit_event.php?id='.$ev['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                            <button class="btn btn-danger btn-sm delete-event" data-id="<?= $ev['id'] ?>" data-title="<?= e($ev['title']) ?>">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                <tr><td colspan="7" style="padding: 2rem; text-align: center; color: #6b625c;">No events found. Create your first event!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Print Report Button -->
    <div style="text-align: right; margin-top: 1.5rem;">
        <a href="<?= base_url('organizer/print_report.php') ?>" class="btn btn-outline" target="_blank">🖨️ Print Report</a>
    </div>
</div>

<!-- Modal for Delete Reason -->
<div id="deleteModal" class="modal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index:2000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 20px; max-width: 500px; width: 90%; padding: 1.5rem;">
        <div class="modal-header" style="font-size: 1.2rem; font-weight: bold; margin-bottom: 1rem;">Delete Event</div>
        <p>Event: <strong id="deleteEventTitle"></strong></p>
        <textarea id="deleteReason" rows="4" placeholder="Please provide a reason for deletion..." style="width: 100%; padding: 0.5rem; border: 1px solid #e0dbd4; border-radius: 12px;"></textarea>
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
            <button id="confirmDelete" class="btn btn-danger">Delete Permanently</button>
            <button id="closeDeleteModal" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<script>
    // Delete modal handling
    let deleteEventId = null;
    document.querySelectorAll('.delete-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            deleteEventId = btn.dataset.id;
            document.getElementById('deleteEventTitle').innerText = btn.dataset.title;
            document.getElementById('deleteModal').style.display = 'flex';
        });
    });
    document.getElementById('confirmDelete').addEventListener('click', () => {
        const reason = document.getElementById('deleteReason').value.trim();
        if (!reason) { alert('Please provide a reason for deletion.'); return; }
        window.location.href = '<?= base_url('organizer/delete_event.php?id=') ?>' + deleteEventId + '&reason=' + encodeURIComponent(reason);
    });
    document.getElementById('closeDeleteModal').addEventListener('click', () => {
        document.getElementById('deleteModal').style.display = 'none';
        document.getElementById('deleteReason').value = '';
    });
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === document.getElementById('deleteModal')) {
            document.getElementById('deleteModal').style.display = 'none';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
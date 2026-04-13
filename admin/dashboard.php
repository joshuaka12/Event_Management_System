<?php
/**
 * admin/dashboard.php – Full admin dashboard with soft delete, restore, permanent delete, charts, reject with reason.
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$conn = db_connect();
$uid = current_user_id();

// ==================== STATS ====================
$stats = [];
$res = $conn->query("SELECT COUNT(*) AS c FROM events WHERE deleted_at IS NULL");
$stats['total_events'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL");
$stats['total_users'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='organizer' AND is_approved=0 AND deleted_at IS NULL");
$stats['pending_organizers'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='organizer' AND is_approved=1 AND deleted_at IS NULL");
$stats['approved_organizers'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM events WHERE status='pending' AND deleted_at IS NULL");
$stats['pending_events'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM events WHERE deleted_at IS NOT NULL");
$stats['trashed_events'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NOT NULL");
$stats['trashed_users'] = $res->fetch_assoc()['c'];

// ==================== FETCH DATA ====================
$events = $conn->query("
    SELECT e.*, u.name AS organizer_name,
           (SELECT COUNT(*) FROM registrations WHERE event_id=e.id AND status='registered') AS reg_count
    FROM events e
    JOIN users u ON u.id = e.created_by
    WHERE e.deleted_at IS NULL
    ORDER BY e.event_date DESC
")->fetch_all(MYSQLI_ASSOC);

$users = $conn->query("
    SELECT id, name, email, role, is_approved, rejection_reason, created_at
    FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$trashed_events = $conn->query("
    SELECT e.*, u.name AS organizer_name
    FROM events e
    JOIN users u ON u.id = e.created_by
    WHERE e.deleted_at IS NOT NULL
    ORDER BY e.deleted_at DESC
")->fetch_all(MYSQLI_ASSOC);

$trashed_users = $conn->query("
    SELECT id, name, email, role, deleted_at
    FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Chart data: monthly registrations
$chart_labels = [];
$chart_counts = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $chart_labels[] = date('M Y', strtotime("-$i months"));
    $res = $conn->query("
        SELECT COUNT(*) AS c FROM registrations
        WHERE DATE_FORMAT(registered_at, '%Y-%m') = '$month' AND status='registered'
    ");
    $chart_counts[] = (int)$res->fetch_assoc()['c'];
}
$chart_labels_json = json_encode($chart_labels);
$chart_counts_json = json_encode($chart_counts);

// Event status pie chart
$status_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$res = $conn->query("SELECT status, COUNT(*) AS c FROM events WHERE deleted_at IS NULL GROUP BY status");
while ($row = $res->fetch_assoc()) {
    if (isset($status_counts[$row['status']])) $status_counts[$row['status']] = (int)$row['c'];
}
$pie_labels = json_encode(array_keys($status_counts));
$pie_data = json_encode(array_values($status_counts));

$conn->close();

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 1.2rem; margin-bottom: 2rem; }
    .stat-card { background: white; border-radius: 20px; padding: 1.2rem; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #ece5df; }
    .stat-number { font-size: 2rem; font-weight: 700; color: #1a1612; }
    .stat-label { color: #6b625c; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .chart-container { background: white; border-radius: 20px; padding: 1.5rem; border: 1px solid #ece5df; margin-bottom: 2rem; }
    .section-title { font-size: 1.3rem; margin: 1.5rem 0 1rem; border-left: 4px solid #c13b2b; padding-left: 0.8rem; }
    .table-wrapper { overflow-x: auto; background: white; border-radius: 16px; border: 1px solid #ece5df; margin-bottom: 2rem; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #ece5df; }
    th { background: #f8f6f2; font-weight: 600; }
    .badge { padding: 0.2rem 0.6rem; border-radius: 30px; font-size: 0.7rem; font-weight: 600; }
    .badge-pending { background: #fef3c7; color: #b45309; }
    .badge-approved { background: #e3f5ec; color: #1e7a48; }
    .badge-rejected { background: #fee9e6; color: #c13b2b; }
    .badge-trash { background: #e0dbd4; color: #4a4540; }
    .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index:2000; align-items: center; justify-content: center; }
    .modal-content { background: white; border-radius: 20px; max-width: 500px; width: 90%; padding: 1.5rem; }
    .modal-header { font-size: 1.2rem; font-weight: bold; margin-bottom: 1rem; }
    .modal textarea { width: 100%; padding: 0.5rem; border: 1px solid #e0dbd4; border-radius: 12px; }
    .modal-buttons { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
    .trash-section { margin-top: 1rem; border-top: 2px dashed #e0dbd4; padding-top: 1rem; }
    .toggle-trash { cursor: pointer; background: #f0ede8; border: none; padding: 0.3rem 1rem; border-radius: 30px; margin-bottom: 1rem; }
    .rejection-reason { font-size: 0.7rem; color: #c13b2b; margin-top: 0.2rem; }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
</style>

<div class="container" style="padding-top: 1rem;">
    <div class="nav-button-wrapper">
        <?php back_button('index.php'); ?>
    </div>

    <h1 style="font-size: 1.8rem; margin-bottom: 0.25rem;">Admin Dashboard</h1>
    <p class="text-muted" style="margin-bottom: 1.5rem;">Manage events, users, organizers, and view reports.</p>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?= $stats['total_events'] ?></div><div class="stat-label">Total Events</div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['total_users'] ?></div><div class="stat-label">Total Users</div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['pending_organizers'] ?></div><div class="stat-label">Pending Organizers</div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['approved_organizers'] ?></div><div class="stat-label">Approved Organizers</div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['pending_events'] ?></div><div class="stat-label">Pending Events</div></div>
        <div class="stat-card"><div class="stat-number"><?= $stats['trashed_events'] ?></div><div class="stat-label">Trash (Events)</div></div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="chart-container">
            <h3>Monthly Registrations</h3>
            <canvas id="regChart" height="200"></canvas>
        </div>
        <div class="chart-container">
            <h3>Event Status Breakdown</h3>
            <canvas id="statusPieChart" height="200"></canvas>
        </div>
    </div>

    <!-- ===== EVENTS TABLE ===== -->
    <h2 class="section-title">📅 All Events</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>ID</th><th>Title</th><th>Organizer</th><th>Date</th><th>Registrations</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?= $ev['id'] ?></td>
                    <td><?= e($ev['title']) ?></td>
                    <td><?= e($ev['organizer_name']) ?></td>
                    <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                    <td><?= (int)$ev['reg_count'] ?> / <?= (int)$ev['capacity'] ?></td>
                    <td><span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
                    <td class="action-buttons">
                        <a href="<?= base_url('event_details.php?id='.$ev['id']) ?>" class="btn btn-ghost btn-sm">View</a>
                        <?php if ($ev['status'] === 'pending'): ?>
                            <a href="#" class="btn btn-primary btn-sm approve-event" data-id="<?= $ev['id'] ?>">Approve</a>
                            <a href="#" class="btn btn-danger btn-sm reject-event" data-id="<?= $ev['id'] ?>" data-title="<?= e($ev['title']) ?>">Reject</a>
                        <?php endif; ?>
                        <a href="#" class="btn btn-danger btn-sm soft-delete-event" data-id="<?= $ev['id'] ?>" data-title="<?= e($ev['title']) ?>">Trash</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?><tr><td colspan="7" class="text-center">No events found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ===== USERS & ORGANIZERS TABLE ===== -->
    <h2 class="section-title">👥 Users & Organizers</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Organizer Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= ucfirst($user['role']) ?></td>
                    <td>
                        <?php if ($user['role'] === 'organizer'): ?>
                            <?php if ($user['is_approved']): ?>
                                <span class="badge badge-approved">Approved</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Pending</span>
                                <?php if (!empty($user['rejection_reason'])): ?>
                                    <div class="rejection-reason" title="Rejection reason">Rejected: <?= e(substr($user['rejection_reason'],0,50)) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="action-buttons">
                        <?php if ($user['role'] === 'organizer' && !$user['is_approved']): ?>
                            <a href="#" class="btn btn-primary btn-sm approve-organizer" data-id="<?= $user['id'] ?>" data-name="<?= e($user['name']) ?>">Approve</a>
                            <a href="#" class="btn btn-danger btn-sm reject-organizer" data-id="<?= $user['id'] ?>" data-name="<?= e($user['name']) ?>">Reject</a>
                        <?php endif; ?>
                        <?php if ($user['role'] !== 'admin'): ?>
                            <a href="#" class="btn btn-danger btn-sm soft-delete-user" data-id="<?= $user['id'] ?>" data-name="<?= e($user['name']) ?>">Trash</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Trash Section (with Restore and Permanent Delete) -->
    <div class="trash-section">
        <button id="toggleTrashBtn" class="toggle-trash">🗑️ Hide Trash</button>
        <div id="trashContent">
            <h2 class="section-title">Trashed Events</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>ID</th><th>Title</th><th>Organizer</th><th>Deleted At</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($trashed_events as $tev): ?>
                        <tr>
                            <td><?= $tev['id'] ?></td>
                            <td><?= e($tev['title']) ?></td>
                            <td><?= e($tev['organizer_name']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($tev['deleted_at'])) ?></td>
                            <td class="action-buttons">
                                <a href="#" class="btn btn-primary btn-sm restore-event" data-id="<?= $tev['id'] ?>">Restore</a>
                                <a href="#" class="btn btn-danger btn-sm permanent-delete-event" data-id="<?= $tev['id'] ?>" data-title="<?= e($tev['title']) ?>">Permanent Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($trashed_events)): ?><tr><td colspan="5">No trashed events.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title">Trashed Users</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Deleted At</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($trashed_users as $tuser): ?>
                        <tr>
                            <td><?= $tuser['id'] ?></td>
                            <td><?= e($tuser['name']) ?></td>
                            <td><?= e($tuser['email']) ?></td>
                            <td><?= ucfirst($tuser['role']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($tuser['deleted_at'])) ?></td>
                            <td class="action-buttons">
                                <a href="#" class="btn btn-primary btn-sm restore-user" data-id="<?= $tuser['id'] ?>">Restore</a>
                                <a href="#" class="btn btn-danger btn-sm permanent-delete-user" data-id="<?= $tuser['id'] ?>" data-name="<?= e($tuser['name']) ?>">Permanent Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Print Report Button -->
    <div style="text-align: center; margin: 2rem 0;">
        <a href="<?= base_url('admin/print_report.php') ?>" class="btn btn-primary" target="_blank">🖨️ Print Full Report</a>
    </div>
</div>

<!-- Modal for Event Rejection -->
<div id="rejectEventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Reject Event</div>
        <p>Event: <strong id="rejectEventTitle"></strong></p>
        <textarea id="eventRejectionReason" rows="4" placeholder="Provide reason for rejection..."></textarea>
        <div class="modal-buttons">
            <button id="confirmEventReject" class="btn btn-danger">Reject</button>
            <button id="closeEventModal" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<!-- Modal for Organizer Rejection -->
<div id="rejectOrganizerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Reject Organizer Application</div>
        <p>Organizer: <strong id="rejectOrganizerName"></strong></p>
        <textarea id="organizerRejectionReason" rows="4" placeholder="Provide reason for rejection..."></textarea>
        <div class="modal-buttons">
            <button id="confirmOrganizerReject" class="btn btn-danger">Reject</button>
            <button id="closeOrganizerModal" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Charts
    new Chart(document.getElementById('regChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= $chart_labels_json ?>,
            datasets: [{ label: 'Registrations', data: <?= $chart_counts_json ?>, borderColor: '#c13b2b', backgroundColor: 'rgba(193,59,43,0.1)', tension: 0.3 }]
        }
    });
    new Chart(document.getElementById('statusPieChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?= $pie_labels ?>,
            datasets: [{ data: <?= $pie_data ?>, backgroundColor: ['#fef3c7', '#e3f5ec', '#fee9e6'] }]
        }
    });

    // Toggle trash visibility
    const toggleBtn = document.getElementById('toggleTrashBtn');
    const trashContent = document.getElementById('trashContent');
    if (toggleBtn && trashContent) {
        toggleBtn.addEventListener('click', () => {
            if (trashContent.style.display === 'none') {
                trashContent.style.display = 'block';
                toggleBtn.textContent = '🗑️ Hide Trash';
            } else {
                trashContent.style.display = 'none';
                toggleBtn.textContent = '🗑️ Show Trash';
            }
        });
    }

    // Approve Event
    document.querySelectorAll('.approve-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Approve this event?')) {
                window.location.href = '<?= base_url('admin/approve_event.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Reject Event modal
    let rejectEventId = null;
    document.querySelectorAll('.reject-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            rejectEventId = btn.dataset.id;
            document.getElementById('rejectEventTitle').innerText = btn.dataset.title;
            document.getElementById('rejectEventModal').style.display = 'flex';
        });
    });
    document.getElementById('confirmEventReject').addEventListener('click', () => {
        const reason = document.getElementById('eventRejectionReason').value.trim();
        if (!reason) { alert('Please provide a rejection reason.'); return; }
        window.location.href = '<?= base_url('admin/reject_event.php?id=') ?>' + rejectEventId + '&reason=' + encodeURIComponent(reason);
    });
    document.getElementById('closeEventModal').addEventListener('click', () => {
        document.getElementById('rejectEventModal').style.display = 'none';
        document.getElementById('eventRejectionReason').value = '';
    });

    // Reject Organizer modal
    let rejectOrganizerId = null;
    document.querySelectorAll('.reject-organizer').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            rejectOrganizerId = btn.dataset.id;
            document.getElementById('rejectOrganizerName').innerText = btn.dataset.name;
            document.getElementById('rejectOrganizerModal').style.display = 'flex';
        });
    });
    document.getElementById('confirmOrganizerReject').addEventListener('click', () => {
        const reason = document.getElementById('organizerRejectionReason').value.trim();
        if (!reason) { alert('Please provide a rejection reason.'); return; }
        window.location.href = '<?= base_url('admin/reject_organizer.php?id=') ?>' + rejectOrganizerId + '&reason=' + encodeURIComponent(reason);
    });
    document.getElementById('closeOrganizerModal').addEventListener('click', () => {
        document.getElementById('rejectOrganizerModal').style.display = 'none';
        document.getElementById('organizerRejectionReason').value = '';
    });

    // Approve organizer
    document.querySelectorAll('.approve-organizer').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm(`Approve organizer "${btn.dataset.name}"?`)) {
                window.location.href = '<?= base_url('admin/approve_organizer.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Soft delete event (move to trash)
    document.querySelectorAll('.soft-delete-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm(`Move "${btn.dataset.title}" to trash? You can restore later.`)) {
                window.location.href = '<?= base_url('admin/delete_event.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Restore event from trash
    document.querySelectorAll('.restore-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Restore this event?')) {
                window.location.href = '<?= base_url('admin/restore_event.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Permanent delete event (hard delete)
    document.querySelectorAll('.permanent-delete-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm(`⚠️ PERMANENT DELETE: "${btn.dataset.title}" will be removed FOREVER. This cannot be undone. Continue?`)) {
                window.location.href = '<?= base_url('admin/permanent_delete_event.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Soft delete user (move to trash)
    document.querySelectorAll('.soft-delete-user').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm(`Move user "${btn.dataset.name}" to trash? They will be unable to log in.`)) {
                window.location.href = '<?= base_url('admin/delete_user.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Restore user from trash
    document.querySelectorAll('.restore-user').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Restore this user?')) {
                window.location.href = '<?= base_url('admin/restore_user.php?id=') ?>' + btn.dataset.id;
            }
        });
    });

    // Permanent delete user (hard delete)
    document.querySelectorAll('.permanent-delete-user').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm(`⚠️ PERMANENT DELETE: User "${btn.dataset.name}" will be removed FOREVER. All their data will be lost. Continue?`)) {
                window.location.href = '<?= base_url('admin/permanent_delete_user.php?id=') ?>' + btn.dataset.id;
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
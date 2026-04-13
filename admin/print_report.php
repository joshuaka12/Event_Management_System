<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$conn = db_connect();

// Fetch data for report
$events = $conn->query("
    SELECT e.*, u.name AS organizer,
           (SELECT COUNT(*) FROM registrations WHERE event_id=e.id AND status='registered') AS regs
    FROM events e JOIN users u ON u.id=e.created_by
    WHERE e.deleted_at IS NULL
    ORDER BY e.event_date DESC
")->fetch_all(MYSQLI_ASSOC);

$users = $conn->query("SELECT id, name, email, role, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$stats = [];
$res = $conn->query("SELECT COUNT(*) AS c FROM events WHERE deleted_at IS NULL"); $stats['total_events'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL"); $stats['total_users'] = $res->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS c FROM registrations WHERE status='registered'"); $stats['total_regs'] = $res->fetch_assoc()['c'];
$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>Admin Report</title>
<style>
    body { font-family: 'DM Sans', sans-serif; padding: 2rem; background: white; }
    h1, h2 { color: #1e1b18; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
    th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
    th { background: #f2f2f2; }
    .stats { display: flex; gap: 2rem; margin-bottom: 2rem; }
    .stat { background: #f8f6f2; padding: 1rem; border-radius: 12px; text-align: center; }
    @media print { body { padding: 0; } .no-print { display: none; } }
</style>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="margin-bottom:1rem;">🖨️ Print / Save PDF</button>
    <h1>CampusEMS – Admin Report</h1>
    <p>Generated on <?= date('F j, Y g:i A') ?></p>
    <div class="stats">
        <div class="stat"><strong><?= $stats['total_events'] ?></strong><br>Events</div>
        <div class="stat"><strong><?= $stats['total_users'] ?></strong><br>Users</div>
        <div class="stat"><strong><?= $stats['total_regs'] ?></strong><br>Registrations</div>
    </div>
    <h2>Events</h2>
    <table><thead><tr><th>Title</th><th>Organizer</th><th>Date</th><th>Venue</th><th>Registrations</th><th>Status</th></tr></thead>
    <tbody>
        <?php foreach ($events as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['title']) ?></td>
            <td><?= htmlspecialchars($e['organizer']) ?></td>
            <td><?= date('d M Y', strtotime($e['event_date'])) ?></td>
            <td><?= htmlspecialchars($e['venue']) ?></td>
            <td><?= (int)$e['regs'] ?> / <?= (int)$e['capacity'] ?></td>
            <td><?= ucfirst($e['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    </table>
    <h2>Users</h2>
    <table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= ucfirst($u['role']) ?></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    </table>
</body>
</html>
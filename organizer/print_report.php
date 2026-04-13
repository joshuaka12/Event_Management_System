<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$uid = current_user_id();
$conn = db_connect();
$events = $conn->query("
    SELECT e.*, (SELECT COUNT(*) FROM registrations WHERE event_id=e.id AND status='registered') AS reg_count
    FROM events e
    WHERE e.created_by = $uid AND e.deleted_at IS NULL
    ORDER BY e.event_date ASC
")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>My Events Report</title>
<style>
    body { font-family: 'DM Sans', sans-serif; padding: 2rem; }
    h1 { color: #c13b2b; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
    th { background: #f2f2f2; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<button onclick="window.print()" class="no-print">🖨️ Print / Save PDF</button>
<h1>My Events Report</h1>
<p>Generated on <?= date('F j, Y g:i A') ?></p>
<table>
    <thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Category</th><th>Registrations</th><th>Status</th></tr></thead>
    <tbody>
        <?php foreach ($events as $e): ?>
        <tr>
            <td><?= e($e['title']) ?></td>
            <td><?= date('d M Y g:i A', strtotime($e['event_date'])) ?></td>
            <td><?= e($e['venue']) ?></td>
            <td><?= e($e['category']) ?></td>
            <td><?= (int)$e['reg_count'] ?> / <?= (int)$e['capacity'] ?></td>
            <td><?= ucfirst($e['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
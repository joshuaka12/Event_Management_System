<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$id = (int)($_GET['id'] ?? 0);
$reason = trim($_GET['reason'] ?? '');
if ($id && $reason) {
    $conn = db_connect();
    $stmt = $conn->prepare("UPDATE events SET status='rejected', rejection_reason=? WHERE id=? AND deleted_at IS NULL");
    $stmt->bind_param('si', $reason, $id);
    $stmt->execute();
    // Also log to rejection log
    $log = $conn->prepare("INSERT INTO event_rejection_log (event_id, rejected_by, reason) VALUES (?, ?, ?)");
    $log->bind_param('iis', $id, $_SESSION['user_id'], $reason);
    $log->execute();
    $stmt->close();
    $log->close();
    $conn->close();
    set_flash('success', 'Event rejected with reason.');
} else {
    set_flash('error', 'Missing reason or event ID.');
}
header('Location: ' . base_url('admin/dashboard.php'));
exit;
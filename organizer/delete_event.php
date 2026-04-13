<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$id = (int)($_GET['id'] ?? 0);
$reason = trim($_GET['reason'] ?? '');
$uid = current_user_id();

if (!$id || !$reason) {
    set_flash('error', 'Missing event ID or deletion reason.');
    header('Location: ' . base_url('organizer/dashboard.php'));
    exit;
}

$conn = db_connect();
// Verify ownership
$stmt = $conn->prepare("SELECT id, title FROM events WHERE id = ? AND created_by = ? AND deleted_at IS NULL");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    set_flash('error', 'Event not found or access denied.');
    header('Location: ' . base_url('organizer/dashboard.php'));
    exit;
}
$stmt->close();

// Log deletion reason
$log = $conn->prepare("INSERT INTO event_deletion_log (event_id, deleted_by, reason) VALUES (?, ?, ?)");
$log->bind_param('iis', $id, $uid, $reason);
$log->execute();
$log->close();

// Soft delete the event
$del = $conn->prepare("UPDATE events SET deleted_at = NOW() WHERE id = ?");
$del->bind_param('i', $id);
$del->execute();
$del->close();
$conn->close();

set_flash('success', 'Event deleted. Reason recorded.');
header('Location: ' . base_url('organizer/dashboard.php'));
exit;
<?php
/**
 * admin/permanent_delete_event.php
 * Permanently removes an event from the database (cannot be restored).
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn = db_connect();
    // Delete registrations first (cascade may handle, but explicit is safe)
    $conn->query("DELETE FROM registrations WHERE event_id = $id");
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    set_flash('success', 'Event permanently deleted.');
} else {
    set_flash('error', 'Invalid event ID.');
}
header('Location: ' . base_url('admin/dashboard.php'));
exit;
?>
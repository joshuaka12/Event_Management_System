<?php
/**
 * admin/reject_organizer.php
 * Reject an organizer application with a reason.
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$id = (int)($_GET['id'] ?? 0);
$reason = trim($_GET['reason'] ?? '');

if ($id && $reason) {
    $conn = db_connect();
    $stmt = $conn->prepare("UPDATE users SET is_approved = 0, rejection_reason = ? WHERE id = ? AND role = 'organizer'");
    $stmt->bind_param('si', $reason, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    set_flash('success', 'Organizer application rejected.');
} else {
    set_flash('error', 'Missing organizer ID or rejection reason.');
}
header('Location: ' . base_url('admin/dashboard.php'));
exit;
?>
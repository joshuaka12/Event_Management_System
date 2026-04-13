<?php
/**
 * admin/permanent_delete_user.php
 * Permanently removes a user from the database (cannot be restored).
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id && $id != $_SESSION['user_id']) { // prevent self deletion
    $conn = db_connect();
    // Delete user's registrations first
    $conn->query("DELETE FROM registrations WHERE user_id = $id");
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    set_flash('success', 'User permanently deleted.');
} else {
    set_flash('error', 'Cannot delete admin or yourself.');
}
header('Location: ' . base_url('admin/dashboard.php'));
exit;
?>
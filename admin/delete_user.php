<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id && $id != $_SESSION['user_id']) { // prevent self-deletion
    $conn = db_connect();
    $stmt = $conn->prepare("UPDATE users SET deleted_at = NOW() WHERE id=? AND role != 'admin'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    set_flash('success', 'User moved to trash.');
} else {
    set_flash('error', 'Cannot delete admin or yourself.');
}
header('Location: ' . base_url('admin/dashboard.php'));
exit;
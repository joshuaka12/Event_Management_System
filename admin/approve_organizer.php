<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn = db_connect();
    $stmt = $conn->prepare("UPDATE users SET is_approved=1 WHERE id=? AND role='organizer'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    set_flash('success', 'Organizer approved.');
} else {
    set_flash('error', 'Invalid user.');
}
header('Location: ' . base_url('admin/dashboard.php'));
exit;
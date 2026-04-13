<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$img_id = (int)($_POST['id'] ?? 0);
$response = ['success' => false];

if ($img_id) {
    $conn = db_connect();
    // Verify that the image belongs to an event owned by this organizer
    $stmt = $conn->prepare("SELECT ei.image_path, e.created_by FROM event_images ei JOIN events e ON e.id = ei.event_id WHERE ei.id = ?");
    $stmt->bind_param('i', $img_id);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    if ($img && $img['created_by'] == current_user_id()) {
        // Delete file
        if ($img['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $img['image_path'])) unlink($_SERVER['DOCUMENT_ROOT'] . $img['image_path']);
        $conn->query("DELETE FROM event_images WHERE id = $img_id");
        $response['success'] = true;
    }
    $stmt->close();
    $conn->close();
}
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$uid = current_user_id();
$conn = db_connect();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name) $errors[] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

    // Handle profile picture
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/campus_ems/uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $new_pic = upload_file($_FILES['profile_pic'], $upload_dir, ['jpg','jpeg','png','gif'], 1048576);
        if ($new_pic) {
            if ($profile_pic && file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_pic)) unlink($_SERVER['DOCUMENT_ROOT'] . $profile_pic);
            $profile_pic = str_replace($_SERVER['DOCUMENT_ROOT'], '', $new_pic);
        }
    }

    // Password change if requested
    if ($new_password) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashed, $uid);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, contact_number=?, position=?, profile_pic=? WHERE id=?");
        $stmt->bind_param('sssssi', $name, $email, $contact, $position, $profile_pic, $uid);
        $stmt->execute();
        $stmt->close();
        $_SESSION['name'] = $name; // update session name
        set_flash('success', 'Profile updated successfully.');
        header('Location: ' . base_url('organizer/profile.php'));
        exit;
    }
}
$conn->close();

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
    <div class="nav-button-wrapper"><?php back_button('organizer/dashboard.php'); ?></div>
    <div class="form-card" style="max-width: 600px;">
        <h2 class="form-title">Organizer Profile</h2>
        <?php if ($errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
            <div class="form-group"><label class="form-label">Contact Number</label><input type="text" name="contact_number" max = "10" class="form-control" value="<?= e($user['contact_number']) ?>"></div>
            <div class="form-group"><label class="form-label">Position/Role</label><input type="text" name="position" class="form-control" value="<?= e($user['position']) ?>" placeholder="e.g., Event Coordinator"></div>
            <div class="form-group"><label class="form-label">Profile Picture</label>
                <?php if ($user['profile_pic']): ?><img src="<?= base_url(ltrim($user['profile_pic'], '/')) ?>" style="width: 80px; border-radius: 50%;"><br><?php endif; ?>
                <input type="file" name="profile_pic" accept="image/*">
            </div>
            <hr>
            <h3>Change Password</h3>
            <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control"></div>
            <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters"></div>
            <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control"></div>
            <button type="submit" class="btn btn-primary btn-full">Update Profile</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

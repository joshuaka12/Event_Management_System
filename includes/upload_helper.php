// In auth/auth.php, add:

function upload_file($file, $target_dir, $allowed_types = ['jpg','jpeg','png','gif','mp4'], $max_size = 5242880) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) return null;
    if ($file['size'] > $max_size) return null;
    $new_name = uniqid() . '.' . $ext;
    $target_path = $target_dir . $new_name;
    if (move_uploaded_file($file['tmp_name'], $target_path)) return $target_path;
    return null;
}
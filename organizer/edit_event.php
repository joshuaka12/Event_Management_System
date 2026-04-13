<?php
/**
 * organizer/edit_event.php
 * Modern edit event page with collision detection, image preview, and gallery management.
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$id = (int)($_GET['id'] ?? 0);
$uid = current_user_id();
$conn = db_connect();

// Fetch event and verify ownership
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND created_by = ? AND deleted_at IS NULL");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
    set_flash('error', 'Event not found or access denied.');
    header('Location: ' . base_url('organizer/dashboard.php'));
    exit;
}
$stmt->close();

// Fetch additional images
$images = [];
$res = $conn->query("SELECT * FROM event_images WHERE event_id = $id ORDER BY uploaded_at ASC");
while ($row = $res->fetch_assoc()) $images[] = $row;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_datetime = $_POST['event_date'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $video_url = trim($_POST['video_url'] ?? '');

    if (!$title) $errors[] = 'Title is required.';
    if (!$event_datetime) $errors[] = 'Date & time is required.';
    if (!$venue) $errors[] = 'Location is required.';

    // Collision check: same venue, same calendar date, exclude current event
    $event_date_only = date('Y-m-d', strtotime($event_datetime));
    $check_stmt = $conn->prepare("SELECT id, title, event_date FROM events WHERE venue = ? AND DATE(event_date) = ? AND deleted_at IS NULL AND id != ? LIMIT 1");
    $check_stmt->bind_param('ssi', $venue, $event_date_only, $id);
    $check_stmt->execute();
    $conflict = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($conflict) {
        $conflict_date = date('d M Y', strtotime($conflict['event_date']));
        $errors[] = "❌ Conflict: Another event '{$conflict['title']}' is already scheduled at '{$venue}' on {$conflict_date}. Please choose a different date or a different venue.";
    }

    // Handle poster upload (replace if new file provided)
    $poster_path = $event['poster_image'];
    if (empty($errors) && isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/campus_ems/uploads/events/';
        $new_poster = upload_file($_FILES['poster'], $upload_dir, ['jpg','jpeg','png','gif'], 2097152);
        if ($new_poster) {
            // Delete old poster if exists
            if ($poster_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $poster_path)) unlink($_SERVER['DOCUMENT_ROOT'] . $poster_path);
            $poster_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $new_poster);
        } else {
            $errors[] = 'Poster upload failed (invalid type or >2MB).';
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, venue=?, capacity=?, category=?, video_url=?, poster_image=? WHERE id=?");
        $stmt->bind_param('ssssisssi', $title, $description, $event_datetime, $venue, $capacity, $category, $video_url, $poster_path, $id);
        $stmt->execute();
        $stmt->close();

        // Add new additional images (if any)
        if (!empty($_FILES['additional_images'])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/campus_ems/uploads/events/';
            $count = count($_FILES['additional_images']['name']);
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $_FILES['additional_images']['name'][$i],
                    'type' => $_FILES['additional_images']['type'][$i],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                    'error' => $_FILES['additional_images']['error'][$i],
                    'size' => $_FILES['additional_images']['size'][$i],
                ];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $path = upload_file($file, $upload_dir, ['jpg','jpeg','png','gif'], 2097152);
                    if ($path) {
                        $rel_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                        $stmt2 = $conn->prepare("INSERT INTO event_images (event_id, image_path) VALUES (?, ?)");
                        $stmt2->bind_param('is', $id, $rel_path);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }
            }
        }

        set_flash('success', 'Event updated successfully.');
        header('Location: ' . base_url('organizer/dashboard.php'));
        exit;
    }
}
$conn->close();

$page_title = 'Edit Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
    <div class="nav-button-wrapper">
        <?php back_button('organizer/dashboard.php'); ?>
    </div>

    <div class="form-card" style="max-width: 900px; margin: 0 auto; padding: 2rem; background: white; border-radius: 28px; box-shadow: 0 12px 30px rgba(0,0,0,0.05);">
        <h2 class="form-title" style="text-align: center; margin-bottom: 1rem;">✏️ Edit Event</h2>
        <p class="text-muted text-center" style="margin-bottom: 2rem;">Update your event details – changes will be reviewed by admin.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem; background: #fee9e6; border-left: 4px solid #c13b2b;">
                <strong>Please fix the following:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.2rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="editEventForm">
            <!-- Two-column layout for basic info -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Event Title <span style="color:#c13b2b;">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= e($event['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="<?= e($event['category']) ?>" placeholder="e.g., Conference, Workshop">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5"><?= e($event['description']) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Date & Time <span style="color:#c13b2b;">*</span></label>
                    <input type="datetime-local" name="event_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>" required>
                    <span class="form-hint">Only the date matters for collision – same venue + same date will be blocked.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Location <span style="color:#c13b2b;">*</span></label>
                    <input type="text" name="venue" class="form-control" value="<?= e($event['venue']) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Expected Attendees</label>
                    <input type="number" name="capacity" class="form-control" value="<?= (int)$event['capacity'] ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Video URL (YouTube/Vimeo)</label>
                    <input type="url" name="video_url" class="form-control" value="<?= e($event['video_url']) ?>" placeholder="https://...">
                </div>
            </div>

            <!-- Poster upload with preview (show existing) -->
            <div class="form-group">
                <label class="form-label">Event Poster</label>
                <?php if ($event['poster_image']): ?>
                    <div id="currentPoster" style="margin-bottom: 0.5rem;">
                        <img src="<?= base_url(ltrim($event['poster_image'], '/')) ?>" style="max-width: 200px; max-height: 150px; border-radius: 12px; border: 1px solid #e0dbd4;">
                        <button type="button" id="removePosterBtn" class="btn btn-ghost btn-sm" style="margin-left: 0.5rem;">✕ Remove</button>
                    </div>
                    <input type="hidden" name="remove_poster" id="removePoster" value="0">
                <?php endif; ?>
                <input type="file" name="poster" id="posterInput" accept="image/*" style="margin-bottom: 0.5rem;">
                <div id="posterPreview" style="margin-top: 0.5rem; display: none;">
                    <img id="posterPreviewImg" style="max-width: 200px; max-height: 150px; border-radius: 12px; border: 1px solid #e0dbd4;">
                </div>
                <span class="form-hint">Replace poster: upload new image (max 2MB, jpg/png/gif).</span>
            </div>

            <!-- Additional images gallery with delete options -->
            <div class="form-group">
                <label class="form-label">Additional Pictures</label>
                <div id="galleryExisting" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                    <?php foreach ($images as $img): ?>
                        <div class="gallery-item" data-id="<?= $img['id'] ?>" style="position: relative;">
                            <img src="<?= base_url(ltrim($img['image_path'], '/')) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 1px solid #e0dbd4;">
                            <button type="button" class="remove-image-btn" data-id="<?= $img['id'] ?>" style="position: absolute; top: -8px; right: -8px; background: #c13b2b; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; font-size: 12px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="file" name="additional_images[]" id="additionalImages" accept="image/*" multiple style="margin-bottom: 0.5rem;">
                <div id="galleryPreview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
                <span class="form-hint">Add more images (jpg, png, each up to 2MB).</span>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1rem; padding: 0.8rem;">Save Changes</button>
        </form>
    </div>
</div>

<script>
    // Poster preview when new file selected
    const posterInput = document.getElementById('posterInput');
    const posterPreview = document.getElementById('posterPreview');
    const posterPreviewImg = document.getElementById('posterPreviewImg');
    posterInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                posterPreviewImg.src = event.target.result;
                posterPreview.style.display = 'block';
                // Hide current poster display if any
                const currentPoster = document.getElementById('currentPoster');
                if (currentPoster) currentPoster.style.display = 'none';
            };
            reader.readAsDataURL(e.target.files[0]);
        } else {
            posterPreview.style.display = 'none';
        }
    });

    // Remove poster button (optional: set hidden field to indicate removal)
    const removePosterBtn = document.getElementById('removePosterBtn');
    const removePosterField = document.getElementById('removePoster');
    if (removePosterBtn) {
        removePosterBtn.addEventListener('click', function() {
            if (confirm('Remove the current poster? You can upload a new one.')) {
                document.getElementById('currentPoster').style.display = 'none';
                removePosterField.value = '1';
                // Optionally, you can add a hidden input to tell backend to delete old poster
                // We'll handle it in PHP if needed, but here we just hide.
            }
        });
    }

    // Remove existing gallery image (AJAX)
    document.querySelectorAll('.remove-image-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const imgId = this.dataset.id;
            if (confirm('Remove this image permanently?')) {
                fetch('<?= base_url('organizer/delete_image.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + imgId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.gallery-item').remove();
                    } else {
                        alert('Failed to remove image.');
                    }
                });
            }
        });
    });

    // Preview for newly added additional images
    const additionalInput = document.getElementById('additionalImages');
    const galleryPreviewDiv = document.getElementById('galleryPreview');
    additionalInput.addEventListener('change', function(e) {
        galleryPreviewDiv.innerHTML = '';
        if (e.target.files) {
            Array.from(e.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const imgDiv = document.createElement('div');
                    imgDiv.style.position = 'relative';
                    imgDiv.style.display = 'inline-block';
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '10px';
                    img.style.border = '1px solid #e0dbd4';
                    imgDiv.appendChild(img);
                    galleryPreviewDiv.appendChild(imgDiv);
                };
                reader.readAsDataURL(file);
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
/**
 * organizer/create_event.php
 * Prevents multiple events at same venue on same date.
 */

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login('organizer');

$uid = current_user_id();
$conn = db_connect();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_datetime = $_POST['event_date'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $video_url = trim($_POST['video_url'] ?? '');
    $status = 'pending';

    // Validation
    if (!$title) $errors[] = 'Event title is required.';
    if (!$event_datetime) $errors[] = 'Event date and time is required.';
    if (!$venue) $errors[] = 'Event location is required.';
    if ($capacity <= 0) $capacity = 0;

    // Extract only the date part (YYYY-MM-DD) for collision check
    $event_date_only = date('Y-m-d', strtotime($event_datetime));

    // ----- COLLISION CHECK (same venue, same calendar date, any time) -----
    $check_stmt = $conn->prepare("SELECT id, title, event_date FROM events WHERE venue = ? AND DATE(event_date) = ? AND deleted_at IS NULL LIMIT 1");
    $check_stmt->bind_param('ss', $venue, $event_date_only);
    $check_stmt->execute();
    $conflict = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($conflict) {
        $conflict_date = date('d M Y', strtotime($conflict['event_date']));
        $errors[] = "❌ Conflict: Another event '{$conflict['title']}' is already scheduled at '{$venue}' on {$conflict_date}. Please choose a different date or a different venue.";
    }

    // Poster upload (only if no errors)
    $poster_path = null;
    if (empty($errors) && isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/campus_ems/uploads/events/';
        $poster_path = upload_file($_FILES['poster'], $upload_dir, ['jpg','jpeg','png','gif'], 2097152);
        if (!$poster_path) $errors[] = 'Poster upload failed (invalid type or >2MB).';
        else $poster_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $poster_path);
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, venue, capacity, created_by, status, category, poster_image, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssiissss', $title, $description, $event_datetime, $venue, $capacity, $uid, $status, $category, $poster_path, $video_url);
        if ($stmt->execute()) {
            $event_id = $stmt->insert_id;
            $stmt->close();

            // Additional images upload...
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
                            $stmt2->bind_param('is', $event_id, $rel_path);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    }
                }
            }
            set_flash('success', 'Event created successfully! Awaiting admin approval.');
            header('Location: ' . base_url('organizer/dashboard.php'));
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}
$conn->close();

$page_title = 'Create New Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
    <div class="nav-button-wrapper">
        <?php back_button('organizer/dashboard.php'); ?>
    </div>

    <div class="form-card" style="max-width: 900px; margin: 0 auto; padding: 2rem; background: white; border-radius: 28px; box-shadow: 0 12px 30px rgba(0,0,0,0.05);">
        <h2 class="form-title" style="text-align: center; margin-bottom: 1rem;">✨ Create New Event</h2>
        <p class="text-muted text-center" style="margin-bottom: 2rem;">Fill in the details below to create a memorable event.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem; background: #fee9e6; border-left: 4px solid #c13b2b;">
                <strong>Please fix the following:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.2rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="createEventForm">
            <!-- Two-column layout for basic info -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Event Title <span style="color:#c13b2b;">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Annual Tech Summit" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="e.g., Conference, Workshop, Social">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Tell attendees what to expect..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Date & Time <span style="color:#c13b2b;">*</span></label>
                    <input type="datetime-local" name="event_date" class="form-control" required>
                    <span class="form-hint">Only the date matters for collision – same venue + same date will be blocked.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Location <span style="color:#c13b2b;">*</span></label>
                    <input type="text" name="venue" class="form-control" placeholder="e.g., Main Auditorium" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Expected Attendees</label>
                    <input type="number" name="capacity" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Video URL (YouTube/Vimeo)</label>
                    <input type="url" name="video_url" class="form-control" placeholder="https://...">
                </div>
            </div>

            <!-- Poster upload with preview -->
            <div class="form-group">
                <label class="form-label">Event Poster (Image)</label>
                <input type="file" name="poster" id="posterInput" accept="image/*" style="margin-bottom: 0.5rem;">
                <div id="posterPreview" style="margin-top: 0.5rem; display: none;">
                    <img id="posterPreviewImg" style="max-width: 200px; max-height: 150px; border-radius: 12px; border: 1px solid #e0dbd4;">
                </div>
                <span class="form-hint">Recommended size: 1200x630px. Max 2MB (jpg, png, gif).</span>
            </div>

            <!-- Additional images (multiple) with preview gallery -->
            <div class="form-group">
                <label class="form-label">Additional Pictures (multiple)</label>
                <input type="file" name="additional_images[]" id="additionalImages" accept="image/*" multiple style="margin-bottom: 0.5rem;">
                <div id="galleryPreview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
                <span class="form-hint">You can select several images (jpg, png). Each up to 2MB.</span>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1rem; padding: 0.8rem;">Create Event</button>
        </form>
    </div>
</div>

<script>
    // Poster preview
    const posterInput = document.getElementById('posterInput');
    const posterPreview = document.getElementById('posterPreview');
    const posterPreviewImg = document.getElementById('posterPreviewImg');
    posterInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                posterPreviewImg.src = event.target.result;
                posterPreview.style.display = 'block';
            };
            reader.readAsDataURL(e.target.files[0]);
        } else {
            posterPreview.style.display = 'none';
        }
    });

    // Additional images gallery preview
    const additionalInput = document.getElementById('additionalImages');
    const galleryPreview = document.getElementById('galleryPreview');
    additionalInput.addEventListener('change', function(e) {
        galleryPreview.innerHTML = '';
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
                    galleryPreview.appendChild(imgDiv);
                };
                reader.readAsDataURL(file);
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
session_start();
include_once __DIR__.'/../model/event.php';

ob_start();
header('Content-Type: application/json');

$eventId = $_SESSION['event_id'] ?? null;
if (!$eventId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'No event ID in session']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Upload error (code ' . ($_FILES['image']['error'] ?? 'no file') . ')']);
    exit;
}

$tmpPath = $_FILES['image']['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);

$allowedMimes = ['image/png', 'image/jpeg'];
if (!in_array($mimeType, $allowedMimes)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'File must bee PNG or JPEG']);
    exit;
}

if (!function_exists('imagewebp')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'WebP not supported GD missing)']);
    exit;
}

$pays = preg_replace('/[^a-z]/', '', strtolower($_POST['pays'] ?? ''));
$slotIndex = (int)($_POST['slot_index'] ?? 0);
$siteName = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['site_name'] ?? 'SITE');
$loginLogout = in_array($_POST['login_logout'] ?? '', ['login', 'logout']) ? $_POST['login_logout'] : 'na';
$section = ($_POST['section'] ?? 'main') === 'result' ? 'result' : 'main';
$originalName = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['original_name'] ?? 'image'));

$filename = strtoupper($siteName) . '-' . $loginLogout . '-' . $eventId . '-' . $section . '-' . $pays . '-' . $originalName . '.webp';
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if ($mimeType === 'image/png') {
    $srcImage = imagecreatefrompng($tmpPath);
} else {
    $srcImage = imagecreatefromjpeg($tmpPath);
}

if (!$srcImage) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to read image']);
    exit;
}

imagealphablending($srcImage, true);
imagesavealpha($srcImage, true);
$saved = imagewebp($srcImage, $uploadDir . $filename, 90);
imagedestroy($srcImage);

if (!$saved) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to convert to WebP']);
    exit;
}

try {
    $stmt = $cnx->prepare("SELECT id FROM image_events WHERE id_event = :id AND pays = :pays AND slot_index = :slot LIMIT 1");
    $stmt->execute([':id' => $eventId, ':pays' => $pays, ':slot' => $slotIndex]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $cnx->prepare("UPDATE image_events SET name_image = :name WHERE id = :iid")
            ->execute([':name' => $filename, ':iid' => $row['id']]);
    } else {
        $cnx->prepare("INSERT INTO image_events (id_event, name_image, pays, slot_index, checked) VALUES (:id, :name, :pays, :slot, 0)")
            ->execute([':id' => $eventId, ':name' => $filename, ':pays' => $pays, ':slot' => $slotIndex]);
    }
} catch (PDOException $e) {
    error_log('image_events insert error: '.$e->getMessage());
}

$_SESSION['images'][$pays][$slotIndex] = $filename;

ob_end_clean();
echo json_encode(['success' => true, 'filename' => $filename]);
exit;
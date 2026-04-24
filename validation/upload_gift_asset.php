<?php

ini_set('display_errors', 1);
error_reporting(E_ALL); 

session_start();
include_once __DIR__ . '/../model/event.php';

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

$tmpPath  = $_FILES['image']['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);

if (!in_array($mimeType, ['image/png', 'image/jpeg'], true)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'File must be PNG or JPEG']);
    exit;
}

if (!function_exists('imagewebp')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'WebP not supported (GD missing)']);
    exit;
}

$allowedAssets = ['logo', 'arriere_plan', 'image1', 'image2'];
$assetType = $_POST['asset_type'] ?? '';
if (!in_array($assetType, $allowedAssets, true)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid asset type']);
    exit;
}


$isGlobal     = in_array($assetType, ['logo', 'arriere_plan'], true);
$allowedPhases = ['collection', 'pre-donation', 'post-donation'];
$phase         = $_POST['phase'] ?? '';

if (!$isGlobal && !in_array($phase, $allowedPhases, true)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid or missing phase for ' . $assetType]);
    exit;
}

$originalName = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['original_name'] ?? 'image'));
if ($originalName === '') $originalName = 'image';

$phaseSlug = $isGlobal ? 'global' : preg_replace('/[^a-z]/', '', $phase);
$filename  = 'GIFT-' . $assetType . '-' . (int)$eventId . '-' . $phaseSlug . '-' . $originalName . '.webp';

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$srcImage = ($mimeType === 'image/png')
    ? imagecreatefrompng($tmpPath)
    : imagecreatefromjpeg($tmpPath);

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

$dbOk = updateGiftAsset((int)$eventId, $isGlobal ? '' : $phase, $assetType, $filename);

ob_end_clean();
if ($dbOk) {
    echo json_encode(['success' => true, 'filename' => $filename, 'asset_type' => $assetType, 'phase' => $phase]);
} else {
    echo json_encode(['success' => false, 'error' => 'File saved but DB update failed']);
}
exit;
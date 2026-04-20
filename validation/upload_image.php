<?php
session_start();
include_once __DIR__.'/../model/event.php';

header('Content-Type: application/json');

$eventId = $_SESSION['event_id'] ?? null;
if (!$eventId) {
    echo json_encode(['success' => false, 'error' => 'No event ID in session']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error']);
    exit;
}

$tmpPath = $_FILES['image']['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
if ($finfo->file($tmpPath) !== 'image/png') {
    echo json_encode(['success' => false, 'error' => 'File must be PNG']);
    exit;
}

$pays = preg_replace('/[^a-z]/', '', strtolower($_POST['pays'] ?? ''));
$slotIndex = (int)($_POST['slot_index'] ?? 0);
$slotKey = $pays.'_'.$slotIndex;
$filename = $slotKey.'_'.time().'.png';

$uploadDir = __DIR__.'/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

$cnx->prepare("DELETE FROM image_event WHERE event_id = :id AND slot_key = :key")
    ->execute([':id' => $eventId, ':key' => $slotKey]);

$cnx->prepare("INSERT INTO image_event (event_id, slot_key, filename) VALUES (:id, :key, :filename)")
    ->execute([':id' => $eventId, ':key' => $slotKey, ':filename' => $filename]);

$_SESSION['images'][$pays][$slotIndex] = $filename;

echo json_encode(['success' => true, 'filename' => $filename]);
exit;
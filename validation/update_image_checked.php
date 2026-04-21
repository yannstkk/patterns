<?php
session_start();
include_once __DIR__.'/../model/event.php';

ob_start();
header('Content-Type: application/json');

$eventId = $_SESSION['event_id'] ?? null;
$nameImage = trim($_POST['name_image'] ?? '');
$checked = ($_POST['checked'] ?? '0') === '1' ? 1 : 0;

if (!$eventId || !$nameImage) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Missing params']);
    exit;
}

try {
    $stmt = $cnx->prepare("SELECT id FROM image_events WHERE id_event = :id AND name_image = :name LIMIT 1");
    $stmt->execute([':id' => $eventId, ':name' => $nameImage]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $cnx->prepare("UPDATE image_events SET checked = :checked WHERE id = :iid")
        ->execute([':checked' => $checked, ':iid' => $row['id']]);

        $_SESSION['checked_images'][$nameImage] = $checked;

        ob_end_clean();
    echo json_encode(['success' => true]);
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Image record not found']);
    }
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
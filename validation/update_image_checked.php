<?php
session_start();
include_once __DIR__.'/../model/event.php';

ob_start();
header('Content-Type: application/json');

$eventId = $_SESSION['event_id'] ?? null;
$nameImage = trim($_POST['name_image'] ?? '');
$checked = ($_POST['checked'] ?? '0') === '1' ? '1' : '0';

if (!$eventId || !$nameImage) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Missing params']);
    exit;
}

try {
    $stmt = $cnx->prepare("SELECT id_image_event FROM image_events WHERE id_event = :id AND name_image = :name LIMIT 1");
    $stmt->execute([':id' => $eventId, ':name' => $nameImage]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $cnx->prepare("UPDATE image_events SET checked = :checked WHERE id_image_event = :iid")
            ->execute([':checked' => $checked, ':iid' => $row['id_image_event']]);
    } else {
        $cnx->prepare("INSERT INTO image_events (id_event, name_image, checked) VALUES (:id, :name, :checked)")
            ->execute([':id' => $eventId, ':name' => $nameImage, ':checked' => $checked]);
    }
    ob_end_clean();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
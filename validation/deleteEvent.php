<?php
session_start();
include_once __DIR__.'/../model/event.php';

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $cnx->prepare("DELETE FROM config_event WHERE ID = :id AND etat_event = 'draft'")
        ->execute([':id' => $id]);
}

header('Location: ../index.php?page=ListEvent');
exit;
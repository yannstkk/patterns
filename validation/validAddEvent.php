<?php

session_start();

include_once __DIR__ . '/../model/event.php';

$action       = $_POST['action']        ?? '';
$statutActuel = $_POST['statut_actuel'] ?? 'draft';
$eventId      = $_SESSION['event_id']   ?? null;

if ($action === 'pre-publish' && $eventId) {
    $nouvelEtat = $statutActuel === 'draft' ? 'pre-prod' : ($statutActuel === 'pre-prod' ? 'prod' : $statutActuel);
    $cnx->prepare("UPDATE config_event SET etat_event = :etat WHERE ID = :id")
        ->execute([':etat' => $nouvelEtat, ':id' => (int)$eventId]);
    $_SESSION['statut'] = $nouvelEtat;
    header('Location: ../index.php?page=AddEvent');
    exit;
}

$_SESSION["reponses"] = $_POST;
$_SESSION["errors"]   = [];
$errors = [];

$isExisting = !empty($_SESSION['event_id']);

$champsObligatoires = [
    'nom_projet'     => 'The project name is required',
    'link'           => 'The event link is required',
    'launching_date' => 'The launch date is required',
    'result_date'    => 'The result date is required',
    'end_date'       => 'The end date is required',
];

foreach ($champsObligatoires as $champ => $message) {
    if (empty(trim($_POST[$champ] ?? ''))) {
        $errors[$champ] = $message;
    }
}

$launchingDate = trim($_POST['launching_date'] ?? '');
$resultDate    = trim($_POST['result_date']    ?? '');
$endDate       = trim($_POST['end_date']       ?? '');

if (empty($errors['launching_date']) && empty($errors['result_date']) && empty($errors['end_date'])) {

    $formatLaunch = DateTime::createFromFormat('Y-m-d', $launchingDate);
    $formatResult = DateTime::createFromFormat('Y-m-d', $resultDate);
    $formatEnd    = DateTime::createFromFormat('Y-m-d', $endDate);

    if (!$formatLaunch) {
        $errors['launching_date'] = 'Invalid launch date format';
    }
    if (!$formatResult) {
        $errors['result_date'] = 'Invalid result date format';
    }
    if (!$formatEnd) {
        $errors['end_date'] = 'Invalid end date format';
    }

    if ($formatLaunch && $formatResult && $formatEnd) {

        $today = new DateTime();
        $today->setTime(0, 0, 0);

        if (!$isExisting && $formatLaunch < $today) {
            $errors['launching_date'] = "The launch date can't be before today";
        }

        if ($formatLaunch >= $formatResult) {
            $errors['result_date'] = 'The result date must be after the launch date';
        }

        if ($formatResult > $formatEnd) {
            $errors['end_date'] = 'The end date must be after the result date';
        }
    }
}

if (empty($_POST['pays_list']) && !$isExisting) {
    $errors['pays_list[]'] = 'At least one country must be selected';
}

if (!isset($_SESSION['images'])) {
    $_SESSION['images'] = [];
}

if (isset($_FILES['images'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['images']['name'] as $pays => $slots) {
        foreach ($slots as $index => $name) {

            $errCode = $_FILES['images']['error'][$pays][$index];

            if ($errCode === UPLOAD_ERR_OK) {

                $tmpPath  = $_FILES['images']['tmp_name'][$pays][$index];
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpPath);

                if ($mimeType !== 'image/png') {
                    $errors['images'][$pays][$index] = 'Only PNG files are accepted (' . htmlspecialchars($name) . ')';
                    continue;
                }

                $filename = $pays . '_' . $index . '_' . basename($name);
                $dest     = $uploadDir . $filename;
                move_uploaded_file($tmpPath, $dest);
                $_SESSION['images'][$pays][$index] = $filename;
            }
        }
    }
}

if (empty($errors)) {

    $nouvelEtat = $statutActuel;

    $id = saveEvent($_POST, $eventId, $nouvelEtat);
    if ($id) {
        $_SESSION['event_id'] = $id;
    }

    $_SESSION['statut'] = $nouvelEtat;
}

$_SESSION['errors'] = $errors;

if (!empty($errors)) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ../index.php?page=AddEvent');
}
exit;
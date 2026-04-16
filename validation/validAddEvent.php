<?php

session_start();

include_once __DIR__ . '/../model/event.php';

$_SESSION["reponses"] = $_POST;
$_SESSION["errors"]   = [];
$errors = [];

// ──────────────────────────────────────────────
// 1. Champs obligatoires avec messages dédiés
// ──────────────────────────────────────────────

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

// ──────────────────────────────────────────────
// 2. Validation des dates (seulement si remplies)
// ──────────────────────────────────────────────

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

        if ($formatLaunch < $today) {
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

// ──────────────────────────────────────────────
// 3. Au moins un pays sélectionné
// ──────────────────────────────────────────────

if (empty($_POST['pays_list'])) {
    $errors['pays_list[]'] = 'At least one country must be selected';
}

// ──────────────────────────────────────────────
// 4. Upload des images (avec vérif MIME réelle)
// ──────────────────────────────────────────────

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

// ──────────────────────────────────────────────
// 5. Sauvegarde en base + changement de statut
//    (seulement si aucune erreur)
// ──────────────────────────────────────────────

if (empty($errors)) {

    $action       = $_POST['action']        ?? '';
    $statutActuel = $_POST['statut_actuel'] ?? 'draft';

    // Calcul du nouvel état
    $nouvelEtat = $statutActuel;
    if ($action === 'pre-publish') {
        if ($statutActuel === 'draft') {
            $nouvelEtat = 'pre-prod';
        } elseif ($statutActuel === 'pre-prod') {
            $nouvelEtat = 'prod';
        }
    }

    // Sauvegarde en base de données (avec le bon état)
    $eventId = $_SESSION['event_id'] ?? null;
    $id = saveEvent($_POST, $eventId, $nouvelEtat);
    if ($id) {
        $_SESSION['event_id'] = $id;
    }

    // Mise à jour de la session statut
    $_SESSION['statut'] = $nouvelEtat;
}

// ──────────────────────────────────────────────
// 6. Redirection
// ──────────────────────────────────────────────

$_SESSION['errors'] = $errors;

if (!empty($errors)) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ../index.php?page=ListEvent');
}
exit;
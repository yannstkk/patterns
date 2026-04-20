<?php
session_start();
include_once __DIR__ . '/../model/event.php';

$action = $_POST['action'] ?? '';
$statutActuel = $_POST['statut_actuel'] ?? 'draft';
$step = (int)($_POST['step'] ?? 1);
$eventId = $_SESSION['event_id'] ?? null;
$isEdit = $_SESSION['is_edit'] ?? false;
$previousRep = $_SESSION['reponses'] ?? [];
$errors = [];

$launchingDate = trim($_POST['launching_date'] ?? '');
$resultDate = trim($_POST['result_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');

function getRequiredImageSlots(array $pays) {
    $required = [];

    if (in_array('france', $pays)) {
        $required['france'] = array_merge(range(0, 6), range(12, 18));
    }

    if (in_array('uk', $pays)) {
        $required['uk'] = range(0, 17);
    }

    if (in_array('others', $pays)) {
        $othersInFrance = array_merge(range(7, 11), range(19, 23));
        $required['france'] = array_unique(array_merge($required['france'] ?? [], $othersInFrance));
        $required['spain'] = range(0, 9);
    }

    return $required;
}

if ($step === 1) {
    if (empty(trim($_POST['nom_projet'] ?? ''))) {
        $errors['nom_projet'] = 'The project name is required';
    }

    $_SESSION['reponses'] = array_merge($previousRep, $_POST);
    $_SESSION['errors'] = $errors;

    if (!empty($errors)) {
        $_SESSION['step'] = $step;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $data = [
        'nom_projet' => trim($_POST['nom_projet']),
        'type_event' => $_POST['type_event'] ?? 'Fun Month',
        'link' => '',
        'launching_date' => null,
        'result_date' => null,
        'end_date' => null,
        'pays_list' => [],
    ];
    $id = saveEvent($data, null, 'draft');
    if ($id) {
        $_SESSION['event_id'] = $id;
        $_SESSION['statut'] = 'draft';
        $_SESSION['reponses'] = array_merge($previousRep, $data);
    }

    unset($_SESSION['step']);
    header('Location: ../index.php?page=AddEvent');
    exit;
}

if (empty(trim($_POST['nom_projet'] ?? ''))) {
    $errors['nom_projet'] = 'The project name is required';
}
if (empty(trim($_POST['link'] ?? ''))) {
    $errors['link'] = 'The event link is required';
}
if (empty($launchingDate)) {
    $errors['launching_date'] = 'The launch date is required';
}
if (empty($resultDate)) {
    $errors['result_date'] = 'The result date is required';
}
if (empty($endDate)) {
    $errors['end_date'] = 'The end date is required';
}

if (empty($errors)) {
    $formatLaunch = DateTime::createFromFormat('Y-m-d', $launchingDate);
    $formatResult = DateTime::createFromFormat('Y-m-d', $resultDate);
    $formatEnd = DateTime::createFromFormat('Y-m-d', $endDate);

    if (!$formatLaunch) {
        $errors['launching_date'] = 'Invalid launch date format';
    }
    if (!$formatResult) {
        $errors['result_date'] = 'Invalid result date format';
    }
    if (!$formatEnd) {
        $errors['end_date'] = 'Invalid end date format';
    }

    if (empty($errors)) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        if (!$isEdit && $formatLaunch < $today) {
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

$paysPost = $_POST['pays_list'] ?? [];
$paysEffectifs = !empty($paysPost) ? $paysPost : ($previousRep['pays_list'] ?? []);

if ($step >= 3 || in_array($statutActuel, ['pre-prod', 'prod'])) {
    if (empty($paysEffectifs)) {
        $errors['pays_list[]'] = 'At least one country must be selected';
    }
}

if (empty($errors) && in_array($statutActuel, ['pre-prod', 'prod'])) {
    $sessionImages = $_SESSION['images'] ?? [];
    $requiredSlots = getRequiredImageSlots($paysEffectifs);

    foreach ($requiredSlots as $pays => $indices) {
        foreach ($indices as $idx) {
            if (!isset($sessionImages[$pays][$idx])) {
                $errors['images'] = 'All images must be uploaded for all selected countries';
                break 2;
            }
        }
    }
}

$_SESSION['reponses'] = array_merge($previousRep, $_POST, ['pays_list' => $paysEffectifs]);
$_SESSION['errors'] = $errors;

if (!empty($errors)) {
    $_SESSION['step'] = $step;
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($action === 'pre-publish' && $eventId) {
    $nouvelEtat = $statutActuel === 'draft' ? 'pre-prod' : ($statutActuel === 'pre-prod' ? 'prod' : $statutActuel);
    $cnx->prepare("UPDATE config_event SET etat_event = :etat WHERE ID = :id")
        ->execute([':etat' => $nouvelEtat, ':id' => (int)$eventId]);
    $_SESSION['statut'] = $nouvelEtat;
    unset($_SESSION['step']);
    header('Location: ../index.php?page=AddEvent');
    exit;
}

if (!isset($_SESSION['images'])) {
    $_SESSION['images'] = [];
}
if (isset($_FILES['images'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    foreach ($_FILES['images']['name'] as $pays => $slots) {
        foreach ($slots as $index => $name) {
            if ($_FILES['images']['error'][$pays][$index] !== UPLOAD_ERR_OK) continue;
            $tmpPath = $_FILES['images']['tmp_name'][$pays][$index];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($tmpPath) !== 'image/png') continue;
            $filename = $pays . '_' . $index . '_' . basename($name);
            move_uploaded_file($tmpPath, $uploadDir . $filename);
            $_SESSION['images'][$pays][$index] = $filename;
        }
    }
}

$data = [
    'nom_projet' => trim($_POST['nom_projet']),
    'type_event' => $_POST['type_event'] ?? 'Fun Month',
    'link' => trim($_POST['link']),
    'launching_date' => $launchingDate,
    'result_date' => $resultDate,
    'end_date' => $endDate,
    'pays_list' => $paysEffectifs,
];
$id = saveEvent($data, $eventId, $statutActuel);
if ($id) {
    $_SESSION['event_id'] = $id;
    $_SESSION['statut'] = $statutActuel;
    $_SESSION['reponses'] = array_merge($_SESSION['reponses'], $data);
}

unset($_SESSION['step']);
header('Location: ../index.php?page=AddEvent');
exit;
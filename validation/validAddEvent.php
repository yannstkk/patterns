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

function buildDatetime(string $date, string $time): string {
    $t = preg_match('/^\d{2}:\d{2}$/', trim($time)) ? trim($time) : '00:00';
    return $date . ' ' . $t . ':00';
}

$launchingDate = trim($_POST['launching_date'] ?? '');
$launchingTime = trim($_POST['launching_time'] ?? '00:00');
$resultDate = trim($_POST['result_date'] ?? '');
$resultTime = trim($_POST['result_time'] ?? '00:00');
$endDate = trim($_POST['end_date'] ?? '');
$endTime = trim($_POST['end_time'] ?? '00:00');

function getRequiredImageSlots(array $pays) {
    $required = [];
    if (in_array('france', $pays)) {
        $required['france'] = array_merge(range(0, 6), range(12, 18));
    }
    if (in_array('uk', $pays)) {
        $required['uk'] = range(0, 17);
    }
    if (in_array('italy', $pays)) {
        $required['italy'] = range(0, 3);
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
    header('Location: https://bao.madeinsurveys.com/bo/index.php?menuprincipal=config_event&partie=AddEvent');
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
    $typeEvent = trim($_POST['type_event'] ?? 'Fun Month');
    header('Location: https://bao.madeinsurveys.com/bo/index.php?menuprincipal=config_event&partie='. ($typeEvent === 'Donation' ? 'AddEventGift' : 'AddEvent'));

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
    $formatLaunch = DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($launchingDate, $launchingTime));
    $formatResult = DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($resultDate, $resultTime));
    $formatEnd = DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($endDate, $endTime));

    if (!$formatLaunch) $errors['launching_date'] = 'Invalid launch date format';
    if (!$formatResult) $errors['result_date'] = 'Invalid result date format';
    if (!$formatEnd) $errors['end_date'] = 'Invalid end date format';

    if (empty($errors)) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        $originalLaunch = null;
        if ($isEdit && $eventId) {
            $stmtOrig = $cnx->prepare("SELECT date_debut FROM config_event WHERE ID = :id LIMIT 1");
            $stmtOrig->execute([':id' => $eventId]);
            $rowOrig = $stmtOrig->fetch(PDO::FETCH_ASSOC);
            if ($rowOrig && !empty($rowOrig['date_debut'])) {
                $originalLaunch = new DateTime($rowOrig['date_debut']);
            }
        }

        $launchDateChanged = !$isEdit || !$originalLaunch || $formatLaunch->format('Y-m-d H:i') !== $originalLaunch->format('Y-m-d H:i');

        if ($launchDateChanged && $formatLaunch < $today) {
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

if (empty($errors) && $action === 'pre-publish') {
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

$_SESSION['reponses'] = array_merge($previousRep, $_POST, [
    'pays_list' => $paysEffectifs,
    'launching_date' => $launchingDate,
    'launching_time' => $launchingTime,
    'result_date' => $resultDate,
    'result_time' => $resultTime,
    'end_date' => $endDate,
    'end_time' => $endTime,
]);
$_SESSION['errors'] = $errors;

if (!empty($errors)) {
    $_SESSION['step'] = $step;
    header('Location: https://bao.madeinsurveys.com/bo/index.php?menuprincipal=config_event&partie=AddEvent');
    exit;
}

$data = [
    'nom_projet' => trim($_POST['nom_projet']),
    'type_event' => $_POST['type_event'] ?? 'Fun Month',
    'link' => trim($_POST['link']),
    'launching_date' => buildDatetime($launchingDate, $launchingTime),
    'result_date' => buildDatetime($resultDate, $resultTime),
    'end_date' => buildDatetime($endDate, $endTime),
    'pays_list' => $paysEffectifs,
];

if ($action === 'pre-publish' && $eventId) {
    $nouvelEtat = $statutActuel === 'draft'
        ? 'pre-prod'
        : ($statutActuel === 'pre-prod' ? 'prod' : $statutActuel);

    saveEvent($data, $eventId, $nouvelEtat);
    $_SESSION['statut'] = $nouvelEtat;
    unset($_SESSION['step']);
    header('Location: https://bao.madeinsurveys.com/bo/index.php?menuprincipal=config_event&partie=AddEvent');
    exit;
}

$id = saveEvent($data, $eventId, $statutActuel);
if ($id) {
    $_SESSION['event_id'] = $id;
    $_SESSION['statut'] = $statutActuel;
}

unset($_SESSION['step']);
    header('Location: https://bao.madeinsurveys.com/bo/index.php?menuprincipal=config_event&partie=AddEvent');
exit;
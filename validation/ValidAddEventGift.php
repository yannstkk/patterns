<?php
session_start();
include_once __DIR__ . '/../model/event.php';

$action = $_POST['action']         ?? '';
$statutActuel = $_POST['statut_actuel']  ?? 'draft';
$step = (int)($_POST['step']     ?? 1);
$eventId = $_SESSION['event_id']    ?? null;
$previousRep  = $_SESSION['reponses']    ?? [];
$errors = [];

if ($step === 1) {
    if (empty(trim($_POST['nom_projet'] ?? ''))) {
        $errors['nom_projet'] = 'The project name is required';
    }

    $_SESSION['reponses'] = array_merge($previousRep, $_POST);
    $_SESSION['errors']   = $errors;

    if (!empty($errors)) {
        $_SESSION['step'] = $step;
        header('Location: ../index.php?page=AddEventGift');
        exit;
    }

    $data = [
        'nom_projet' => trim($_POST['nom_projet']),
        'type_event' => 'Gift',
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
        $_SESSION['reponses'] = array_merge($previousRep, [
            'nom_projet' => trim($_POST['nom_projet']),
            'type_event' => 'Gift',
            'active_phase' => 'collection',
        ]);
    }

    unset($_SESSION['step']);
    $typeEvent = trim($_POST['type_event'] ?? 'Gift');
    header('Location: ../index.php?page=' . ($typeEvent === 'Fun Month' ? 'AddEvent' : 'AddEventGift'));
    exit;
}

if (empty(trim($_POST['nom_projet'] ?? ''))) {
    $errors['nom_projet'] = 'The project name is required';
}
if (empty(trim($_POST['link'] ?? ''))) {
    $errors['link'] = 'The event link is required';
}

$launchingDate = trim($_POST['launching_date']    ?? '');
$preDonationDate = trim($_POST['pre_donation_date']  ?? '');
$postDonationDate= trim($_POST['post_donation_date'] ?? '');

if (empty($launchingDate))    $errors['launching_date']    = 'The launch collection phase date is required';
if (empty($preDonationDate))  $errors['pre_donation_date'] = 'The pre-donation handover date is required';
if (empty($postDonationDate)) $errors['post_donation_date']= 'The post-donation handover date is required';

if (empty($errors)) {
    $fLaunch = DateTime::createFromFormat('Y-m-d', $launchingDate);
    $fPre = DateTime::createFromFormat('Y-m-d', $preDonationDate);
    $fPost = DateTime::createFromFormat('Y-m-d', $postDonationDate);

    if (!$fLaunch) $errors['launching_date']    = 'Invalid date format';
    if (!$fPre) $errors['pre_donation_date']  = 'Invalid date format';
    if (!$fPost) $errors['post_donation_date'] = 'Invalid date format';

    if (empty($errors)) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $isEdit = $_SESSION['is_edit'] ?? false;

        $originalLaunch = null;
        if ($isEdit && $eventId) {
            $stmtOrig = $cnx->prepare("SELECT date_debut FROM config_event WHERE ID = :id LIMIT 1");
            $stmtOrig->execute([':id' => $eventId]);
            $rowOrig = $stmtOrig->fetch(PDO::FETCH_ASSOC);
            if ($rowOrig && !empty($rowOrig['date_debut'])) {
                $originalLaunch = DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime($rowOrig['date_debut'])));
            }
        }
        $launchChanged = !$isEdit || !$originalLaunch || $fLaunch->format('Y-m-d') !== $originalLaunch->format('Y-m-d');

        if ($launchChanged && $fLaunch < $today) {
            $errors['launching_date'] = "The launch date can't be before today";
        }
        if ($fLaunch >= $fPre) {
            $errors['pre_donation_date'] = 'The pre-donation date must be after the launch date';
        }
        if ($fPre >= $fPost) {
            $errors['post_donation_date'] = 'The post-donation date must be after the pre-donation date';
        }
    }
}

if (empty(trim($_POST['pays'] ?? ''))) {
    $errors['pays'] = 'Please select a country';
}

$paysValue  = trim($_POST['pays'] ?? 'france');
$langMap    = ['france' => 'fr', 'uk' => 'en', 'italy' => 'it', 'others' => 'others'];
$association= trim($_POST['association'] ?? '');
$activePhase= $_POST['active_phase'] ?? 'collection';

$_SESSION['reponses'] = array_merge($previousRep, [
    'nom_projet' => trim($_POST['nom_projet'] ?? ''),
    'type_event' => 'Gift',
    'link' => trim($_POST['link'] ?? ''),
    'launching_date' => $launchingDate,
    'pre_donation_date' => $preDonationDate,
    'post_donation_date'=> $postDonationDate,
    'pays' => $paysValue,
    'association' => $association,
    'active_phase' => $activePhase,
]);
$_SESSION['errors'] = $errors;

if (!empty($errors)) {
    $_SESSION['step'] = $step;
    header('Location: ../index.php?page=AddEventGift');
    exit;
}


$data = [
    'nom_projet' => trim($_POST['nom_projet']),
    'type_event' => 'Gift',
    'link' => trim($_POST['link']),
    'launching_date' => $launchingDate,
    'result_date' => $preDonationDate,   
    'end_date' => $postDonationDate,  
    'pays_list' => [$paysValue],
];
$id = saveEvent($data, $eventId, $statutActuel);
if ($id) {
    $_SESSION['event_id'] = $id;
    $_SESSION['statut']   = $statutActuel;
}


$phasesPost = $_POST['phases'] ?? [];
error_log('PHASES POST: ' . print_r($phasesPost, true));

saveGiftPhaseText((int)$id, $association, $phasesPost);

unset($_SESSION['step']);
header('Location: ../index.php?page=AddEventGift');
exit;
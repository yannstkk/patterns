<?php
session_start();
include_once __DIR__.'/../model/event.php';

$action = $_POST['action'] ?? '';
$statutActuel = $_POST['statut_actuel'] ?? 'draft';
$step = (int)($_POST['step'] ?? 1);
$eventId = $_SESSION['event_id'] ?? null;
$previousRep = $_SESSION['reponses'] ?? [];
$errors = [];

function buildDatetime(string $date, string $time): string {
    $t = preg_match('/^\d{2}:\d{2}$/', trim($time)) ? trim($time) : '00:00';
    return $date . ' ' . $t . ':00';
}

$launchingDate = trim($_POST['launching_date'] ?? '');
$launchingTime = trim($_POST['launching_time'] ?? '00:00');
$preDonationDate = trim($_POST['pre_donation_date'] ?? '');
$preDonationTime = trim($_POST['pre_donation_time'] ?? '00:00');
$postDonationDate = trim($_POST['post_donation_date'] ?? '');
$postDonationTime = trim($_POST['post_donation_time'] ?? '00:00');
$closeDate = trim($_POST['close_date'] ?? '');
$closeTime = trim($_POST['close_time'] ?? '00:00');

if ($step === 1) {
    if (empty(trim($_POST['nom_projet'] ?? ''))) {
        $errors['nom_projet'] = 'The project name is required';
    }

    $_SESSION['reponses'] = array_merge($previousRep, $_POST);
    $_SESSION['errors'] = $errors;

    if (!empty($errors)) {
        $_SESSION['step'] = $step;
        header('Location: ./index.php?menuprincipal=config_envent&partie=AddEventGift');
        exit;
    }

    $data = [
        'nom_projet' => trim($_POST['nom_projet']),
        'type_event' => 'Donation',
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
            'type_event' => 'Donation',
            'active_phase' => 'collection',
        ]);
    }

    unset($_SESSION['step']);
    $typeEvent = trim($_POST['type_event'] ?? 'Donation');
    header('Location: ./index.php?menuprincipal=config_envent&partie= '. ($typeEvent === 'Fun Month' ? 'AddEvent' : 'AddEventGift'));

    exit;
}

if (empty(trim($_POST['nom_projet'] ?? ''))) {
    $errors['nom_projet'] = 'The project name is required';
}
if (empty(trim($_POST['link'] ?? ''))) {
    $errors['link'] = 'The event link is required';
}
if (empty($launchingDate)) $errors['launching_date'] = 'The launch collection phase date is required';
if (empty($preDonationDate)) $errors['pre_donation_date'] = 'The pre-donation handover date is required';
if (empty($postDonationDate)) $errors['post_donation_date'] = 'The post-donation handover date is required';

if (empty($errors)) {
    $fLaunch = DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($launchingDate, $launchingTime));
    $fPre = DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($preDonationDate, $preDonationTime));
    $fPost = DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($postDonationDate, $postDonationTime));
    $fClose = !empty($closeDate) ? DateTime::createFromFormat('Y-m-d H:i:s', buildDatetime($closeDate, $closeTime)) : null;

    if (!$fLaunch) $errors['launching_date'] = 'Invalid date format';
    if (!$fPre) $errors['pre_donation_date'] = 'Invalid date format';
    if (!$fPost) $errors['post_donation_date'] = 'Invalid date format';
    if (!empty($closeDate) && !$fClose) $errors['close_date'] = 'Invalid date format';

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
                $originalLaunch = new DateTime($rowOrig['date_debut']);
            }
        }
        $launchChanged = !$isEdit || !$originalLaunch || $fLaunch->format('Y-m-d H:i') !== $originalLaunch->format('Y-m-d H:i');

        if ($launchChanged && $fLaunch < $today) {
            $errors['launching_date'] = "The launch date can't be before today";
        }
        if ($fLaunch >= $fPre) {
            $errors['pre_donation_date'] = 'The pre-donation date must be after the launch date';
        }
        if ($fPre >= $fPost) {
            $errors['post_donation_date'] = 'The post-donation date must be after the pre-donation date';
        }
        if ($fClose && $fClose <= $fPost) {
            $errors['close_date'] = 'The close date must be after the post-donation date';
        }
    }
}

if (empty(trim($_POST['pays'] ?? ''))) {
    $errors['pays'] = 'Please select a country';
}

$paysValue = trim($_POST['pays'] ?? 'france');
$association = trim($_POST['association'] ?? '');
$activePhase = $_POST['active_phase'] ?? 'collection';

$_SESSION['reponses'] = array_merge($previousRep, [
    'nom_projet' => trim($_POST['nom_projet'] ?? ''),
    'type_event' => 'Donation',
    'link' => trim($_POST['link'] ?? ''),
    'launching_date' => $launchingDate,
    'launching_time' => $launchingTime,
    'pre_donation_date' => $preDonationDate,
    'pre_donation_time' => $preDonationTime,
    'post_donation_date' => $postDonationDate,
    'post_donation_time' => $postDonationTime,
    'close_date' => $closeDate,
    'close_time' => $closeTime,
    'pays' => $paysValue,
    'association' => $association,
    'active_phase' => $activePhase,
]);
$_SESSION['errors'] = $errors;

if (!empty($errors)) {
    $_SESSION['step'] = $step;
    header('Location: ./index.php?menuprincipal=config_envent&partie=AddEventGift');

    exit;
}

$data = [
    'nom_projet' => trim($_POST['nom_projet']),
    'type_event' => 'Donation',
    'link' => trim($_POST['link']),
    'launching_date' => buildDatetime($launchingDate, $launchingTime),
    'result_date' => buildDatetime($preDonationDate, $preDonationTime),
    'end_date' => buildDatetime($postDonationDate, $postDonationTime),
    'close_date' => !empty($closeDate) ? buildDatetime($closeDate, $closeTime) : null,
    'pays_list' => [$paysValue],
];

$phasesPost = $_POST['phases'] ?? [];
saveGiftPhaseText((int)$eventId, $association, $phasesPost);

if ($action === 'pre-publish' && $eventId) {
    $nouvelEtat = $statutActuel === 'draft'
        ? 'pre-prod'
        : ($statutActuel === 'pre-prod' ? 'prod' : $statutActuel);
    $id = saveEvent($data, $eventId, $nouvelEtat);
    $_SESSION['statut'] = $nouvelEtat;
    if ($id) $_SESSION['event_id'] = $id;
    unset($_SESSION['step']);
    header('Location: ./index.php?menuprincipal=config_envent&partie=AddEventGift');
    exit;
}

$id = saveEvent($data, $eventId, $statutActuel);
if ($id) {
    $_SESSION['event_id'] = $id;
    $_SESSION['statut'] = $statutActuel;
}

unset($_SESSION['step']);
    header('Location: ./index.php?menuprincipal=config_envent&partie=AddEventGift');
exit;
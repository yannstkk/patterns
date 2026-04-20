<?php
session_start();
include_once __DIR__ . '/model/event.php';

if (isset($_GET['new'])) {
    $_SESSION['reponses'] = [];
    $_SESSION['errors'] = [];
    $_SESSION['statut'] = 'draft';
    $_SESSION['event_id'] = null;
    $_SESSION['images'] = [];
    $_SESSION['is_edit'] = false;
    unset($_SESSION['step']);
    header('Location: index.php?page=AddEvent');
    exit;
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $cnx->prepare("SELECT * FROM config_event WHERE ID = :id LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $eventData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($eventData) {
        $langToPays = [
            'fr' => 'france', 'en' => 'uk', 'it' => 'italy',
            'others' => 'others', 'es' => 'others', 'spain' => 'others',
            'france' => 'france', 'uk' => 'uk', 'italy' => 'italy',
        ];
        $pays = [];
        foreach (explode(',', $eventData['langue'] ?? '') as $code) {
            $code = strtolower(trim($code));
            if (isset($langToPays[$code])) $pays[] = $langToPays[$code];
        }
        $pays = array_unique($pays);

        $_SESSION['event_id'] = $editId;
        $_SESSION['statut'] = $eventData['etat_event'] ?? 'draft';
        $_SESSION['errors'] = [];
        $_SESSION['is_edit'] = true;
        unset($_SESSION['step']);
        $_SESSION['reponses'] = [
            'nom_projet' => $eventData['titre'] ?? '',
            'type_event' => $eventData['type_event'] ?? '',
            'link' => $eventData['supplement_url'] ?? '',
            'launching_date' => !empty($eventData['date_debut']) ? date('Y-m-d', strtotime($eventData['date_debut'])) : '',
            'result_date' => !empty($eventData['date_winner']) ? date('Y-m-d', strtotime($eventData['date_winner'])) : '',
            'end_date' => !empty($eventData['date_fin']) ? date('Y-m-d', strtotime($eventData['date_fin'])) : '',
            'pays_list' => $pays,
        ];

        $_SESSION['images'] = [];
        try {
            $stmtImg = $cnx->prepare("SELECT slot_key, filename FROM image_event WHERE event_id = :id");
            $stmtImg->execute([':id' => $editId]);
            foreach ($stmtImg->fetchAll(PDO::FETCH_ASSOC) as $img) {
                $parts = explode('_', $img['slot_key'], 2);
                $imgPays = $parts[0];
                $imgIndex = isset($parts[1]) ? (int)$parts[1] : 0;
                $_SESSION['images'][$imgPays][$imgIndex] = $img['filename'];
            }
        } catch (PDOException $e) {}
    }
}

$statut = $_SESSION['statut'] ?? 'draft';
$rep = $_SESSION['reponses'] ?? [];
$errors = $_SESSION['errors'] ?? [];
$eventId = $_SESSION['event_id'] ?? null;

if (!empty($errors) && isset($_SESSION['step'])) {
    $step = (int)$_SESSION['step'];
} else {
    $step = 1;
    if ($eventId) {
        $step = 2;
        if (!empty($rep['link']) && !empty($rep['launching_date']) && !empty($rep['result_date']) && !empty($rep['end_date'])) {
            $step = 3;
        }
    }
}

$paysList = $rep['pays_list'] ?? [];
$hFR = in_array('france', $paysList) ? '' : 'style="display:none"';
$hUK = in_array('uk', $paysList) ? '' : 'style="display:none"';
$hOTH = in_array('others', $paysList) ? '' : 'style="display:none"';

$saveBtnDisabled = in_array($statut, ['pre-prod', 'prod']) ? 'disabled' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add an Event</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<div class="page">

    <div class="entete">
        <h1 style="color:red;">Add an Event</h1>
        <div style="font-size:20px;">
            Event Status :
            <span class="badge badge-<?= $statut ?>"><?= ucfirst($statut) ?></span>
        </div>
    </div>

    <form method="POST" action="./validation/validAddEvent.php" enctype="multipart/form-data">
        <input type="hidden" name="statut_actuel" value="<?= $statut ?>">
        <input type="hidden" name="step" value="<?= $step ?>">

        <h2>Event information :</h2>

        <div class="ligne">
            <label class="<?= isset($errors['nom_projet']) ? 'error' : '' ?>">Project name :</label>
            <input type="text" name="nom_projet"
                   class="<?= isset($errors['nom_projet']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($rep['nom_projet'] ?? '') ?>">
            <?php if (isset($errors['nom_projet'])): ?>
                <span class="error"><?= $errors['nom_projet'] ?></span>
            <?php endif; ?>
        </div>

        <div class="ligne">
            <label>Event type :</label>
            <select name="type_event" <?= $statut === 'prod' ? 'disabled' : '' ?>>
                <?php foreach (['Fun Month', 'Donnation'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($rep['type_event'] ?? '') === $opt ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($step === 1): ?>
        <div class="boutons" style="margin-top:16px;">
            <button type="submit" name="action" value="save" class="btn-save">Save</button>
        </div>
        <?php endif; ?>

        <?php if ($step >= 2): ?>
        <div class="ligne">
            <label class="<?= isset($errors['link']) ? 'error' : '' ?>">Event link :</label>
            <input type="text" name="link" style="width:300px"
                   class="<?= isset($errors['link']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($rep['link'] ?? '') ?>">
            <?php if (isset($errors['link'])): ?>
                <span class="error"><?= $errors['link'] ?></span>
            <?php endif; ?>
        </div>

        <div class="ligne">
            <label class="<?= isset($errors['launching_date']) ? 'error' : '' ?>">Event launch :</label>
            <input type="date" name="launching_date"
                   class="<?= isset($errors['launching_date']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($rep['launching_date'] ?? '') ?>">
            <?php if (isset($errors['launching_date'])): ?>
                <span class="error"><?= $errors['launching_date'] ?></span>
            <?php endif; ?>
        </div>

        <div class="ligne">
            <label class="<?= isset($errors['result_date']) ? 'error' : '' ?>">Display results :</label>
            <input type="date" name="result_date"
                   class="<?= isset($errors['result_date']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($rep['result_date'] ?? '') ?>">
            <?php if (isset($errors['result_date'])): ?>
                <span class="error"><?= $errors['result_date'] ?></span>
            <?php endif; ?>
        </div>

        <div class="ligne">
            <label class="<?= isset($errors['end_date']) ? 'error' : '' ?>">Event end :</label>
            <input type="date" name="end_date"
                   class="<?= isset($errors['end_date']) ? 'error' : '' ?>"
                   value="<?= htmlspecialchars($rep['end_date'] ?? '') ?>">
            <?php if (isset($errors['end_date'])): ?>
                <span class="error"><?= $errors['end_date'] ?></span>
            <?php endif; ?>
        </div>

        <div class="ligne">
            <label><input type="checkbox"> Enable an internal version of the event</label>
        </div>

        <?php if (!empty($errors)): ?>
            <span class="error">Some input(s) are empty or invalid</span>
        <?php endif; ?>
        <?php if (isset($errors['images'])): ?>
            <span class="error"><?= $errors['images'] ?></span>
        <?php endif; ?>

        <?php if ($step === 2): ?>
        <div class="boutons" style="margin-top:16px;">
            <button type="submit" name="action" value="save" class="btn-save">Save</button>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($step >= 3): ?>
        <br><hr>

        <div class="ligne">
            <label class="<?= isset($errors['pays_list[]']) ? 'error' : '' ?>">Country :</label>
            <div class="pays-liste">
                <?php foreach (['france' => 'France', 'uk' => 'UK', 'italy' => 'Italy', 'others' => 'Others'] as $val => $label): ?>
                <label>
                    <input type="checkbox" value="<?= $val ?>" name="pays_list[]" onchange="updateOnglets()"
                        <?= in_array($val, $rep['pays_list'] ?? []) ? 'checked' : '' ?>
                        <?= $statut === 'prod' ? 'disabled' : '' ?>>
                    <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (isset($errors['pays_list[]'])): ?>
            <span class="error"><?= $errors['pays_list[]'] ?></span>
        <?php endif; ?>

        <div class="bloc-banniere">
            <h2>Event banner management</h2>
            <p style="color:#999;font-style:italic;">Count equals the number of missing images per language</p>

            <div class="onglets" id="onglets">
                <button type="button" class="onglet" id="onglet-france" style="display:none" onclick="chargeOnglet('france', this)">
                    French (<span id="compteur-france">0</span>)
                </button>
                <button type="button" class="onglet" id="onglet-uk" style="display:none" onclick="chargeOnglet('uk', this)">
                    English (<span id="compteur-uk">0</span>)
                </button>
                <button type="button" class="onglet" id="onglet-italy" style="display:none" onclick="chargeOnglet('italy', this)">
                    Italian (<span id="compteur-italy">0</span>)
                </button>
                <button type="button" class="onglet" id="onglet-spain" style="display:none" onclick="chargeOnglet('spain', this)">
                    Spanish (<span id="compteur-spain">0</span>)
                </button>
            </div>

            <p id="message-aucun-pays" style="color:#999;font-style:italic;">Please select at least one country</p>

            <div class="contenu" id="france">
                <h3>Main display</h3>
                <div class="grille">
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">P 220x181 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="220x181"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">DCM 298x130 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="298x130"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">DCM 428x125 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">TDP 500x400 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="500x400"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">TDP ?x? (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">AP 455x184 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">FCT ? x ? (Deco &amp; CO) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">TRY (Taille à def) (CO &amp; Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">APPS 620x180 (Others) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                </div>
                <h3>Display results</h3>
                <div class="grille">
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">P 220x181 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="220x181"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">DCM 298x130 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="298x130"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">DCM 428x125 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">TDP 500x400 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="500x400"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">TDP ?x? (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">AP 455x184 (Co et Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="france" <?= $hFR ?>><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">FCT ? x ? (Deco &amp; CO) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">TRY (Taille à def) (CO &amp; Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">APPS 620x180 (Others) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                </div>
            </div>

            <div class="contenu" id="uk">
                <h3>Main display</h3>
                <div class="grille">
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">Survey Friends 870x310 (Co &amp; Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="870x310"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PFG (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PFG (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">MDO 298x130 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="298x130"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">MDO (Déco) 428x125 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PPT testing 500x400 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="500x400"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PPT ?x? (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">AP 455x184 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                </div>
                <h3>Display results</h3>
                <div class="grille">
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">Survey Friends 870x310 (Co &amp; Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="870x310"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PFG (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PFG (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">MDO 298x130 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="298x130"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">MDO (Déco) 428x125 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PPT testing 500x400 (Déco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="500x400"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">PPT ?x? (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">AP 455x184 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="uk" <?= $hUK ?>><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                </div>
            </div>

            <div class="contenu" id="italy"></div>

            <div class="contenu" id="spain">
                <h3>Main display</h3>
                <div class="grille">
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">FCT ? x ? (Deco &amp; CO) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">TRY (Taille à def) (CO &amp; Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                </div>
                <h3>Display results</h3>
                <div class="grille">
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">FCT ? x ? (Deco &amp; CO) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">TRY (Taille à def) (CO &amp; Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Deco) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">RS 455x184 (Co) <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
                    <div class="slot" data-source="others" <?= $hOTH ?>><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?</span></div>
                        <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
                </div>
            </div>

        </div>

        <div class="boutons">
            <button type="submit" name="action" value="save" class="btn-save" <?= $saveBtnDisabled ?>>Save</button>
            <?php if ($statut !== 'prod'): ?>
            <button type="submit" name="action" value="pre-publish" class="btn-publish" disabled>
                <?= $statut === 'pre-prod' ? 'Publish' : 'Pre-publish' ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </form>
</div>

<script>
    var savedImages = <?= json_encode($_SESSION['images'] ?? []) ?>;
    var eventId = <?= json_encode($_SESSION['event_id'] ?? null) ?>;
</script>
<script src="./js/script.js"></script>
</body>
</html>
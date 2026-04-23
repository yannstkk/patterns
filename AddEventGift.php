<?php
session_start();
include_once __DIR__.'/model/event.php';

if (isset($_GET['new'])) {
    $_SESSION['reponses'] = [];
    $_SESSION['errors'] = [];
    $_SESSION['statut'] = 'draft';
    $_SESSION['event_id'] = null;
    $_SESSION['images'] = [];
    $_SESSION['gift_assets'] = [];
    $_SESSION['is_edit'] = false;
    unset($_SESSION['step']);
    header('Location: index.php?page=AddEventGift');
    exit;
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $cnx->prepare("SELECT * FROM config_event WHERE ID = :id LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $eventData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($eventData) {
        $langToSingle = [
            'fr' => 'france', 'en' => 'uk', 'it' => 'italy',
            'others' => 'others', 'france' => 'france', 'uk' => 'uk', 'italy' => 'italy',
        ];
        $langRaw = strtolower(trim(explode(',', $eventData['langue'] ?? '')[0]));
        $pays = $langToSingle[$langRaw] ?? 'france';

        $_SESSION['event_id'] = $editId;
        $_SESSION['statut'] = $eventData['etat_event'] ?? 'draft';
        $_SESSION['errors'] = [];
        $_SESSION['is_edit'] = true;
        unset($_SESSION['step']);
        $_SESSION['reponses'] = [
            'nom_projet' => $eventData['titre'] ?? '',
            'type_event' => 'Gift',
            'link' => $eventData['supplement_url'] ?? '',
            'launching_date' => !empty($eventData['date_debut']) ? date('Y-m-d', strtotime($eventData['date_debut'])) : '',
            'pre_donation_date' => !empty($eventData['date_winner']) ? date('Y-m-d', strtotime($eventData['date_winner'])) : '',
            'post_donation_date' => !empty($eventData['date_fin']) ? date('Y-m-d', strtotime($eventData['date_fin'])) : '',
            'pays' => $pays,
            'association' => $eventData['association'] ?? '',
            'introduction' => $eventData['introduction'] ?? '',
            'about_association' => $eventData['about_association'] ?? '',
            'active_phase' => 'collection',
        ];

        $_SESSION['images'] = [];
        try {
            $stmtImg = $cnx->prepare("SELECT name_image, slot_index, pays FROM image_events WHERE id_event = :id");
            $stmtImg->execute([':id' => $editId]);
            foreach ($stmtImg->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $_SESSION['images'][$row['pays']][(int)$row['slot_index']] = $row['name_image'];
            }
        } catch (PDOException $e) {}

        $_SESSION['gift_assets'] = [];
        foreach (['logo' => 0, 'arriere_plan' => 1, 'image1' => 2, 'image2' => 3] as $key => $idx) {
            if (isset($_SESSION['images']['gift_assets'][$idx])) {
                $_SESSION['gift_assets'][$key] = $_SESSION['images']['gift_assets'][$idx];
            }
        }
    }
}

$statut = $_SESSION['statut'] ?? 'draft';
$rep = $_SESSION['reponses'] ?? [];
$errors = $_SESSION['errors'] ?? [];
$eventId = $_SESSION['event_id'] ?? null;
$giftAssets = $_SESSION['gift_assets'] ?? [];

if (!empty($errors) && isset($_SESSION['step'])) {
    $step = (int)$_SESSION['step'];
} else {
    $step = 1;
    if ($eventId) {
        $step = 2;
    }
}

$pays = $rep['pays'] ?? 'france';
$activePhase = $rep['active_phase'] ?? 'collection';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add an Event - Gift</title>
<link rel="stylesheet" href="./css/style.css">
<link rel="stylesheet" href="./css/style_gift.css">
</head>
<body>
<div class="page">

<div class="entete">
    <h1 style="color:red;">Add an event</h1>
    <div style="font-size:20px;">
        Event status :
        <span class="badge badge-<?= $statut ?>"><?= ucfirst($statut) ?></span>
    </div>
</div>

<form method="POST" action="./validation/validAddEventGift.php" enctype="multipart/form-data" id="gift-form">
<input type="hidden" name="statut_actuel" value="<?= $statut ?>">
<input type="hidden" name="step" value="<?= $step ?>">
<input type="hidden" name="active_phase" value="<?= htmlspecialchars($activePhase) ?>" id="input-active-phase">

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
    <select name="type_event" <?= $eventId ? 'disabled style="background:#e9e9e9;color:#888;cursor:not-allowed;"' : '' ?>>
        <?php foreach (['Fun Month', 'Gift'] as $opt): ?>
            <option value="<?= $opt ?>" <?= ($rep['type_event'] ?? 'Gift') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($eventId): ?>
        <input type="hidden" name="type_event" value="<?= htmlspecialchars($rep['type_event'] ?? 'Gift') ?>">
    <?php endif; ?>
</div>

<?php if ($step === 1): ?>
<div class="boutons" style="margin-top:16px;">
    <button type="submit" name="action" value="save" class="btn-save">Save</button>
</div>
<?php endif; ?>

<?php if ($step >= 2): ?>

<div class="ligne">
    <label class="<?= isset($errors['link']) ? 'error' : '' ?>">Event links :</label>
    <input type="text" name="link" style="width:340px"
        class="<?= isset($errors['link']) ? 'error' : '' ?>"
        value="<?= htmlspecialchars($rep['link'] ?? '') ?>">
    <?php if (isset($errors['link'])): ?>
        <span class="error"><?= $errors['link'] ?></span>
    <?php endif; ?>
</div>

<div class="ligne gift-assets-ligne">
    <div class="gift-asset-bloc">
        <label>Logo :</label>
        <div class="gift-asset-input" data-asset="logo">
            <span class="gift-asset-name"><?= htmlspecialchars($giftAssets['logo'] ?? '') ?: 'Add picture' ?></span>
            <img src="./img/AddPngPicture.png" style="width:14px;height:15px;flex-shrink:0;">
            <input type="file" accept="image/png,image/jpeg" style="display:none;">
        </div>
    </div>
    <div class="gift-asset-bloc">
        <label>Associations :</label>
        <input type="text" name="association" style="width:220px"
            value="<?= htmlspecialchars($rep['association'] ?? '') ?>"
            placeholder="Association name">
    </div>
    <div class="gift-asset-bloc">
        <label>Arriere plan :</label>
        <div class="gift-asset-input" data-asset="arriere_plan">
            <span class="gift-asset-name"><?= htmlspecialchars($giftAssets['arriere_plan'] ?? '') ?: 'Add picture' ?></span>
            <img src="./img/AddPngPicture.png" style="width:14px;height:15px;flex-shrink:0;">
            <input type="file" accept="image/png,image/jpeg" style="display:none;">
        </div>
    </div>
</div>

<div class="ligne">
    <label class="<?= isset($errors['launching_date']) ? 'error' : '' ?>">Launch Collection phase :</label>
    <input type="date" name="launching_date"
        class="<?= isset($errors['launching_date']) ? 'error' : '' ?>"
        value="<?= htmlspecialchars($rep['launching_date'] ?? '') ?>">
    <?php if (isset($errors['launching_date'])): ?>
        <span class="error"><?= $errors['launching_date'] ?></span>
    <?php endif; ?>
</div>

<div class="ligne">
    <label class="<?= isset($errors['pre_donation_date']) ? 'error' : '' ?>">Pre-donation handover :</label>
    <input type="date" name="pre_donation_date"
        class="<?= isset($errors['pre_donation_date']) ? 'error' : '' ?>"
        value="<?= htmlspecialchars($rep['pre_donation_date'] ?? '') ?>">
    <?php if (isset($errors['pre_donation_date'])): ?>
        <span class="error"><?= $errors['pre_donation_date'] ?></span>
    <?php endif; ?>
</div>

<div class="ligne">
    <label class="<?= isset($errors['post_donation_date']) ? 'error' : '' ?>">Post-donation handover :</label>
    <input type="date" name="post_donation_date"
        class="<?= isset($errors['post_donation_date']) ? 'error' : '' ?>"
        value="<?= htmlspecialchars($rep['post_donation_date'] ?? '') ?>">
    <?php if (isset($errors['post_donation_date'])): ?>
        <span class="error"><?= $errors['post_donation_date'] ?></span>
    <?php endif; ?>
</div>

<div class="phase-tabs">
    <button type="button" class="phase-tab <?= $activePhase === 'collection' ? 'phase-tab-actif' : '' ?>" data-phase="collection" onclick="setPhase('collection', this)">
        Collection phase
    </button>
    <button type="button" class="phase-tab <?= $activePhase === 'pre-donation' ? 'phase-tab-actif' : '' ?>" data-phase="pre-donation" onclick="setPhase('pre-donation', this)">
        Pre-donation
    </button>
    <button type="button" class="phase-tab <?= $activePhase === 'post-donation' ? 'phase-tab-actif' : '' ?>" data-phase="post-donation" onclick="setPhase('post-donation', this)">
        Post-donation
    </button>
</div>

<div class="ligne gift-images-ligne">
    <div class="gift-image-bloc">
        <label>Image 1 :</label>
        <div class="gift-asset-input gift-img-full" data-asset="image1">
            <span class="gift-asset-name"><?= htmlspecialchars($giftAssets['image1'] ?? '') ?: '' ?></span>
            <img src="./img/AddPngPicture.png" style="width:14px;height:15px;flex-shrink:0;">
            <input type="file" accept="image/png,image/jpeg" style="display:none;">
        </div>
    </div>
    <div class="gift-image-bloc">
        <label>Image 2 :</label>
        <div class="gift-asset-input gift-img-full" data-asset="image2">
            <span class="gift-asset-name"><?= htmlspecialchars($giftAssets['image2'] ?? '') ?: '' ?></span>
            <img src="./img/AddPngPicture.png" style="width:14px;height:15px;flex-shrink:0;">
            <input type="file" accept="image/png,image/jpeg" style="display:none;">
        </div>
    </div>
</div>

<div class="gift-editors-section">
    <div class="gift-editors-left">
        <div class="editor-bloc">
            <label>Introduction :</label>
            <div class="editor-wrapper">
                <div class="editor-toolbar" id="toolbar-intro">
                    <button type="button" class="editor-btn" onclick="editorCmd('introduction-editor','bold')"><b>B</b></button>
                    <button type="button" class="editor-btn editor-italic" onclick="editorCmd('introduction-editor','italic')">I</button>
                    <button type="button" class="editor-btn editor-underline" onclick="editorCmd('introduction-editor','underline')">U</button>
                    <select class="editor-fontsize" onchange="editorFontSize('introduction-editor', this.value)">
                        <option value="1">8pt</option>
                        <option value="2">10pt</option>
                        <option value="3" selected>12pt</option>
                        <option value="4">14pt</option>
                        <option value="5">18pt</option>
                    </select>
                    <button type="button" class="editor-btn editor-align" onclick="editorCmd('introduction-editor','justifyLeft')"><img src="./img/JustifyLeft_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
                    <button type="button" class="editor-btn editor-align" onclick="editorCmd('introduction-editor','justifyCenter')"><img src="./img/JustifyCenter_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
                    <button type="button" class="editor-btn editor-align editor-align-right" onclick="editorCmd('introduction-editor','justifyRight')"><img src="./img/JustifyRight_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
                    <button type="button" class="editor-btn editor-align" onclick="editorCmd('introduction-editor','justifyFull')"><img src="./img/JustifyFull_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
                </div>
                <div class="editor-content" id="introduction-editor" contenteditable="true"><?= $rep['introduction'] ?? '' ?></div>
                <input type="hidden" name="introduction" id="introduction-hidden" value="<?= htmlspecialchars($rep['introduction'] ?? '') ?>">
            </div>
        </div>
    </div>
    <div class="gift-variables-panel">
        <p class="titre-bloc-variable">Several variables can be used in the text:</p>
        <div class="variable-ligne"><span class="variable-tag">[@@Association@@]</span> <span class="variable-desc">:  = Association name</span></div>
        <div class="variable-ligne"><span class="variable-tag">[@@Dons@@]</span> <span class="variable-desc">:  = Total amount collected in the fundraiser</span></div>
        <div class="variable-ligne"><span class="variable-tag">[@@DateFin@@]</span> <span class="variable-desc">:  = Display results</span></div>
        <div class="variable-ligne"><span class="variable-tag">[@@DateRemise@@]</span> <span class="variable-desc">:  = Display results</span></div>
        <div class="variable-ligne"><span class="variable-tag">[@@Oldgift@@]</span> <span class="variable-desc">:  = Result of the previous campaign</span></div>
    </div>
</div>

<div class="editor-bloc" style="margin-top:14px;">
    <label>About the association :</label>
    <div class="editor-wrapper">
        <div class="editor-toolbar">
            <button type="button" class="editor-btn" onclick="editorCmd('about-editor','bold')"><b>B</b></button>
            <button type="button" class="editor-btn editor-italic" onclick="editorCmd('about-editor','italic')">I</button>
            <button type="button" class="editor-btn editor-underline" onclick="editorCmd('about-editor','underline')">U</button>
            <select class="editor-fontsize" onchange="editorFontSize('about-editor', this.value)">
                <option value="1">8pt</option>
                <option value="2">10pt</option>
                <option value="3" selected>12pt</option>
                <option value="4">14pt</option>
                <option value="5">18pt</option>
            </select>
            <button type="button" class="editor-btn editor-align" onclick="editorCmd('about-editor','justifyLeft')"><img src="./img/JustifyLeft_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
            <button type="button" class="editor-btn editor-align" onclick="editorCmd('about-editor','justifyCenter')"><img src="./img/JustifyCenter_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
            <button type="button" class="editor-btn editor-align editor-align-right" onclick="editorCmd('about-editor','justifyRight')"><img src="./img/JustifyRight_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
            <button type="button" class="editor-btn editor-align" onclick="editorCmd('about-editor','justifyFull')"><img src="./img/JustifyFull_icon.png" style="width:14px;height:14px;pointer-events:none;"></button>
        </div>
        <div class="editor-content" id="about-editor" contenteditable="true"><?= $rep['about_association'] ?? '' ?></div>
        <input type="hidden" name="about_association" id="about-hidden" value="<?= htmlspecialchars($rep['about_association'] ?? '') ?>">
    </div>
</div>

<div style="display:flex;justify-content:flex-end;margin-top:10px;">
    <button type="button" class="btn-preview" onclick="openPreview()">Preview</button>
</div>

<br><hr>

<div class="ligne">
    <label class="<?= isset($errors['pays']) ? 'error' : '' ?>">Pays de l'evenement :</label>
    <div class="pays-liste">
        <?php foreach (['france' => 'France', 'uk' => 'UK', 'italy' => 'Italy', 'others' => 'Others'] as $val => $label): ?>
        <label>
            <input type="radio" name="pays" value="<?= $val ?>"
                <?= $pays === $val ? 'checked' : '' ?>
                onchange="updateGiftOnglet(this.value)">
            <?= $label ?>
        </label>
        <?php endforeach; ?>
    </div>
    <?php if (isset($errors['pays'])): ?>
        <span class="error"><?= $errors['pays'] ?></span>
    <?php endif; ?>
</div>

<div class="bloc-banniere">
    <h2>Event banner management</h2>
    <p style="color:#999;font-style:italic;">Count equals the number of missing images per language</p>

    <div class="onglets" id="onglets">
        <button type="button" class="onglet actif" id="onglet-france" onclick="chargeGiftOnglet('france', this)">
            French (<span id="compteur-france">0</span>)
        </button>
        <button type="button" class="onglet" id="onglet-uk" style="display:none" onclick="chargeGiftOnglet('uk', this)">
            English (<span id="compteur-uk">0</span>)
        </button>
        <button type="button" class="onglet" id="onglet-italy" style="display:none" onclick="chargeGiftOnglet('italy', this)">
            Italian (<span id="compteur-italy">0</span>)
        </button>
        <button type="button" class="onglet" id="onglet-others" style="display:none" onclick="chargeGiftOnglet('others', this)">
            Others (<span id="compteur-others">0</span>)
        </button>
    </div>

    <div class="contenu actif" id="france">
        <h3>Main display</h3>
        <div class="grille">
            <div class="slot" data-source="france" data-site="P" data-mode="logInOut"><div class="slot-label">P 220x181 (Co et Déco) <span class="icon-interrogation">?<img src="./img/banner_P.png" class="banner-preview-large"></span></div>
                <div class="slot-input" data-size="220x181"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="DCM" data-mode="logIN"><div class="slot-label">DCM 298x130 (Co) <span class="icon-interrogation">?<img src="./img/banner_DCM_login.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="298x130"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="DCM" data-mode="logout"><div class="slot-label">DCM 428x125 (Déco) <span class="icon-interrogation">?<img src="./img/banner_DCM_logout.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="TDP" data-mode="logout"><div class="slot-label">TDP 500x400 (Déco) <span class="icon-interrogation">?<img src="./img/banner_TDP_logout.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="500x400"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="TDP" data-mode="logIN"><div class="slot-label">TDP ? X ? (Co) <span class="icon-interrogation">?<img src="./img/banner1.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="AP" data-mode="logInOut"><div class="slot-label">AP 455x184 (Co et Déco) <span class="icon-interrogation">?<img src="./img/banner_AP.png" class="banner-preview-large"></span></div>
                <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="APPS" data-mode=""><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?<img src="./img/banner_APPS.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
        </div>

        <h3>Results display</h3>
        <div class="grille">
            <div class="slot" data-source="france" data-site="P" data-mode="logInOut"><div class="slot-label">P 220x181 (Co et Déco) <span class="icon-interrogation">?<img src="./img/banner_P.png" class="banner-preview-large"></span></div>
                <div class="slot-input" data-size="220x181"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="DCM" data-mode="logIN"><div class="slot-label">DCM 298x130 (Co) <span class="icon-interrogation">?<img src="./img/banner_DCM_login.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="298x130"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="DCM" data-mode="logout"><div class="slot-label">DCM 428x125 (Déco) <span class="icon-interrogation">?<img src="./img/banner_DCM_logout.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="TDP" data-mode="logout"><div class="slot-label">TDP 500x400 (Déco) <span class="icon-interrogation">?<img src="./img/banner_TDP_logout.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="500x400"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="TDP" data-mode="logIN"><div class="slot-label">TDP ? X ? (Co) <span class="icon-interrogation">?<img src="./img/banner1.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size=""><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="AP" data-mode="logInOut"><div class="slot-label">AP 455x184 (Co et Déco) <span class="icon-interrogation">?<img src="./img/banner_AP.png" class="banner-preview-large"></span></div>
                <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
            <div class="slot" data-source="france" data-site="APPS" data-mode=""><div class="slot-label">APPS 620x180 <span class="icon-interrogation">?<img src="./img/banner_APPS.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
        </div>
    </div>

    <div class="contenu" id="uk">
        <h3>Main display</h3>
        <div class="grille">
            <div class="slot" data-source="uk" data-site="MDO" data-mode="logout"><div class="slot-label">MDO 428x125 (Déco) <span class="icon-interrogation">?<img src="./img/banner1.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="428x125"><span>Add PNG picture</span></div></div>
        </div>
    </div>

    <div class="contenu" id="italy">
        <h3>Main display</h3>
        <div class="grille">
            <div class="slot" data-source="italy" data-site="RS" data-mode="logout"><div class="slot-label">RS 620x180 (Déco) <span class="icon-interrogation">?<img src="./img/banner1.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="620x180"><span>Add PNG picture</span></div></div>
        </div>
    </div>

    <div class="contenu" id="others">
        <h3>Main display</h3>
        <div class="grille">
            <div class="slot" data-source="others" data-site="RS" data-mode="logout"><div class="slot-label">RS 455x184 (Déco) <span class="icon-interrogation">?<img src="./img/banner1.png" class="banner-preview"></span></div>
                <div class="slot-input" data-size="455x184"><span>Add PNG picture</span></div></div>
        </div>
    </div>
</div>

<div class="boutons">
    <button type="submit" name="action" value="save" class="btn-save">Save</button>
    <button type="submit" name="action" value="pre-publish" class="btn-publish" disabled>Pre-publish</button>
</div>

<?php endif; ?>
</form>
</div>

<script>
var savedImages = <?= json_encode($_SESSION['images'] ?? []) ?>;
var checkedImages = {};
var eventId = <?= json_encode($_SESSION['event_id'] ?? null) ?>;
var giftAssets = <?= json_encode($_SESSION['gift_assets'] ?? []) ?>;
</script>
<script src="./js/script_gift.js"></script>
</body>
</html>
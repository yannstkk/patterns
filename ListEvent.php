<?php
include("./model/event.php");
session_start();
$events = getListEvent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management of Events</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<div class="page">

    <h1 class="list-title">Events' Management</h1>

    <div class="filtres">
        <button class="filtre-btn actif" onclick="filtrer('all', this)">All</button>
        <button class="filtre-btn" onclick="filtrer('Fun Month', this)">Fun Month</button>
        <button class="filtre-btn" onclick="filtrer('Gift', this)">Gift</button>
    </div>

    <div class="barre-outils">
        <div class="recherche">
            <img src="./img/searchIcon.png" alt="" style="width:13px;opacity:0.55;flex-shrink:0;">
            <input type="text" id="champ-recherche" placeholder="Search" oninput="appliquerFiltres()">
        </div>
        <a href="index.php?page=AddEvent&new=1" class="btn-add">+Add an event</a>
    </div>


    
    <table class="list-table">
        <thead>
            <tr>
                <th style="width:42px;">N°</th>
                <th style="width:190px;">Project Name</th>
                <th style="width:95px;">Event</th>
                <th style="width:75px;">Start</th>
                <th style="width:75px;">Result</th>
                <th style="width:75px;">End</th>
                <th style="width:70px;">Country</th>
                <th style="width:85px;">Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="corps-tableau">
        <?php foreach ($events as $e): ?>
        <?php
            $fStart = !empty($e['date_debut']) ? date('d/m/y', strtotime($e['date_debut']))  : '-';
            $fResult = !empty($e['date_winner']) ? date('d/m/y', strtotime($e['date_winner'])) : '-';
            $fEnd = !empty($e['date_fin']) ? date('d/m/y', strtotime($e['date_fin'])) : '-';

            $langMap = ['fr'=>'FR','en'=>'EN','it'=>'IT','es'=>'ES','others'=>'ES',
                        'france'=>'FR','uk'=>'EN','italy'=>'IT','spain'=>'ES'];
            $rawLangs = array_filter(array_map('trim', explode(',', $e['langue'] ?? '')));
            $langs = array_unique(array_filter(array_map(function($l) use ($langMap) {
                return isset($langMap[strtolower($l)]) ? $langMap[strtolower($l)] : null;
            }, $rawLangs)));
            $countryDisplay = count($langs) >= 4 ? 'ALL' : implode(', ', $langs);
            if (empty($countryDisplay)) $countryDisplay = '-';

            $etat = $e['etat_event'] ?? 'draft';


            if (!empty($e['date_close']) && new DateTime() >= new DateTime($e['date_close'])) {
                $etat = 'close';
            }

            if ($etat === 'pre-prod') {
                $badgeClass = 'status-preprod';
                $badgeLabel = 'Pre-prod';
            } elseif ($etat === 'prod') {
                $badgeClass = 'status-prod';
                $badgeLabel = 'Prod';
            } elseif ($etat === 'close') {
                $badgeClass = 'status-close';
                $badgeLabel = 'Close';
            } else {
                $badgeClass = 'status-draft';
                $badgeLabel = 'Draft';
            }
        ?>
        <tr data-type="<?= htmlspecialchars($e['type_event'] ?? '') ?>">
            <td class="cell-center"><?= (int)$e['ID'] ?></td>
            <td class="cell-name"><?= htmlspecialchars($e['titre'] ?? '') ?></td>
            <td class="cell-center"><?= htmlspecialchars($e['type_event'] ?? '') ?></td>
            <td class="cell-center"><?= $fStart ?></td>
            <td class="cell-center"><?= $fResult ?></td>
            <td class="cell-center"><?= $fEnd ?></td>
            <td class="cell-center"><?= htmlspecialchars($countryDisplay) ?></td>
            <td class="cell-status"><span class="status-badge <?= $badgeClass ?>" ><?= $badgeLabel ?></span></td>
            <td class="cell-actions">
                <a href="<?= htmlspecialchars($e['supplement_url'] ?? '#') ?>" target="_blank" title="View">
                    <img src="./img/webIcon.png" alt="View" style="width:17px;">
                </a>
                <?php $editPage = ($e['type_event'] === 'Gift') ? 'AddEventGift' : 'AddEvent'; ?>
                <a href="index.php?page=<?= $editPage ?>&edit=<?= (int)$e['ID'] ?>" title="Edit">
                    <img src="./img/editIcon.png" alt="Edit" style="width:17px;">
                </a>
                <?php if ($etat === 'draft'): ?>
                <a href="validation/deleteEvent.php?id=<?= (int)$e['ID'] ?>"
                onclick="return confirm('Delete this event?')" title="Delete">
                    <img src="./img/deleteIcon.png" alt="Delete" style="width:17px;">
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
<script src="./js/script.js"></script>
</body>
</html>
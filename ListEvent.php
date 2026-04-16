<?php 


$events = []; 


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

    <div class="entete">
        <h1 style="color:red;">Management of Fun Month</h1>
    </div>

    <div class="filtres">
        <button class="filtre-btn actif" onclick="filtrer('all', this)">All</button>
        <button class="filtre-btn" onclick="filtrer('Fun Month', this)">Fun Month</button>
        <button class="filtre-btn" onclick="filtrer('Gift', this)">Gift</button>
    </div>

    <div class="barre-outils">
        <div class="recherche">
            <input type="text" id="champ-recherche" placeholder="Search" oninput="appliquerFiltres()">
        </div>
        <a href="index.php?page=AddEvent&new=1" class="btn-add">+ Add an event</a>
    </div>

  

    <table class="tableau-events">
        <thead>
            <tr>
                <th>N°</th>
                <th>Project Name</th>
                <th>Event</th>
                <th>Start</th>
                <th>Result</th>
                <th>Fin</th>
                <th>Country</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="corps-tableau">
            <?php foreach ($events as $i => $e): ?>
            <tr data-type="<?= htmlspecialchars($e['type_event']) ?>">
                <td><?= $e['id'] ?></td>
                <td class="nom-projet"><?= htmlspecialchars($e['nom_projet']) ?></td>
                <td><?= htmlspecialchars($e['type_event']) ?></td>
                <td><?= $e['date_lancement'] ?></td>
                <td><?= $e['date_resultats'] ?></td>
                <td><?= $e['date_fin'] ?></td>
                <td><?= $e['pays'] ?></td>
                <td><span class="badge badge-<?= $e['statut'] ?>"><?= $e['statut'] ?></span></td>
                <td>
                    <div class="actions">
                        <a href="#" title="Voir">&#127760;</a>
                        <a href="index.php?page=AddEvent&id=<?= $i ?>" title="Modifier">&#9998;</a>
                        <button class="btn-suppr" title="Supprimer">&#128465;</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

 

<script src="./js/script.js"></script>
</body>
</html>
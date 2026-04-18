<?php
include(__DIR__ . "/../config/config.php");

$cnx = connectBaseExterne_PDO(200);

function getListEvent() {
    global $cnx;
    $stmt = $cnx->prepare("SELECT * FROM config_event");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function saveEvent(array $data, $eventId = null, $etat = 'draft') {
    global $cnx;
    if (!$cnx) { error_log("saveEvent : connexion BDD null"); return null; }

    $mapLangue = [
        'france' => 'fr', 'uk' => 'en', 'italy' => 'it', 'others' => 'others',
    ];

    $codes = [];
    foreach (($data['pays_list'] ?? []) as $pays) {
        if (isset($mapLangue[$pays])) $codes[] = $mapLangue[$pays];
    }
    $langue = implode(',', array_unique($codes));

    $params = [
        ':titre'          => $data['nom_projet'],
        ':type_event'     => $data['type_event'],
        ':supplement_url' => $data['link'],
        ':date_debut'     => $data['launching_date'],
        ':date_winner'    => $data['result_date'],
        ':date_fin'       => $data['end_date'],
        ':langue'         => $langue,
        ':etat_event'     => $etat,
    ];

    try {
        if ($eventId) {
            $params[':id'] = (int)$eventId;
            $stmt = $cnx->prepare("
                UPDATE config_event SET
                    titre = :titre, type_event = :type_event, supplement_url = :supplement_url,
                    date_debut = :date_debut, date_winner = :date_winner, date_fin = :date_fin,
                    langue = :langue, etat_event = :etat_event
                WHERE ID = :id
            ");
            $stmt->execute($params);
            return $eventId;
        } else {
            $params[':etat_event'] = 'draft';
            $stmt = $cnx->prepare("
                INSERT INTO config_event
                    (titre, type_event, supplement_url, date_debut, date_winner, date_fin, langue, etat_event)
                VALUES
                    (:titre, :type_event, :supplement_url, :date_debut, :date_winner, :date_fin, :langue, :etat_event)
            ");
            $stmt->execute($params);
            return $cnx->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log("saveEvent erreur : " . $e->getMessage());
        return null;
    }
}
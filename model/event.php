<?php
include(__DIR__."/../config/config.php");

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
        ':titre' => $data['nom_projet'],
        ':type_event' => $data['type_event'],
        ':supplement_url' => $data['link'],
        ':date_debut' => !empty($data['launching_date']) ? $data['launching_date'] : null,
        ':date_winner'=> !empty($data['result_date'])    ? $data['result_date']    : null,
        ':date_fin'=> !empty($data['end_date']) ? $data['end_date']       : null,
        ':date_close'=> !empty($data['close_date']) ? $data['close_date']     : null,
        ':langue'=> $langue,
        ':etat_event'=> $etat,
    ];

    try {
        if ($eventId) {
            $params[':id'] = (int)$eventId;
            $stmt = $cnx->prepare("
                UPDATE config_event SET
                    titre = :titre, type_event = :type_event, supplement_url = :supplement_url,
                    date_debut = :date_debut, date_winner = :date_winner, date_fin = :date_fin,
                    date_close = :date_close, langue = :langue, etat_event = :etat_event
                WHERE ID = :id
            ");
            $stmt->execute($params);
            return $eventId;
        } else {
            $params[':etat_event'] = 'draft';
            $stmt = $cnx->prepare("
                INSERT INTO config_event
                    (titre, type_event, supplement_url, date_debut, date_winner, date_fin, date_close, langue, etat_event)
                VALUES
                    (:titre, :type_event, :supplement_url, :date_debut, :date_winner, :date_fin, :date_close, :langue, :etat_event)
            ");
            $stmt->execute($params);
            return $cnx->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log("saveEvent erreur : " . $e->getMessage());
        return null;
    }
}


function getGiftConfig(int $eventId): array {
    global $cnx;
    try {
        $stmt = $cnx->prepare("SELECT * FROM gift_event_config WHERE id_event = :id LIMIT 1");
        $stmt->execute([':id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return [];

        return [
            'collection' => [
                'association' => $row['association'],
                'logo' => $row['logo'],
                'arriere_plan' => $row['arriere_plan'],
                'introduction' => $row['col_introduction'],
                'about_association' => $row['col_about_association'],
                'image1' => $row['col_image1'],
                'image2' => $row['col_image2'],
            ],
            'pre-donation' => [
                'association' => $row['association'],
                'logo' => $row['logo'],
                'arriere_plan' => $row['arriere_plan'],
                'introduction' => $row['pre_introduction'],
                'about_association' => $row['pre_about_association'],
                'image1' => $row['pre_image1'],
                'image2' => $row['pre_image2'],
            ],
            'post-donation' => [
                'association' => $row['association'],
                'logo' => $row['logo'],
                'arriere_plan' => $row['arriere_plan'],
                'introduction' => $row['post_introduction'],
                'about_association' => $row['post_about_association'],
                'image1' => $row['post_image1'],
                'image2' => $row['post_image2'],
            ],
        ];
    } catch (PDOException $e) {
        error_log('getGiftConfig error: ' . $e->getMessage());
        return [];
    }
}


function saveGiftPhaseText(int $eventId, string $association, array $phases): bool {
    global $cnx;
    try {
        $stmt = $cnx->prepare("SELECT id FROM gift_event_config WHERE id_event = :id LIMIT 1");
        $stmt->execute([':id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $params = [
            ':id_event' => $eventId,
            ':association' => $association,
            ':col_intro' => $phases['collection']['introduction']      ?? '',
            ':col_about' => $phases['collection']['about_association'] ?? '',
            ':pre_intro' => $phases['pre-donation']['introduction']    ?? '',
            ':pre_about' => $phases['pre-donation']['about_association'] ?? '',
            ':post_intro' => $phases['post-donation']['introduction']   ?? '',
            ':post_about' => $phases['post-donation']['about_association'] ?? '',
        ];

        if ($row) {
            $params[':id'] = $row['id'];
            unset($params[':id_event']);
            
            $stmt = $cnx->prepare("
                UPDATE gift_event_config SET
                    association = :association,
                    col_introduction = :col_intro,
                    col_about_association = :col_about,
                    pre_introduction = :pre_intro,
                    pre_about_association = :pre_about,
                    post_introduction = :post_intro,
                    post_about_association = :post_about
                WHERE id = :id
            ");
        } else {
            unset($params[':id']);
            $stmt = $cnx->prepare("
                INSERT INTO gift_event_config
                    (id_event, association, col_introduction, col_about_association,
                     pre_introduction, pre_about_association,
                     post_introduction, post_about_association)
                VALUES
                    (:id_event, :association, :col_intro, :col_about,
                     :pre_intro, :pre_about,
                     :post_intro, :post_about)
            ");
        }
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log('saveGiftPhaseText error: ' . $e->getMessage());
        return false;
    }
}




function updateGiftAsset(int $eventId, string $phase, string $assetCol, string $filename): bool {
    global $cnx;

    $colMap = [
        'logo' => 'logo',
        'arriere_plan' => 'arriere_plan',
        'collection_image1' => 'col_image1',
        'collection_image2' => 'col_image2',
        'pre-donation_image1'  => 'pre_image1',
        'pre-donation_image2'  => 'pre_image2',
        'post-donation_image1' => 'post_image1',
        'post-donation_image2' => 'post_image2',
    ];

    $key = in_array($assetCol, ['logo', 'arriere_plan']) ? $assetCol : $phase . '_' . $assetCol;

    if (!isset($colMap[$key])) {
        error_log('updateGiftAsset: invalid key ' . $key);
        return false;
    }
    $col = $colMap[$key];

    try {
        $stmt = $cnx->prepare("SELECT id FROM gift_event_config WHERE id_event = :id LIMIT 1");
        $stmt->execute([':id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $stmt = $cnx->prepare("UPDATE gift_event_config SET `{$col}` = :filename WHERE id = :id");
            return $stmt->execute([':filename' => $filename, ':id' => $row['id']]);
        } else {
            $stmt = $cnx->prepare("INSERT INTO gift_event_config (id_event, `{$col}`) VALUES (:id_event, :filename)");
            return $stmt->execute([':id_event' => $eventId, ':filename' => $filename]);
        }
    } catch (PDOException $e) {
        error_log('updateGiftAsset error: ' . $e->getMessage());
        return false;
    }
}




<?php
/**
 * INDEX.PHP - Point d'entrée de l'application
 * Routeur simple pour l'architecture MVC
 */

// Démarrer la session
session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/controllers/GameController.php';
require_once __DIR__ . '/controllers/ScoreController.php';

// Récupérer la page demandée
$page = isset($_GET['page']) ? $_GET['page'] : 'game';

// Router les requêtes
switch ($page) {
    case 'game':
        // Afficher la page du jeu
        $gameController = new GameController();
        $gameController->index();
        break;
        
    case 'scores':
        // Afficher la page des scores
        $scoreController = new ScoreController();
        $scoreController->index();
        break;
        
    case 'api':
        // API pour les requêtes AJAX
        handleApiRequest();
        break;
        
    default:
        // Page non trouvée - rediriger vers le jeu
        header('Location: index.php?page=game');
        exit;
}

/**
 * Gère les requêtes API
 */
function handleApiRequest() {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    $scoreController = new ScoreController();
    
    switch ($action) {
        case 'save':
            // Enregistrer un score
            $scoreController->save();
            break;
            
        case 'top':
            // Obtenir les meilleurs scores
            $scoreController->getTop();
            break;
            
        case 'cards':
            // Obtenir les cartes du jeu
            $gameController = new GameController();
            $gameController->apiGetCards();
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Action non reconnue']);
            exit;
    }
}

<?php
/**
 * CONTROLLER : ScoreController
 * Gère la logique des scores
 */

require_once __DIR__ . '/../models/Score.php';

class ScoreController {
    private $scoreModel;

    public function __construct() {
        $this->scoreModel = new Score();
    }

    /**
     * Affiche la page des scores
     */
    public function index() {
        $topScores = $this->scoreModel->getTopScores(10);
        $totalGames = $this->scoreModel->getTotalGames();
        
        require_once __DIR__ . '/../views/scores.php';
    }

    /**
     * API pour enregistrer un score
     */
    public function save() {
        header('Content-Type: application/json');

        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        // Récupérer les données JSON
        $data = json_decode(file_get_contents('php://input'), true);

        // Valider les données
        if (!isset($data['playerName']) || !isset($data['time']) || !isset($data['moves'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }

        $playerName = trim($data['playerName']);
        $time = (int)$data['time'];
        $moves = (int)$data['moves'];

        // Validation
        if (empty($playerName) || strlen($playerName) > 50) {
            echo json_encode(['success' => false, 'message' => 'Nom invalide']);
            exit;
        }

        if ($time <= 0 || $moves <= 0) {
            echo json_encode(['success' => false, 'message' => 'Temps ou coups invalides']);
            exit;
        }

        // Enregistrer le score
        $success = $this->scoreModel->saveScore($playerName, $time, $moves);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Score enregistré avec succès!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement'
            ]);
        }
        exit;
    }

    /**
     * API pour obtenir les meilleurs scores
     */
    public function getTop() {
        header('Content-Type: application/json');
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $scores = $this->scoreModel->getTopScores($limit);
        
        echo json_encode($scores);
        exit;
    }
}

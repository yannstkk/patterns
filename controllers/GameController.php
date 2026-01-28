<?php
/**
 * CONTROLLER : GameController
 * Gère la logique du jeu de mémoire
 */

class GameController {
    
    /**
     * Affiche la page du jeu
     */
    public function index() {
        // Démarrer la session si pas encore démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Charger la vue du jeu
        require_once __DIR__ . '/../views/game.php';
    }

    /**
     * Génère les données des cartes pour le jeu
     * 
     * @return array Tableau des cartes mélangées
     */
    public function getCards() {
        // Emojis pour les cartes (8 paires = 16 cartes)
        $symbols = ['🎮', '🎲', '🎯', '🎪', '🎨', '🎭', '🎬', '🎤'];
        
        // Créer les paires
        $cards = [];
        foreach ($symbols as $index => $symbol) {
            $cards[] = [
                'id' => $index * 2,
                'symbol' => $symbol,
                'matched' => false
            ];
            $cards[] = [
                'id' => $index * 2 + 1,
                'symbol' => $symbol,
                'matched' => false
            ];
        }

        // Mélanger les cartes
        shuffle($cards);

        return $cards;
    }

    /**
     * API pour obtenir les cartes en JSON
     */
    public function apiGetCards() {
        header('Content-Type: application/json');
        echo json_encode($this->getCards());
        exit;
    }
}

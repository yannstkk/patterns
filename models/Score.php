<?php
/**
 * MODEL : Score
 * Gère les interactions avec la table 'scores' dans la base de données
 */

require_once __DIR__ . '/../config/database.php';

class Score {
    private $db;
    private $table = 'scores';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Enregistre un nouveau score
     * 
     * @param string $playerName Nom du joueur
     * @param int $timeSeconds Temps en secondes
     * @param int $moves Nombre de coups
     * @return bool True si succès, False sinon
     */
    public function saveScore($playerName, $timeSeconds, $moves) {
        try {
            $sql = "INSERT INTO {$this->table} (player_name, time_seconds, moves) 
                    VALUES (:player_name, :time_seconds, :moves)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':player_name' => htmlspecialchars($playerName),
                ':time_seconds' => (int)$timeSeconds,
                ':moves' => (int)$moves
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement du score : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les meilleurs scores
     * 
     * @param int $limit Nombre de scores à récupérer
     * @return array Tableau des meilleurs scores
     */
    public function getTopScores($limit = 10) {
        try {
            $sql = "SELECT player_name, time_seconds, moves, created_at 
                    FROM {$this->table} 
                    ORDER BY time_seconds ASC, moves ASC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des scores : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les scores
     * 
     * @return array Tableau de tous les scores
     */
    public function getAllScores() {
        try {
            $sql = "SELECT player_name, time_seconds, moves, created_at 
                    FROM {$this->table} 
                    ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les scores : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre total de parties jouées
     * 
     * @return int Nombre de parties
     */
    public function getTotalGames() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des parties : " . $e->getMessage());
            return 0;
        }
    }
}

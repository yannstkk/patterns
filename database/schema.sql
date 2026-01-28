-- Base de données pour Memory Game
-- À exécuter dans phpMyAdmin après avoir créé la base 'memory_game'

CREATE TABLE IF NOT EXISTS `scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `player_name` VARCHAR(50) NOT NULL,
  `time_seconds` INT NOT NULL,
  `moves` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_time` (`time_seconds`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion de quelques scores exemples (optionnel)
INSERT INTO `scores` (`player_name`, `time_seconds`, `moves`) VALUES
('Alice', 45, 20),
('Bob', 52, 24),
('Charlie', 38, 18),
('Diana', 60, 28),
('Emma', 41, 19);

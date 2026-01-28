<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement - Memory Card Game</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🏆 Classement des Meilleurs Scores</h1>
            <nav>
                <a href="index.php?page=game" class="nav-link">Jouer</a>
                <a href="index.php?page=scores" class="nav-link active">Classement</a>
            </nav>
        </header>

        <div class="scores-container">
            <div class="stats-summary">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $totalGames; ?></div>
                    <div class="stat-label">Parties jouées</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($topScores); ?></div>
                    <div class="stat-label">Meilleurs scores</div>
                </div>
            </div>

            <?php if (empty($topScores)): ?>
                <div class="no-scores">
                    <p>Aucun score enregistré pour le moment.</p>
                    <a href="index.php?page=game" class="btn btn-primary">Jouer maintenant !</a>
                </div>
            <?php else: ?>
                <div class="scores-table">
                    <table>
                        <thead>
                            <tr>
                                <th class="rank-col">Rang</th>
                                <th class="name-col">Joueur</th>
                                <th class="time-col">Temps</th>
                                <th class="moves-col">Coups</th>
                                <th class="date-col">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topScores as $index => $score): ?>
                                <tr class="<?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                                    <td class="rank-col">
                                        <?php if ($index === 0): ?>
                                            🥇
                                        <?php elseif ($index === 1): ?>
                                            🥈
                                        <?php elseif ($index === 2): ?>
                                            🥉
                                        <?php else: ?>
                                            <?php echo $index + 1; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="name-col"><?php echo htmlspecialchars($score['player_name']); ?></td>
                                    <td class="time-col"><?php echo $score['time_seconds']; ?>s</td>
                                    <td class="moves-col"><?php echo $score['moves']; ?></td>
                                    <td class="date-col">
                                        <?php 
                                        $date = new DateTime($score['created_at']);
                                        echo $date->format('d/m/Y H:i'); 
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="back-to-game">
                    <a href="index.php?page=game" class="btn btn-primary">Battre ces records !</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

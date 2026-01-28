<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Card Game - Jeu de Mémoire</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🎮 Memory Card Game</h1>
            <nav>
                <a href="index.php?page=game" class="nav-link active">Jouer</a>
                <a href="index.php?page=scores" class="nav-link">Classement</a>
            </nav>
        </header>

        <!-- Écran de démarrage -->
        <div id="startScreen" class="screen">
            <div class="welcome-card">
                <h2>Bienvenue !</h2>
                <p>Trouvez toutes les paires de cartes identiques en un minimum de temps et de coups.</p>
                <input type="text" id="playerName" placeholder="Entrez votre nom" maxlength="50" required>
                <button id="startBtn" class="btn btn-primary">Démarrer le jeu</button>
            </div>
        </div>

        <!-- Écran de jeu -->
        <div id="gameScreen" class="screen hidden">
            <div class="game-info">
                <div class="info-item">
                    <span class="label">Joueur :</span>
                    <span id="playerNameDisplay" class="value"></span>
                </div>
                <div class="info-item">
                    <span class="label">⏱️ Temps :</span>
                    <span id="timer" class="value">0s</span>
                </div>
                <div class="info-item">
                    <span class="label">🎯 Coups :</span>
                    <span id="moves" class="value">0</span>
                </div>
            </div>

            <div id="gameBoard" class="game-board">
                <!-- Les cartes seront générées par JavaScript -->
            </div>
        </div>

        <!-- Écran de victoire -->
        <div id="winScreen" class="screen hidden">
            <div class="win-card">
                <h2>🎉 Félicitations !</h2>
                <p>Vous avez trouvé toutes les paires !</p>
                <div class="win-stats">
                    <div class="stat">
                        <span class="stat-label">Temps :</span>
                        <span id="finalTime" class="stat-value"></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Coups :</span>
                        <span id="finalMoves" class="stat-value"></span>
                    </div>
                </div>
                <div class="win-message" id="winMessage"></div>
                <div class="win-actions">
                    <button id="playAgainBtn" class="btn btn-primary">Rejouer</button>
                    <a href="index.php?page=scores" class="btn btn-secondary">Voir le classement</a>
                </div>
            </div>
        </div>
    </div>

    <script src="public/js/game.js"></script>
</body>
</html>

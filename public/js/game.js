/**
 * JAVASCRIPT - Logique du jeu Memory Card
 */

// État du jeu
let gameState = {
    playerName: '',
    cards: [],
    flippedCards: [],
    matchedPairs: 0,
    moves: 0,
    startTime: null,
    timerInterval: null,
    canFlip: true
};

// Éléments DOM
const elements = {
    startScreen: document.getElementById('startScreen'),
    gameScreen: document.getElementById('gameScreen'),
    winScreen: document.getElementById('winScreen'),
    playerNameInput: document.getElementById('playerName'),
    playerNameDisplay: document.getElementById('playerNameDisplay'),
    startBtn: document.getElementById('startBtn'),
    gameBoard: document.getElementById('gameBoard'),
    timerDisplay: document.getElementById('timer'),
    movesDisplay: document.getElementById('moves'),
    finalTime: document.getElementById('finalTime'),
    finalMoves: document.getElementById('finalMoves'),
    winMessage: document.getElementById('winMessage'),
    playAgainBtn: document.getElementById('playAgainBtn')
};

/**
 * Initialisation du jeu
 */
function init() {
    elements.startBtn.addEventListener('click', startGame);
    elements.playAgainBtn.addEventListener('click', resetGame);
    
    // Permettre de démarrer avec la touche Enter
    elements.playerNameInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && elements.playerNameInput.value.trim()) {
            startGame();
        }
    });
}

/**
 * Démarre une nouvelle partie
 */
function startGame() {
    const playerName = elements.playerNameInput.value.trim();
    
    if (!playerName) {
        alert('Veuillez entrer votre nom !');
        elements.playerNameInput.focus();
        return;
    }
    
    gameState.playerName = playerName;
    gameState.moves = 0;
    gameState.matchedPairs = 0;
    gameState.flippedCards = [];
    
    elements.playerNameDisplay.textContent = playerName;
    
    // Afficher l'écran de jeu
    showScreen('game');
    
    // Générer et afficher les cartes
    generateCards();
    
    // Démarrer le chronomètre
    startTimer();
}

/**
 * Génère les cartes du jeu
 */
function generateCards() {
    // Symboles pour les cartes (8 paires)
    const symbols = ['🎮', '🎲', '🎯', '🎪', '🎨', '🎭', '🎬', '🎤'];
    
    // Créer les paires
    gameState.cards = [];
    symbols.forEach((symbol, index) => {
        gameState.cards.push({ id: index * 2, symbol, matched: false });
        gameState.cards.push({ id: index * 2 + 1, symbol, matched: false });
    });
    
    // Mélanger les cartes
    shuffleArray(gameState.cards);
    
    // Afficher les cartes
    renderCards();
}

/**
 * Affiche les cartes dans le DOM
 */
function renderCards() {
    elements.gameBoard.innerHTML = '';
    
    gameState.cards.forEach((card) => {
        const cardElement = document.createElement('div');
        cardElement.className = 'card';
        cardElement.dataset.id = card.id;
        cardElement.dataset.symbol = card.symbol;
        
        cardElement.innerHTML = `
            <div class="card-front">?</div>
            <div class="card-back">${card.symbol}</div>
        `;
        
        cardElement.addEventListener('click', () => flipCard(card, cardElement));
        elements.gameBoard.appendChild(cardElement);
    });
}

/**
 * Retourne une carte
 */
function flipCard(card, cardElement) {
    // Vérifications
    if (!gameState.canFlip) return;
    if (cardElement.classList.contains('flipped')) return;
    if (cardElement.classList.contains('matched')) return;
    if (gameState.flippedCards.length >= 2) return;
    
    // Retourner la carte
    cardElement.classList.add('flipped');
    gameState.flippedCards.push({ card, element: cardElement });
    
    // Si deux cartes sont retournées
    if (gameState.flippedCards.length === 2) {
        gameState.moves++;
        updateMoves();
        gameState.canFlip = false;
        
        setTimeout(checkMatch, 800);
    }
}

/**
 * Vérifie si les deux cartes retournées correspondent
 */
function checkMatch() {
    const [first, second] = gameState.flippedCards;
    
    if (first.card.symbol === second.card.symbol) {
        // Paire trouvée !
        first.element.classList.add('matched');
        second.element.classList.add('matched');
        first.card.matched = true;
        second.card.matched = true;
        
        gameState.matchedPairs++;
        
        // Vérifier si le jeu est terminé
        if (gameState.matchedPairs === 8) {
            setTimeout(endGame, 500);
        }
    } else {
        // Pas de correspondance
        setTimeout(() => {
            first.element.classList.remove('flipped');
            second.element.classList.remove('flipped');
        }, 500);
    }
    
    gameState.flippedCards = [];
    gameState.canFlip = true;
}

/**
 * Démarre le chronomètre
 */
function startTimer() {
    gameState.startTime = Date.now();
    
    gameState.timerInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - gameState.startTime) / 1000);
        elements.timerDisplay.textContent = `${elapsed}s`;
    }, 100);
}

/**
 * Arrête le chronomètre
 */
function stopTimer() {
    if (gameState.timerInterval) {
        clearInterval(gameState.timerInterval);
    }
}

/**
 * Met à jour l'affichage des coups
 */
function updateMoves() {
    elements.movesDisplay.textContent = gameState.moves;
}

/**
 * Termine le jeu
 */
function endGame() {
    stopTimer();
    
    const finalTimeSeconds = Math.floor((Date.now() - gameState.startTime) / 1000);
    
    elements.finalTime.textContent = `${finalTimeSeconds}s`;
    elements.finalMoves.textContent = gameState.moves;
    
    // Enregistrer le score
    saveScore(gameState.playerName, finalTimeSeconds, gameState.moves);
    
    // Afficher l'écran de victoire
    showScreen('win');
}

/**
 * Enregistre le score dans la base de données
 */
async function saveScore(playerName, time, moves) {
    try {
        const response = await fetch('index.php?page=api&action=save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                playerName: playerName,
                time: time,
                moves: moves
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            elements.winMessage.textContent = '✅ Votre score a été enregistré !';
        } else {
            elements.winMessage.textContent = '⚠️ Erreur lors de l\'enregistrement du score.';
        }
    } catch (error) {
        console.error('Erreur:', error);
        elements.winMessage.textContent = '⚠️ Erreur de connexion au serveur.';
    }
}

/**
 * Réinitialise le jeu
 */
function resetGame() {
    stopTimer();
    elements.playerNameInput.value = '';
    showScreen('start');
}

/**
 * Affiche un écran spécifique
 */
function showScreen(screen) {
    elements.startScreen.classList.add('hidden');
    elements.gameScreen.classList.add('hidden');
    elements.winScreen.classList.add('hidden');
    
    switch(screen) {
        case 'start':
            elements.startScreen.classList.remove('hidden');
            elements.playerNameInput.focus();
            break;
        case 'game':
            elements.gameScreen.classList.remove('hidden');
            break;
        case 'win':
            elements.winScreen.classList.remove('hidden');
            break;
    }
}

/**
 * Mélange un tableau (algorithme Fisher-Yates)
 */
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

// Initialiser le jeu au chargement de la page
document.addEventListener('DOMContentLoaded', init);

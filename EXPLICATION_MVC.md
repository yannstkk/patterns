

## Les Trois Composants

### 1. MODEL (Modèle) 
**Rôle** : Gère les données et la logique métier

**Dans notre projet** :
- `models/Score.php`

**Responsabilités** :
- Interaction avec la base de données (SELECT, INSERT, UPDATE, DELETE)
- Validation des données
- Logique métier (règles de calcul, transformations)
- Ne contient AUCUN code HTML
- Ne gère PAS l'affichage

**Exemple concret** :
```php
// Le modèle Score gère tout ce qui concerne les scores
class Score {
    public function saveScore($name, $time, $moves) {
        // Sauvegarde dans la DB
    }
    
    public function getTopScores($limit) {
        // Récupère les meilleurs scores
    }
}
```

---

### 2. VIEW (Vue) 
**Rôle** : Affiche les données à l'utilisateur

**Dans notre projet** :
- `views/game.php`
- `views/scores.php`

**Responsabilités** :
- Affichage HTML/CSS
- Présentation des données reçues du contrôleur
- Formulaires et interface utilisateur
- Ne contient PAS de logique métier
- N'accède PAS directement à la base de données

**Exemple concret** :
```php
<!-- La vue affiche simplement les données -->
<h2>Scores</h2>
<?php foreach ($scores as $score): ?>
    <p><?php echo $score['player_name']; ?></p>
<?php endforeach; ?>
```

---

### 3. CONTROLLER (Contrôleur) 🎮
**Rôle** : Chef d'orchestre entre le Modèle et la Vue

**Dans notre projet** :
- `controllers/GameController.php`
- `controllers/ScoreController.php`

**Responsabilités** :
- Reçoit les requêtes de l'utilisateur
- Appelle les modèles pour récupérer/modifier des données
- Prépare les données pour la vue
- Choisi quelle vue afficher
- Ne contient PAS de requêtes SQL directes
- Ne contient PAS de code HTML

**Exemple concret** :
```php
class ScoreController {
    public function index() {
        $scores = $this->scoreModel->getTopScores(10);
        
        require 'views/scores.php';
    }
}
```

---

## Flux de Données dans Notre Application

### Scénario 1 : Afficher les scores

```
1. L'utilisateur clique sur "Classement"
   │
   ▼
2. index.php reçoit ?page=scores
   │
   ▼
3. ScoreController::index() est appelé
   │
   ├──► 4. Appelle Score::getTopScores()
   │          │
   │          ▼
   │       5. Le modèle interroge la DB
   │          │
   │          ▼
   │       6. Retourne un tableau de scores
   │
   ▼
7. Le contrôleur passe les données à views/scores.php
   │
   ▼
8. La vue affiche les scores en HTML
```

### Scénario 2 : Enregistrer un score

```
1. JavaScript envoie une requête AJAX
   │
   ▼
2. index.php reçoit ?page=api&action=save
   │
   ▼
3. ScoreController::save() est appelé
   │
   ├──► 4. Valide les données reçues
   │
   ├──► 5. Appelle Score::saveScore()
   │          │
   │          ▼
   │       6. Le modèle INSERT dans la DB
   │          │
   │          ▼
   │       7. Retourne success/error
   │
   ▼
8. Le contrôleur retourne du JSON
   │
   ▼
9. JavaScript affiche le résultat
```

---

## Organisation des Fichiers

### Structure Complète
```
memory-game-mvc/
│
├── config/                 
│   └── database.php        
│
├── models/                
│   └── Score.php           
│
├── views/                  
│   ├── game.php            
│   └── scores.php          
│
├── controllers/            
│   ├── GameController.php  
│   └── ScoreController.php 
│
├── public/                 
│   ├── css/
│   │   └── style.css       
│   └── js/
│       └── game.js         
│
├── database/               
│   └── schema.sql          
│
└── index.php               
```


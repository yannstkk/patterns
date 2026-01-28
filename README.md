# Memory Card Game - Projet MVC en PHP

## 🎮 Description
Un jeu de mémoire (Memory Card) développé en PHP avec l'architecture MVC, JavaScript, HTML et CSS. Le jeu enregistre les scores des joueurs dans une base de données.

## 📋 Prérequis
- XAMPP installé
- Visual Studio Code
- Navigateur web moderne

## 🚀 Installation et Configuration

### Étape 1 : Démarrer XAMPP
1. Ouvrez le panneau de contrôle XAMPP
2. Démarrez **Apache** (pour PHP)
3. Démarrez **MySQL** (pour la base de données)

### Étape 2 : Placer le projet
1. Copiez le dossier `memory-game-mvc` dans le répertoire `htdocs` de XAMPP
   - Chemin typique : `C:\xampp\htdocs\` (Windows) ou `/Applications/XAMPP/htdocs/` (Mac)

### Étape 3 : Créer la base de données
1. Ouvrez votre navigateur et allez sur `http://localhost/phpmyadmin`
2. Cliquez sur "Nouvelle base de données"
3. Nom de la base : `memory_game`
4. Cliquez sur "Créer"
5. Sélectionnez la base `memory_game` dans la liste à gauche
6. Cliquez sur l'onglet "SQL"
7. Copiez-collez le script SQL fourni dans `database/schema.sql`
8. Cliquez sur "Exécuter"

### Étape 4 : Configurer la connexion
1. Ouvrez le fichier `config/database.php`
2. Vérifiez les paramètres de connexion (par défaut : host=localhost, user=root, pass='')

### Étape 5 : Lancer le jeu
1. Ouvrez votre navigateur
2. Allez sur `http://localhost/memory-game-mvc`
3. Jouez ! 🎉

## 📁 Structure du Projet (MVC)

```
memory-game-mvc/
├── config/              # Configuration
│   └── database.php     # Connexion DB
├── controllers/         # Contrôleurs (logique)
│   ├── GameController.php
│   └── ScoreController.php
├── models/             # Modèles (données)
│   └── Score.php
├── views/              # Vues (affichage)
│   ├── game.php
│   └── scores.php
├── public/             # Fichiers publics
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── game.js
│   └── images/         # Images des cartes
├── database/           # Scripts DB
│   └── schema.sql
└── index.php           # Point d'entrée
```

## 🎯 Comment jouer
1. Entrez votre nom
2. Cliquez sur "Démarrer"
3. Retournez les cartes pour trouver les paires
4. Le chronomètre compte vos secondes
5. Trouvez toutes les paires pour gagner !
6. Votre score sera enregistré

## 🔧 Fonctionnalités
- ✅ Architecture MVC complète
- ✅ Base de données MySQL
- ✅ Système de scores avec classement
- ✅ Chronomètre
- ✅ Compteur de coups
- ✅ Animations CSS
- ✅ Design responsive

## 📚 Concepts abordés
- **PHP** : POO, PDO, sessions
- **MVC** : Séparation des responsabilités
- **JavaScript** : Manipulation DOM, événements, AJAX
- **CSS** : Animations, flexbox, grid
- **SQL** : CREATE, INSERT, SELECT, ORDER BY

## 🐛 Dépannage

**Erreur "Cannot connect to database"**
- Vérifiez que MySQL est démarré dans XAMPP
- Vérifiez les identifiants dans `config/database.php`

**Page blanche**
- Vérifiez que Apache est démarré
- Regardez les erreurs PHP dans `C:\xampp\apache\logs\error.log`

**Base de données non trouvée**
- Vérifiez que vous avez créé la base `memory_game`
- Vérifiez que le script SQL a été exécuté

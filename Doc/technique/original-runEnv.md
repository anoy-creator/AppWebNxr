# 🚀 Environnement de Développement

## Démarrage rapide

### 1. Démarrer les services Docker
```bash
docker compose down
docker compose up -d --build
```

### 2. Terminal PHP - Backend Symfony
```bash
docker exec -it symfony_php bash
composer install          # Une seule fois
```

### 3. Terminal Node - Frontend Assets
```bash
docker exec -it symfony_node bash
yarn install              # Une seule fois
yarn watch                # Auto-rechargement en développement
# ou yarn build           # Build uniquement
```

---

## 🗄️ Base de Données

### Configuration
- **Host**: `db` (Docker) ou `localhost:3306`
- **Database**: `symfony`
- **User**: `symfony`
- **Password**: `symfony`
- **Driver**: MySQL (MariaDB 11)

### Vérifier la connexion
```bash
docker exec symfony_php php bin/console dbal:run-sql "SELECT 1"
```

### Créer une base de données
```bash
docker exec symfony_php php bin/console doctrine:database:create
```

### Lister les tables
```bash
docker exec symfony_php php bin/console dbal:run-sql "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='symfony'"
```

---

## 🎮 Entités Esports Call of Duty

### Tables créées
| Entité | Table | Description |
|--------|-------|-------------|
| User | users | Joueurs (auth Discord) |
| Team | teams | Équipes |
| TeamMember | team_members | Roster de l'équipe (historique) |
| Season | seasons | Saisons de compétition |
| GameMap | game_maps | Cartes Call of Duty |
| Game | matches | Matchs entre équipes |
| MatchPlayer | match_players | Statistiques des joueurs par match |

### Relations principales
```
User (1) ──→ (Many) MatchPlayer
User (1) ──→ (Many) TeamMember
          ↓
Team (1) ──→ (Many) TeamMember
       ├─→ (Many) Matches (as TeamA)
       ├─→ (Many) Matches (as TeamB)
       └─→ (Many) Matches (won)

Season (1) ──→ (Many) Matches
GameMap (1) ──→ (Many) Matches
Match (1) ──→ (Many) MatchPlayer
```

### Champs clés
- **User**: discordId, username, email, avatar, lastLoginAt
- **Team**: name, tag, logo, description
- **Game**: scoreTeamA, scoreTeamB, playedAt, winnerTeam
- **MatchPlayer**: kills, deaths, assists, score, damage, objectiveScore, kdRatio()
- **TeamMember**: joinedAt, leftAt, isActive()

---

## 📝 Commandes Doctrine

### Créer une entité
```bash
docker exec -it symfony_php bash
php bin/console make:entity NomEntite
```

### Générer une migration
```bash
php bin/console make:migration
```

### Exécuter les migrations
```bash
php bin/console doctrine:migrations:migrate
```

### Annuler les migrations
```bash
php bin/console doctrine:migrations:migrate --down 1
```

### Afficher le statut des migrations
```bash
php bin/console doctrine:migrations:status
```

### Valider le schéma
```bash
php bin/console doctrine:schema:validate
```

### Réinitialiser la base de données
```bash
# Drop + Create + Migrate
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
```

---

## 🛠️ Commandes Symfony

### Créer un contrôleur
```bash
php bin/console make:controller NomController
```

### Générer des fixtures
```bash
php bin/console make:fixtures NomFixtures
```

### Charger les fixtures
```bash
php bin/console doctrine:fixtures:load
```

### Vider le cache
```bash
php bin/console cache:clear
```

### Mode debug
```bash
# Vérifier la config
php bin/console about

# Vérifier les routes
php bin/console debug:router
```

---

## 🎯 Accéder à l'application

- **Application**: http://localhost:8080
- **PhpMyAdmin** (optionnel): http://localhost:8081

---

## 📂 Structure des dossiers

### Code source
- `/app/src/Entity/` - Entités Doctrine (User, Team, Game, Season, etc.)
- `/app/src/Repository/` - Repositories (GameRepository, MatchPlayerRepository, etc.)
- `/app/src/Controller/` - Contrôleurs Symfony
- `/app/src/Service/` - Services métier

### Configuration
- `/app/config/` - Configuration Symfony
- `/app/config/packages/doctrine.yaml` - Configuration Doctrine ORM
- `/app/config/packages/doctrine_migrations.yaml` - Configuration migrations

### Données
- `/app/migrations/` - Migrations de base de données
- `/app/src/DataFixtures/` - Données de test

### Frontend
- `/app/templates/` - Templates Twig
- `/app/assets/` - Assets (CSS, JS)
- `/app/public/` - Assets compilés

---

## 🔍 Requêtes utiles Doctrine

### Récupérer les stats d'un joueur
```php
$stats = $em->getRepository(MatchPlayer::class)->findPlayerStats($user);
```

### Récupérer les matchs d'une équipe
```php
$games = $em->getRepository(Game::class)->findTeamGamesBySeason($team, $season);
```

### Récupérer les joueurs actifs d'une équipe
```php
$members = $em->getRepository(TeamMember::class)->findActiveMembers($team);
```

### Récupérer les top joueurs
```php
$topPlayers = $em->getRepository(MatchPlayer::class)->findTopPlayers(10);
```

---

## 📌 Bonnes pratiques

### Git
- Ne pas commiter `/vendor`, `/node_modules`, `/var`, `/public/build`
- Commiter les migrations
- Commiter les entités Doctrine

### Doctrine
- Toujours générer des migrations avant de déployer
- Tester les migrations en local
- Utiliser les repositories pour les requêtes complexes

### Développement
- Utiliser Docker pour garantir la cohérence
- Vider le cache après des changements
- Utiliser `symfony serve` localement (optionnel, Docker est recommandé)

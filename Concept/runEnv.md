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

---

## 📝 Commandes Doctrine courantes

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

### Afficher le statut des migrations
```bash
php bin/console doctrine:migrations:status
```

---

## 🛠️ Commandes Symfony courantes

### Créer un contrôleur
```bash
php bin/console make:controller NomController
```

### Vider le cache
```bash
php bin/console cache:clear
```

### Accéder à l'application
```
http://localhost:8080
```

---

## 📂 Structure des dossiers importants

- `/app/src/Entity/` - Entités Doctrine (modèles de données)
- `/app/src/Controller/` - Contrôleurs Symfony
- `/app/src/Service/` - Services métier
- `/app/templates/` - Templates Twig
- `/app/config/` - Configuration Symfony
- `/app/migrations/` - Migrations de base de données
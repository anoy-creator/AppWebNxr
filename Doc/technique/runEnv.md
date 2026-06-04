# 🚀 Guide rapide pour les contributeurs

## 1. Récupérer le projet

```bash
git clone <url-du-repo>
cd <nom-du-projet>

git checkout main
git pull origin main
```

## 2. Démarrer l'environnement Docker

Construire et lancer tous les conteneurs :

```bash
docker compose down
docker compose up -d --build
```

## 3. Créer sa branche de travail

```bash
git checkout -b feature/nom-feature
```

---

## 4. Initialiser le Back-end

```bash
docker exec -it symfony_php bash

composer install

php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load
```

---

## 5. Initialiser le Front-end

```bash
docker exec -it symfony_node bash

yarn install
npm install tom-select
```

---

## 6. Développement

### Front

```bash
docker exec -it symfony_node bash
yarn watch
```

### Back

Le conteneur PHP est déjà prêt à recevoir les modifications Symfony.

# 🤖 BOTDISCORD

## Accéder au conteneur

```bash
docker exec -it discord_bot bash
```

```
docker exec discord_bot npm run deploy:commands
docker compose restart discord_bot
docker exec symfony_php php bin/console cache:clear
```

---

## 7. Vérification du code

Avant chaque commit :

```bash
docker exec -it symfony_php bash

vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 8. Tests

```bash
docker exec -it symfony_php bash

vendor/bin/phpunit --testdox
```

---

## 9. Commit & Push

```bash
git add .
git commit -m "feat: description courte"
git push --set-upstream origin feature/nom-feature
```

---

## 10. Créer une Pull Request

```text
feature/nom-feature → main
```

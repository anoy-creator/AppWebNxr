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
php bin/console doctrine:migrations:migrate
```

Pour initialiser une base de test avec des donnees de demonstration :

```bash
php bin/console doctrine:fixtures:load --group=test
```

Pour initialiser une base de production sans contenu public de demo :

```bash
NXR_ADMIN_USERNAME=admin NXR_ADMIN_PASSWORD='mot-de-passe-fort' php bin/console doctrine:fixtures:load --group=prod
```

Cette fixture cree uniquement le compte admin, un roster technique et deux equipes de base. Elle ne cree aucune actualite, joueur, planning, tournoi, match ou stat.

---

## 5. Initialiser le Front-end

```bash
docker exec -it symfony_node bash

npm install
```

---

## 6. Développement

### Front

```bash
docker exec -it symfony_node bash
npm run watch
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

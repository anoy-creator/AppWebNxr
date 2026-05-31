# Commandes rapides — workflow pour les contributeurs

# 1. Récupérer la branche principale et créer la branche locale
git checkout main
git pull origin main

git checkout -b feature/nom-feature

# 2. Démarrer les conteneurs Docker (env recommandé)
docker compose down
docker compose up -d --build

# 3. Back‑end (dans le conteneur PHP)
docker exec -it symfony_php bash
# Installer dépendances PHP (une seule fois ou après  , mettre a jour la bdd , load les données de test)
composer install
php bin/console do:sc:update --force
php bin/console doctrine:fixtures:load

# Vérifier et afficher les corrections de style (dry-run)
docker exec -it symfony_php bash
vendor/bin/php-cs-fixer fix --dry-run --diff (en gros cette commande avant de commit)
# (Optionnel) Appliquer automatiquement : vendor/bin/php-cs-fixer fix

# 4. Front‑end (dans le conteneur Node)
docker exec -it symfony_node bash
yarn install (une fois seule)
# En dev :
yarn watch
# Ou build :
yarn build

# 5. Symfony CLI (optionnel, local sans Docker)
# depuis le dossier app
cd app
symfony server:start
# arrêter : symfony server:stop
cd ..

# 6. Base de données (migrations)
docker exec -it symfony_php bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
exit

# 7. Tests
# (dans conteneur PHP)
docker exec -it symfony_php bash
vendor/bin/phpunit --testdox

exit

# 8. Git : commit & push
git add .
git commit -m "feat: description courte"
git push --set-upstream origin feature/nom-feature

# 9. Ouvrir PR via GitHub (compare feature/nom-feature → main)

# Notes
- Docker = environnement recommandé pour cohérence entre contributeurs.
- Symfony CLI utile pour tests rapides locaux mais pas pour CI.
- Ne pas committer /vendor ni /node_modules.

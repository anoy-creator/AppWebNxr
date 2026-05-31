# Commandes

git clone <repo-url>
cd AppWebNxr
docker compose down
docker compose up -d --build

# Back-end (dans le conteneur PHP)
docker exec -it symfony_php bash
composer install
vendor/bin/php-cs-fixer fix --dry-run --diff
exit

# Front-end (dans le conteneur Node)
docker exec -it symfony_node bash
yarn install
yarn watch
exit

# Base de données (migrations)
docker exec -it symfony_php bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
exit

# Git
git add .
git commit -m "<message>"
git push

# Symfony CLI (optionnel — exécution locale sans Docker)
# Depuis le dossier de l'application
cd app

# CsFixer depuis l'intérieur du conteneur PHP :
docker exec -it symfony_php bash
vendor/bin/php-cs-fixer fix --dry-run --diff
# ou
vendor/bin/php-cs-fixer fix

# Démarrer le serveur local
docker exec -it symfony_php bash
symfony server:start

# Arrêter le serveur
symfony server:stop

# Remarque: utiliser Symfony CLI uniquement pour développement local rapide. Docker reste la méthode recommandée pour un environnement identique aux autres contributeurs.

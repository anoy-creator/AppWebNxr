# Commandes pour démarrer le projet localement et push Git

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

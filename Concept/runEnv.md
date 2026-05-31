Run =>

docker compose down
docker compose up -d --build

Dans un terminal :
docker exec -it symfony_php bash
=> composer install
=> symfony serve:start

Dans un autre terminal :
docker exec -it symfony_node bash
=> yarn install
=> yarn watch / yarn build

(install ( 1seule fois / watch auto maj / build maj uniquement)

composer install
php bin/console make:controller IndexController
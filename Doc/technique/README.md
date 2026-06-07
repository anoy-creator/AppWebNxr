# AppWebNxr - Documentation technique

Application web Symfony pour la team Naxera eSport. Le projet regroupe le site public, une administration de contenu, une connexion Discord, une API de synchronisation Discord et un bot Discord.

---

## 1. Stack technique

| Bloc | Technologie |
| --- | --- |
| Backend | Symfony 7.4, PHP 8.3 FPM |
| Frontend | Twig, Webpack Encore, jQuery, Tom Select |
| Base de donnees | MariaDB 11 |
| Serveur web | Nginx |
| Bot Discord | Node.js 22, discord.js |
| Conteneurs | Docker Compose |

Le code Symfony est dans `app/`. Le bot principal est dans `bot/`.

---

## 2. Services Docker

Le fichier racine `docker-compose.yml` lance les services suivants :

| Service | Container | Role |
| --- | --- | --- |
| `php` | `symfony_php` | PHP FPM, Composer, console Symfony |
| `nginx` | `symfony_nginx` | Serve l'application sur `http://localhost:8080` |
| `db` | `symfony_db` | MariaDB 11 |
| `node` | `symfony_node` | Build/watch des assets Encore |
| `discord_bot` | `discord_bot` | Bot Discord Node.js |

Demarrage complet :

```bash
docker compose up -d --build
```

Arret :

```bash
docker compose down
```

Logs utiles :

```bash
docker logs symfony_php
docker logs symfony_nginx
docker logs symfony_db
docker logs discord_bot
```

---

## 3. Acces locaux

| Ressource | URL / acces |
| --- | --- |
| Site web | `http://localhost:8080` |
| Login membre Discord | `/login` |
| Login admin | `/login/admin` |
| CGU | `/cgu` |
| API Discord ping | `/api/discord/ping` |

La base MariaDB est exposee sur le port local `3306`.

| Parametre | Valeur |
| --- | --- |
| Host Docker | `db` |
| Port | `3306` |
| Database | `symfony` |
| User | `symfony` |
| Password | `symfony` |

`DATABASE_URL` attendu cote Symfony :

```env
DATABASE_URL="mysql://symfony:symfony@db:3306/symfony"
```

---

## 4. Installation backend

Entrer dans le conteneur PHP :

```bash
docker exec -it symfony_php bash
```

Installer les dependances et initialiser la base :

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

Charger les fixtures de test si vous voulez des donnees de demonstration :

```bash
php bin/console doctrine:fixtures:load --group=test
```

Charger les fixtures de production minimale pour une base propre :

```bash
NXR_ADMIN_USERNAME=admin NXR_ADMIN_PASSWORD='mot-de-passe-fort' php bin/console doctrine:fixtures:load --group=prod
```

Cette fixture cree uniquement :

* un compte admin ;
* un roster technique ;
* deux equipes de base.

Elle ne cree pas de news, joueurs publics, planning, tournoi, match ou statistiques.

---

## 5. Installation frontend

Entrer dans le conteneur Node :

```bash
docker exec -it symfony_node bash
```

Installer les dependances :

```bash
npm install
```

Compiler en developpement :

```bash
npm run dev
```

Watcher les assets :

```bash
npm run watch
```

Compiler pour production :

```bash
npm run build
```

Les assets sources sont dans `app/assets/`. Les fichiers generes vont dans `app/public/build/` et ne doivent pas etre versionnes.

---

## 6. Variables d'environnement importantes

### Symfony / Discord OAuth

| Variable | Role |
| --- | --- |
| `DATABASE_URL` | Connexion MariaDB |
| `OAUTH_DISCORD_CLIENT_ID` | Client ID OAuth Discord |
| `OAUTH_DISCORD_CLIENT_SECRET` | Secret OAuth Discord |
| `DISCORD_GUILD_ID` | Serveur Discord utilise pour les roles |
| `DISCORD_ADMIN_ROLE_ID` | Role Discord donnant l'admin |
| `DISCORD_BOT_TOKEN` ou `DISCORD_TOKEN` | Token bot utilise pour lire les roles |
| `API_KEY` | Cle partagee entre le site et le bot |
| `BOT_WEBHOOK_URL` | Webhook appele par le site quand un tournoi est modifie |

### Fixture admin production

| Variable | Defaut |
| --- | --- |
| `NXR_ADMIN_USERNAME` | `admin` |
| `NXR_ADMIN_PASSWORD` | `adminNxr` |
| `NXR_ADMIN_EMAIL` | `admin@nxr.local` |
| `NXR_ADMIN_DISCORD_ID` | `admin-prod` |
| `NXR_ADMIN_DISCORD_NAME` | `Admin NxR` |

### Bot Discord

| Variable | Role |
| --- | --- |
| `TOKEN` | Token du bot Discord |
| `CLIENT_ID` | ID application Discord |
| `GUILD_ID` / `DISCORD_GUILD_ID` | Serveur cible pour les slash commands |
| `ROUTE_API` | Base URL de l'API site, ex: `http://nginx/api` |
| `API_KEY` | Cle envoyee dans le header `x-api-key` |
| `COMMAND_CHANNEL_ID` | Salon autorise pour les commandes |
| `LOG_CHANNEL_ID` | Salon de logs |
| `ROSTER_CHANNEL_ID` | Salon d'annonce roster |
| `CAPITAINE_ROLE_ID` | Role Discord capitaine |

---

## 7. Routes principales

### Public

| Route | Description |
| --- | --- |
| `/` | Accueil |
| `/news` | Actualites |
| `/players` | Joueurs |
| `/rosters` | Rosters |
| `/schedule` | Planning |
| `/matches` | Matchs |
| `/cgu` | Conditions generales d'utilisation |

### Compte

| Route | Description |
| --- | --- |
| `/login` | Connexion Discord |
| `/connect/discord` | Depart OAuth Discord |
| `/auth/discord/callback` | Callback OAuth Discord |
| `/ajax/profile` | Fragment profil |
| `/ajax/profile/socials` | Mise a jour des liens reseaux |
| `/ajax/profile/delete` | Suppression/anonymisation du profil |
| `/logout` | Deconnexion |

### Administration

| Route | Description |
| --- | --- |
| `/login/admin` | Connexion admin par formulaire |
| `/admin/content/modal/{modal}` | Chargement modal admin |
| `/admin/content/news` | Creation news |
| `/admin/content/player` | Creation joueur |
| `/admin/content/roster` | Creation roster |
| `/admin/content/event` | Creation event |
| `/admin/content/match` | Creation match |
| `/admin/content/edit/{type}/{id}` | Lecture / sauvegarde edition |
| `/admin/content/match/{id}/result` | Mise a jour resultat |
| `/admin/content/tournament/{id}/players` | Joueurs d'un tournoi |

### API Discord

Toutes les routes `/api/discord/*` protegent les actions sensibles avec le header `x-api-key`.

| Route | Methode | Description |
| --- | --- | --- |
| `/api/discord/ping` | GET | Test API |
| `/api/discord/register` | POST | Synchronise un compte Discord |
| `/api/discord/add-event` | POST | Ajoute un event depuis le bot |
| `/api/discord/add-tournois` | POST | Synchronise les tournois |
| `/api/discord/tournoi-checkin` | POST | Synchronise une presence tournoi |

---

## 8. Bot Discord

Entrer dans le conteneur :

```bash
docker exec -it discord_bot bash
```

Installer les dependances si besoin :

```bash
npm install
```

Deployer les slash commands :

```bash
docker exec discord_bot npm run deploy:commands
```

Redemarrer le bot :

```bash
docker compose restart discord_bot
```

Verifier la syntaxe des commandes :

```bash
docker exec discord_bot sh -c "for f in cmd/*.js; do node --check \"$f\"; done"
```

---

## 9. Structure du projet

```text
AppWebNxr/
|-- app/
|   |-- assets/                 # JS/CSS sources Encore
|   |-- config/                 # Configuration Symfony
|   |-- migrations/             # Migrations Doctrine
|   |-- public/                 # Front controller et build assets
|   |-- src/
|   |   |-- Controller/         # Controllers web/admin
|   |   |-- Controller/Api/     # API Discord
|   |   |-- Entity/             # Entites Doctrine
|   |   |-- Form/               # Formulaires admin
|   |   |-- Service/            # Services metier
|   |   `-- DataFixtures/       # Fixtures test/prod
|   |-- templates/              # Templates Twig
|   `-- tests/                  # Tests PHPUnit
|-- bot/                        # Bot Discord actif
|-- botMerge/                   # Variante/archive bot
|-- botReav/                    # Variante/archive bot
|-- docker/nginx/default.conf   # Configuration Nginx
|-- Dockerfile                  # Image PHP FPM
`-- docker-compose.yml          # Stack locale
```

---

## 10. Conventions frontend

* Les templates Twig doivent rester structurels.
* Ne pas ajouter de `style="..."` dans les Twig.
* Ne pas ajouter de blocs `<style>` dans les Twig.
* Placer les styles dans `app/assets/styles/`.
* Les styles de page vont dans `app/assets/styles/pages/<page>/<page>.css`.
* Importer les nouveaux fichiers CSS dans `app/assets/styles/app.css`.
* Les comportements JS globaux vont dans `app/assets/app.js`.
* Les comportements par page vont dans `app/assets/js/pages/...`.

Exemples recents :

* styles profil : `app/assets/styles/pages/profile/profile.css` ;
* styles CGU : `app/assets/styles/pages/cgu/cgu.css` ;
* styles admin : `app/assets/styles/pages/admin/admin.css`.

---

## 11. Donnees personnelles

Le profil membre permet :

* d'afficher les infos Discord utiles ;
* de modifier les liens reseaux publics ;
* de supprimer le profil.

La suppression du profil :

* supprime le compte web `User` ;
* deconnecte la session ;
* vide les liens reseaux du joueur lie ;
* retire le `discordId` du joueur ;
* anonymise les donnees personnelles restantes du joueur ;
* retire les checkins et entrees roster d'evenements liees au Discord ID.

Les donnees sportives anonymisees peuvent rester pour conserver l'historique des matchs et evenements.

---

## 12. Qualite et tests

Dans le conteneur PHP :

```bash
docker exec -it symfony_php bash
```

Verifier le style PHP :

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Analyse statique :

```bash
vendor/bin/phpstan analyse
```

Tests PHPUnit :

```bash
vendor/bin/phpunit --testdox
```

Compiler le front :

```bash
docker exec -it symfony_node bash
npm run dev
```

---

## 13. Fichiers a ne pas versionner

Ces dossiers/fichiers sont generes localement et doivent rester ignores :

```text
app/vendor/
app/var/
app/node_modules/
app/public/build/
app/.phpunit.cache/
.idea/
.vscode/
```

Les caches `app/var/` et `app/.phpunit.cache/` peuvent etre supprimes sans risque : Symfony/PHPUnit les regenerent.

---

## 14. Workflow Git

Branches conseillees :

| Branche | Usage |
| --- | --- |
| `main` | Production |
| `feature/*` | Nouvelle fonctionnalite |
| `fix/*` | Correction |

Commandes :

```bash
git checkout main
git pull origin main
git checkout -b feature/nom-feature
```

Avant commit :

```bash
git status
docker exec -it symfony_php vendor/bin/phpunit --testdox
docker exec -it symfony_node npm run dev
```

Commit :

```bash
git add .
git commit -m "feat: description courte"
git push --set-upstream origin feature/nom-feature
```

---

## 15. Deploiement

La stack de production reprend la meme base Docker :

```bash
docker compose up -d --build
```

Apres deploiement :

```bash
docker exec -it symfony_php php bin/console doctrine:migrations:migrate --no-interaction
docker exec -it symfony_php php bin/console cache:clear
docker exec -it symfony_node npm run build
docker compose restart discord_bot
```

Verifier ensuite :

* `http://localhost:8080` ou l'URL publique ;
* `/api/discord/ping` ;
* connexion admin ;
* connexion Discord ;
* synchronisation bot/site.

---

## 16. Licence

Projet prive maintenu par la team Naxera eSport.

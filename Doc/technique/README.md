# 🚀 AppWebNxr

Application web basée sur Symfony, conteneurisée avec Docker pour garantir un environnement de développement et de production identique.

---

## 📋 Présentation

AppWebNxr est une application développée avec Symfony et exécutée dans un environnement Docker composé de :

* PHP 8.3 (FPM)
* Symfony
* Nginx
* MariaDB 11
* Composer
* Docker & Docker Compose

L'objectif est de fournir une plateforme simple à déployer, maintenable et compatible avec les environnements Debian 13.5.

---

# 🏗️ Architecture

```text
┌─────────────┐
│   Nginx     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Symfony     │
│ PHP 8.3 FPM │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ MariaDB 11  │
└─────────────┘
```

---

# 📂 Structure du projet

```text
AppWebNxr/
│
├── app/                    # Application Symfony
│
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   │
│   └── php/
│
├── Dockerfile
├── docker-compose.yml
├── .env
└── README.md
```

---

# ⚙️ Prérequis

Avant de commencer, assurez-vous d'avoir installé :

* Docker Desktop
* Docker Compose

Téléchargement :

https://www.docker.com/products/docker-desktop/

---

# 🚀 Installation

## 1. Cloner le dépôt

```bash
git clone <repo-url>
cd AppWebNxr
```

## 2. Construire et démarrer l'environnement

```bash
docker compose up -d --build
```

## 3. Accéder au conteneur PHP

```bash
docker exec -it symfony_php bash
```

---

# 🌐 Accès à l'application

Une fois les conteneurs démarrés :

```text
http://localhost:8080
```

---

# 🗄️ Base de données

## Informations de connexion

| Paramètre | Valeur  |
| --------- | ------- |
| Host      | db      |
| Port      | 3306    |
| Database  | symfony |
| User      | symfony |
| Password  | symfony |

### Configuration Symfony

```env
DATABASE_URL="mysql://symfony:symfony@db:3306/symfony"
```

---

# 🛠️ Commandes Docker utiles

## Démarrer les services

```bash
docker compose up -d
```

## Arrêter les services

```bash
docker compose down
```

## Rebuild complet

```bash
docker compose up -d --build
```

## Afficher les logs

### PHP

```bash
docker logs symfony_php
```

### Nginx

```bash
docker logs symfony_nginx
```

### MariaDB

```bash
docker logs symfony_db
```

---

# 🧰 Symfony CLI

La Symfony CLI peut être utilisée pour du diagnostic uniquement.

⚠️ L'application doit toujours être exécutée via Docker et Nginx.

Exemple :

```bash
symfony check:requirements
```

---

# 🌿 Workflow Git

## Branches

| Branche   | Utilisation   |
| --------- | ------------- |
| main      | Production    |
| feature/* | Développement |

---

## Créer une nouvelle fonctionnalité

```bash
git checkout main
git pull origin main

git checkout -b feature/nom-feature
```

---

## Commit et Push

```bash
git add .

git commit -m "feat: description de la fonctionnalité"

git push
```

---

## Fusion vers la branche principale

Les fusions vers `main` sont réalisées après validation de l'équipe de développement.

---

# 🚀 Déploiement Production

Le déploiement utilise exactement la même stack que l'environnement de développement.

```bash
docker compose up -d --build
```

Compatible :

* Debian 13.5
* Docker Engine
* Docker Compose

---

# 📌 Bonnes pratiques

### Git

* Toujours effectuer un `git pull` avant de commencer.
* Ne jamais développer directement sur `main`.
* Utiliser des messages de commit explicites.

Exemples :

```text
feat: ajout du système de tournois
fix: correction de l'authentification Discord
chore: mise à jour des dépendances
```

### Symfony

* Utiliser Composer uniquement dans le conteneur Docker.
* Ne jamais lancer l'application avec `symfony serve`.

### Versionnement

Ne jamais versionner :

```text
/vendor
/var
/.idea
/.vscode
```

Ajouter ces éléments dans le `.gitignore`.

---

# 👥 Équipe

Projet développé et maintenu par l'équipe **AppWebNxr**.

---

# 📄 Licence

Projet privé.

Tous droits réservés.
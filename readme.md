# Rezilio

Rezilio est une application de suivi de mise en conformité pensées pour les DSI et les RSSI.

L'objectif du projet est de centraliser le pilotage de la conformité, de suivre les écarts, de structurer les plans d'action et de donner une vision claire de la progression vers les exigences d'un référentiel (directive NIS2 par exemple), notamment autour de la gouvernance, de la gestion des risques, de la continuité d'activité, de la sécurité de la chaîne d'approvisionnement et de la gestion des incidents.

## Pourquoi Rezilio

La France et l'Europe renforce fortement les attentes en matière de cybersécurité pour les organisations, avec un accent clair sur la supervision par la direction, la formation, la gestion des risques cyber et les mesures organisationnelles, techniques et humaines.

Pour les organisations, la trajectoire de mise en conformité reste structurante et progressive, dans un contexte où leur exposition à la menace est reconnue comme élevée et où les modalités exactes d'intégration en droit français se précisent au fil de la transposition.

Rezilio a été imaginé pour répondre à ce besoin opérationnel avec une approche simple : transformer une obligation réglementaire complexe en un pilotage concret, mesurable et collaboratif.

## Ce que permet l'application

- Cartographier les exigences et les relier à des mesures concrètes.
- Évaluer le niveau de maturité ou de conformité par domaine.
- Identifier les écarts et prioriser les actions de remédiation.
- Suivre l'avancement des plans d'action dans le temps.
- Affecter des responsables, des échéances et des statuts aux mesures.
- Préparer les audits, revues internes et travaux de gouvernance.
- Produire une vision consolidée pour la direction, la DSI et le RSSI.

## Cas d'usage

### Pour une collectivité territoriale

Rezilio peut servir à structurer la feuille de route NIS2, suivre les obligations applicables, documenter les preuves de conformité et coordonner les équipes métiers, techniques et de direction autour d'un référentiel commun.

### Pour un éditeur de logiciels

L'application peut aussi être utilisée pour piloter ses propres mesures de cybersécurité ou accompagner ses clients dans leur démarche de conformité, avec une logique de suivi par exigences, plans d'actions et indicateurs.

## Positionnement produit

Rezilio n'est pas seulement un outil documentaire. C'est un outil de pilotage qui aide à passer :

- d'une lecture réglementaire à une exécution opérationnelle ;
- d'exigences générales à des mesures concrètes ;
- d'un état des lieux ponctuel à un suivi continu.

## Fonctionnalités envisagées

- Tableau de bord de conformité.
- Bibliothèque d'exigences et de mesures.
- Gestion des écarts et des risques.
- Plans d'actions et workflows de validation.
- Gestion des preuves et pièces associées.
- Reporting de maturité et export des suivis.
- Multi-entités, multi-sites ou multi-clients.
- Historisation des décisions et traçabilité.

## Public visé

- RSSI
- DSI
- Responsables conformité
- Directions générales
- Collectivités territoriales

## Vision

Rezilio vise à devenir un socle de pilotage de la conformité cyber pour les organisations qui doivent transformer les exigences NIS2 en actions concrètes, suivies et démontrables.

## Statut du projet

Projet en cours de conception et de développement.

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.2+ / Symfony 7 |
| Base de données | PostgreSQL 16 |
| ORM | Doctrine (inclus dans Symfony) |
| Authentification | Symfony Security |
| Frontend | JavaScript (Stimulus / Turbo via AssetMapper) |
| Environnement dev | Docker + Docker Compose |
| Hébergement cible | Cloud souverain, on-premise ou environnement maîtrisé |

### Prérequis

Avant de démarrer, assurez-vous d'avoir installé :

- [Docker](https://docs.docker.com/get-docker/) et [Docker Compose](https://docs.docker.com/compose/) (v2+)
- [PHP 8.2+](https://www.php.net/downloads) avec les extensions `pdo_pgsql`, `intl`, `mbstring`, `xml`
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download) _(optionnel mais recommandé pour le dev local)_

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/CyrilCharlier/rezilio.git
cd rezilio
```

### 2. Configurer l'environnement

Copiez le fichier d'exemple et adaptez les valeurs à votre environnement local :

```bash
cp .env.example .env.local
```

Éditez `.env.local` et renseignez au minimum :

```dotenv
# Générez un secret avec : php -r "echo bin2hex(random_bytes(16));"
APP_SECRET=votre_secret_local

# Adaptez les identifiants PostgreSQL si nécessaire
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/rezilio?serverVersion=16&charset=utf8"
```

> **Note :** `.env.local` est ignoré par Git (voir `.gitignore`). Ne commitez jamais vos secrets.

### 3. Démarrer les services Docker

```bash
docker compose up -d
```

Cela démarre un conteneur PostgreSQL accessible sur le port `5432` par défaut.

### 4. Installer les dépendances PHP

```bash
composer install
```

### 5. Créer la base de données et appliquer les migrations

```bash
# Créer la base de données (si elle n'existe pas encore)
php bin/console doctrine:database:create

# Appliquer toutes les migrations
php bin/console doctrine:migrations:migrate
```

Confirmez avec `yes` lorsque Symfony demande la validation.

### 6. Lancer le serveur de développement

Avec la CLI Symfony :

```bash
symfony serve
```

Ou directement avec le serveur PHP intégré :

```bash
php -S localhost:8000 -t public/
```

L'application est alors disponible sur [http://localhost:8000](http://localhost:8000).

### Commandes utiles

```bash
# Vérifier l'état des migrations
php bin/console doctrine:migrations:status

# Générer une nouvelle migration après modification d'une entité
php bin/console make:migration

# Vider le cache Symfony
php bin/console cache:clear

# Lister toutes les routes disponibles
php bin/console debug:router

# Arrêter les services Docker
docker compose down
```

## Roadmap

- Authentification et gestion des organisations
- Référentiels d'exigences (NIS2, RGPD, Guide d'hygiène ANSSI, ...)
- Évaluation de conformité
- Plans d'actions et remédiation
- Dashboard et indicateurs
- Exports et reporting
- Gestion des preuves
- Administration multi-tenant

## Licence

À définir.

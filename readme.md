# Rezilio

[![CI](https://github.com/CyrilCharlier/rezilio/actions/workflows/ci.yml/badge.svg)](https://github.com/CyrilCharlier/rezilio/actions/workflows/ci.yml)
[![CodeQL](https://github.com/CyrilCharlier/rezilio/actions/workflows/codeql.yml/badge.svg)](https://github.com/CyrilCharlier/rezilio/actions/workflows/codeql.yml)
[![Dependabot Updates](https://github.com/CyrilCharlier/rezilio/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/CyrilCharlier/rezilio/actions/workflows/dependabot/dependabot-updates)

Rezilio est une application web de pilotage de la mise en conformité cybersécurité, pensée pour les RSSI, les DSI et les responsables conformité des organisations assujetties à des référentiels réglementaires tels que la directive NIS2.[1][2]

L'objectif est de centraliser le pilotage de la conformité, de suivre les écarts, de structurer les plans de remédiation et de donner une vision claire et mesurable de la progression vers les exigences d'un référentiel, autour de la gouvernance, de la gestion des risques, de la continuité d'activité, de la sécurité de la chaîne d'approvisionnement et de la gestion des incidents.[3]

***

## Pourquoi Rezilio

La transposition française de la directive NIS2 suit le projet de loi « Résilience », déposé en octobre 2024, adopté par le Sénat en mars 2025 et poursuivi ensuite dans le processus parlementaire en 2025, avec une entrée en vigueur conditionnée à la promulgation des textes de transposition.[1][2]

En parallèle, l’ANSSI met à disposition depuis mars 2026 le Référentiel Cyber France (ReCyF), en version de travail, pour aider les futures entités assujetties à atteindre les objectifs de sécurité fixés par NIS2 et à s’en prévaloir lors d’un contrôle.[4][3]

Rezilio a été conçu pour répondre à ce besoin opérationnel : transformer une obligation réglementaire complexe en un pilotage concret, mesurable et collaboratif.

***

## Ce que permet l'application

- Cartographier les exigences réglementaires et les relier à des mesures concrètes.
- Gérer plusieurs référentiels de conformité (création et gestion par les rôles d’administration).
- Gérer plusieurs sociétés et rattacher un utilisateur à une ou plusieurs sociétés selon son niveau de droit.
- Évaluer le niveau de maturité ou de conformité par domaine.
- Identifier les écarts et prioriser les actions de remédiation.
- Piloter les remédiations en vue Kanban ou liste, avec gestion des statuts, priorités et échéances.
- Affecter des responsables et suivre l'avancement dans le temps.
- Préparer les audits, revues internes et travaux de gouvernance.
- Produire une vision consolidée pour la direction, la DSI et le RSSI.
- Associer des preuves de conformité à chaque revue de mesure (pièces jointes, justificatifs, comptes rendus).

***

## Cas d'usage

### Pour une collectivité territoriale

Structurer la feuille de route NIS2, suivre les obligations applicables, documenter les preuves de conformité et coordonner les équipes métiers, techniques et de direction autour d'un référentiel commun.

### Pour un éditeur de logiciels

Piloter ses propres mesures de cybersécurité ou accompagner ses clients dans leur démarche de conformité, avec une logique de suivi par exigences, plans d'actions et indicateurs.

***

## Positionnement produit

Rezilio n'est pas un outil documentaire. C'est un outil de pilotage qui aide à passer :

- d'une lecture réglementaire à une exécution opérationnelle ;
- d'exigences générales à des mesures concrètes ;
- d'un état des lieux ponctuel à un suivi continu.

***

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.3 · Symfony 7 |
| Templating | Twig 3 |
| Frontend | Bootstrap 5.3 · Bootstrap Icons · AdminLTE 4 |
| JavaScript | Vanilla JS (ES2022) · SortableJS |
| Base de données | PostgreSQL |
| Authentification | Symfony Security · 2FA TOTP (SchebTwoFactorBundle) [5] |
| ORM | Doctrine ORM |
| Formulaires | Symfony Forms · thème Bootstrap 5 |
| QR Code | endroid/qr-code-bundle |
| Logging sécurité | Monolog (channel `security_rezilio`) |
| Logging métier | Monolog (channel `business_rezilio`, événements structurés JSON) |
| Déploiement | Docker Compose · Caddy |

***

## Installation

### Développement local

```bash
git clone https://github.com/CyrilCharlier/rezilio.git
cd rezilio
composer install
cp .env .env.local
# configurer DATABASE_URL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
```

### Pré-requis

- PHP 8.3
- PostgreSQL
- Composer
- Extension PHP `gd` si la génération du QR code 2FA est activée dans l’environnement cible
- Accès à une base PostgreSQL configurée via `DATABASE_URL`

### Première initialisation

Après installation, il est recommandé de :

- créer la base de données ;
- exécuter les migrations ;
- vérifier le login ;
- vérifier le workflow 2FA si celui-ci est activé ;
- contrôler la génération du QR code si l’enrôlement TOTP est exposé dans l’interface.[5]

***

## Développement sur serveur

Rezilio peut aussi être développé directement sur le serveur, notamment dans un contexte auto-hébergé avec `code-server`, Docker Compose et Caddy, ce qui correspond au mode d’exploitation actuellement retenu.[8]

### Répertoire de travail

```bash
cd ~/workspace/rezilio
```

### Changement de code PHP / Symfony

Pour un changement de code applicatif classique, il n’est généralement pas nécessaire de rebuild l’image Docker si le code est déjà monté dans le conteneur applicatif.

Le workflow recommandé est alors :

```bash
cd ~/workspace/rezilio
docker compose -f compose.prod.yaml exec app php bin/console cache:clear --env=prod
docker compose -f compose.prod.yaml restart app
```

Le `cache:clear` Symfony vide et réchauffe le cache applicatif, ce qui est la méthode standard pour prendre en compte un changement de code en production ou préproduction.[6][7]

### Changement nécessitant un rebuild

Un rebuild de l’image `app` devient nécessaire en cas de modification du `Dockerfile`, d’ajout d’une extension PHP ou de dépendances système, par exemple pour activer `ext-gd` utilisée dans la génération de QR code 2FA.

```bash
cd ~/workspace/rezilio
docker compose -f compose.prod.yaml build app
docker compose -f compose.prod.yaml up -d app
docker compose -f compose.prod.yaml exec app php bin/console cache:clear --env=prod
```

***

## Supervision et healthcheck

Rezilio dispose d’un endpoint `/healthz` destiné à la supervision légère et aux `healthcheck` Docker.[14]

Cet endpoint peut être utilisé pour :

- vérifier que Symfony répond ;
- vérifier la connectivité PostgreSQL ;
- confirmer qu’un déploiement s’est bien déroulé ;
- alimenter un `healthcheck` Compose.

Exemples :

```bash
curl -i http://localhost/healthz
curl -i https://rezilio.domaine.com/healthz
```

***

## Gestion des preuves et pièces jointes

Rezilio permet d’associer des preuves de conformité à chaque revue de mesure, sous forme de pièces jointes structurées et journalisées.

### UX et ergonomie

- Panneau latéral de gestion des preuves accessible depuis une revue de mesure.
- Formulaire compact d’ajout de preuve (fichier + description) dans un panneau dédié.
- Liste des preuves associées à la revue, avec nom, taille, auteur et date d’upload.
- Téléchargement direct des pièces jointes et suppression contrôlée (CSRF, droits).

L’objectif est d’intégrer la preuve dans le contexte de la revue, sans surcharger l’interface principale.

### Stockage et sécurité

- Fichiers stockés hors de `public/` (par exemple dans `var/uploads/evidence/`).
- Téléchargement uniquement via un contrôleur Symfony, après contrôle d’accès.
- Vérification stricte du type MIME et de la taille côté serveur.
- Rattachement des preuves à une revue de mesure (`MeasureReview`) et à l’utilisateur qui l’a déposée.

Cette approche vise à limiter l’exposition directe des fichiers et à garder un contrôle fin sur qui peut consulter quoi.

### Traçabilité et logs métier

Les actions sur les preuves sont journalisées dans le canal métier `business_rezilio`, avec un format JSON structuré cohérent avec les autres événements métiers :

- Ajout de preuve : `evidence.upload.success`
- Suppression de preuve : `evidence.delete.success`
- Téléchargement de preuve : `evidence.download.success`
- Téléchargement avec fichier physique manquant : `evidence.download.file_missing`
- Suppression avec CSRF invalide : `evidence.delete.csrf_invalid`
- Refus d’accès à une revue contenant des preuves : `evidence.access.denied`

Chaque événement inclut notamment :

- l’identifiant de la preuve et le nom de fichier original ;
- l’identifiant de la revue et de la mesure associée ;
- l’initiateur (id, username) ;
- le contexte HTTP (IP, route, méthode, user-agent).

Ces logs sont pensés pour être exploitables dans un SIEM au même titre que les événements de sécurité.

***

## Fonctionnalités implémentées

- [x] Authentification et gestion de session
- [x] Authentification forte (2FA)
  - Activation forcée de la 2FA à la connexion si non configurée
  - Enrôlement TOTP (URI `otpauth://` + QR code dans l’interface)
  - Vérification du premier code pour activer la 2FA
  - Challenge 2FA à chaque connexion pour les comptes activés
  - Tolérance configurable sur la dérive d’horloge (leeway)
- [x] Journalisation des événements de sécurité
  - Channel dédié `security_rezilio` (Monolog)
  - Événements normalisés (`auth.login.*`, `auth.logout`, `auth.2fa.*`, `user.account.*`, etc.)
  - ID de corrélation `auth_flow_id` pour relier login et challenge 2FA
  - Logs structurés pour exploitation SIEM (ELK, Splunk, Sentinel…)
- [x] Bibliothèque d'exigences NIS2 par article
- [x] Revues de conformité par mesure (statut, score, commentaire)
- [x] Dashboard de conformité par domaine
- [x] Multi‑référentiels et multi‑sociétés
  - Création et gestion de plusieurs référentiels (rôle `ROLE_ADMIN`)
  - Gestion de plusieurs sociétés / entités
  - Rattachement d’un même utilisateur à plusieurs sociétés, avec niveaux de droits
- [x] Gestion des remédiations
  - Vue Kanban avec drag-and-drop (SortableJS)
  - Vue liste / tableau
  - Drawer de création / édition (offcanvas Bootstrap)
  - Gestion des statuts, priorités, responsables et échéances
  - Filtres persistants (état sauvegardé en session)
  - Mise à jour de statut par PATCH AJAX
- [x] Gestion des preuves de conformité
  - Panneau de gestion des preuves par revue de mesure
  - Upload sécurisé de pièces jointes (vérification MIME, taille)
  - Stockage des fichiers hors `public/`
  - Téléchargement contrôlé via contrôleur Symfony
  - Journalisation métier des actions (upload, suppression, téléchargement, accès refusé)
- [x] Endpoint de health/readiness (`/healthz`) pour supervision et healthcheck Docker

***

## Roadmap

- [ ] Exports PDF / CSV des suivis
- [ ] Reporting de maturité et indicateurs graphiques
- [ ] Historisation fine des décisions et traçabilité
- [ ] Intégration avancée du ReCyF (Référentiel Cyber France — ANSSI) [4][3]
- [ ] Exemples de pipelines SIEM / dashboards pour les logs de sécurité et les logs métier (preuves, remédiations)
- [ ] Backup codes / trusted devices pour la 2FA, selon les besoins futurs [5]

***

## Documentation technique

- [docs/security-logging.md](docs/security-logging.md) : format des logs de sécurité et exemples d’événements (auth, 2FA, comptes).
- [docs/health-readiness.md](docs/health-readiness.md) : endpoint `/healthz`, supervision et intégration Docker.
- [docs/deployment-process.md](docs/deployment-process.md) : procédure de déploiement, rebuild, vérifications post-déploiement et rollback.
- (à venir) `docs/evidence-management.md` : modèle de données des preuves, flux d’upload/Téléchargement, format des logs métier `evidence.*`.

***

## Déploiement production

Le déploiement actuel repose sur Docker Compose avec exposition via Caddy et protections renforcées sur les surfaces d’administration, notamment par restriction IP et authentification HTTP en frontal pour `pgadmin` et `code-server`.[9][8]

Dans ce modèle :

- un changement de code PHP/Symfony nécessite généralement un `cache:clear` applicatif ;
- un changement de `Dockerfile` ou d’extensions PHP nécessite un rebuild de l’image ;
- les changements Caddy doivent être validés et rechargés proprement ;
- les vérifications post-déploiement passent par `docker compose ps`, les logs et l’endpoint `/healthz`.[15][8]

La procédure détaillée est documentée dans [docs/deployment-process.md](docs/deployment-process.md).

***

## Public visé

- RSSI
- DSI
- Responsables conformité
- Directions générales
- Collectivités territoriales
- Éditeurs de logiciels assujettis NIS2

***

## Licence

Rezilio est distribué sous licence Apache 2.0. Voir le fichier `LICENSE` pour plus de détails.

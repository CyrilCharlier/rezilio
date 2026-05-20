# Rezilio

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
- Évaluer le niveau de maturité ou de conformité par domaine.
- Identifier les écarts et prioriser les actions de remédiation.
- Piloter les remédiations en vue Kanban ou liste, avec gestion des statuts, priorités et échéances.
- Affecter des responsables et suivre l'avancement dans le temps.
- Préparer les audits, revues internes et travaux de gouvernance.
- Produire une vision consolidée pour la direction, la DSI et le RSSI.

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

Rezilio peut aussi être développé directement sur le serveur, notamment dans un contexte auto-hébergé avec `code-server`, Docker Compose et Caddy, ce qui correspond au mode d’exploitation actuellement retenu.

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

## Configuration serveur actuelle

L’environnement serveur actuellement documenté repose sur une exposition publique via Caddy et une segmentation des services par sous-domaines dédiés.[8]

### Exposition des services

- `rezilio.domaine.com` : application principale
- `ide.domaine.com` : accès `code-server`
- `pgadmin.domaine.com` : administration PostgreSQL

### Reverse proxy Caddy

Caddy est utilisé comme reverse proxy frontal pour exposer les services Docker en HTTPS et pour gérer les protections d’accès sur les surfaces d’administration.[8]

Les services sensibles `ide.domaine.com` et `pgadmin.domaine.com` sont protégés par :

- une restriction IP via matcher `remote_ip` ;
- une authentification HTTP `basic_auth` ;
- puis l’authentification native du service exposé derrière le proxy.[9][8]

### Pare-feu et SSH

Le serveur est protégé par UFW avec :

- politique par défaut `deny incoming` ;
- HTTP/HTTPS autorisés ;
- SSH déplacé sur le port `2223` ;
- accès SSH limité à des IP autorisées.[10][11][12]

Ce modèle réduit fortement l’exposition des interfaces d’administration et l’accès distant au serveur.[13][12]

### pgAdmin

`pgadmin` est exposé uniquement via Caddy et n’est plus censé être exposé via un port local dédié si le passage par le reverse proxy est retenu à 100%.[8]

### code-server

`code-server` est exposé via `ide.domaine.com` et bénéficie du même principe de défense en profondeur : allowlist IP, `basic_auth` Caddy, puis authentification du service lui-même.[9]

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
- [x] Gestion des remédiations
  - Vue Kanban avec drag-and-drop (SortableJS)
  - Vue liste / tableau
  - Drawer de création / édition (offcanvas Bootstrap)
  - Gestion des statuts, priorités, responsables et échéances
  - Filtres persistants (état sauvegardé en session)
  - Mise à jour de statut par PATCH AJAX
- [x] Endpoint de health/readiness (`/healthz`) pour supervision et healthcheck Docker

***

## Roadmap

- [ ] Gestion des preuves et pièces jointes
- [ ] Exports PDF / CSV des suivis
- [ ] Reporting de maturité et indicateurs graphiques
- [ ] Multi-entités / multi-référentiels
- [ ] Historisation des décisions et traçabilité
- [ ] Intégration avancée du ReCyF (Référentiel Cyber France — ANSSI) [4][3]
- [ ] Administration multi-tenant
- [ ] Exemples de pipelines SIEM / dashboards pour les logs de sécurité
- [ ] Backup codes / trusted devices pour la 2FA, selon les besoins futurs [5]

***

## Documentation technique

- [docs/security-logging.md](docs/security-logging.md) : format des logs de sécurité et exemples d’événements (auth, 2FA, comptes).
- [docs/health-readiness.md](docs/health-readiness.md) : endpoint `/healthz`, supervision et intégration Docker.
- [docs/deployment-process.md](docs/deployment-process.md) : procédure de déploiement, rebuild, vérifications post-déploiement et rollback.

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

À définir.
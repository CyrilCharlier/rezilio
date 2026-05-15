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

## Idée de stack

À adapter selon ton implémentation réelle :

- Frontend : JavaScript
- Backend : Symfony
- Base de données : PostgreSQL
- Authentification : Symfony Auth
- Hébergement : cloud souverain, on-premise ou environnement maîtrisé

## Installation

```bash
git clone <repo-url>
cd rezilio
composer install
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

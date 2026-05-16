# Rezilio

Rezilio est une application web de pilotage de la mise en conformité cybersécurité, pensée pour les RSSI, les DSI et les responsables conformité des organisations assujetties à des référentiels réglementaires tels que la directive NIS2.

L'objectif est de centraliser le pilotage de la conformité, de suivre les écarts, de structurer les plans de remédiation et de donner une vision claire et mesurable de la progression vers les exigences d'un référentiel — autour de la gouvernance, de la gestion des risques, de la continuité d'activité, de la sécurité de la chaîne d'approvisionnement et de la gestion des incidents.

---

## Pourquoi Rezilio

La France transpose actuellement la directive NIS2 (UE 2022/2555) via le projet de loi « Résilience ». Adopté au Sénat en mars 2025 et en commission spéciale à l'Assemblée en septembre 2025, le texte est attendu en séance publique lors de la session extraordinaire de juillet 2026. À terme, près de **15 000 organisations** dans 18 secteurs d'activité seront assujetties — dont les collectivités territoriales de plus de 30 000 habitants et les éditeurs de logiciels —, contre 500 entités sous NIS1.

En parallèle, l'ANSSI a publié en mars 2026 le **Référentiel Cyber France (ReCyF)**, document de travail listant les mesures recommandées pour atteindre les objectifs NIS2 et s'en prévaloir en cas de contrôle.

Rezilio a été conçu pour répondre à ce besoin opérationnel : transformer une obligation réglementaire complexe en un pilotage concret, mesurable et collaboratif.

---

## Ce que permet l'application

- Cartographier les exigences réglementaires et les relier à des mesures concrètes.
- Évaluer le niveau de maturité ou de conformité par domaine.
- Identifier les écarts et prioriser les actions de remédiation.
- Piloter les remédiations en vue Kanban ou liste, avec gestion des statuts, priorités et échéances.
- Affecter des responsables et suivre l'avancement dans le temps.
- Préparer les audits, revues internes et travaux de gouvernance.
- Produire une vision consolidée pour la direction, la DSI et le RSSI.

---

## Cas d'usage

### Pour une collectivité territoriale

Structurer la feuille de route NIS2, suivre les obligations applicables, documenter les preuves de conformité et coordonner les équipes métiers, techniques et de direction autour d'un référentiel commun.

### Pour un éditeur de logiciels

Piloter ses propres mesures de cybersécurité ou accompagner ses clients dans leur démarche de conformité, avec une logique de suivi par exigences, plans d'actions et indicateurs.

---

## Positionnement produit

Rezilio n'est pas un outil documentaire. C'est un outil de pilotage qui aide à passer :

- d'une lecture réglementaire à une exécution opérationnelle ;
- d'exigences générales à des mesures concrètes ;
- d'un état des lieux ponctuel à un suivi continu.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.3 · Symfony 7 |
| Templating | Twig 3 |
| Frontend | Bootstrap 5.3 · Bootstrap Icons · AdminLTE 4 |
| JavaScript | Vanilla JS (ES2022) · SortableJS |
| Base de données | PostgreSQL |
| Authentification | Symfony Security |
| ORM | Doctrine ORM |
| Formulaires | Symfony Forms · thème Bootstrap 5 |

---

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

## Roadmap

- [ ] Gestion des preuves et pièces jointes
- [ ] Exports PDF / CSV des suivis
- [ ] Reporting de maturité et indicateurs graphiques
- [ ] Multi-entités / multi-référentiels
- [ ] Historisation des décisions et traçabilité
- [ ] Intégration du ReCyF (Référentiel Cyber France — ANSSI, mars 2026)
- [ ] Administration multi-tenant

---

## Public visé

- RSSI
- DSI
- Responsables conformité
- Directions générales
- Collectivités territoriales (>30 000 hab.)
- Éditeurs de logiciels assujettis NIS2

---

## Licence

À définir.

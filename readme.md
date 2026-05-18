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

| Couche              | Technologie                                         |
|---------------------|-----------------------------------------------------|
| Backend             | PHP 8.3 · Symfony 7                                 |
| Templating          | Twig 3                                              |
| Frontend            | Bootstrap 5.3 · Bootstrap Icons · AdminLTE 4        |
| JavaScript          | Vanilla JS (ES2022) · SortableJS                    |
| Base de données     | PostgreSQL                                          |
| Authentification    | Symfony Security · 2FA TOTP (SchebTwoFactorBundle)  |
| ORM                 | Doctrine ORM                                        |
| Formulaires         | Symfony Forms · thème Bootstrap 5                   |
| QR Code             | endroid/qr-code-bundle                              |
| Logging sécurité    | Monolog (channel `security_rezilio`)                |

---

## Installation

```bash
git clone https://github.com/CyrilCharlier/rezilio.git
cd rezilio
composer install

cp .env .env.local   # configurer DATABASE_URL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

symfony serve
```

---

## Fonctionnalités implémentées

- [x] Authentification et gestion de session
- [x] Authentification forte (2FA)
  - Activation forcée de la 2FA à la connexion si non configurée
  - Enrôlement TOTP (URI otpauth:// + QR code dans l’interface)
  - Vérification du premier code pour activer la 2FA
  - Challenge 2FA à chaque connexion pour les comptes activés
  - Tolérance configurable sur la dérive d’horloge (leeway)
- [x] Journalisation des événements de sécurité
  - Channel dédié `security_rezilio` (Monolog)
  - Événements normalisés (`auth.login.*`, `auth.logout`, `auth.2fa.*`, `user.account.*`, etc.)
  - ID de corrélation `auth_flow_id` pour relier login et challenges 2FA
  - Logs structurés pour exploitation SIEM (ELK, Splunk, Sentinel…)
   _Voir la [documentation détaillée des logs](docs/security-logging.md) pour l’exploitation dans un SIEM._
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

---

## Roadmap

- [ ] Gestion des preuves et pièces jointes
- [ ] Exports PDF / CSV des suivis
- [ ] Reporting de maturité et indicateurs graphiques
- [ ] Multi-entités / multi-référentiels
- [ ] Historisation des décisions et traçabilité
- [ ] Intégration du ReCyF (Référentiel Cyber France — ANSSI, mars 2026)
- [ ] Administration multi-tenant
- [ ] Exemples de pipelines SIEM / dashboards pour les logs de sécurité

---

## Documentation technique

- `docs/security-logging.md` : format des logs de sécurité et exemples d’événements (auth, 2FA, comptes).

---

## Public visé

- RSSI
- DSI
- Responsables conformité
- Directions générales
- Collectivités territoriales (> 30 000 hab.)
- Éditeurs de logiciels assujettis NIS2

---

## Licence

À définir.

# Processus de déploiement Rezilio

Ce document décrit le processus de déploiement applicatif de Rezilio sur l’infrastructure de production actuelle, basée sur Docker Compose, Caddy, PostgreSQL et une application Symfony/PHP exposée via `rezilio.charlier.cloud`.[1]

## Objectif

Ce processus vise à standardiser les mises en production, limiter les erreurs manuelles, vérifier l’état de santé de l’application après changement, et conserver une procédure exploitable en cas d’incident ou de rollback.[2]

## Périmètre

Le périmètre couvert par cette procédure comprend :

- l’application Symfony `rezilio-app` ;
- la base PostgreSQL `database` ;
- le reverse proxy Caddy ;
- les services d’administration exposés via sous-domaines dédiés comme `pgadmin.charlier.cloud` et `ide.charlier.cloud` avec restrictions IP et authentification HTTP en amont.[1][3][4]

## Principes d’exploitation

Les changements de code applicatif PHP, Symfony, Twig ou configuration métier ne nécessitent pas forcément un rebuild de l’image si le code est monté dans le conteneur et que l’environnement reste inchangé.

En revanche, toute modification touchant à l’image applicative, notamment l’ajout d’une extension PHP comme `gd`, impose un rebuild de l’image puis un redéploiement du service concerné.[5]

## Pré-requis

Avant tout déploiement, vérifier les points suivants :

- accès SSH au serveur sur le port `2223` depuis une IP autorisée ;[6]
- état des conteneurs Docker Compose ;
- cohérence de la configuration applicative et des variables d’environnement ;
- sauvegarde ou point de restauration disponible pour la base si le déploiement comporte une migration sensible ;
- accès fonctionnel à l’endpoint de santé applicatif `/healthz` une fois mis en place.[7][2]

## Déploiement sans rebuild d’image

Ce cas couvre les changements de code PHP, contrôleurs, services, templates Twig, routes, logique Symfony ou fichiers applicatifs ne modifiant pas la composition système de l’image Docker.

### Étapes

1. Se connecter au serveur.
2. Se placer dans le répertoire du projet.
3. Vérifier l’état des services.
4. Vider le cache Symfony en production.
5. Redémarrer le service applicatif si nécessaire.
6. Vérifier l’endpoint `/healthz`.
7. Vérifier l’application via l’URL publique.

### Commandes

```bash
cd ~/workspace/rezilio
docker compose -f compose.prod.yaml ps
docker compose -f compose.prod.yaml exec app php bin/console cache:clear --env=prod
docker compose -f compose.prod.yaml restart app
curl -i http://localhost/healthz
curl -i https://rezilio.charlier.cloud/healthz
```

Le `cache:clear` Symfony vide et réchauffe le cache applicatif, ce qui en fait la méthode standard pour prendre en compte un changement de code en production.[8][9]

## Déploiement avec rebuild d’image

Ce cas couvre les modifications de `Dockerfile`, l’ajout d’extensions PHP, de paquets système, de binaires utilitaires ou toute évolution nécessitant une reconstruction de l’image du service `app`.

### Exemples de cas concernés

- ajout de `ext-gd` pour la génération d’images ou QR codes ;
- ajout de `curl` ou `wget` pour un healthcheck ;
- ajout de dépendances système de compilation ou runtime ;
- changement de version de l’image de base PHP/Apache.[5][10]

### Étapes

1. Modifier le `Dockerfile` ou la définition du service.
2. Rebuilder l’image `app`.
3. Recréer le service.
4. Vider le cache Symfony si nécessaire.
5. Vérifier l’état des conteneurs.
6. Contrôler l’endpoint de santé.

### Commandes

```bash
cd ~/workspace/rezilio
docker compose -f compose.prod.yaml build app
docker compose -f compose.prod.yaml up -d app
docker compose -f compose.prod.yaml exec app php bin/console cache:clear --env=prod
docker compose -f compose.prod.yaml ps
curl -i http://localhost/healthz
```

Docker Compose permet de reconstruire puis de recréer le service ciblé sans redéployer l’ensemble de la stack, ce qui est adapté à un déploiement incrémental sur une architecture simple.[10][11]

## Cas particulier : modifications Caddy

Les changements de reverse proxy, `basic_auth`, restrictions IP par `remote_ip`, ou exposition de services d’administration passent par une validation et un rechargement gracieux de Caddy.[5][12]

### Commandes

```bash
docker exec caddy caddy fmt --overwrite /etc/caddy/Caddyfile
docker exec caddy caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
docker exec caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
```

Cette séquence permet d’éviter les erreurs de syntaxe et d’appliquer les changements sans interrompre brutalement le service reverse proxy.[5][13]

## Vérifications post-déploiement

Après chaque déploiement, contrôler au minimum les points suivants :

- `docker compose -f compose.prod.yaml ps` indique des services démarrés ;
- l’endpoint `/healthz` retourne `200 OK` si l’application et la base sont disponibles ;
- l’accès à `https://rezilio.charlier.cloud` fonctionne ;
- les surfaces sensibles comme `pgadmin.charlier.cloud` et `ide.charlier.cloud` restent protégées par IP allowlist et `basic_auth` ;
- les logs applicatifs ne montrent pas d’erreurs critiques immédiates.[3][4]

### Commandes utiles

```bash
cd ~/workspace/rezilio
docker compose -f compose.prod.yaml ps
docker compose -f compose.prod.yaml logs --tail=100 app
docker compose -f compose.prod.yaml logs --tail=100 database
curl -i https://rezilio.charlier.cloud/healthz
```

## Contrôles de sécurité opérationnels

Le serveur est déjà protégé par UFW avec une politique `deny incoming`, HTTP/HTTPS ouverts, et SSH déplacé sur `2223` avec allowlist IP, ce qui doit être préservé lors des interventions futures.[6][7]

Les services d’administration exposés sur Internet doivent conserver les protections suivantes :

- exposition uniquement via Caddy ;
- restrictions IP avec `remote_ip` ;
- `basic_auth` en frontal ;
- authentification native applicative derrière ;
- mots de passe distincts par surface d’administration.[3][4][14]

## Gestion d’incident et rollback

En cas d’échec après déploiement, la priorité est de restaurer rapidement l’accès applicatif, même avec une action minimaliste et temporaire.[2]

### Réponses rapides possibles

- relancer le service `app` ;
- revenir au `Caddyfile` précédent si le problème vient du proxy ;
- réappliquer la dernière image fonctionnelle si une modification de build a introduit la panne ;
- désactiver temporairement une modification non critique pour rétablir le service ;
- en cas de perte d’accès SSH liée à une erreur de filtrage, utiliser le mode rescue OVH comme mécanisme de récupération.[15][16]

## Hygiène documentaire

Chaque déploiement significatif devrait idéalement laisser une trace minimale :

- date et heure ;
- nature du changement ;
- commande exécutée ;
- résultat du healthcheck ;
- éventuel incident constaté ;
- action corrective appliquée.

Ce niveau de traçabilité reste léger mais améliore fortement la maintenabilité et les retours d’expérience sur un serveur auto-hébergé à long terme.[17]

## Commandes de référence

### Cache Symfony

```bash
docker compose -f compose.prod.yaml exec app php bin/console cache:clear --env=prod
```

### Rebuild applicatif

```bash
docker compose -f compose.prod.yaml build app
docker compose -f compose.prod.yaml up -d app
```

### Vérification état des conteneurs

```bash
docker compose -f compose.prod.yaml ps
```

### Reload Caddy

```bash
docker exec caddy caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
docker exec caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
```

### Test healthcheck

```bash
curl -i http://localhost/healthz
curl -i https://rezilio.charlier.cloud/healthz
```

## Recommandation d’usage

La bonne pratique opérationnelle pour Rezilio consiste à distinguer clairement :

- les déploiements applicatifs simples ;
- les déploiements nécessitant rebuild ;
- les changements d’infrastructure proxy/sécurité ;
- les opérations sensibles touchant à l’accès SSH ou aux règles de filtrage.[6]

Cette séparation réduit les erreurs, facilite les diagnostics, et rend la procédure exploitable même plusieurs semaines après sa rédaction.[2]
#!/usr/bin/env bash
set -euo pipefail

cd /home/cyril/workspace/rezilio

echo "==> Vérification du statut Git"
git status --short

echo "==> Installation des dépendances Composer"
docker compose -f compose.prod.yaml exec app composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Exécution des migrations Doctrine"
docker compose -f compose.prod.yaml exec app php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Nettoyage du cache Symfony"
docker compose -f compose.prod.yaml exec app php bin/console cache:clear

echo "==> Redémarrage du conteneur applicatif"
docker compose -f compose.prod.yaml restart app

echo "==> Déploiement terminé"

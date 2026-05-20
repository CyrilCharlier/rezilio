# Endpoint de health/readiness

Ce document décrit l’endpoint de supervision applicative de Rezilio destiné à un usage de type healthcheck Docker, reverse proxy ou supervision externe légère.

## Objectif

L’endpoint `/healthz` a pour but de fournir un signal simple sur l’état de disponibilité de l’application Symfony et, si souhaité, sur l’accessibilité de la base PostgreSQL.

Cet endpoint peut être utilisé pour :

- les `healthcheck` Docker Compose ;
- la supervision externe ;
- les vérifications de disponibilité après déploiement ;
- les diagnostics rapides en cas d’incident.

## URL

```text
GET /healthz
```

Exemple en production :

```text
https://rezilio.charlier.cloud/healthz
```

## Comportement attendu

### Cas nominal

Lorsque l’application est opérationnelle et que la connectivité base de données est disponible, l’endpoint doit renvoyer :

- code HTTP : `200 OK`
- corps de réponse : `OK`

### Cas dégradé

Lorsque l’application répond mais que la base de données n’est pas joignable, l’endpoint peut renvoyer :

- code HTTP : `503 Service Unavailable`
- corps de réponse : `DB ERROR`

## Implémentation Symfony

Une implémentation simple repose sur un contrôleur dédié qui exécute un `SELECT 1` sur la connexion Doctrine.

Exemple :

```php
<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HealthController
{
    #[Route('/healthz', name: 'app_healthz', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $em): Response
    {
        try {
            $em->getConnection()->executeQuery('SELECT 1')->fetchOne();
        } catch (\Throwable $e) {
            return new Response('DB ERROR', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
```

## Intégration sécurité

L’endpoint doit rester accessible sans authentification afin de pouvoir être consommé par Docker ou un système de supervision.

Exemple de configuration `security.yaml` :

```yaml
security:
  firewalls:
    healthz:
      pattern: ^/healthz
      security: false
```

Selon le niveau d’exposition souhaité, il peut être conservé publiquement accessible, ou protégé ensuite au niveau reverse proxy si une stratégie plus restrictive est retenue.

## Intégration Docker Compose

L’endpoint peut être branché directement dans le service applicatif comme healthcheck HTTP.

Exemple :

```yaml
healthcheck:
  test: ["CMD-SHELL", "curl -f http://localhost/healthz || exit 1"]
  interval: 30s
  timeout: 5s
  retries: 3
  start_period: 30s
```

Si `curl` n’est pas disponible dans l’image, il est possible d’utiliser `wget` ou un script PHP équivalent.

## Usages recommandés

### Vérification manuelle

Depuis le serveur :

```bash
curl -i http://localhost/healthz
```

Depuis l’extérieur :

```bash
curl -i https://rezilio.charlier.cloud/healthz
```

### Vérification après déploiement

Après une mise à jour applicative, l’endpoint permet de confirmer rapidement que :

- Apache répond ;
- Symfony démarre correctement ;
- Doctrine atteint PostgreSQL ;
- l’application est prête à servir du trafic.

## Bonnes pratiques

- Garder la réponse volontairement simple et stable.
- Éviter d’exposer des détails techniques sensibles dans le corps de réponse.
- Réserver les détails d’erreur complets aux logs applicatifs.
- Utiliser `200` uniquement lorsque l’application est réellement prête.
- Utiliser `503` lorsqu’une dépendance critique, comme PostgreSQL, n’est pas disponible.

## Évolution possible

L’endpoint peut ensuite être étendu pour distinguer deux usages :

- **liveness** : l’application tourne ;
- **readiness** : l’application est prête à recevoir du trafic.

Dans une première version pour Rezilio, un endpoint unique `/healthz` avec vérification base de données constitue une base simple, lisible et suffisante.
# Rezilio – Spécification des logs de sécurité

Ce document décrit le format des logs de sécurité émis par Rezilio sur le channel `security_rezilio`.  
L’objectif est de permettre l’exploitation de ces événements dans un SIEM (ELK, Splunk, Sentinel, etc.).

## 1. Structure globale d’un log

Chaque événement de sécurité est une entrée de log Monolog avec la structure suivante :

```json
{
  "message": "auth_event",
  "context": {
    "event_type": "auth.login.success",
    "user": {
      "id": 1,
      "username": "test@example.com"
    },
    "context": {
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (...)",
      "2fa_used": false,
      "2fa_method": null
    },
    "meta": {
      "initiator": "user",
      "reason": null,
      "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c"
    }
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security_rezilio",
  "datetime": "2026-05-17T22:42:37.795513+02:00",
  "extra": {}
}
```

Champs principaux :

- `message` : toujours `auth_event` pour ce type de logs  
- `context` : charge utile fonctionnelle (voir sections suivantes)  
- `level` / `level_name` : niveau de log (INFO par défaut pour ces événements)  
- `channel` : `security_rezilio`  
- `datetime` : horodatage ISO 8601  
- `extra` : réservé à d’éventuels enrichissements ultérieurs

Toutes les informations de sécurité utiles se trouvent dans `context`.

## 2. Structure de `context`

Le champ `context` est un objet qui contient lui‑même quatre blocs :

```json
"context": {
  "event_type": "auth.login.success",
  "user": {
    "id": 1,
    "username": "test@example.com"
  },
  "context": {
    "ip": "127.0.0.1",
    "user_agent": "Mozilla/5.0 (...)",
    "2fa_used": false,
    "2fa_method": null
  },
  "meta": {
    "initiator": "user",
    "reason": null,
    "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c"
  }
}
```

### 2.1 `event_type`

Type d’événement fonctionnel, issu de l’énumération `App\Enum\AuthEventType` :

- Authentification :
  - `auth.login.success`
  - `auth.login.failure`
  - `auth.login.remembered`
  - `auth.logout`
- Cycle de vie des comptes :
  - `user.account.created`
  - `user.account.disabled`
  - `user.account.enabled`
  - `user.account.updated`
  - `user.account.deleted`
- Mot de passe :
  - `auth.password.reset.requested`
  - `auth.password.reset.completed`
  - `auth.password.changed`
- Double authentification (2FA) :
  - `auth.2fa.enabled`
  - `auth.2fa.disabled`
  - `auth.2fa.challenge.success`
  - `auth.2fa.challenge.failure`

### 2.2 Bloc `user`

```json
"user": {
  "id": 1,
  "username": "test@example.com"
}
```

- `id` : identifiant interne du compte utilisateur (nullable)  
- `username` : identifiant de connexion (souvent l’email)

### 2.3 Bloc `context` (détails techniques)

```json
"context": {
  "ip": "127.0.0.1",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (...)",
  "2fa_used": true,
  "2fa_method": "totp",
  "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c" // présent pour certains événements 2FA
}
```

- `ip` : adresse IP vue par l’application  
- `user_agent` : chaîne User-Agent brute du client  
- `2fa_used` :  
  - `true` si une 2FA est utilisée sur ce flow,  
  - `false` sinon  
- `2fa_method` : méthode de 2FA utilisée (`"totp"`, `"email"`, etc., ou `null` si non applicable)  
- `auth_flow_id` :  
  - présent dans certains événements (notamment 2FA),  
  - identique à `meta.auth_flow_id` quand il est renseigné,  
  - permet une corrélation rapide dans certains parsers SIEM.

> Remarque : l’ID de corrélation principal est décrit dans la section `meta`.

### 2.4 Bloc `meta`

```jsonc
"meta": {
  "initiator": "user",
  "reason": "two_factor_flow_complete",
  "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c"
}
```

- `initiator` :  
  - `"user"` : action déclenchée par un utilisateur (login, saisie code 2FA…)  
  - `"system"` : action système (ex. `auth.login.remembered` via cookie remember-me)  
- `reason` :  
  - cause fonctionnelle (ex. `bad_credentials`, `invalid_code`, `remember_me_cookie`, `two_factor_flow_complete`),  
  - `null` si non applicable.  
- `auth_flow_id` :  
  - identifiant UUID v4 généré à chaque `auth.login.success`,  
  - recopié dans tous les événements 2FA associés (succès/échec de challenge, complétion),  
  - permet de corréler toutes les étapes d’une même tentative d’authentification (login + 2FA) dans le SIEM.

## 3. Exemples réels

Les exemples ci‑dessous sont issus de logs effectivement générés par Rezilio (format JSON ligne par ligne).

### 3.1 Déconnexion

```json
{
  "message": "auth_event",
  "context": {
    "event_type": "auth.logout",
    "user": {
      "id": 1,
      "username": "test@example.com"
    },
    "context": {
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
      "2fa_used": false,
      "2fa_method": null
    },
    "meta": {
      "initiator": "user",
      "reason": null,
      "auth_flow_id": null
    }
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security_rezilio",
  "datetime": "2026-05-17T22:42:14.034089+02:00",
  "extra": {}
}
```

### 3.2 Connexion réussie (avant challenge 2FA)

```json
{
  "message": "auth_event",
  "context": {
    "event_type": "auth.login.success",
    "user": {
      "id": 1,
      "username": "test@example.com"
    },
    "context": {
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
      "2fa_used": false,
      "2fa_method": null
    },
    "meta": {
      "initiator": "user",
      "reason": null,
      "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c"
    }
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security_rezilio",
  "datetime": "2026-05-17T22:42:37.795513+02:00",
  "extra": {}
}
```

### 3.3 Challenge 2FA réussi

```json
{
  "message": "auth_event",
  "context": {
    "event_type": "auth.2fa.challenge.success",
    "user": {
      "id": 1,
      "username": "test@example.com"
    },
    "context": {
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
      "2fa_used": true,
      "2fa_method": "totp",
      "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c"
    },
    "meta": {
      "initiator": "user"
    }
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security_rezilio",
  "datetime": "2026-05-17T22:42:59.510245+02:00",
  "extra": {}
}
```

### 3.4 Challenge 2FA réussi – fin du flow

```json
{
  "message": "auth_event",
  "context": {
    "event_type": "auth.2fa.challenge.success",
    "user": {
      "id": 1,
      "username": "test@example.com"
    },
    "context": {
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
      "2fa_used": true,
      "2fa_method": "totp",
      "auth_flow_id": "71d3a2c1-6254-4a83-b723-b1731b6cbc6c"
    },
    "meta": {
      "initiator": "user",
      "reason": "two_factor_flow_complete"
    }
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security_rezilio",
  "datetime": "2026-05-17T22:42:59.513193+02:00",
  "extra": {}
}
```

### 3.5 Connexion via cookie « remember me »

```json
{
  "message": "auth_event",
  "context": {
    "event_type": "auth.login.remembered",
    "user": {
      "id": 1,
      "username": "test@example.com"
    },
    "context": {
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
      "2fa_used": false,
      "2fa_method": null
    },
    "meta": {
      "initiator": "system",
      "reason": "remember_me_cookie"
    }
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security_rezilio",
  "datetime": "2026-05-17T22:42:59.539267+02:00",
  "extra": {}
}
```

## 4. Corrélation et exploitation dans un SIEM

- Utiliser `context.event_type` pour filtrer par type d’événement.  
- Utiliser `context.meta.auth_flow_id` pour regrouper toutes les étapes d’un même flow d’authentification (login + 2FA).  
- Utiliser `context.context.2fa_used` et `2fa_method` pour suivre l’usage effectif de la double authentification.  
- Surveiller les séquences de `auth.login.failure` et `auth.2fa.challenge.failure` pour détecter des tentatives d’attaque ou des erreurs utilisateur.

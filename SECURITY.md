# Security Policy

## Supported Versions

Les versions ci-dessous reçoivent des correctifs de sécurité.

| Version | Support sécurité     |
|-------- |----------------------|
| 1.x     | ✅                    |
| 0.x     | ❌                    |

Seule la dernière version mineure de chaque branche majeure encore supportée peut recevoir des correctifs de sécurité.  
Nous recommandons fortement de mettre à jour vers la dernière version stable avant de déployer en production.

## Reporting a Vulnerability

Si vous pensez avoir identifié une vulnérabilité de sécurité dans Rezilio, **merci de ne pas créer d’issue publique** sur GitHub.  
Veuillez suivre le processus de divulgation responsable ci‑dessous.

### Canal de contact

Merci de nous contacter via l’un des canaux suivants :

- Par email : **security at rezilio.fr**  

### Informations à fournir

Pour nous aider à analyser et corriger la vulnérabilité, merci de fournir autant d’informations que possible :

- Une description claire de la vulnérabilité.
- Les étapes détaillées pour la reproduire.
- La version de Rezilio concernée (tag, branche, ou commit).
- La configuration pertinente (options, modules activés, environnement).
- Le cas échéant, un PoC (Proof of Concept), captures d’écran ou extraits de logs.

### Délais et suivi

- Nous accusons réception de votre signalement en général sous **3 jours ouvrés**.
- Une première analyse est réalisée dans un délai cible de **10 jours ouvrés**.
- Si la vulnérabilité est confirmée, nous travaillons sur un correctif et/ou une mesure de mitigation et nous vous tenons informé :
  - de la **priorité** (gravité estimée),
  - du **planning** de correction,
  - et de la **date cible de publication** du correctif ou de l’avis de sécurité. [web:568]

Lorsque le correctif est prêt, nous pouvons publier :
- une nouvelle version de Rezilio incluant le correctif,
- et, si nécessaire, un avis de sécurité public (security advisory GitHub) décrivant la vulnérabilité, son impact et la façon de mettre à jour. [web:568][web:576]

### Divulgation responsable

Nous vous demandons de :
- ne pas exploiter la vulnérabilité au‑delà de ce qui est strictement nécessaire pour la démonstration,
- ne pas divulguer publiquement les détails tant qu’un correctif n’est pas disponible ou qu’une date de divulgation coordonnée n’a pas été convenue,
- respecter la confidentialité des données auxquelles vous pourriez avoir accès dans le cadre du test.

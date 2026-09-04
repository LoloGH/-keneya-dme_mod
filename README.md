# Keneya DME App

Application autonome de démonstration pour la Phase 1 de Keneya-DME, un dossier médical électronique destiné à devenir ultérieurement un module de Keneya Workflow. Toutes les données sont fictives.

## État actuel — v0.1.0

Le dépôt reçu était vide. Cette première itération fournit une interface responsive et fonctionnelle côté navigateur : connexion de démonstration, tableau de bord, liste/recherche/création de patients, dossier patient, consultations et constantes, alertes d’allergies/conditions, rendez-vous, journal d’audit et navigation pour les autres domaines DME. Les données sont conservées dans le `localStorage` du navigateur pour permettre une démonstration immédiate.

## Démarrer

```bash
npm start
```

Puis ouvrir l’adresse affichée par le serveur. Compte de démonstration : `admin@keneya.test` avec n’importe quel mot de passe non vide.

```bash
npm test
```

## Décision d’architecture

L’environnement ne contient ni PHP, ni Composer, ni Laravel et aucun dépôt initial n’a été fourni. L’interface est donc volontairement indépendante des dépendances et sert de socle UX. Pour rendre la solution médicale exploitable en production, la prochaine étape est d’installer PHP 8.4+/Composer et de créer le backend Laravel 12 : authentification, RBAC, policies, migrations, stockage privé, API, queue SMS et génération PDF. Aucun stockage `localStorage` ne doit être utilisé pour des données médicales réelles.

## Périmètre futur

Le projet demeure autonome : aucune intégration avec Keneya Workflow ou `keneya-dme_modul` n’est réalisée.

# Keneya DME App

Application autonome de démonstration pour la Phase 1 de Keneya-DME, un dossier médical électronique destiné à devenir ultérieurement un module de Keneya Workflow. Toutes les données sont fictives.

## État actuel — v0.1.0

Le dépôt reçu était vide. La version actuelle est une SPA React + Vite + TypeScript : connexion multi-rôles simulée, tableau de bord, liste/recherche/création de patients, dossier patient à onglets avec données fictives, parcours DME, audit et simulation SMS. Les mocks sont isolés dans une couche de services afin de pouvoir les remplacer ultérieurement par une API Laravel.

## Démarrer

```bash
npm install
npm run dev
```

Puis ouvrir l’adresse affichée par le serveur. Compte de démonstration : `admin@keneya.test` avec n’importe quel mot de passe non vide.

```bash
npm run build
```

## Décision d’architecture

L’environnement ne contient ni PHP, ni Composer, ni Laravel et aucun dépôt initial n’a été fourni. L’interface est donc volontairement indépendante du backend et sert de socle UX. Pour rendre la solution médicale exploitable en production, la prochaine étape est d’installer PHP 8.4+/Composer et de créer le backend Laravel 12 : authentification, RBAC, policies, migrations, stockage privé, API, queue SMS et génération PDF. Les mocks ne doivent jamais être remplacés par un stockage navigateur pour des données médicales réelles.

## Périmètre futur

Le projet demeure autonome : aucune intégration avec Keneya Workflow ou `keneya-dme_modul` n’est réalisée.

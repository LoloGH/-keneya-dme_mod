# Keneya-DME — module

**Module Laravel de Dossier Médical Électronique, destiné à être monté dans une
application hôte** (Keneya Workflow). C'est la **phase 2** du développement en
trois temps commencé avec `keneya-dme_app` :

```text
keneya-dme_app          →   keneya-dme_mod          →   keneya_workflow
(application autonome)      (module Laravel)            (plateforme Keneya)
        phase 1                  phase 2                    phase 3
```

Le module apporte l'intégralité du dossier médical de la phase 1 — patients,
consultations, prescriptions, laboratoire, imagerie, hospitalisation, soins
infirmiers, soins programmés, rendez-vous, documents, notifications, audit — mais
il ne se comporte plus comme une application : il **s'installe chez un hôte**,
et lui délègue trois choses qui ne sont pas de son ressort.

> **Toutes les données de ce dépôt sont fictives.** Aucune donnée médicale réelle
> n'y figure et aucune ne doit y être introduite.

---

## Sommaire

- [Ce que le module délègue à l'hôte](#ce-que-le-module-délègue-à-lhôte)
- [Installation dans une application hôte](#installation-dans-une-application-hôte)
- [Câblage minimal côté hôte](#câblage-minimal-côté-hôte)
- [Liaison des patients](#liaison-des-patients)
- [Envoi de SMS](#envoi-de-sms)
- [Contrôle d'accès](#contrôle-daccès)
- [Mode autonome de développement](#mode-autonome-de-développement)
- [Développer et tester le module seul](#développer-et-tester-le-module-seul)
- [Ressources front](#ressources-front)
- [Ce qui reste à faire côté Keneya Workflow](#ce-qui-reste-à-faire-côté-keneya-workflow)

---

## Ce que le module délègue à l'hôte

| Responsabilité | Avant (phase 1) | Maintenant |
|---|---|---|
| **Authentification** | Page de connexion et session propres | La session vient de l'hôte. Plus de page de connexion en fonctionnement normal. |
| **Droit d'entrer dans le DME** | Le module décidait seul | L'hôte accorde l'accès ; le module le vérifie et **refuse par défaut**. |
| **Envoi de SMS** | Pipeline interne appelé directement | Le code métier ne connaît que `SmsDispatcherContract`. |
| **Identité du patient** | Créé dans le DME | Reçu déjà identifié, rapproché par `patient_identifiers`. |

Ce qui **reste** au module : ses permissions internes (`Keneya\Dme\Support\Rbac` —
qui peut créer une ordonnance, valider un résultat, voir le laboratoire…), son
annuaire de praticiens, ses écrans, ses données cliniques et son journal d'audit.

---

## Installation dans une application hôte

Le module s'installe comme n'importe quel package Composer. En attendant une
publication sur Packagist, on le référence par un dépôt de type `path` :

```json
{
    "repositories": [
        { "type": "path", "url": "../keneya-dme_mod" }
    ],
    "require": {
        "keneya/dme": "*"
    }
}
```

Puis, dans l'application hôte :

```bash
composer require keneya/dme
php artisan vendor:publish --tag=dme-config
php artisan vendor:publish --tag=dme-assets --force
php artisan migrate
php artisan db:seed --class="Keneya\Dme\Database\Seeders\DmeSeeder"
```

Le fournisseur de services est découvert automatiquement. Il enregistre :

- les routes web sous `/dme` et les routes API sous `/dme/api`, toutes nommées
  `dme.*` — aucune collision possible avec celles de l'hôte ;
- les migrations du module, **additives et prudentes** : si l'hôte possède déjà
  une table `users`, `roles` ou `personal_access_tokens`, le module l'enrichit
  au lieu de la recréer ;
- les vues et traductions sous l'espace de noms `dme::` ;
- ses commandes, ses tâches planifiées et ses limiteurs de débit
  (`dme-login`, `dme-api`), sous des noms qui lui sont propres.

`--force` sur les assets est normal : ce sont des fichiers compilés, republiés à
chaque montée de version du module.

---

## Câblage minimal côté hôte

Tout ce qu'une application hôte a réellement à écrire tient en quelques lignes.
L'application de test du dépôt en donne l'exemple complet
([`workbench/app/Providers/WorkbenchServiceProvider.php`](workbench/app/Providers/WorkbenchServiceProvider.php)) :

```php
use Keneya\Dme\Dme;
use Keneya\Dme\Contracts\SmsDispatcherContract;

public function boot(): void
{
    // 1. Qui a le droit d'ouvrir un dossier médical ? C'est l'hôte qui tranche.
    Dme::authorizeAccessUsing(
        fn ($user) => $user->hasPermissionTo('dossier-medical.acceder')
    );

    // 2. Comment part un SMS ? L'hôte fournit son implémentation.
    $this->app->singleton(SmsDispatcherContract::class, WorkflowSmsDispatcher::class);
}
```

---

## Liaison des patients

Le module reçoit un patient **déjà identifié par l'hôte**. Le rapprochement passe
par la table `patient_identifiers`, contrainte en unicité sur `(system, value)`,
et par `Keneya\Dme\Patients\PatientIdentifierResolver` :

```php
use Keneya\Dme\Patients\PatientIdentifierResolver;

$patient = app(PatientIdentifierResolver::class)->resolve(
    system: 'keneya_workflow',
    value: $patientDeLHote->uuid,
    attributes: [
        'last_name'  => $patientDeLHote->nom,
        'first_name' => $patientDeLHote->prenom,
        'sex'        => $patientDeLHote->genre,   // M/F/homme/femme/male/female…
        'age'        => $patientDeLHote->age,     // ou 'birth_date'
        'phone'      => $patientDeLHote->telephone,
    ],
);

return redirect()->route('dme.patients.show', $patient);
```

Trois garanties, couvertes par les tests :

- un identifiant déjà connu retourne le patient existant **sans jamais réécrire
  son dossier** avec les données de l'hôte ;
- un identifiant inconnu crée le patient et l'y rattache dans la même
  transaction ;
- deux appels identiques ne produisent qu'un seul patient.

Un âge sans date de naissance produit une date **explicitement marquée comme
estimée** : une approximation ne doit jamais passer pour de l'état civil.

---

## Envoi de SMS

Le code métier ne dépend que du contrat :

```php
namespace Keneya\Dme\Contracts;

interface SmsDispatcherContract
{
    public function dispatch(string $to, string $message, ?string $context = null): void;
}
```

Deux implémentations de repli sont fournies, le temps que le module vive seul :

| `dme.sms.dispatcher` | Classe | Comportement |
|---|---|---|
| `queued` (défaut) | `Sms\Pipeline\QueuedSmsDispatcher` | File d'attente interne : persistance, passerelle, historique, réessais. |
| `log` | `Sms\LogSmsDispatcher` | Journalise, n'émet rien. |

Tout le pipeline interne — file, passerelles SMSGate/log/array, écran
d'historique, commandes de suivi — vit sous `src/Sms/Pipeline` et **pourra être
supprimé d'un bloc** le jour où l'hôte fournira son implémentation. L'écran SMS
et les tâches planifiées associées disparaissent alors d'eux-mêmes. Un test
structurel ([`tests/Unit/SmsDependencyBoundaryTest.php`](tests/Unit/SmsDependencyBoundaryTest.php))
vérifie qu'aucune classe métier ne franchit cette frontière.

Le paramètre `$context` transporte, sous forme de chaîne, le patient, l'acte
déclencheur et l'heure d'envoi souhaitée (`Keneya\Dme\Sms\SmsContext`). Une
implémentation d'hôte peut l'ignorer sans rien casser.

---

## Contrôle d'accès

L'intergiciel `dme.access` s'applique à **toutes** les routes web du module,
avant toute vérification de permission interne. Il accepte trois formes
d'autorisation, examinées dans cet ordre :

1. le résolveur déclaré par l'hôte — `Dme::authorizeAccessUsing(...)` ;
2. une capacité (Gate ou permission) — `dme.access.ability`, par défaut `dme.access` ;
3. un attribut booléen porté par l'utilisateur — `dme.access.attribute`, par
   défaut `can_access_dme`.

**En l'absence de toute forme d'accord, l'accès est refusé.** Un administrateur
du DME, avec toutes les permissions internes, reste dehors si l'hôte ne l'a pas
laissé entrer. Chaque refus est tracé dans le journal d'audit.

Une requête anonyme n'est pas refusée par cet intergiciel : elle est laissée à
`auth`, qui la renvoie vers l'authentification de l'hôte.

---

## Mode autonome de développement

Le module reste navigable sans hôte, pour le développement et la démonstration.
Il faut le demander explicitement :

```dotenv
DME_STANDALONE_DEV=true
```

Il ouvre alors une page de connexion locale (`/dme/connexion`), accorde
l'autorisation d'accès et active les garde-fous de développement. `DME_STANDALONE_AUTO_LOGIN=true`
ouvre en plus la session du praticien de développement sans passer par le
formulaire.

**Ce mode est inactif par défaut et refuse de s'activer en production**, quelle
que soit la valeur de la variable : la demande est journalisée et ignorée. C'est
ce qui rend impossible une authentification maison sur une installation réelle.

Le praticien de développement est créé par un seeder dédié, qui refuse lui aussi
de s'exécuter hors de ce mode :

```bash
php artisan db:seed --class="Keneya\Dme\Database\Seeders\StandaloneDevSeeder"
```

Son mot de passe vient de `DME_STANDALONE_USER_PASSWORD` ; à défaut, il est
généré aléatoirement et affiché une seule fois. Aucun mot de passe par défaut
n'existe dans ce dépôt.

---

## Développer et tester le module seul

Le dépôt embarque une application Laravel hôte minimale, fournie par
[Orchestra Testbench](https://packages.tools/testbench) : c'est elle qui sert à
vérifier que le module se monte, et à le parcourir sans Keneya Workflow.

```bash
composer install
composer build      # crée la base SQLite, migre, publie les assets
composer serve      # http://127.0.0.1:8000
```

L'accueil de l'hôte de test affiche l'état du montage (préfixe, mode autonome,
implémentation SMS active) et un lien vers le dossier médical. Pour disposer de
données :

```bash
php vendor/bin/testbench db:seed --class="Workbench\Database\Seeders\DatabaseSeeder"
```

La suite de tests s'exécute dans cette même application hôte — donc dans la
situation réelle du module, et non dans l'application autonome de la phase 1 :

```bash
composer test
```

Par défaut, les tests tournent **mode autonome désactivé** : l'autorisation
d'accès y est accordée comme le ferait un vrai hôte, et les tests qui vérifient
le refus la retirent.

---

## Ressources front

Une application hôte n'a aucune raison de compiler les feuilles de style du
module. Elles sont donc construites ici, sous des noms stables, versionnées dans
`public/build/`, et copiées chez l'hôte par `vendor:publish --tag=dme-assets` :

```bash
npm install
npm run build
```

Les vues y accèdent par `\Keneya\Dme\Dme::asset('build/app.css')`, qui ajoute la
version du module à l'URL pour invalider le cache du navigateur.

---

## Ce qui reste à faire côté Keneya Workflow

Cette phase s'arrête volontairement à la frontière du module. Restent à faire
dans `keneya_workflow`, en phase 3 :

- déclarer la capacité d'accès au dossier médical dans son RBAC et la brancher
  sur `Dme::authorizeAccessUsing()` ;
- ajouter le point d'entrée « Mes patients » qui appelle
  `PatientIdentifierResolver` puis redirige vers `dme.patients.show` ;
- lier `SmsDispatcherContract` à son propre `SendSmsJob`, ce qui retirera du
  module son pipeline SMS interne.

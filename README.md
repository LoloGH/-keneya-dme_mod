# Keneya-DME App

**Keneya-DME App est la première implémentation autonome du futur module DME de Keneya Workflow.**

Application de **Dossier Médical Électronique** complète et autonome : gestion des patients,
consultations, prescriptions, laboratoire, imagerie, hospitalisation, soins infirmiers,
rendez-vous, documents, historique, audit, notifications et SMS.

> **Toutes les données de ce dépôt sont fictives.** Aucune donnée médicale réelle n'y figure
> et aucune ne doit y être introduite.

Déployable en installation native (Linux et Windows), derrière Apache ou Nginx, ou via
Docker. **Docker est une option, pas une dépendance.**

---

## Sommaire

- [Objectifs](#objectifs)
- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
- [Stack technique](#stack-technique)
- [Déploiement](#déploiement)
- [Installation](#installation)
- [Configuration](#configuration)
- [Base de données et seeders](#base-de-données-et-seeders)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Sécurité](#sécurité)
- [Service SMS](#service-sms)
- [API REST](#api-rest)
- [Documents et PDF](#documents-et-pdf)
- [Tests](#tests)
- [Feuille de route](#feuille-de-route)
- [Phase 2 — transformation en module](#phase-2--transformation-en-module)
- [Phase 3 — intégration à Keneya Workflow](#phase-3--intégration-à-keneya-workflow)
- [Limites connues](#limites-connues)

---

## Objectifs

Cette application constitue la **phase 1** d'un développement en trois temps :

```text
keneya-dme_app          →   keneya-dme_modul        →   keneya_workflow
(application autonome)      (module Laravel)            (plateforme Keneya)
```

L'objectif de cette phase est de disposer d'un DME **réellement utilisable et démontrable
indépendamment de Keneya Workflow**, afin de valider son ergonomie, son architecture, ses
fonctionnalités et sa valeur métier avant toute intégration.

**Aucune intégration avec `keneya_workflow` n'est réalisée à ce stade** — c'est délibéré
(§60 de la spécification).

---

## Fonctionnalités

### Dossier médical électronique

Le DME est le cœur de l'application. Chaque dossier patient s'articule autour de
**14 onglets** : Résumé, Consultations, Antécédents, Allergies, Médicaments, Ordonnances,
Laboratoire, Imagerie, Hospitalisations, Soins, Rendez-vous, Documents, Historique, Audit.

Un bandeau d'identité et les **alertes cliniques permanentes** (allergies sévères,
pathologies chroniques actives) restent visibles sur tous les onglets.

### Parcours couvert

| Domaine | Contenu |
|---|---|
| **Patients** | Identité, coordonnées, contact d'urgence, groupe sanguin, médecin traitant, recherche multicritère, export CSV |
| **Antécédents** | Personnels, chirurgicaux, familiaux, gynéco-obstétriques, facteurs de risque |
| **Allergies** | Allergène, réaction, gravité (faible/modérée/sévère/inconnue), statut |
| **Médicaments** | Traitements habituels : dosage, fréquence, voie, période, statut |
| **Consultations** | Motif, anamnèse, constantes, examen clinique par appareil, diagnostics CIM-10, plan de soins |
| **Constantes** | TA, pouls, température, SpO₂, poids, taille, IMC (calculé), glycémie + courbes d'évolution |
| **Ordonnances** | Constructeur multi-lignes, **contrôle d'allergie**, validation, délivrance, PDF avec QR code |
| **Laboratoire** | Demandes, catalogue d'examens, saisie et validation des résultats, valeurs de référence |
| **Imagerie** | 6 modalités, indication, compte rendu structuré, numéro d'accession (accroche DICOM) |
| **Hospitalisation** | Admission, timeline de séjour, sortie, compte rendu PDF |
| **Soins infirmiers** | Soins, administrations, observations, incidents, transmissions horodatés |
| **Rendez-vous** | Calendrier jour / semaine / mois, statuts, confirmation et rappel SMS |
| **Documents** | Import, versionnement, aperçu PDF intégré, téléchargement contrôlé |
| **Historique** | Timeline chronologique unifiée, filtrable par type, à chargement progressif |
| **Audit** | Journal append-only : utilisateur, rôle, action, patient, date, IP, résultat |
| **Notifications** | Centre de notifications par catégorie et criticité |
| **SMS** | Service transversal : modèles, file d'attente, statuts, erreurs, réessais, historique |

---

## Architecture

### Principe directeur

L'application est autonome, mais son architecture est conçue **dès maintenant** pour
faciliter sa transformation future en module Laravel (§3) — sans pour autant sur-architecturer
cette première version.

```text
app/
├── Models/              Modèles Eloquent + traits partagés (Concerns/)
├── Policies/            Autorisations, sur un socle commun DomainPolicy
├── Services/
│   ├── Identifiers/     Génération des identifiants métier stables
│   ├── Patients/        Recherche globale, timeline médicale
│   ├── Prescriptions/   Contrôle d'allergie
│   ├── Documents/       Stockage privé, génération PDF, QR codes
│   ├── Notifications/   Pont entre événements métier et notifications/SMS
│   └── Sms/             ← Service SMS transversal, extractible tel quel
├── Http/
│   ├── Controllers/Web/ Interface
│   ├── Controllers/Api/ API REST
│   ├── Requests/        Validation
│   ├── Resources/       Représentations API (structure FHIR)
│   └── Middleware/      Traçabilité des accès aux dossiers
├── Jobs/                Envoi SMS asynchrone
└── Support/Rbac.php     Référentiel unique des rôles et permissions
```

### Décisions structurantes

**Séparation logique métier / interface.** Les contrôleurs orchestrent, les services portent
la logique réutilisable. Le contrôle d'allergie, la génération d'identifiants ou l'envoi de
SMS ne dépendent d'aucun contrôleur et sont testés isolément.

**Service SMS découplé.** `App\Services\Sms` ne référence **aucun** modèle du domaine médical.
Il reçoit un numéro, un texte et un contexte scalaire. `NotificationService` est la seule
couche qui connaît à la fois le métier et le SMS — c'est donc la seule à réécrire en phase 2.

**Référentiel RBAC unique.** `App\Support\Rbac` est la source de vérité des rôles et
permissions ; seeder, policies, écran d'administration et tests le consomment.

**Identifiants stables.** `PAT-2026-000001`, `CONS-…`, `ORD-…`, `LAB-…`, `IMG-…`, `HOSP-…`,
`DOC-…`, `RDV-…` sont générés sous transaction avec verrou pessimiste, jamais réattribués,
et servent de clé de correspondance pour une future intégration FHIR/HL7.

**Historisation.** Aucune donnée médicale importante n'est écrasée : les constantes créent
un nouveau relevé daté et signé, les documents sont versionnés, les consultations terminées
deviennent non modifiables, le journal d'audit est en écriture seule.

---

## Stack technique

| Composant | Version | Rôle |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel | 12 | Framework |
| Blade + Alpine.js | 3 | Interface rendue côté serveur, interactivité légère |
| Tailwind CSS | 4 | Système de design |
| Vite | 7 | Build des assets |
| MySQL 8.4 / PostgreSQL 16 / SQLite | — | Migrations, seeders et suite de tests vérifiés sur les trois |
| spatie/laravel-permission | 6 | Rôles et permissions |
| barryvdh/laravel-dompdf | 3 | Génération PDF |
| bacon/bacon-qr-code | 3 | QR codes de vérification |
| laravel/sanctum | 4 | Jetons d'API |
| SMSGate | API v1 | Passerelle SMS de production |

> **Note d'architecture.** La spécification mentionnait Livewire comme piste possible.
> L'interface est finalement rendue côté serveur en Blade, avec Alpine.js pour les
> comportements locaux (tiroirs, listes dynamiques, anti-rebond). Ce choix évite une couche
> supplémentaire sans rien retirer aux exigences d'ergonomie, garde chaque écran testable
> par une simple requête HTTP, et n'empêche pas d'introduire Livewire ultérieurement sur un
> écran donné.

---

## Déploiement

Sept configurations sont supportées et documentées dans
**[docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md)** :

| Plateforme | Serveur | Documentation |
|---|---|---|
| Linux | natif (`php artisan serve`) | [§B](docs/DEPLOIEMENT.md#b--installation-linux-native) |
| Linux | Apache + PHP-FPM | [§D](docs/DEPLOIEMENT.md#d--apache) |
| Linux | Nginx + PHP-FPM | [§E](docs/DEPLOIEMENT.md#e--nginx) |
| Linux | Docker | [§F](docs/DEPLOIEMENT.md#f--docker) |
| Windows | natif | [§C](docs/DEPLOIEMENT.md#c--installation-windows) |
| Windows | Apache (XAMPP, Laragon) | [§C.4](docs/DEPLOIEMENT.md#c4--apache-sous-windows) |
| Windows | Docker Desktop | [§F.6](docs/DEPLOIEMENT.md#f6--docker-desktop-windows) |

Fichiers fournis :

```text
deploy/apache/keneya-dme.conf      VirtualHost Apache durci
deploy/nginx/keneya-dme.conf       server block Nginx durci
deploy/systemd/*.service           worker de file et planificateur
docker-compose.yml                 stack Nginx + PHP-FPM + MySQL + worker + scheduler
Dockerfile                         image multi-étapes (Composer, Vite, PHP-FPM, Nginx)
scripts/install.sh | install.ps1   installation Linux | Windows
scripts/update.sh  | update.ps1    mise à jour Linux | Windows
```

### Démarrage rapide

**Linux / macOS**

```bash
git clone https://github.com/LoloGH/keneya-dme_app.git
cd keneya-dme_app
./scripts/install.sh --with-demo
php artisan serve
```

**Windows (PowerShell)**

```powershell
git clone https://github.com/LoloGH/keneya-dme_app.git
cd keneya-dme_app
.\scripts\install.ps1 -WithDemo
php artisan serve
```

**Docker**

```bash
cp .env.docker.example .env
# renseigner APP_KEY, DB_PASSWORD et DB_ROOT_PASSWORD
docker compose up -d --build     # → http://localhost:8080
```

---

## Installation

### Prérequis

- PHP **8.4+** avec les extensions `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `openssl`,
  `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`
- Composer 2
- Node.js 20+ et npm

### Mise en route

```bash
git clone https://github.com/LoloGH/keneya-dme_app.git
cd keneya-dme_app

composer install
npm install

cp .env.example .env
php artisan key:generate

# Renseignez DEMO_USER_PASSWORD dans .env avant de continuer :
# aucun mot de passe par défaut n'est fourni par l'application.

touch database/database.sqlite
php artisan migrate --seed

npm run build
php artisan serve
```

L'application est alors disponible sur <http://127.0.0.1:8000>.

En développement, `npm run dev` lance Vite en mode watch à la place de `npm run build`.

### File d'attente

Les SMS transitent par la file d'attente. En développement, la connexion `database` est
utilisée ; lancez un worker pour les traiter :

```bash
php artisan queue:work --queue=sms
```

Sans worker, les messages restent visibles à l'état « Dans la file » dans l'écran SMS —
aucun acte médical n'en est affecté.

---

## Configuration

Toute la configuration métier est centralisée et **aucune valeur n'est codée en dur** :

| Fichier | Contenu |
|---|---|
| `config/keneya.php` | Établissement, préfixes d'identifiants, pagination, documents, seuils de constantes |
| `config/sms.php` | Passerelle, file, politique de réessai, indicatif par défaut |
| `config/permission.php` | Configuration spatie/laravel-permission |

Variables d'environnement principales (voir `.env.example`) :

```dotenv
KENEYA_FACILITY_NAME="Centre Hospitalier Keneya"
KENEYA_FACILITY_ADDRESS="Bamako, Mali"

SMS_DRIVER=log          # log (défaut, aucun envoi réel) | array (tests) | http
SMS_SENDER="Keneya"
SMS_MAX_ATTEMPTS=3

DEMO_SEED_ENABLED=true
DEMO_USER_PASSWORD=     # à renseigner — aucun défaut
```

---

## Base de données et seeders

### Entités

```text
users · roles · permissions · services
patients · patient_identifiers · emergency_contacts
medical_histories · allergies · chronic_conditions · medications
consultations · vital_signs · clinical_notes · diagnoses
prescriptions · prescription_items
lab_orders · lab_order_items · lab_results
imaging_orders · imaging_reports
hospitalizations · hospitalization_events · nursing_notes
appointments · medical_documents · notifications
sms_templates · sms_messages · identifier_sequences · activity_log
```

Les index couvrent les colonnes de recherche et de jointure : `patients.patient_number`,
`patients.phone`, `patients.last_name`, `consultations.patient_id`, `consultations.doctor_id`,
`prescriptions.patient_id`, `lab_orders.patient_id`, `appointments.patient_id`,
`medical_documents.patient_id`, `activity_log.patient_id`, `sms_messages.status`, etc.

### Seeders

| Seeder | Portée | Contenu |
|---|---|---|
| `RoleAndPermissionSeeder` | Tous environnements | 7 rôles, 37 permissions |
| `ServiceSeeder` | Tous environnements | 11 services de l'établissement |
| `SmsTemplateSeeder` | Tous environnements | 4 modèles de SMS |
| `DemoUserSeeder` | **local / testing uniquement** | 8 comptes professionnels |
| `DemoMedicalDataSeeder` | **local / testing uniquement** | 9 patients fictifs et leur parcours complet |

Les deux seeders de démonstration refusent de s'exécuter hors des environnements `local` et
`testing`, et `DemoUserSeeder` échoue explicitement si `DEMO_USER_PASSWORD` n'est pas défini.

Réinitialisation complète :

```bash
php artisan migrate:fresh --seed
```

---

## Comptes de démonstration

Disponibles **uniquement en environnement local**, après avoir défini `DEMO_USER_PASSWORD`
dans votre `.env`. Le mot de passe est celui que vous avez choisi — il n'en existe aucun par
défaut dans le code.

| Rôle | Adresse e-mail | Périmètre |
|---|---|---|
| Administrateur | `admin@keneya.test` | Accès complet |
| Médecin | `medecin@keneya.test` | Parcours clinique complet |
| Médecin (cardiologie) | `cardiologue@keneya.test` | Idem, service Cardiologie |
| Infirmier | `infirmier@keneya.test` | Constantes, soins, suivi de séjour |
| Laboratoire | `laboratoire@keneya.test` | Examens et résultats |
| Radiologie | `radiologie@keneya.test` | Imagerie et comptes rendus |
| Pharmacien | `pharmacien@keneya.test` | Ordonnances (consultation et délivrance) |
| Réception | `reception@keneya.test` | Patients, rendez-vous, administratif |

---

## Sécurité

| Mesure | Mise en œuvre |
|---|---|
| Authentification | Session, régénérée à la connexion, invalidée à la déconnexion |
| Mots de passe | Hachés (bcrypt), 12 caractères minimum, complexité imposée, vérification des fuites en production |
| RBAC | 7 rôles, 37 permissions granulaires, **vérifiées côté serveur** |
| Policies | Une policy par domaine, sur un socle `DomainPolicy` commun |
| Limitation de débit | 5 tentatives/minute sur la connexion, 60 requêtes/minute sur l'API |
| CSRF | Middleware appliqué à toutes les routes web |
| XSS | Échappement Blade systématique |
| Injection SQL | Requêtes paramétrées via Eloquent — aucune interpolation de chaîne |
| Documents | Disque privé, chemin jamais exposé, route contrôlée, extraction journalisée |
| Audit | Journal append-only, modification et suppression bloquées au niveau du modèle |
| Comptes désactivés | Connexion refusée, permissions révoquées, historique préservé |
| Sessions | Chiffrées, `HttpOnly`, `SameSite=Lax` |
| TLS | À assurer par le serveur web en production |

### Points d'attention

- Un compte n'est **jamais supprimé** : il porte l'historique médical et les signatures
  d'actes. La désactivation coupe l'accès en préservant la traçabilité.
- Les SMS ne contiennent **jamais de résultat clinique** : le réseau n'est pas maîtrisé.
- Le contrôle d'allergie **avertit sans jamais bloquer** : la décision revient au prescripteur,
  qui doit confirmer explicitement et peut justifier son choix — le tout est tracé.

---

## Service SMS

```text
Événement métier → NotificationService → SmsService → File → SMSGate → Opérateur → Patient
                                                                 │
                                                          Suivi d'acheminement
```

Le service est **transversal et découplé** : `App\Services\Sms` ne référence aucun modèle
du domaine médical. Les contrôleurs n'appellent jamais une passerelle directement.
`NotificationService` est la seule couche qui connaît à la fois le métier et le SMS — donc
la seule à réécrire en phase 2.

### Passerelle de production : SMSGate

[SMSGate](https://sms-gate.app) (*SMS Gateway for Android™*) expose la même API REST en
mode cloud et en mode local :

| Mode | `SMSGATE_BASE_URL` | Intérêt |
|---|---|---|
| **Cloud** | `https://api.sms-gate.app/3rdparty/v1` | L'appareil n'est pas joignable depuis le serveur |
| **Local** | `http://<ip-appareil>:8080/3rdparty/v1` | Aucune donnée ne quitte l'établissement |

```dotenv
SMS_GATEWAY=smsgate
SMSGATE_BASE_URL=https://api.sms-gate.app/3rdparty/v1
SMSGATE_USERNAME=
SMSGATE_PASSWORD=
```

```bash
php artisan keneya:sms:check     # configuration et joignabilité, sans afficher de secret
```

### Passerelles de développement

| Pilote | Comportement |
|---|---|
| `log` | **Défaut.** Aucun envoi réel ; l'interface affiche un bandeau d'avertissement |
| `array` | Inerte, utilisé par la suite de tests |

Ajouter un opérateur revient à implémenter `App\Services\Sms\SmsGateway` et à déclarer sa
configuration — aucun code métier n'est à modifier.

### Un message n'est jamais annoncé comme envoyé sans confirmation

```text
pending → queued → accepted → sent → delivered
                       │        │
                       └────────┴──→ failed
```

SMSGate répond d'abord `Pending` : le message n'a pas encore atteint le téléphone. L'état
`accepted` traduit fidèlement cette réalité, et **n'est jamais promu en « envoyé »**. La
progression est assurée par le planificateur :

```bash
php artisan keneya:sms:refresh   # planifié toutes les 5 minutes
```

### Sécurité

- Les identifiants ne figurent que dans `.env` — jamais dans le dépôt, les journaux ou l'interface.
- Les messages d'erreur sont expurgés : un secret apparaissant dans une URL devient `***`.
- Aucun résultat clinique n'est transmis par SMS : le réseau mobile n'est pas maîtrisé.

### Modèles fournis

`appointment_scheduled` · `appointment_reminder` · `lab_result_available` · `prescription_ready`

---

## API REST

Authentification par jeton (Sanctum). **Un jeton n'accorde jamais plus que le rôle de son
porteur** : les policies sont les mêmes que pour l'interface web.

```http
POST   /api/auth/token                        # obtenir un jeton
GET    /api/auth/me                           # profil, rôles et permissions
DELETE /api/auth/token                        # révoquer le jeton courant

GET    /api/patients
POST   /api/patients
GET    /api/patients/{id}
PUT    /api/patients/{id}

GET    /api/patients/{id}/consultations
POST   /api/patients/{id}/consultations

GET    /api/patients/{id}/prescriptions
POST   /api/patients/{id}/prescriptions       # contrôle d'allergie appliqué

GET    /api/patients/{id}/laboratory
POST   /api/laboratory/orders

GET    /api/patients/{id}/documents
POST   /api/documents
```

Exemple :

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/auth/token \
  -H 'Accept: application/json' \
  -d 'email=medecin@keneya.test&password=VOTRE_MOT_DE_PASSE&device_name=cli' \
  | php -r 'echo json_decode(file_get_contents("php://stdin"))->token;')

curl -s http://127.0.0.1:8000/api/patients \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

### Préparation FHIR

Les représentations suivent la structure des ressources FHIR sans prétendre à la conformité
complète (§44) :

| Modèle Keneya-DME | Ressource FHIR visée |
|---|---|
| Patient | `Patient` |
| Consultation | `Encounter` |
| Diagnosis | `Condition` |
| Allergy | `AllergyIntolerance` |
| Medication | `MedicationStatement` |
| Prescription | `MedicationRequest` |
| VitalSign | `Observation` |
| LabOrder / LabResult | `ServiceRequest` / `DiagnosticReport` |
| ImagingOrder | `ImagingStudy` |
| Appointment | `Appointment` |
| MedicalDocument | `DocumentReference` |
| User | `Practitioner` |
| Service | `Organization` |

---

## Documents et PDF

Six documents sont générés : **ordonnance**, **compte rendu de consultation**,
**compte rendu de laboratoire**, **compte rendu d'hospitalisation**, **fiche patient**
et **certificat médical**.

Chacun porte l'établissement, le patient, la date, l'auteur, sa référence métier, un
**QR code de vérification** et un emplacement de signature.

Un PDF généré peut être archivé au dossier du patient : il devient alors un document
soumis aux mêmes règles d'accès que les fichiers importés.

---

## Tests

```bash
php artisan test                      # suite complète
php artisan test --testsuite=Unit     # tests unitaires
php artisan test --filter=Security    # tests de sécurité
php artisan test --filter=SmsGate     # passerelle SMSGate
```

> Utiliser `php artisan test` **sans** `--env=testing` : ce drapeau réactive la protection
> CSRF que le harnais contourne normalement, et fait échouer les envois de formulaire.

**154 tests, 364 assertions**, vérifiés sur SQLite, MySQL 8.4 et PostgreSQL 16.

| Suite | Couverture |
|---|---|
| `Unit/PatientTest` | Identifiants, âge, recherche, alertes cliniques |
| `Unit/VitalSignTest` | Calcul de l'IMC, seuils, historisation |
| `Unit/AllergyCheckerTest` | Correspondances directes et par famille, absence de faux positifs |
| `Unit/IdentifierGeneratorTest` | Format, incrément, cloisonnement, stabilité |
| `Feature/PatientJourneyTest` | **Parcours complet §67**, de la création du patient au SMS |
| `Feature/AuthorizationTest` | Matrice d'accès des 7 rôles sur tous les écrans |
| `Feature/SecurityTest` | Accès non autorisé, IDOR, CSRF, XSS, injection SQL, documents, élévation de privilège, audit append-only |
| `Feature/SmsServiceTest` | Normalisation, file, statuts, réessais, découplage |
| `Feature/ApiTest` | Jetons, structure FHIR, policies, contrôle d'allergie via API |
| `Feature/SmsGateGatewayTest` | Contrat REST SMSGate, états d'acheminement, non-fuite des secrets |
| `Feature/SmokeTest` | Rendu réel de tous les écrans et des 14 onglets du DME |

Le mode strict d'Eloquent (`preventLazyLoading`) est actif hors production : toute requête
N+1 fait échouer les tests plutôt que de dégrader la production.

---

## Feuille de route

```text
PHASE 1 — Keneya-DME App          ← vous êtes ici (v0.1.0)
   Validation fonctionnelle
   Validation UI/UX
   Validation sécurité
   Validation métier
        ↓
PHASE 2 — Keneya-DME Modul
   Encapsulation Laravel
   Adaptation des dépendances
   Interfaces d'intégration
        ↓
PHASE 3 — Keneya Workflow (kw_v3.3.0)
   Intégration DME
   Patient unique · RBAC partagé · SMS partagé
```

### Versions

- **v0.1.0** — socle fonctionnel complet (version actuelle)
- v0.2.0 — référentiel CIM-10 embarqué, recherche de diagnostic assistée
- v0.3.0 — tableaux de bord par service, exports statistiques
- v1.0.0 — première application fonctionnelle validée en conditions réelles

---

## Phase 2 — transformation en module

L'architecture actuelle prépare cette transformation :

- **Domaines séparés**, sans dépendances croisées inutiles
- **Services métier réutilisables**, indépendants des contrôleurs
- **Configuration centralisée** (`config/keneya.php`, `config/sms.php`), publiable telle quelle
- **RBAC en un point unique** (`App\Support\Rbac`), raccordable rôle à rôle
- **Service SMS extractible** sans modification
- **Identifiants stables** et modèle Patient raccordable via `patient_identifiers`

### Point d'attention sur le Patient (§62)

Keneya-DME App possède son propre modèle `Patient`. Le raccordement au patient unique de
Keneya Workflow se fera par la table **`patient_identifiers`** (`system` + `value`), sans
toucher aux données cliniques — toutes rattachées à `patients.id`. Aucun choix de cette phase
ne rend cette migration impossible.

---

## Phase 3 — intégration à Keneya Workflow

À terme, Keneya Workflow reste la plateforme centrale et le DME devient une **interface
parallèle** — pas une application séparée :

```text
https://keneya.example/            → Keneya Workflow
https://keneya.example/workflow    → Workflow
https://keneya.example/dme         → Keneya-DME
https://keneya.example/dme/patients
```

Avec une authentification, un utilisateur, un patient, des permissions centrales et des
services partagés. La future intégration devra éviter les doublons (utilisateurs,
journal d'activité, système SMS).

---

## Limites connues

Ces points sont assumés pour la phase 1 et documentés pour la suite :

1. **Contrôle d'allergie textuel.** La correspondance repose sur les noms et quelques
   familles médicamenteuses usuelles. Elle ne remplace pas une base médicamenteuse
   (Thériaque, ANSM), dont l'intégration reste à faire.
2. **CIM-10 non embarquée.** Les champs `code` / `code_system` existent et sont exploités,
   mais le référentiel complet n'est pas fourni : la saisie du code est libre.
3. **Accès aux dossiers non cloisonné par service.** Tout professionnel disposant de
   `patients.view` accède aux dossiers de l'établissement — comportement usuel d'un DME
   hospitalier. Une restriction plus fine (relation de soin établie) suppose une règle
   organisationnelle qui n'est pas du ressort de cette phase.
4. **Pas de PACS.** L'imagerie stocke les métadonnées, le compte rendu et les documents.
   `accession_number` et `study_instance_uid` sont les points d'accroche d'une future
   passerelle DICOM.
5. **HL7 non implémenté.** Conformément à la spécification (§45), aucune couche HL7
   artificielle n'a été ajoutée : le travail a porté sur des données normalisées, des
   services découplés, une API et des identifiants stables.
6. **Paramètres en lecture seule.** L'écran Paramètres expose la configuration effective ;
   sa modification passe par les fichiers de configuration et l'environnement.
7. **Image Docker non construite de bout en bout dans l'environnement de développement.**
   Les étapes Composer, Vite et Nginx sont vérifiées, la stack tourne réellement en mode
   SQLite, mais l'installation des extensions PHP (`apk`) n'a pas pu être exécutée ici :
   les dépôts de paquets système y sont filtrés. Voir le rapport de version pour le détail.

---

## Licence et usage

Projet interne Keneya. Données de démonstration exclusivement fictives.

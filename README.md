# regards singuliers - Site Web d'Architecture d'Intérieur

## 📋 Contexte du projet
Ce projet a été développé dans le cadre d'un stage professionnel pour le studio d'aménagement "regards singuliers", créé en 2019 par l'architecte d'intérieur Priscilia Leote. Le site web répond à un double besoin : présenter les réalisations de l'architecte et permettre aux clients de réserver des prestations en ligne.

## 🎯 Objectifs du projet
- Créer une vitrine professionnelle pour présenter les réalisations
- Mettre en place un système de réservation en ligne
- Faciliter la communication entre l'architecte et ses clients
- Gérer le contenu du site de manière autonome
- Assurer la confidentialité des données clients

## 📝 Fonctionnalités principales

### Espace Public
- Présentation de l'entreprise et des services
- Galerie de réalisations
- Blog d'actualités
- Formulaire de contact
- Présentation des prestations

### Espace Client
- Inscription et authentification
- Gestion du profil utilisateur
- Réservation de prestations
- Messagerie interne
- Suivi des rendez-vous

### Espace Administrateur
- Gestion du contenu (articles, réalisations)
- Gestion des prestations
- Gestion des rendez-vous
- Messagerie interne
- Gestion des utilisateurs

## 🔧 Technologies utilisées
- Symfony 6 (Framework PHP)
- MySQL (Base de données)
- HTML5 / CSS3
- JavaScript (ES6+)
- Webpack (Gestion des assets)
- Docker (Environnement de développement)

## 💡 Concepts clés abordés
- Architecture MVP
- Sécurité des données (Privacy by Design)
- Interface responsive
- Gestion des utilisateurs et des rôles
- Système de messagerie
- Calendrier de réservation
- Gestion de contenu dynamique

## 📦 Installation et configuration

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- Node.js et npm


## 🚀 Structure du projet
```
regards-singuliers/
├── assets/                   # Assets frontend
│   ├── app.js               # Point d'entrée JavaScript
│   ├── bootstrap.js         # Configuration Bootstrap
│   ├── controllers.json     # Configuration des contrôleurs
│   ├── honeypot.js          # Protection anti-spam
│   ├── admin.js             # Scripts admin
│   ├── controllers/         # Contrôleurs JavaScript
│   ├── fonts/               # Polices de caractères
│   ├── images/              # Images du projet
│   ├── styles/              # Styles CSS
│   ├── vendor/              # Bibliothèques tierces
│   └── videos/              # Vidéos
├── bin/                      # Scripts exécutables
│   └── console              # Console Symfony
├── config/                   # Configuration Symfony
│   ├── packages/            # Configuration des bundles
│   ├── routes/              # Définition des routes
│   └── services.yaml        # Configuration des services
├── migrations/              # Migrations de base de données
│   └── Version*.php        # Fichiers de migration
├── node_modules/           # Dépendances Node.js
├── public/                  # Point d'entrée public
│   ├── index.php           # Point d'entrée de l'application
│   ├── build/              # Assets compilés
│   └── uploads/            # Fichiers uploadés
├── scripts/                # Scripts personnalisés
│   └── download-fonts.sh   # Script de téléchargement des polices
├── src/                    # Code source de l'application
│   ├── Command/            # Commandes console
│   ├── Controller/         # Contrôleurs
│   ├── Entity/             # Entités Doctrine
│   ├── EventListener/      # Écouteurs d'événements
│   ├── EventSubscriber/    # Abonnés aux événements
│   ├── Form/               # Formulaires
│   ├── Repository/         # Repositories Doctrine
│   ├── Security/           # Sécurité
│   ├── Service/            # Services métier
│   └── Kernel.php          # Noyau de l'application
├── templates/             # Templates Twig
│   ├── admin/             # Templates admin
│   ├── contact/           # Templates contact
│   ├── email/             # Templates email
│   ├── home/              # Templates accueil
│   ├── legal/             # Templates légaux
│   ├── pdf/               # Templates PDF
│   ├── profile/           # Templates profil
│   ├── realisation/       # Templates réalisations
│   ├── registration/      # Templates inscription
│   ├── reservation/       # Templates réservation
│   ├── security/          # Templates sécurité
│   ├── service/           # Templates services
│   ├── studio/            # Templates studio
│   ├── auth_base.html.twig # Template de base authentifié
│   └── base.html.twig     # Template de base
├── tests/                 # Tests unitaires et fonctionnels
│   ├── Controller/        # Tests des contrôleurs
│   ├── Entity/            # Tests des entités
│   └── Service/           # Tests des services
├── translations/          # Fichiers de traduction
│   ├── messages.fr.yaml   # Traductions françaises
│   └── validators.fr.yaml # Messages de validation
├── var/                   # Fichiers temporaires et logs
│   ├── cache/            # Cache
│   └── log/              # Logs
├── vendor/               # Dépendances PHP
├── .browserslistrc       # Configuration des navigateurs cibles
├── .env                  # Variables d'environnement
├── .env.dev              # Variables d'environnement développement
├── .env.local            # Variables d'environnement locales
├── .env.test             # Variables d'environnement tests
├── .gitignore            # Fichiers ignorés par Git
├── composer.json         # Configuration Composer
├── composer.lock         # Verrouillage des versions Composer
├── compose.yaml          # Configuration Docker
├── compose.override.yaml # Surcharge de la configuration Docker
├── importmap.php         # Configuration des imports JavaScript
├── package.json          # Configuration npm
├── package-lock.json     # Verrouillage des versions npm
├── phpunit.xml.dist      # Configuration PHPUnit
├── postcss.config.js     # Configuration PostCSS
├── symfony.lock          # Verrouillage des versions Symfony
└── webpack.config.js     # Configuration Webpack
```

## 🔒 Sécurité et Confidentialité
Le projet intègre une approche Privacy by Design, garantissant :
- Protection des données personnelles
- Conformité RGPD
- Sécurisation des échanges
- Gestion sécurisée des mots de passe
- Charte de confidentialité

## 📚 Documentation
La documentation complète est disponible dans le dossier `docs/` du projet.

## 🏆 Compétences développées
- Développement d'une application web complète
- Gestion de projet
- Sécurité des données
- Interface utilisateur responsive
- Gestion de base de données
- Communication client

___
Projet réalisé dans le cadre de la formation [Développeur Web et Web Mobile](https://elan-formation.fr/formation/19754) <br>
📅 Date du stage : 03/02/2025 - 14/03/2025 <br>
✍️ Auteur : [Alexandre Leote](https://github.com/alexandreleote)

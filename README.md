# Immobilier Auxois Morvan

Site immobilier avec version publique OVH en PHP + MySQL/MariaDB.

Le workflow local utilise la même couche PHP que la mise en ligne OVH, afin de vérifier un rendu aussi proche que possible de la production.

## Stack actuelle

- Front public OVH en PHP
- MySQL / MariaDB
- Docker Compose pour reproduire le runtime PHP localement
- Auth admin email + mot de passe hashé via bcrypt
- Uploads images sur le filesystem de l'hébergement
- Éditeur riche Quill pour l'admin

## Démarrage local

Mode recommandé, identique au runtime OVH :

```bash
npm install
npm run dev
```

Cela lance MariaDB + PHP via Docker Compose, puis expose :

- site public : http://127.0.0.1:8000
- admin : http://127.0.0.1:8000/admin/login.php

Pour arrêter les conteneurs :

```bash
npm run php:stop
```

Pour repartir d'une base locale vierge :

```bash
npm run php:db:reset
```

Préparer le dossier de déploiement PHP :

```bash
npm run build
```

## Variables d'environnement

Exemple dans [ovh/.env.example](ovh/.env.example).

Variables attendues :

- APP_ENV
- APP_URL
- DB_HOST
- DB_PORT
- DB_NAME
- DB_USER
- DB_PASSWORD
- SESSION_COOKIE_NAME
- UPLOAD_DIR
- UPLOAD_PUBLIC_BASE
- INSTALL_TOKEN

## Schéma SQL

Le schéma initial du CMS est dans [db/schema.sql](db/schema.sql).

Il crée notamment :

- cms_admin_users
- cms_pages
- cms_blog_posts
- cms_media
- cms_site_settings
- cms_contact_requests

## Création des comptes admin

1. Générez un hash bcrypt pour chaque mot de passe :

```bash
npm run hash-password -- "votre-mot-de-passe"
```

2. Remplacez les placeholders dans [db/admin-users.example.sql](db/admin-users.example.sql).

3. Exécutez le SQL sur la base OVH.

Le login se fait ensuite sur /admin/login.

## Administration disponible

Routes actuellement en place :

- /admin
- /admin/login
- /admin/pages
- /admin/local-pages
- /admin/blog
- /admin/media
- /admin/settings

Modules actuellement fonctionnels :

- Connexion admin avec session serveur
- Tableau de bord privé
- Édition des pages principales
- Édition des pages locales SEO
- Édition du blog
- Réglages globaux du site
- Upload d'images JPG / PNG / WebP

## Fallback public

Le site public lit progressivement la base :

- pages principales publiées en base si elles existent
- pages locales publiées en base si elles existent
- articles de blog publiés en base si ils existent
- réglages globaux depuis la base si ils existent

Sinon, certains blocs publics complémentaires utilisent le snapshot versionné dans [data/content-snapshot.json](data/content-snapshot.json).

## Déploiement OVH

Objectif de déploiement :

- code déployé depuis GitHub
- contenu modifié directement depuis l'admin
- base de données OVH pour les contenus
- dossier uploads sur l'hébergement OVH
- aucune nécessité de redéployer pour une modification de contenu

Étapes recommandées :

1. Créer la base MySQL/MariaDB OVH.
2. Importer [db/schema.sql](db/schema.sql).
3. Créer les comptes admin avec [db/admin-users.example.sql](db/admin-users.example.sql).
4. Renseigner les variables d'environnement serveur à partir de [.env.example](.env.example).
5. Préparer le build PHP avec `npm run ovh:prepare`.
6. Uploader le contenu de `ovh-build` vers la racine web OVH.
7. Vérifier les droits d'écriture du dossier défini par UPLOAD_DIR.

## État de migration

Déjà migré :

- mode serveur
- auth admin
- pages principales
- pages locales
- blog
- réglages globaux
- uploads de base

Encore à finaliser :

- médiathèque avancée : suppression, alt, renommage
- SEO avancé : sitemap, robots, canonical, indexabilité complète
- formulaires / demandes reçues
- durcissement sécurité et documentation d'exploitation OVH plus détaillée

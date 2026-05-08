# Immobilier Auxois Morvan

Site Astro en mode serveur avec CMS maison progressif.

Le site public conserve le design et le contenu existants, avec une migration progressive vers une base MySQL/MariaDB. Tant qu'un contenu n'est pas publié en base, le site continue d'utiliser les fichiers actuels.

## Stack actuelle

- Astro 6 en mode serveur via @astrojs/node
- Tailwind CSS 4
- MySQL / MariaDB via mysql2
- Auth admin email + mot de passe hashé via bcrypt
- Uploads images sur le filesystem de l'hébergement
- Éditeur riche Quill pour l'admin

## Démarrage local

```bash
npm install
npm run dev
```

Build de production :

```bash
npm run build
```

## Variables d'environnement

Exemple dans [.env.example](.env.example).

Variables attendues :

- DB_HOST
- DB_PORT
- DB_USER
- DB_PASSWORD
- DB_NAME
- DB_SSL
- CMS_UPLOAD_DIR
- CMS_SESSION_TTL

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

Sinon, il retombe automatiquement sur les fichiers dans src/content.

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
5. Déployer le build Astro serveur sur l'hébergement Node compatible, ou sur une cible OVH adaptée.
6. Vérifier les droits d'écriture du dossier défini par CMS_UPLOAD_DIR.

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

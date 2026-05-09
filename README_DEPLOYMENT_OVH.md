# Déploiement OVH mutualisé

Ce dépôt contient désormais une seule couche applicative : le mini-CMS PHP + MySQL compatible hébergement OVH mutualisé, sans serveur Node permanent.

Le site en ligne sert directement le dossier `ovh` préparé dans `ovh-build`.

## Architecture retenue

La migration la plus fiable pour OVH mutualisé consiste à faire tourner le back-office et le front public éditable en PHP + MySQL.

Pourquoi :

- l'offre mutualisée OVH exécute PHP nativement
- elle ne nécessite aucun process Node permanent
- un CMS PHP permet des changements visibles immédiatement sans redéploiement GitHub
- les mots de passe admin restent hashés en base avec password_hash / password_verify

## Ce que fait le dossier ovh

- connexion admin email + mot de passe sur /admin/login
- sessions serveur sécurisées
- protection de toute la zone /admin
- gestion des pages principales
- gestion des pages locales
- gestion des images
- gestion des réglages globaux du site
- gestion des deux comptes admin
- rendu public PHP lisant directement MySQL

## 1. Préparer la base OVH

Dans phpMyAdmin OVH :

1. importe [db/schema.sql](db/schema.sql)
2. importe ensuite [db/ovh-seed.sql](db/ovh-seed.sql)

Cela peuple :

- les réglages de site
- les pages principales actuelles
- les pages locales actuelles

## 2. Créer les comptes admin Mickael et Marion

Option recommandée : générer les hashes en local.

```bash
npm run hash-password -- "mot-de-passe-mickael"
npm run hash-password -- "mot-de-passe-marion"
```

Remplace ensuite les placeholders dans [db/admin-users.example.sql](db/admin-users.example.sql) puis importe ce fichier dans phpMyAdmin.

Le CMS est volontairement limité à deux comptes admin au maximum.

## 3. Configurer le mini-CMS OVH

Crée un fichier ovh/.env à partir de [ovh/.env.example](ovh/.env.example) avec les vraies valeurs :

```env
APP_ENV=production
APP_URL=https://immobilier-auxois-morvan.fr
DB_HOST=immobiy296.mysql.db
DB_PORT=3306
DB_NAME=immobiy296
DB_USER=immobiy296
DB_PASSWORD=mot-de-passe-reel
SESSION_COOKIE_NAME=immobilier_auxois_admin
UPLOAD_DIR=uploads/cms
UPLOAD_PUBLIC_BASE=/uploads/cms
INSTALL_TOKEN=jeton-long-et-aleatoire
```

Ce fichier ne doit jamais être versionné.

## 4. Publier sur l'hébergement OVH

Prépare d'abord un dossier de déploiement complet :

```bash
npm run ovh:prepare
```

Cette commande génère le dossier ovh-build avec :

- le mini-CMS PHP
- les images existantes de public/uploads
- favicon.ico et favicon.svg
- les scripts SQL d'installation initiale
- le dossier uploads/cms pour les futurs médias

Upload ensuite le contenu du dossier ovh-build vers la racine web OVH.

Concrètement, côté OVH tu dois avoir :

- .htaccess
- index.php
- admin/
- app/
- assets/
- uploads/cms/
- .env

Important :

- .htaccess protège .env et app/
- uploads/cms doit être inscriptible par PHP

Pour une préproduction sans écraser l'existant, tu peux aussi uploader ovh-build dans un sous-dossier comme cms-preview, puis lancer :

- /cms-preview/admin/install.php?token=INSTALL_TOKEN

Cela crée les tables si besoin et importe les textes/pages seedés depuis le serveur OVH lui-même.

## 5. Vérifier après mise en ligne

1. ouvre /admin/login
2. connecte-toi avec Mickael ou Marion
3. modifie une page principale
4. publie la page
5. recharge la page publique correspondante
6. téléverse une image depuis /admin/media
7. colle son URL dans une page et republie
8. ajuste les informations globales du site depuis /admin/settings
9. contrôle les images historiques déjà présentes sur le site public
10. supprime ensuite admin/install.php et le dossier install si tu ne veux plus laisser l'outil d'import sur le serveur

## 6. Cycle de travail recommandé

Pour le code et le design :

1. tu travailles directement dans les templates PHP de [ovh](ovh) et les styles de [ovh/assets/site.css](ovh/assets/site.css)
2. tu testes en local avec `npm run dev`
3. tu prépares le build avec `npm run ovh:prepare`
4. tu redéploies le contenu de `ovh-build`

Pour le contenu :

1. Mickael ou Marion vont sur /admin
2. ils modifient textes, images, pages locales, SEO
3. le site public reflète immédiatement les changements via MySQL

## 7. Étape suivante recommandée

Après la première mise en ligne, la suite logique est :

1. synchroniser régulièrement la base et les uploads OVH vers le local si tu veux un contenu strictement identique
2. ajouter un export/sauvegarde automatisé de la base et des uploads pour l'exploitation OVH
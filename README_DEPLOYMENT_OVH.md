# Déploiement OVH mutualisé

Ce dépôt contient désormais deux couches distinctes :

- Astro reste la source de travail pour le design, les composants et l'évolution technique locale.
- ovh contient le mini-CMS PHP + MySQL compatible hébergement OVH mutualisé, sans serveur Node permanent.

## Architecture retenue

La migration la plus fiable pour OVH mutualisé consiste à faire tourner le back-office et le front public éditable en PHP + MySQL.

Pourquoi :

- l'offre mutualisée OVH exécute PHP nativement
- elle ne fournit pas de process Node permanent fiable pour Astro server
- un CMS PHP permet des changements visibles immédiatement sans redéploiement GitHub
- les mots de passe admin restent hashés en base avec password_hash / password_verify

## Ce que fait le dossier ovh

- connexion admin email + mot de passe sur /admin/login
- sessions serveur sécurisées
- protection de toute la zone /admin
- gestion des pages principales
- gestion des pages locales
- gestion des images
- gestion des deux comptes admin
- rendu public PHP lisant directement MySQL

## 1. Préparer la base OVH

Dans phpMyAdmin OVH :

1. importe [db/schema.sql](db/schema.sql)
2. dans le repo local, génère le seed initial depuis le contenu Astro actuel :

```bash
npm install
npm run ovh:seed
```

3. importe ensuite [db/ovh-seed.sql](db/ovh-seed.sql)

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
```

Ce fichier ne doit jamais être versionné.

## 4. Publier sur l'hébergement OVH

Upload le contenu du dossier ovh vers la racine web OVH.

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

## 5. Vérifier après mise en ligne

1. ouvre /admin/login
2. connecte-toi avec Mickael ou Marion
3. modifie une page principale
4. publie la page
5. recharge la page publique correspondante
6. téléverse une image depuis /admin/media
7. colle son URL dans une page et republie

## 6. Cycle de travail recommandé

Pour le code et le design :

1. tu continues à travailler dans Astro dans Codespaces
2. quand tu veux faire évoluer le rendu OVH, tu mets à jour les templates PHP dans ovh et les styles de [ovh/assets/site.css](ovh/assets/site.css)
3. tu redéploies uniquement le dossier ovh

Pour le contenu :

1. Mickael ou Marion vont sur /admin
2. ils modifient textes, images, pages locales, SEO
3. le site public reflète immédiatement les changements via MySQL

## 7. Limites actuelles de cette première migration

- le rendu public PHP reprend l'identité visuelle et le modèle de contenu, mais pas l'intégralité des composants Astro avancés
- le blog Astro existant n'a pas encore été rebranché sur le runtime PHP mutualisé
- les réglages globaux de site sont semés via SQL mais ne disposent pas encore d'un écran dédié dans l'admin

## 8. Étape suivante recommandée

Après la première mise en ligne, la suite logique est :

1. ajouter un écran admin de réglages globaux
2. brancher le blog sur le front PHP si tu veux l'éditer depuis le CMS aussi
3. rapprocher encore le rendu PHP de la home Astro actuelle si tu veux un match visuel presque parfait
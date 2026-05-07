# Immobilier Auxois Morvan

Site vitrine Astro pour Mickael Gury et Marion Roulier, avec contenu éditable dans le dépôt et administration Git via Sveltia CMS.

## Stack

- Astro 6
- Tailwind CSS 4
- Contenus Markdown et JSON dans src/content
- Administration statique sur /admin via Sveltia CMS

## Démarrage

```bash
npm install
npm run dev
```

Le site est alors disponible sur http://localhost:4321 et l'admin sur http://localhost:4321/admin.

Build de production :

```bash
npm run build
```

## Structure utile

- src/content/pages : pages principales
- src/content/local-pages : pages locales SEO
- src/content/blog : articles
- src/content/testimonials : avis clients
- src/content/settings/site.json : paramètres globaux
- public/uploads : médias utilisés par le site et par le CMS
- public/admin : interface Sveltia CMS

## Administration

L'administration est servie sur /admin.

Collections disponibles :

- Pages principales
- Pages locales SEO
- Articles de blog
- Avis clients
- Paramètres globaux

Les images envoyées depuis le CMS sont stockées dans public/uploads et référencées sur le site via /uploads/....

## Authentification GitHub

La configuration actuelle pointe sur le dépôt GitHub gury-mickael/immobilier-auxois-morvan avec la branche main.

Deux modes sont possibles :

1. Mode simple pour un usage technique : connexion GitHub via token personnel si aucun pont OAuth n'est configuré.
2. Mode confortable pour Marion et les éditeurs non techniques : ajouter un pont OAuth compatible Decap/Sveltia, puis compléter backend.base_url et backend.auth_endpoint dans public/admin/config.yml.

Pour un déploiement Vercel sans backend maison, le plus propre est d'utiliser un service tiers de pont OAuth compatible GitHub. Une fois ce service créé, il suffit de :

1. Garder backend.name, repo et branch.
2. Ajouter backend.base_url.
3. Ajouter backend.auth_endpoint.
4. Redéployer le site.

Après cela, la connexion depuis /admin se fait via GitHub sans demander de token manuel.

## Modifier le contenu

Depuis /admin, vous pouvez :

1. Modifier les pages principales existantes.
2. Créer ou dépublier une page locale SEO.
3. Rédiger un article de blog avec image mise en avant.
4. Ajouter un avis client.
5. Mettre à jour les coordonnées, zones couvertes et CTA globaux.

Chaque sauvegarde crée un commit Git dans le dépôt configuré.

## Déploiement

Le site reste entièrement statique. Un build Astro suffit.

Réglages attendus sur Vercel :

- Build command : npm run build
- Output directory : dist

## Notes

- L'ancienne configuration Pages CMS a été retirée au profit de Sveltia.
- Le schéma de contenu Astro reste la source de vérité applicative dans src/content.config.ts.
- Si vous changez le nom du dépôt ou la branche principale, mettez aussi à jour public/admin/config.yml.

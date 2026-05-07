# Auxois Morvan Immobilier - Site V1

Site web vitrine moderne pour Mickael Gury et Marion Roulier, conseillers immobiliers IAD en Auxois et Morvan.

**Technologie**: Astro 6 + Tailwind CSS 4

## 🚀 Démarrage rapide

### Installation
```bash
npm install
```

### Développement
```bash
npm run dev
```
Le site sera accessible à `http://localhost:4322`

### Build pour production
```bash
npm run build
```
Les fichiers statiques seront générés dans `./dist/`

### Preview du build
```bash
npm run preview
```

## 📁 Structure du projet

```
src/
├── components/          # Composants réutilisables
│   ├── Button.astro
│   ├── Header.astro
│   ├── Footer.astro
│   ├── SectionTitle.astro
│   ├── ServiceCard.astro
│   ├── CTASection.astro
│   └── TestimonialCard.astro
├── layouts/             # Layouts
│   └── Layout.astro     # Layout principal avec Header/Footer
├── pages/               # Pages du site
│   ├── index.astro      # Accueil
│   ├── vendre.astro     # Service Vendre
│   ├── acheter.astro    # Service Acheter
│   ├── estimation.astro # Service Estimation
│   ├── fonds.astro      # Fonds de commerce
│   ├── secteur.astro    # Secteur d'intervention
│   ├── contact.astro    # Contact
│   └── blog/            # Pages blog
│       ├── index.astro
│       ├── vendre-maison-auxois.astro
│       ├── estimer-bien-immobilier.astro
│       ├── acheter-morvan.astro
│       └── vendre-fonds-commerce.astro
└── styles/
    └── global.css       # Styles Tailwind

public/                 # Favicon et assets statiques
```

## 📄 Pages disponibles

| URL | Description |
|-----|-------------|
| `/` | Accueil avec hero, services, avis, blog |
| `/vendre` | Accompagnement pour vendre |
| `/acheter` | Accompagnement pour acheter |
| `/estimation` | Formulaire d'estimation |
| `/fonds` | Services fonds de commerce |
| `/secteur` | Zones couvertes |
| `/blog` | Listing articles |
| `/blog/vendre-maison-auxois` | Article blog |
| `/blog/estimer-bien-immobilier` | Article blog |
| `/blog/acheter-morvan` | Article blog |
| `/blog/vendre-fonds-commerce` | Article blog |
| `/contact` | Page contact avec formulaire |

## 🎨 Design

- **Couleur primaire**: Amber (amber-600)
- **Couleurs secondaires**: Slate, Blue, Green, Purple, Orange, Teal
- **Typographie**: System font stack
- **Breakpoints**: Responsive mobile-first
- **Composants**: Cartes arrondies, beaucoup d'espace blanc, boutons visibles

## ⚙️ Configuration

- **Framework**: Astro 6.2.2
- **CSS**: Tailwind CSS 4.2.4
- **Output**: Static pre-rendered HTML
- **SEO**: Meta tags par page, H1 uniques

## 🔧 Customisation

### Ajouter une nouvelle page

1. Créer un fichier `.astro` dans `src/pages/`
2. Importer `Layout` depuis `../layouts/Layout.astro`
3. Importer les composants nécessaires

Exemple :
```astro
---
import Layout from '../layouts/Layout.astro';
import Button from '../components/Button.astro';
---

<Layout
  title="Ma Page"
  description="Description SEO"
  currentPage="/ma-page"
>
  <!-- Contenu ici -->
</Layout>
```

### Personnaliser les couleurs

Les couleurs sont utilisées via les classes Tailwind. 
Modifiez le gradient primaire dans `src/pages/index.astro` ou les composants directement.

### Formulaires

Les formulaires sont actuellement en front-end. Pour traiter les données:
- Option 1: Ajouter une action Netlify Forms
- Option 2: Intégrer Formspree, Basin, ou autre service
- Option 3: Ajouter un backend Node/Express personnalisé

## 📱 Responsive

Le site est responsive sur :
- Mobile (320px+)
- Tablet (640px+)
- Desktop (1024px+)

Testez avec `npm run dev` et redimensionnez votre navigateur.

## 🚢 Déploiement

### Netlify (recommandé)
1. Push vers GitHub
2. Connecter le repo à Netlify
3. Build command: `npm run build`
4. Publish directory: `dist/`

### Vercel
1. Import du projet
2. Deploy automatique depuis Git

### Autre hosting
1. Run `npm run build` localement
2. Upload le dossier `dist/` via FTP/SFTP

## 🐛 Troubleshooting

**Port 4321 déjà utilisé**
```bash
npm run dev -- --port 3000
```

**Cache du build problématique**
```bash
rm -rf dist node_modules
npm install
npm run build
```

## 📚 Ressources

- [Documentation Astro](https://docs.astro.build)
- [Documentation Tailwind CSS](https://tailwindcss.com/docs)
- [Astro Best Practices](https://docs.astro.build/en/guides/best-practices/)

## 📝 Notes

- V1 sans backend (formulaires visuel seulement)
- Pas de CMS configuré
- Images placeholder avec emojis/gradients (à remplacer par vraies images)
- Contenu blog en dur (peut être migré vers collections Astro)

## Points forts de cette V1

✅ **Architecture clean** - Composants modulaires et réutilisables  
✅ **Design premium** - Cohérent, chaleureux et professionnel  
✅ **Responsive** - Mobile, tablet, desktop optimisés  
✅ **SEO** - Meta tags, titres uniques, structure sémantique  
✅ **Performance** - Build léger, pas de JavaScript inutile  
✅ **Maintenabilité** - Code simple, facile à étendre  
✅ **Contenu riche** - 12 pages avec contenu de qualité  

---

**Développé pour**: Mickael Gury & Marion Roulier, conseillers immobiliers IAD  
**Date**: Mai 2024  
**Version**: 1.0.0
# Astro Starter Kit: Basics

```sh
npm create astro@latest -- --template basics
```

> 🧑‍🚀 **Seasoned astronaut?** Delete this file. Have fun!

## 🚀 Project Structure

Inside of your Astro project, you'll see the following folders and files:

```text
/
├── public/
│   └── favicon.svg
├── src
│   ├── assets
│   │   └── astro.svg
│   ├── components
│   │   └── Welcome.astro
│   ├── layouts
│   │   └── Layout.astro
│   └── pages
│       └── index.astro
└── package.json
```

To learn more about the folder structure of an Astro project, refer to [our guide on project structure](https://docs.astro.build/en/basics/project-structure/).

## 🧞 Commands

All commands are run from the root of the project, from a terminal:

| Command                   | Action                                           |
| :------------------------ | :----------------------------------------------- |
| `npm install`             | Installs dependencies                            |
| `npm run dev`             | Starts local dev server at `localhost:4321`      |
| `npm run build`           | Build your production site to `./dist/`          |
| `npm run preview`         | Preview your build locally, before deploying     |
| `npm run astro ...`       | Run CLI commands like `astro add`, `astro check` |
| `npm run astro -- --help` | Get help using the Astro CLI                     |

## 👀 Want to learn more?

Feel free to check [our documentation](https://docs.astro.build) or jump into our [Discord server](https://astro.build/chat).

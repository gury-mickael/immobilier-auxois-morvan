import fs from 'node:fs/promises';
import path from 'node:path';
import matter from 'gray-matter';

const root = process.cwd();
const sourceDir = path.join(root, 'ovh');
const buildDir = path.join(root, 'ovh-build');
const publicDir = path.join(root, 'public');

const snapshotConfig = {
  localPagesDir: path.join(root, 'src/content/local-pages'),
  blogDir: path.join(root, 'src/content/blog'),
  testimonialsDir: path.join(root, 'src/content/testimonials'),
  settingsPath: path.join(root, 'src/content/settings/site.json'),
};

async function exists(targetPath) {
  try {
    await fs.access(targetPath);
    return true;
  } catch {
    return false;
  }
}

async function copyIfExists(sourcePath, destinationPath) {
  if (!(await exists(sourcePath))) {
    return;
  }

  await fs.cp(sourcePath, destinationPath, { recursive: true, force: true });
}

function toHtmlParagraphs(value) {
  const input = String(value ?? '').trim();
  if (!input) {
    return '<p></p>';
  }

  return input
    .split(/\n\s*\n/)
    .map((paragraph) => `<p>${paragraph.trim().replaceAll('\n', '<br>')}</p>`)
    .join('');
}

function stripMarkdown(value) {
  return String(value ?? '')
    .replace(/^#{1,6}\s+/gm, '')
    .replace(/\*\*(.*?)\*\*/g, '$1')
    .replace(/\*(.*?)\*/g, '$1')
    .replace(/`(.*?)`/g, '$1')
    .trim();
}

function toExcerpt(value, maxLength = 180) {
  const text = String(value ?? '').replace(/[#>*_`\-]+/g, ' ').replace(/\s+/g, ' ').trim();
  if (text.length <= maxLength) {
    return text;
  }

  return `${text.slice(0, maxLength).trim()}...`;
}

async function readCollection(directory) {
  const fileNames = (await fs.readdir(directory)).sort();
  const entries = [];

  for (const fileName of fileNames) {
    const raw = await fs.readFile(path.join(directory, fileName), 'utf8');
    const parsed = matter(raw);
    entries.push({ fileName, ...parsed });
  }

  return entries;
}

async function writeSnapshot(destinationDir) {
  const [settingsRaw, localPages, blogPosts, testimonials] = await Promise.all([
    fs.readFile(snapshotConfig.settingsPath, 'utf8'),
    readCollection(snapshotConfig.localPagesDir),
    readCollection(snapshotConfig.blogDir),
    readCollection(snapshotConfig.testimonialsDir),
  ]);

  const siteSettings = JSON.parse(settingsRaw);

  const snapshot = {
    siteSettings,
    localPages: localPages
      .filter(({ data }) => data.published)
      .map(({ data }) => ({
        title: data.h1,
        city: data.city,
        pageType: data.pageType,
        href: `/${data.slug}`,
        excerpt: toExcerpt(data.intro, 160),
        image: data.heroImage ?? '',
      })),
    blogPosts: blogPosts
      .filter(({ data }) => data.published)
      .sort((left, right) => new Date(right.data.date).getTime() - new Date(left.data.date).getTime())
      .map(({ data, content }) => ({
        title: data.title,
        slug: data.slug,
        metaTitle: data.title,
        metaDescription: data.metaDescription,
        excerpt: data.excerpt,
        href: `/blog/${data.slug}`,
        category: data.category,
        date: data.date,
        image: data.featuredImage ?? '',
        imageAlt: data.featuredImageAlt ?? '',
        bodyHtml: toHtmlParagraphs(stripMarkdown(content)),
        isIndexable: true,
      })),
    testimonials: testimonials
      .filter(({ data }) => data.published)
      .map(({ data, content }) => ({
        quote: toExcerpt(content, 220),
        author: data.name,
        title: data.propertyType,
        location: data.location,
        rating: Number(data.rating ?? 5),
      })),
    services: [
      {
        title: 'Vendre',
        description: 'Positionner votre bien au bon prix, le valoriser et piloter la vente jusqu\'à la signature.',
        href: '/vendre',
        features: ['Estimation juste', 'Diffusion ciblée', 'Négociation pilotée'],
      },
      {
        title: 'Acheter',
        description: 'Comparer les secteurs, affiner la recherche et avancer avec des repères concrets sur le terrain.',
        href: '/acheter',
        features: ['Lecture du marché local', 'Accompagnement des visites', 'Aide à la décision'],
      },
      {
        title: 'Estimation',
        description: 'Obtenir un avis de valeur argumenté pour préparer une mise en vente ou arbitrer un projet.',
        href: '/estimation',
        features: ['Analyse locale', 'Spécificités du bien', 'Conseil utile à la décision'],
      },
      {
        title: 'Fonds de commerce',
        description: 'Structurer une transmission, présenter le dossier et qualifier les repreneurs avec discrétion.',
        href: '/fonds',
        features: ['Valorisation', 'Dossier acquéreur', 'Accompagnement jusqu\'à la vente'],
      },
    ],
    areaDescriptions: {
      'Arnay-le-Duc': 'Un marché de proximité où la lecture du cadre de vie et des villages environnants fait la différence.',
      'Pouilly-en-Auxois': 'Un secteur charnière entre mobilité, vie locale et recherche résidentielle dans l\'Auxois.',
      'Autun': 'Un bassin de vie structuré, avec des quartiers et profils acquéreurs à bien distinguer.',
      'Saulieu': 'Une ville d\'équilibre entre centralité locale, patrimoine et environnement naturel recherché.',
      'Beaune': 'Un marché attractif où l\'emplacement, la qualité du bien et le projet acquéreur pèsent fortement.',
      'Dijon': 'Une zone plus large où l\'accompagnement local aide à mieux arbitrer les opportunités et les secteurs.',
    },
    areaImages: {
      'Arnay-le-Duc': '/uploads/arnay.jpg',
      'Pouilly-en-Auxois': '/uploads/pouilly.jpg',
      'Autun': '/uploads/autun.jpg',
      'Saulieu': '/uploads/saulieu.jpg',
      'Beaune': '/uploads/beaune.jpg',
      'Dijon': '/uploads/dijon.jpg',
    },
  };

  await fs.mkdir(path.join(destinationDir, 'data'), { recursive: true });
  await fs.writeFile(path.join(destinationDir, 'data', 'content-snapshot.json'), JSON.stringify(snapshot, null, 2), 'utf8');
}

async function main() {
  await fs.rm(buildDir, { recursive: true, force: true });
  await fs.cp(sourceDir, buildDir, { recursive: true, force: true });

  await copyIfExists(path.join(publicDir, 'uploads'), path.join(buildDir, 'uploads'));
  await copyIfExists(path.join(publicDir, 'favicon.ico'), path.join(buildDir, 'favicon.ico'));
  await copyIfExists(path.join(publicDir, 'favicon.svg'), path.join(buildDir, 'favicon.svg'));
  await copyIfExists(path.join(root, 'db', 'schema.sql'), path.join(buildDir, 'install', 'schema.sql'));
  await copyIfExists(path.join(root, 'db', 'ovh-seed.sql'), path.join(buildDir, 'install', 'seed.sql'));
  await writeSnapshot(buildDir);

  await fs.mkdir(path.join(buildDir, 'uploads', 'cms'), { recursive: true });

  const envExample = path.join(buildDir, '.env.example');
  if (await exists(envExample)) {
    await fs.rename(envExample, path.join(buildDir, '.env.example.txt'));
  }

  console.log(`Dossier de déploiement prêt : ${buildDir}`);
  console.log('Contenu prêt à uploader sur OVH :');
  console.log('- fichiers PHP du mini-CMS');
  console.log('- images historiques depuis public/uploads');
  console.log('- favicons');
  console.log('- install/schema.sql et install/seed.sql pour l\'import initial');
  console.log('- data/content-snapshot.json pour rapprocher le rendu PHP du front Astro');
  console.log('- dossier uploads/cms pour les futurs uploads');
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
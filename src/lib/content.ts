import { getCollection } from 'astro:content';
import siteSettings from '../content/settings/site.json';
import { getPublishedBlogOverrides, getPublishedLocalPageOverrides, getPublishedMainPageOverride, getSiteSettingsOverride } from './server/cms';

export type MainPageId =
  | 'accueil'
  | 'vendre'
  | 'acheter'
  | 'estimation'
  | 'secteur'
  | 'fonds-de-commerce'
  | 'contact';

const pageSlugById: Record<MainPageId, string> = {
  accueil: '/',
  vendre: '/vendre',
  acheter: '/acheter',
  estimation: '/estimation',
  secteur: '/secteur',
  'fonds-de-commerce': '/fonds',
  contact: '/contact'
};

export async function getSiteSettings() {
  const dbSettings = await getSiteSettingsOverride();

  return dbSettings ?? siteSettings;
}

export async function getPageContent(id: MainPageId) {
  const dbPage = await getPublishedMainPageOverride(id);

  if (dbPage) {
    return dbPage;
  }

  const targetSlug = pageSlugById[id];
  const entries = await getCollection('pages');
  const entry = entries.find((item) => item.data.slug === targetSlug);

  if (!entry) {
    throw new Error(`Le contenu de la page ${id} est introuvable.`);
  }

  return entry;
}

export async function getPublishedBlogPosts() {
  const [entries, dbOverrides] = await Promise.all([
    getCollection('blog', ({ data }) => data.published),
    getPublishedBlogOverrides()
  ]);

  const posts = entries.map((entry) => ({
    id: entry.id,
    source: 'file' as const,
    entry,
    bodyHtml: entry.body,
    data: {
      title: entry.data.title,
      displayTitle: entry.data.title,
      metaDescription: entry.data.metaDescription,
      slug: entry.data.slug,
      category: entry.data.category,
      date: entry.data.date,
      excerpt: entry.data.excerpt,
      featuredImage: entry.data.featuredImage,
      featuredImageAlt: entry.data.featuredImageAlt,
      published: entry.data.published,
      isIndexable: true
    }
  }));

  const bySlug = new Map(posts.map((post) => [post.data.slug, post]));

  for (const post of dbOverrides) {
    bySlug.set(post.data.slug, post);
  }

  return Array.from(bySlug.values()).sort((left, right) => right.data.date.getTime() - left.data.date.getTime());
}

export async function getPublishedTestimonials() {
  const entries = await getCollection('testimonials', ({ data }) => data.published);

  return entries.map((entry) => ({
    ...entry,
    quote: entry.body.trim()
  }));
}

export async function getPublishedLocalPages() {
  const [entries, dbOverrides] = await Promise.all([
    getCollection('local-pages', ({ data }) => data.published),
    getPublishedLocalPageOverrides()
  ]);

  const bySlug = new Map(entries.map((entry) => [entry.data.slug, entry]));

  for (const page of dbOverrides) {
    bySlug.set(page.data.slug, page);
  }

  return Array.from(bySlug.values()).sort((left, right) => left.data.city.localeCompare(right.data.city, 'fr'));
}

export function formatLongDate(date: Date) {
  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(date);
}

export function getPageIntro(entry: Awaited<ReturnType<typeof getPageContent>>) {
  return entry.data.intro;
}

export function resolveAssetSrc(asset?: string | { src: string }) {
  if (!asset) {
    return undefined;
  }

  return typeof asset === 'string' ? asset : asset.src;
}
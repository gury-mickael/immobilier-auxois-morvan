import { getCollection } from 'astro:content';
import siteSettings from '../content/settings/site.json';

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
  return siteSettings;
}

export async function getPageContent(id: MainPageId) {
  const targetSlug = pageSlugById[id];
  const entries = await getCollection('pages');
  const entry = entries.find((item) => item.data.slug === targetSlug);

  if (!entry) {
    throw new Error(`Le contenu de la page ${id} est introuvable.`);
  }

  return entry;
}

export async function getPublishedBlogPosts() {
  const entries = await getCollection('blog', ({ data }) => data.published);

  return entries.sort((left, right) => right.data.date.getTime() - left.data.date.getTime());
}

export async function getPublishedTestimonials() {
  const entries = await getCollection('testimonials', ({ data }) => data.published);

  return entries.map((entry) => ({
    ...entry,
    quote: entry.body.trim()
  }));
}

export async function getPublishedLocalPages() {
  const entries = await getCollection('local-pages', ({ data }) => data.published);

  return entries.sort((left, right) => left.data.city.localeCompare(right.data.city, 'fr'));
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
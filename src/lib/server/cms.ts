import { getCollection } from 'astro:content';
import siteSettingsFile from '../../content/settings/site.json';
import { withDb } from './db';

export type MainPageKey =
  | 'accueil'
  | 'vendre'
  | 'acheter'
  | 'estimation'
  | 'secteur'
  | 'fonds-de-commerce'
  | 'contact';

export interface CmsSection {
  eyebrow?: string;
  title: string;
  text: string;
  image?: string;
  imageAlt?: string;
  buttonLabel?: string;
  buttonUrl?: string;
  items?: string[];
  stats?: { label: string; value: string }[];
}

export interface CmsMainPageData {
  title: string;
  metaDescription: string;
  slug: string;
  h1: string;
  heroTitle: string;
  heroSubtitle: string;
  heroImage?: string;
  heroImageAlt?: string;
  intro: string;
  sections: CmsSection[];
  ctaTitle: string;
  ctaText: string;
  ctaButtonLabel: string;
  ctaButtonUrl: string;
  published: boolean;
}

export interface CmsLocalPageData extends CmsMainPageData {
  city: string;
  pageType: string;
  localAdvantages: string[];
  nearbyCities: string[];
}

interface CmsPageRow {
  id?: number;
  page_key: MainPageKey | null;
  slug: string;
  title: string;
  meta_description: string;
  h1: string;
  hero_title: string;
  hero_subtitle: string;
  hero_image: string | null;
  hero_image_alt: string | null;
  intro_html: string;
  sections_json: string;
  cta_title: string;
  cta_text: string;
  cta_button_label: string;
  cta_button_url: string;
  city: string | null;
  local_page_type: string | null;
  local_advantages_json: string | null;
  nearby_cities_json: string | null;
  status: 'draft' | 'published';
  updated_at: string;
}

interface CmsSettingsRow {
  site_name: string;
  baseline: string;
  mickael_name: string;
  marion_name: string;
  mickael_photo: string | null;
  marion_photo: string | null;
  phone: string;
  email: string;
  main_city: string;
  covered_areas_json: string;
  facebook_url: string | null;
  instagram_url: string | null;
  iad_url: string | null;
  footer_text: string;
  main_cta_label: string;
  main_cta_url: string;
}

interface CmsBlogRow {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  category: string | null;
  featured_image: string | null;
  featured_image_alt: string | null;
  content_html: string;
  meta_title: string | null;
  meta_description: string | null;
  is_indexable: number;
  status: 'draft' | 'published';
  published_at: string | null;
  created_at: string;
  updated_at: string;
}

export const mainPageSlugByKey: Record<MainPageKey, string> = {
  accueil: '/',
  vendre: '/vendre',
  acheter: '/acheter',
  estimation: '/estimation',
  secteur: '/secteur',
  'fonds-de-commerce': '/fonds',
  contact: '/contact'
};

export const mainPageKeys = Object.keys(mainPageSlugByKey) as MainPageKey[];

function mapRowToBlogPost(row: CmsBlogRow) {
  return {
    id: String(row.id),
    source: 'database' as const,
    bodyHtml: row.content_html,
    data: {
      title: row.meta_title?.trim() || row.title,
      displayTitle: row.title,
      metaDescription: row.meta_description?.trim() || row.excerpt,
      slug: row.slug,
      category: row.category ?? 'Actualité',
      date: new Date(row.published_at ?? row.created_at),
      excerpt: row.excerpt,
      featuredImage: row.featured_image ?? undefined,
      featuredImageAlt: row.featured_image_alt ?? undefined,
      published: row.status === 'published',
      isIndexable: Boolean(row.is_indexable)
    }
  };
}

function mapRowToLocalPage(row: CmsPageRow) {
  const sections = safeJsonParse<CmsSection[]>(row.sections_json, []).map(normalizeSection);

  return {
    id: String(row.id ?? row.slug),
    data: {
      title: row.title,
      metaDescription: row.meta_description,
      slug: row.slug,
      city: row.city ?? '',
      pageType: row.local_page_type ?? '',
      h1: row.h1,
      heroTitle: row.hero_title,
      heroSubtitle: row.hero_subtitle,
      heroImage: row.hero_image ?? undefined,
      heroImageAlt: row.hero_image_alt ?? undefined,
      intro: row.intro_html,
      sections,
      localAdvantages: safeJsonParse<string[]>(row.local_advantages_json, []),
      nearbyCities: safeJsonParse<string[]>(row.nearby_cities_json, []),
      published: row.status === 'published',
      ctaTitle: row.cta_title,
      ctaText: row.cta_text,
      ctaButtonLabel: row.cta_button_label,
      ctaButtonUrl: row.cta_button_url
    }
  };
}

function safeJsonParse<T>(value: string | null | undefined, fallback: T): T {
  if (!value) {
    return fallback;
  }

  try {
    return JSON.parse(value) as T;
  } catch {
    return fallback;
  }
}

function normalizeSection(section: Partial<CmsSection>): CmsSection {
  return {
    eyebrow: section.eyebrow?.trim() || undefined,
    title: section.title?.trim() || 'Section',
    text: section.text?.trim() || '',
    image: section.image?.trim() || undefined,
    imageAlt: section.imageAlt?.trim() || undefined,
    buttonLabel: section.buttonLabel?.trim() || undefined,
    buttonUrl: section.buttonUrl?.trim() || undefined,
    items: Array.isArray(section.items) ? section.items.map((item) => item.trim()).filter(Boolean) : undefined,
    stats: Array.isArray(section.stats)
      ? section.stats
          .map((stat) => ({ label: stat.label?.trim() || '', value: stat.value?.trim() || '' }))
          .filter((stat) => stat.label && stat.value)
      : undefined
  };
}

function mapRowToMainPage(row: CmsPageRow) {
  const sections = safeJsonParse<CmsSection[]>(row.sections_json, []).map(normalizeSection);

  return {
    id: row.page_key ?? row.slug,
    data: {
      title: row.title,
      metaDescription: row.meta_description,
      slug: row.slug,
      h1: row.h1,
      heroTitle: row.hero_title,
      heroSubtitle: row.hero_subtitle,
      heroImage: row.hero_image ?? undefined,
      heroImageAlt: row.hero_image_alt ?? undefined,
      intro: row.intro_html,
      sections,
      published: row.status === 'published',
      ctaTitle: row.cta_title,
      ctaText: row.cta_text,
      ctaButtonLabel: row.cta_button_label,
      ctaButtonUrl: row.cta_button_url
    }
  };
}

export async function getPublishedMainPageOverride(pageKey: MainPageKey) {
  return withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT page_key, slug, title, meta_description, h1, hero_title, hero_subtitle, hero_image,
              hero_image_alt, intro_html, sections_json, cta_title, cta_text, cta_button_label,
              cta_button_url, status, updated_at
         FROM cms_pages
        WHERE page_type = 'main' AND page_key = ? AND status = 'published'
        LIMIT 1`,
      [pageKey]
    );

    const row = Array.isArray(rows) ? (rows[0] as CmsPageRow | undefined) : undefined;
    return row ? mapRowToMainPage(row) : null;
  }, null as ReturnType<typeof mapRowToMainPage> | null);
}

export async function getSiteSettingsOverride() {
  return withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT site_name, baseline, mickael_name, marion_name, mickael_photo, marion_photo, phone, email, main_city,
              covered_areas_json, facebook_url, instagram_url, iad_url, footer_text,
              main_cta_label, main_cta_url
         FROM cms_site_settings
        WHERE id = 1
        LIMIT 1`
    );

    const row = Array.isArray(rows) ? (rows[0] as CmsSettingsRow | undefined) : undefined;

    if (!row) {
      return null;
    }

    return {
      siteName: row.site_name,
      baseline: row.baseline,
      mickaelName: row.mickael_name,
      marionName: row.marion_name,
      mickaelPhoto: row.mickael_photo ?? undefined,
      marionPhoto: row.marion_photo ?? undefined,
      phone: row.phone,
      email: row.email,
      mainCity: row.main_city,
      coveredAreas: safeJsonParse<string[]>(row.covered_areas_json, []),
      facebookUrl: row.facebook_url ?? undefined,
      instagramUrl: row.instagram_url ?? undefined,
      iadUrl: row.iad_url ?? undefined,
      footerText: row.footer_text,
      mainCtaLabel: row.main_cta_label,
      mainCtaUrl: row.main_cta_url
    };
  }, null as typeof siteSettingsFile | null);
}

export async function getAdminSiteSettings() {
  const dbSettings = await getSiteSettingsOverride();

  return dbSettings ?? siteSettingsFile;
}

export async function upsertSiteSettings(payload: typeof siteSettingsFile) {
  return withDb(async (db) => {
    await db.query(
      `INSERT INTO cms_site_settings (
         id, site_name, baseline, mickael_name, marion_name, phone, email, main_city,
         mickael_photo, marion_photo, covered_areas_json, facebook_url, instagram_url, iad_url, footer_text,
         main_cta_label, main_cta_url
       ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE
         site_name = VALUES(site_name),
         baseline = VALUES(baseline),
         mickael_name = VALUES(mickael_name),
         marion_name = VALUES(marion_name),
         mickael_photo = VALUES(mickael_photo),
         marion_photo = VALUES(marion_photo),
         phone = VALUES(phone),
         email = VALUES(email),
         main_city = VALUES(main_city),
         covered_areas_json = VALUES(covered_areas_json),
         facebook_url = VALUES(facebook_url),
         instagram_url = VALUES(instagram_url),
         iad_url = VALUES(iad_url),
         footer_text = VALUES(footer_text),
         main_cta_label = VALUES(main_cta_label),
         main_cta_url = VALUES(main_cta_url)`,
      [
        payload.siteName,
        payload.baseline,
        payload.mickaelName,
        payload.marionName,
        payload.phone,
        payload.email,
        payload.mainCity,
        payload.mickaelPhoto ?? null,
        payload.marionPhoto ?? null,
        JSON.stringify(payload.coveredAreas),
        payload.facebookUrl ?? null,
        payload.instagramUrl ?? null,
        payload.iadUrl ?? null,
        payload.footerText,
        payload.mainCtaLabel,
        payload.mainCtaUrl
      ]
    );
  }, undefined);
}

export async function getAdminMainPages() {
  const entries = await getCollection('pages');
  const fallbackBySlug = new Map(entries.map((entry) => [entry.data.slug, entry]));
  const dbRows = await withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT page_key, slug, title, status, updated_at
         FROM cms_pages
        WHERE page_type = 'main'`
    );

    return Array.isArray(rows) ? (rows as Array<Pick<CmsPageRow, 'page_key' | 'slug' | 'title' | 'status' | 'updated_at'>>) : [];
  }, [] as Array<Pick<CmsPageRow, 'page_key' | 'slug' | 'title' | 'status' | 'updated_at'>>);

  const dbByKey = new Map(dbRows.map((row) => [row.page_key, row]));

  return mainPageKeys.map((pageKey) => {
    const slug = mainPageSlugByKey[pageKey];
    const fallback = fallbackBySlug.get(slug);
    const current = dbByKey.get(pageKey);

    return {
      pageKey,
      slug,
      title: current?.title ?? fallback?.data.title ?? pageKey,
      status: current?.status ?? 'file',
      updatedAt: current?.updated_at ?? null
    };
  });
}

export async function getPublishedLocalPageOverrides() {
  return withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT id, page_key, slug, title, meta_description, h1, hero_title, hero_subtitle, hero_image,
              hero_image_alt, intro_html, sections_json, cta_title, cta_text, cta_button_label,
              cta_button_url, city, local_page_type, local_advantages_json, nearby_cities_json,
              status, updated_at
         FROM cms_pages
        WHERE page_type = 'local' AND status = 'published'
        ORDER BY city ASC, title ASC`
    );

    return Array.isArray(rows) ? (rows as CmsPageRow[]).map(mapRowToLocalPage) : [];
  }, [] as Array<ReturnType<typeof mapRowToLocalPage>>);
}

export async function getPublishedBlogOverrides() {
  return withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT id, title, slug, excerpt, category, featured_image, featured_image_alt,
              content_html, meta_title, meta_description, is_indexable, status,
              published_at, created_at, updated_at
         FROM cms_blog_posts
        WHERE status = 'published'
        ORDER BY COALESCE(published_at, created_at) DESC`
    );

    return Array.isArray(rows) ? (rows as CmsBlogRow[]).map(mapRowToBlogPost) : [];
  }, [] as Array<ReturnType<typeof mapRowToBlogPost>>);
}

export async function getAdminBlogPosts() {
  const fileEntries = await getCollection('blog');
  const dbRows = await withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT id, title, slug, excerpt, category, status, updated_at
         FROM cms_blog_posts
        ORDER BY updated_at DESC, title ASC`
    );

    return Array.isArray(rows)
      ? (rows as Array<Pick<CmsBlogRow, 'id' | 'title' | 'slug' | 'excerpt' | 'category' | 'status' | 'updated_at'>>)
      : [];
  }, [] as Array<Pick<CmsBlogRow, 'id' | 'title' | 'slug' | 'excerpt' | 'category' | 'status' | 'updated_at'>>);

  const rows = dbRows.map((row) => ({
    id: String(row.id),
    slug: row.slug,
    title: row.title,
    excerpt: row.excerpt,
    category: row.category ?? 'Actualité',
    status: row.status,
    updatedAt: row.updated_at,
    source: 'database' as const
  }));

  const seenSlugs = new Set(rows.map((row) => row.slug));

  for (const entry of fileEntries) {
    if (seenSlugs.has(entry.data.slug)) {
      continue;
    }

    rows.push({
      id: entry.id,
      slug: entry.data.slug,
      title: entry.data.title,
      excerpt: entry.data.excerpt,
      category: entry.data.category,
      status: entry.data.published ? 'file' : 'draft',
      updatedAt: null,
      source: 'file' as const
    });
  }

  return rows.sort((left, right) => left.title.localeCompare(right.title, 'fr'));
}

export async function getAdminBlogPost(postSlug: string) {
  const fileEntries = await getCollection('blog');
  const fallback = fileEntries.find((entry) => entry.data.slug === postSlug);

  const dbPost = await withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT id, title, slug, excerpt, category, featured_image, featured_image_alt,
              content_html, meta_title, meta_description, is_indexable, status,
              published_at, created_at, updated_at
         FROM cms_blog_posts
        WHERE slug = ?
        LIMIT 1`,
      [postSlug]
    );

    const row = Array.isArray(rows) ? (rows[0] as CmsBlogRow | undefined) : undefined;
    return row ? mapRowToBlogPost(row) : null;
  }, null as ReturnType<typeof mapRowToBlogPost> | null);

  if (!dbPost && !fallback) {
    return null;
  }

  return {
    source: dbPost ? 'database' : 'file',
    status: dbPost?.data.published ? 'published' : dbPost ? 'draft' : 'file',
    data: dbPost?.data ?? {
      title: fallback!.data.title,
      displayTitle: fallback!.data.title,
      metaDescription: fallback!.data.metaDescription,
      slug: fallback!.data.slug,
      category: fallback!.data.category,
      date: fallback!.data.date,
      excerpt: fallback!.data.excerpt,
      featuredImage: fallback!.data.featuredImage,
      featuredImageAlt: fallback!.data.featuredImageAlt,
      published: fallback!.data.published,
      isIndexable: true
    },
    bodyHtml: dbPost?.bodyHtml ?? fallback?.body ?? ''
  };
}

export async function upsertBlogPost(postSlug: string, payload: {
  title: string;
  metaTitle?: string;
  metaDescription: string;
  slug: string;
  category: string;
  excerpt: string;
  featuredImage?: string;
  featuredImageAlt?: string;
  contentHtml: string;
  isIndexable: boolean;
  status: 'draft' | 'published';
}) {
  return withDb(async (db) => {
    await db.query(
      `INSERT INTO cms_blog_posts (
         title, slug, excerpt, category, featured_image, featured_image_alt,
         content_html, meta_title, meta_description, is_indexable, status, published_at
       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'published' THEN NOW() ELSE NULL END)
       ON DUPLICATE KEY UPDATE
         title = VALUES(title),
         slug = VALUES(slug),
         excerpt = VALUES(excerpt),
         category = VALUES(category),
         featured_image = VALUES(featured_image),
         featured_image_alt = VALUES(featured_image_alt),
         content_html = VALUES(content_html),
         meta_title = VALUES(meta_title),
         meta_description = VALUES(meta_description),
         is_indexable = VALUES(is_indexable),
         status = VALUES(status),
         published_at = CASE WHEN VALUES(status) = 'published' THEN COALESCE(cms_blog_posts.published_at, NOW()) ELSE cms_blog_posts.published_at END`,
      [
        payload.title,
        payload.slug,
        payload.excerpt,
        payload.category,
        payload.featuredImage ?? null,
        payload.featuredImageAlt ?? null,
        payload.contentHtml,
        payload.metaTitle ?? null,
        payload.metaDescription,
        payload.isIndexable ? 1 : 0,
        payload.status,
        payload.status
      ]
    );
  }, undefined);
}

export async function getAdminLocalPages() {
  const fileEntries = await getCollection('local-pages');
  const fallbackBySlug = new Map(fileEntries.map((entry) => [entry.data.slug, entry]));
  const dbRows = await withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT id, slug, title, city, local_page_type, status, updated_at
         FROM cms_pages
        WHERE page_type = 'local'
        ORDER BY city ASC, title ASC`
    );

    return Array.isArray(rows)
      ? (rows as Array<Pick<CmsPageRow, 'id' | 'slug' | 'title' | 'city' | 'local_page_type' | 'status' | 'updated_at'>>)
      : [];
  }, [] as Array<Pick<CmsPageRow, 'id' | 'slug' | 'title' | 'city' | 'local_page_type' | 'status' | 'updated_at'>>);

  const rows = dbRows.map((row) => ({
    id: String(row.id ?? row.slug),
    slug: row.slug,
    title: row.title,
    city: row.city ?? '',
    pageType: row.local_page_type ?? '',
    status: row.status,
    updatedAt: row.updated_at,
    source: 'database' as const
  }));

  const seenSlugs = new Set(rows.map((row) => row.slug));

  for (const entry of fileEntries) {
    if (seenSlugs.has(entry.data.slug)) {
      continue;
    }

    rows.push({
      id: entry.id,
      slug: entry.data.slug,
      title: entry.data.title,
      city: entry.data.city,
      pageType: entry.data.pageType,
      status: 'file',
      updatedAt: null,
      source: 'file' as const
    });
  }

  return rows.sort((left, right) => left.city.localeCompare(right.city, 'fr') || left.title.localeCompare(right.title, 'fr'));
}

export async function getAdminLocalPage(pageSlug: string) {
  const fileEntries = await getCollection('local-pages');
  const fallback = fileEntries.find((entry) => entry.data.slug === pageSlug);

  const dbPage = await withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT id, page_key, slug, title, meta_description, h1, hero_title, hero_subtitle, hero_image,
              hero_image_alt, intro_html, sections_json, cta_title, cta_text, cta_button_label,
              cta_button_url, city, local_page_type, local_advantages_json, nearby_cities_json,
              status, updated_at
         FROM cms_pages
        WHERE page_type = 'local' AND slug = ?
        LIMIT 1`,
      [pageSlug]
    );

    const row = Array.isArray(rows) ? (rows[0] as CmsPageRow | undefined) : undefined;
    return row ? mapRowToLocalPage(row) : null;
  }, null as ReturnType<typeof mapRowToLocalPage> | null);

  if (!dbPage && !fallback) {
    return null;
  }

  return {
    source: dbPage ? 'database' : 'file',
    status: dbPage?.data.published ? 'published' : dbPage ? 'draft' : 'file',
    data: dbPage?.data ?? fallback!.data
  };
}

export async function upsertLocalPage(pageSlug: string, payload: CmsLocalPageData & { status: 'draft' | 'published' }) {
  const sectionsJson = JSON.stringify(payload.sections.map(normalizeSection));

  return withDb(async (db) => {
    await db.query(
      `INSERT INTO cms_pages (
         page_type, page_key, slug, title, meta_description, h1, hero_title, hero_subtitle, hero_image,
         hero_image_alt, intro_html, sections_json, cta_title, cta_text, cta_button_label, cta_button_url,
         city, local_page_type, local_advantages_json, nearby_cities_json, status, published_at
       ) VALUES (
         'local', NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
         CASE WHEN ? = 'published' THEN NOW() ELSE NULL END
       )
       ON DUPLICATE KEY UPDATE
         slug = VALUES(slug),
         title = VALUES(title),
         meta_description = VALUES(meta_description),
         h1 = VALUES(h1),
         hero_title = VALUES(hero_title),
         hero_subtitle = VALUES(hero_subtitle),
         hero_image = VALUES(hero_image),
         hero_image_alt = VALUES(hero_image_alt),
         intro_html = VALUES(intro_html),
         sections_json = VALUES(sections_json),
         cta_title = VALUES(cta_title),
         cta_text = VALUES(cta_text),
         cta_button_label = VALUES(cta_button_label),
         cta_button_url = VALUES(cta_button_url),
         city = VALUES(city),
         local_page_type = VALUES(local_page_type),
         local_advantages_json = VALUES(local_advantages_json),
         nearby_cities_json = VALUES(nearby_cities_json),
         status = VALUES(status),
         published_at = CASE WHEN VALUES(status) = 'published' THEN COALESCE(cms_pages.published_at, NOW()) ELSE cms_pages.published_at END`,
      [
        pageSlug,
        payload.title,
        payload.metaDescription,
        payload.h1,
        payload.heroTitle,
        payload.heroSubtitle,
        payload.heroImage ?? null,
        payload.heroImageAlt ?? null,
        payload.intro,
        sectionsJson,
        payload.ctaTitle,
        payload.ctaText,
        payload.ctaButtonLabel,
        payload.ctaButtonUrl,
        payload.city,
        payload.pageType,
        JSON.stringify(payload.localAdvantages),
        JSON.stringify(payload.nearbyCities),
        payload.status,
        payload.status
      ]
    );
  }, undefined);
}

export async function getAdminMainPage(pageKey: MainPageKey) {
  const entries = await getCollection('pages');
  const fallback = entries.find((entry) => entry.data.slug === mainPageSlugByKey[pageKey]);

  if (!fallback) {
    throw new Error(`Page principale introuvable: ${pageKey}`);
  }

  const dbPage = await withDb(async (db) => {
    const [rows] = await db.query(
      `SELECT page_key, slug, title, meta_description, h1, hero_title, hero_subtitle, hero_image,
              hero_image_alt, intro_html, sections_json, cta_title, cta_text, cta_button_label,
              cta_button_url, status, updated_at
         FROM cms_pages
        WHERE page_type = 'main' AND page_key = ?
        LIMIT 1`,
      [pageKey]
    );

    const row = Array.isArray(rows) ? (rows[0] as CmsPageRow | undefined) : undefined;
    return row ? mapRowToMainPage(row) : null;
  }, null as ReturnType<typeof mapRowToMainPage> | null);

  return {
    pageKey,
    source: dbPage ? 'database' : 'file',
    status: dbPage?.data.published ? 'published' : dbPage ? 'draft' : 'file',
    data: dbPage?.data ?? fallback.data
  };
}

export async function upsertMainPage(pageKey: MainPageKey, payload: CmsMainPageData & { status: 'draft' | 'published' }) {
  const sectionsJson = JSON.stringify(payload.sections.map(normalizeSection));

  return withDb(async (db) => {
    await db.query(
      `INSERT INTO cms_pages (
         page_type, page_key, slug, title, meta_description, h1, hero_title, hero_subtitle, hero_image,
         hero_image_alt, intro_html, sections_json, cta_title, cta_text, cta_button_label, cta_button_url,
         status, published_at
       ) VALUES (
         'main', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
         CASE WHEN ? = 'published' THEN NOW() ELSE NULL END
       )
       ON DUPLICATE KEY UPDATE
         slug = VALUES(slug),
         title = VALUES(title),
         meta_description = VALUES(meta_description),
         h1 = VALUES(h1),
         hero_title = VALUES(hero_title),
         hero_subtitle = VALUES(hero_subtitle),
         hero_image = VALUES(hero_image),
         hero_image_alt = VALUES(hero_image_alt),
         intro_html = VALUES(intro_html),
         sections_json = VALUES(sections_json),
         cta_title = VALUES(cta_title),
         cta_text = VALUES(cta_text),
         cta_button_label = VALUES(cta_button_label),
         cta_button_url = VALUES(cta_button_url),
         status = VALUES(status),
         published_at = CASE WHEN VALUES(status) = 'published' THEN COALESCE(cms_pages.published_at, NOW()) ELSE cms_pages.published_at END`,
      [
        pageKey,
        payload.slug,
        payload.title,
        payload.metaDescription,
        payload.h1,
        payload.heroTitle,
        payload.heroSubtitle,
        payload.heroImage ?? null,
        payload.heroImageAlt ?? null,
        payload.intro,
        sectionsJson,
        payload.ctaTitle,
        payload.ctaText,
        payload.ctaButtonLabel,
        payload.ctaButtonUrl,
        payload.status,
        payload.status
      ]
    );
  }, undefined);
}

export async function seedCmsFromContentFiles() {
  const [pages, localPages, blogPosts, existingSettings] = await Promise.all([
    getCollection('pages'),
    getCollection('local-pages'),
    getCollection('blog'),
    getSiteSettingsOverride()
  ]);

  await withDb(async (db) => {
    for (const pageKey of mainPageKeys) {
      const slug = mainPageSlugByKey[pageKey];
      const entry = pages.find((item) => item.data.slug === slug);

      if (!entry) {
        continue;
      }

      await upsertMainPage(pageKey, {
        title: entry.data.title,
        metaDescription: entry.data.metaDescription,
        slug: entry.data.slug,
        h1: entry.data.h1,
        heroTitle: entry.data.heroTitle,
        heroSubtitle: entry.data.heroSubtitle,
        heroImage: entry.data.heroImage,
        heroImageAlt: entry.data.heroImageAlt,
        intro: entry.data.intro,
        sections: entry.data.sections,
        ctaTitle: entry.data.ctaTitle,
        ctaText: entry.data.ctaText,
        ctaButtonLabel: entry.data.ctaButtonLabel,
        ctaButtonUrl: entry.data.ctaButtonUrl,
        published: true,
        status: 'published'
      });
    }

    for (const entry of localPages) {
      await upsertLocalPage(entry.data.slug, {
        title: entry.data.title,
        metaDescription: entry.data.metaDescription,
        slug: entry.data.slug,
        city: entry.data.city,
        pageType: entry.data.pageType,
        h1: entry.data.h1,
        heroTitle: entry.data.heroTitle,
        heroSubtitle: entry.data.heroSubtitle,
        heroImage: entry.data.heroImage,
        heroImageAlt: entry.data.heroImageAlt,
        intro: entry.data.intro,
        sections: entry.data.sections,
        localAdvantages: entry.data.localAdvantages,
        nearbyCities: entry.data.nearbyCities,
        ctaTitle: entry.data.ctaTitle,
        ctaText: entry.data.ctaText,
        ctaButtonLabel: entry.data.ctaButtonLabel,
        ctaButtonUrl: entry.data.ctaButtonUrl,
        published: Boolean(entry.data.published),
        status: entry.data.published ? 'published' : 'draft'
      });
    }

    for (const entry of blogPosts) {
      await upsertBlogPost(entry.data.slug, {
        title: entry.data.title,
        metaTitle: entry.data.title,
        metaDescription: entry.data.metaDescription,
        slug: entry.data.slug,
        category: entry.data.category,
        excerpt: entry.data.excerpt,
        featuredImage: entry.data.featuredImage,
        featuredImageAlt: entry.data.featuredImageAlt,
        contentHtml: entry.body,
        isIndexable: true,
        status: entry.data.published ? 'published' : 'draft'
      });
    }

    if (!existingSettings) {
      await db.query(
        `INSERT INTO cms_site_settings (
           id, site_name, baseline, mickael_name, marion_name, phone, email, main_city,
           covered_areas_json, facebook_url, instagram_url, iad_url, footer_text,
           main_cta_label, main_cta_url
         ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           site_name = VALUES(site_name),
           baseline = VALUES(baseline),
           mickael_name = VALUES(mickael_name),
           marion_name = VALUES(marion_name),
           phone = VALUES(phone),
           email = VALUES(email),
           main_city = VALUES(main_city),
           covered_areas_json = VALUES(covered_areas_json),
           facebook_url = VALUES(facebook_url),
           instagram_url = VALUES(instagram_url),
           iad_url = VALUES(iad_url),
           footer_text = VALUES(footer_text),
           main_cta_label = VALUES(main_cta_label),
           main_cta_url = VALUES(main_cta_url)`,
        [
          siteSettingsFile.siteName,
          siteSettingsFile.baseline,
          siteSettingsFile.mickaelName,
          siteSettingsFile.marionName,
          siteSettingsFile.phone,
          siteSettingsFile.email,
          siteSettingsFile.mainCity,
          JSON.stringify(siteSettingsFile.coveredAreas),
          siteSettingsFile.facebookUrl ?? null,
          siteSettingsFile.instagramUrl ?? null,
          siteSettingsFile.iadUrl ?? null,
          siteSettingsFile.footerText,
          siteSettingsFile.mainCtaLabel,
          siteSettingsFile.mainCtaUrl
        ]
      );
    }
  }, undefined);
}

export async function createMediaRecord(file: {
  originalName: string;
  fileName: string;
  publicUrl: string;
  mimeType: string;
  sizeBytes: number;
}) {
  return withDb(async (db) => {
    await db.query(
      `INSERT INTO cms_media (original_name, file_name, public_url, mime_type, size_bytes)
       VALUES (?, ?, ?, ?, ?)`,
      [file.originalName, file.fileName, file.publicUrl, file.mimeType, file.sizeBytes]
    );
  }, undefined);
}
import fs from 'node:fs/promises';
import path from 'node:path';
import matter from 'gray-matter';

const root = process.cwd();
const pagesDir = path.join(root, 'src/content/pages');
const localPagesDir = path.join(root, 'src/content/local-pages');
const settingsPath = path.join(root, 'src/content/settings/site.json');
const outputPath = path.join(root, 'db/ovh-seed.sql');

const mainPageKeyByFile = {
  'accueil.md': 'accueil',
  'vendre.md': 'vendre',
  'acheter.md': 'acheter',
  'estimation.md': 'estimation',
  'secteur.md': 'secteur',
  'fonds-de-commerce.md': 'fonds-de-commerce',
  'contact.md': 'contact',
};

function sqlString(value) {
  if (value === null || value === undefined) {
    return 'NULL';
  }

  return `'${String(value).replaceAll('\\', '\\\\').replaceAll("'", "\\'")}'`;
}

function toHtml(value) {
  const input = String(value ?? '').trim();
  if (!input) {
    return '<p></p>';
  }

  return input
    .split(/\n\s*\n/)
    .map((paragraph) => `<p>${paragraph.trim().replaceAll('\n', '<br>')}</p>`)
    .join('');
}

function normalizeSections(sections = []) {
  return sections.map((section) => ({
    eyebrow: section.eyebrow ?? '',
    title: section.title ?? 'Section',
    text: toHtml(section.text ?? ''),
    image: section.image ?? '',
    imageAlt: section.imageAlt ?? '',
    buttonLabel: section.buttonLabel ?? '',
    buttonUrl: section.buttonUrl ?? '',
    items: Array.isArray(section.items) ? section.items : [],
    stats: Array.isArray(section.stats) ? section.stats : [],
  }));
}

function buildPageRow(pageType, pageKey, data) {
  return `(${[
    sqlString(pageType),
    sqlString(pageKey),
    sqlString(data.slug),
    sqlString(data.title),
    sqlString(data.metaDescription),
    '1',
    sqlString(data.h1),
    sqlString(data.heroTitle),
    sqlString(data.heroSubtitle),
    sqlString(data.heroImage ?? null),
    sqlString(data.heroImageAlt ?? null),
    sqlString(toHtml(data.intro ?? '')),
    sqlString(JSON.stringify(normalizeSections(data.sections), null, 0)),
    sqlString(data.ctaTitle),
    sqlString(toHtml(data.ctaText ?? '')),
    sqlString(data.ctaButtonLabel),
    sqlString(data.ctaButtonUrl),
    sqlString(data.city ?? null),
    sqlString(data.pageType ?? null),
    sqlString(JSON.stringify(Array.isArray(data.localAdvantages) ? data.localAdvantages : [])),
    sqlString(JSON.stringify(Array.isArray(data.nearbyCities) ? data.nearbyCities : [])),
    sqlString('published'),
    'NOW()'
  ].join(', ')})`;
}

async function readFrontmatters(directory) {
  const files = await fs.readdir(directory);
  const entries = [];

  for (const file of files.sort()) {
    const fullPath = path.join(directory, file);
    const raw = await fs.readFile(fullPath, 'utf8');
    const parsed = matter(raw);
    entries.push({ file, data: parsed.data });
  }

  return entries;
}

async function main() {
  const [settingsRaw, mainPages, localPages] = await Promise.all([
    fs.readFile(settingsPath, 'utf8'),
    readFrontmatters(pagesDir),
    readFrontmatters(localPagesDir),
  ]);

  const settings = JSON.parse(settingsRaw);
  const mainRows = mainPages.map(({ file, data }) => buildPageRow('main', mainPageKeyByFile[file], data));
  const localRows = localPages.map(({ data }) => buildPageRow('local', null, data));

  const sql = `-- Généré automatiquement par scripts/export-ovh-seed.mjs\n\nINSERT INTO cms_site_settings (\n  id, site_name, baseline, mickael_name, marion_name, phone, email, main_city,\n  covered_areas_json, facebook_url, instagram_url, iad_url, footer_text, main_cta_label, main_cta_url\n) VALUES (\n  1, ${sqlString(settings.siteName)}, ${sqlString(settings.baseline)}, ${sqlString(settings.mickaelName)}, ${sqlString(settings.marionName)},\n  ${sqlString(settings.phone)}, ${sqlString(settings.email)}, ${sqlString(settings.mainCity)}, ${sqlString(JSON.stringify(settings.coveredAreas))},\n  ${sqlString(settings.facebookUrl)}, ${sqlString(settings.instagramUrl)}, ${sqlString(settings.iadUrl)}, ${sqlString(settings.footerText)},\n  ${sqlString(settings.mainCtaLabel)}, ${sqlString(settings.mainCtaUrl)}\n) ON DUPLICATE KEY UPDATE\n  site_name = VALUES(site_name),\n  baseline = VALUES(baseline),\n  mickael_name = VALUES(mickael_name),\n  marion_name = VALUES(marion_name),\n  phone = VALUES(phone),\n  email = VALUES(email),\n  main_city = VALUES(main_city),\n  covered_areas_json = VALUES(covered_areas_json),\n  facebook_url = VALUES(facebook_url),\n  instagram_url = VALUES(instagram_url),\n  iad_url = VALUES(iad_url),\n  footer_text = VALUES(footer_text),\n  main_cta_label = VALUES(main_cta_label),\n  main_cta_url = VALUES(main_cta_url);\n\nINSERT INTO cms_pages (\n  page_type, page_key, slug, title, meta_description, is_indexable, h1, hero_title, hero_subtitle, hero_image, hero_image_alt,\n  intro_html, sections_json, cta_title, cta_text, cta_button_label, cta_button_url, city, local_page_type, local_advantages_json, nearby_cities_json, status, published_at\n) VALUES\n${[...mainRows, ...localRows].join(',\n')}\nON DUPLICATE KEY UPDATE\n  slug = VALUES(slug),\n  title = VALUES(title),\n  meta_description = VALUES(meta_description),\n  is_indexable = VALUES(is_indexable),\n  h1 = VALUES(h1),\n  hero_title = VALUES(hero_title),\n  hero_subtitle = VALUES(hero_subtitle),\n  hero_image = VALUES(hero_image),\n  hero_image_alt = VALUES(hero_image_alt),\n  intro_html = VALUES(intro_html),\n  sections_json = VALUES(sections_json),\n  cta_title = VALUES(cta_title),\n  cta_text = VALUES(cta_text),\n  cta_button_label = VALUES(cta_button_label),\n  cta_button_url = VALUES(cta_button_url),\n  city = VALUES(city),\n  local_page_type = VALUES(local_page_type),\n  local_advantages_json = VALUES(local_advantages_json),\n  nearby_cities_json = VALUES(nearby_cities_json),\n  status = VALUES(status),\n  published_at = VALUES(published_at);\n`;

  await fs.writeFile(outputPath, sql, 'utf8');
  console.log(`Fichier généré : ${outputPath}`);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
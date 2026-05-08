function escapeHtml(value: string) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

export function plainTextToHtml(value?: string) {
  if (!value?.trim()) {
    return '';
  }

  return value
    .split(/\n{2,}/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean)
    .map((paragraph) => `<p>${escapeHtml(paragraph).replaceAll('\n', '<br />')}</p>`)
    .join('');
}

export function normalizeRichText(value?: string) {
  if (!value?.trim()) {
    return '';
  }

  return /<[^>]+>/.test(value) ? value : plainTextToHtml(value);
}
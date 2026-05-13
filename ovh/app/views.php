<?php

declare(strict_types=1);

function cms_public_image_path(string $src): string
{
  $src = trim($src);

  if ($src === '' || preg_match('#^(https?:)?//#i', $src) === 1 || str_starts_with($src, 'data:')) {
    return '';
  }

  $path = parse_url($src, PHP_URL_PATH);
  if (!is_string($path) || $path === '') {
    return '';
  }

  $path = '/' . ltrim($path, '/');

  return str_starts_with($path, '/uploads/') ? $path : '';
}

function cms_image_url(string $src): string
{
  return preg_match('#^(https?:)?//#i', $src) === 1 || str_starts_with($src, 'data:') ? $src : cms_url($src);
}

function cms_optimized_image_srcset(string $src): string
{
  $path = cms_public_image_path($src);
  if ($path === '') {
    return '';
  }

  $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
  if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    return '';
  }

  $directory = trim((string) dirname($path), '/');
  if ($directory === '.' || $directory === '') {
    return '';
  }

  $relativeDirectory = preg_replace('#^uploads/?#', '', $directory) ?? '';
  $optimizedDirectory = '/uploads/optimized' . ($relativeDirectory !== '' ? '/' . $relativeDirectory : '');
  $name = (string) pathinfo($path, PATHINFO_FILENAME);
  $root = rtrim((string) cms_config()['root'], '/');
  $variants = [];

  foreach (glob($root . $optimizedDirectory . '/' . $name . '-*.webp') ?: [] as $file) {
    if (preg_match('/-(\d+)\.webp$/', $file, $matches) !== 1) {
      continue;
    }

    $variants[(int) $matches[1]] = cms_url($optimizedDirectory . '/' . basename($file)) . ' ' . (int) $matches[1] . 'w';
  }

  ksort($variants);

  return implode(', ', array_values($variants));
}

function cms_image_dimensions(string $src): array
{
  $path = cms_public_image_path($src);
  if ($path === '') {
    return [];
  }

  $imagePath = rtrim((string) cms_config()['root'], '/') . $path;
  if (!is_file($imagePath)) {
    return [];
  }

  $dimensions = @getimagesize($imagePath);
  if (!is_array($dimensions) || empty($dimensions[0]) || empty($dimensions[1])) {
    return [];
  }

  return ['width' => (string) $dimensions[0], 'height' => (string) $dimensions[1]];
}

function cms_html_attributes(array $attributes): string
{
  $html = '';

  foreach ($attributes as $name => $value) {
    if ($value === false || $value === null) {
      continue;
    }

    if ($value === true) {
      $html .= ' ' . cms_h((string) $name);
      continue;
    }

    $html .= ' ' . cms_h((string) $name) . '="' . cms_h((string) $value) . '"';
  }

  return $html;
}

function cms_render_image(string $src, string $alt = '', array $attributes = []): void
{
  $src = trim($src);
  if ($src === '') {
    return;
  }

  $sizes = (string) ($attributes['sizes'] ?? '100vw');
  $srcset = cms_optimized_image_srcset($src);
  unset($attributes['sizes']);

  $attributes = array_merge([
    'src' => cms_image_url($src),
    'alt' => $alt,
    'loading' => 'lazy',
    'decoding' => 'async',
  ], cms_image_dimensions($src), $attributes);

  if ($sizes !== '') {
    $attributes['sizes'] = $sizes;
  }

  if ($srcset !== '') {
    ?><picture><source type="image/webp" srcset="<?= cms_h($srcset) ?>" sizes="<?= cms_h($sizes) ?>"><img<?= cms_html_attributes($attributes) ?>></picture><?php
    return;
  }

  ?><img<?= cms_html_attributes($attributes) ?>><?php
}

function cms_render_admin_start(string $title, string $currentNav): void
{
    $admin = cms_current_admin();
    $flash = cms_consume_flash();
    $navigationGroups = [
        'Pilotage' => [
            '/admin' => ['label' => 'Tableau de bord', 'icon' => 'M3 12 12 4l9 8M5 10v10h14V10'],
        ],
        'Contenus' => [
            '/admin/pages' => ['label' => 'Pages principales', 'icon' => 'M6 4h9l5 5v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm9 0v5h5'],
            '/admin/local-pages' => ['label' => 'Pages locales', 'icon' => 'M12 22s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z'],
            '/admin/blog' => ['label' => 'Blog', 'icon' => 'M5 4h11l4 4v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm3 7h9M8 15h9M8 19h6'],
            '/admin/media' => ['label' => 'Images', 'icon' => 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 12 5-5 4 4 3-3 4 4M9 11a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z'],
        ],
        'Conversion' => [
            '/admin/estimation-requests' => ['label' => 'Estimations', 'icon' => 'M3 17v3h18v-3M7 13l3-3 3 3 5-5M5 17V9m4 8V13m4 4V11m4 6V8'],
        ],
        'Acquisition' => [
          '/admin/seo' => ['label' => 'SEO local', 'icon' => 'M4 19V5m0 14h16M8 15l3-3 3 2 4-6M18 8h2v2'],
        ],
        'Système' => [
            '/admin/settings' => ['label' => 'Réglages', 'icon' => 'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm8.94 3a8.94 8.94 0 0 0-.13-1.5l2.05-1.6-2-3.46-2.42.97a8.94 8.94 0 0 0-2.6-1.5L15.5 2h-7l-.34 2.91a8.94 8.94 0 0 0-2.6 1.5l-2.42-.97-2 3.46 2.05 1.6c-.08.49-.13.99-.13 1.5s.05 1.01.13 1.5L1.14 15.1l2 3.46 2.42-.97a8.94 8.94 0 0 0 2.6 1.5L8.5 22h7l.34-2.91a8.94 8.94 0 0 0 2.6-1.5l2.42.97 2-3.46-2.05-1.6c.08-.49.13-.99.13-1.5Z'],
            '/admin/users' => ['label' => 'Utilisateurs', 'icon' => 'M16 14a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0 2c-2.67 0-8 1.34-8 4v2h10m6-6c-.7 0-1.5.07-2.34.2 1.46.96 2.34 2.16 2.34 3.8v2h8v-2c0-2.66-5.33-4-8-4Z'],
        ],
    ];

    $adminName = (string) ($admin['name'] ?? '');
    $adminEmail = (string) ($admin['email'] ?? '');
    $initials = '';
    foreach (preg_split('/\s+/', trim($adminName)) ?: [] as $part) {
        if ($part !== '' && mb_strlen($initials) < 2) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }
    if ($initials === '' && $adminEmail !== '') {
        $initials = mb_strtoupper(mb_substr($adminEmail, 0, 1));
    }
    ?>
    <!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title><?= cms_h($title) ?></title>
        <link rel="preload" href="<?= cms_h(cms_url('/assets/fonts/inter-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/admin.css')) ?>?v=<?= cms_h((string) (@filemtime(__DIR__ . '/../assets/admin.css') ?: time())) ?>">
      </head>
      <body class="admin-shell">
        <aside class="admin-sidebar">
          <div class="admin-sidebar-top">
            <a class="admin-brand" href="<?= cms_h(cms_url('/admin')) ?>">
              <span class="admin-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 11 12 4l9 7"/>
                  <path d="M5 10v10h14V10"/>
                  <path d="M10 20v-6h4v6"/>
                </svg>
              </span>
              <span class="admin-brand-text">
                <strong>Auxois CMS</strong>
                <small>Admin</small>
              </span>
            </a>
          </div>
          <details class="admin-menu-details" open>
            <summary class="admin-menu-summary">
              <span>Menu admin</span>
              <span class="admin-menu-current"><?= cms_h($title) ?></span>
            </summary>
          <nav class="admin-nav" aria-label="Navigation principale">
            <?php foreach ($navigationGroups as $groupLabel => $items): ?>
              <div class="admin-nav-group">
                <p class="admin-nav-heading"><?= cms_h($groupLabel) ?></p>
                <?php foreach ($items as $href => $entry): ?>
                  <a class="admin-nav-link<?= $currentNav === $href ? ' is-active' : '' ?>" href="<?= cms_h(cms_url($href)) ?>">
                    <span class="admin-nav-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="<?= cms_h($entry['icon']) ?>"/>
                      </svg>
                    </span>
                    <span><?= cms_h($entry['label']) ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </nav>
          <div class="admin-sidebar-footer">
            <div class="admin-user-card">
              <span class="admin-user-avatar" aria-hidden="true"><?= cms_h($initials !== '' ? $initials : 'A') ?></span>
              <div class="admin-user-info">
                <strong><?= cms_h($adminName !== '' ? $adminName : 'Administrateur') ?></strong>
                <?php if ($adminEmail !== ''): ?><small><?= cms_h($adminEmail) ?></small><?php endif; ?>
              </div>
            </div>
            <a class="admin-logout" href="<?= cms_h(cms_url('/admin/logout')) ?>">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
              Se déconnecter
            </a>
          </div>
          </details>
        </aside>
        <main class="admin-main">
          <?php if ($flash): ?>
            <div class="flash flash-<?= cms_h($flash['type']) ?>"><?= cms_h($flash['message']) ?></div>
          <?php endif; ?>
    <?php
}

function cms_render_admin_end(): void
{
    ?>
        </main>
        <script>
          (() => {
            const menu = document.querySelector('.admin-menu-details');
            if (!menu) return;

            const media = window.matchMedia('(max-width: 1080px)');
            const syncMenu = () => {
              menu.open = !media.matches;
            };

            syncMenu();
            if (typeof media.addEventListener === 'function') {
              media.addEventListener('change', syncMenu);
            } else if (typeof media.addListener === 'function') {
              media.addListener(syncMenu);
            }
          })();
        </script>
      </body>
    </html>
    <?php
}

function cms_render_media_picker_field(string $label, string $name, string $value = '', string $placeholder = '/uploads/cms/nom-image.webp'): void
{
    $previewValue = trim($value);
    $previewSrc = '';

    if ($previewValue !== '') {
        $previewSrc = preg_match('#^https?://#i', $previewValue) === 1 ? $previewValue : cms_url($previewValue);
    }
    ?>
    <div class="media-picker-field">
      <span class="media-picker-label"><?= cms_h($label) ?></span>
      <div class="media-picker-input-row">
        <input name="<?= cms_h($name) ?>" value="<?= cms_h($value) ?>" placeholder="<?= cms_h($placeholder) ?>" class="js-media-picker-input">
        <button type="button" class="ghost-button" data-open-media-picker>Choisir</button>
        <button type="button" class="secondary-button media-picker-clear" data-clear-media-picker>Vider</button>
      </div>
      <div class="media-picker-preview<?= $previewSrc !== '' ? ' has-image' : '' ?>">
        <?php if ($previewSrc !== ''): ?>
          <img src="<?= cms_h($previewSrc) ?>" alt="Aperçu du média sélectionné" class="js-media-picker-preview" loading="lazy" decoding="async">
        <?php else: ?>
          <div class="media-picker-preview-empty js-media-picker-empty">Aucune image sélectionnée</div>
          <img src="" alt="Aperçu du média sélectionné" class="js-media-picker-preview" hidden>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

function cms_render_media_picker_modal(array $mediaItems): void
{
    ?>
    <div class="media-library-modal" id="media-library-modal" hidden>
      <div class="media-library-backdrop" data-close-media-picker></div>
      <div class="media-library-dialog" role="dialog" aria-modal="true" aria-labelledby="media-library-title">
        <div class="media-library-head">
          <div>
            <p class="eyebrow">Médiathèque</p>
            <h2 id="media-library-title">Choisir une image</h2>
          </div>
          <button type="button" class="secondary-button" data-close-media-picker>Fermer</button>
        </div>
        <label class="media-library-search">
          Rechercher
          <input type="search" id="media-library-search" placeholder="Nom, alt, chemin...">
        </label>
        <div class="media-library-grid" id="media-library-grid">
          <?php foreach ($mediaItems as $item): ?>
            <?php if (!cms_media_is_available($item)) {
                continue;
            } ?>
            <?php
              $rawUrl = cms_media_public_url($item);
              $displayUrl = preg_match('#^https?://#i', $rawUrl) === 1 ? $rawUrl : cms_url($rawUrl);
              $title = (string) ($item['title'] ?: $item['original_name']);
              $alt = trim((string) ($item['alt_text'] ?? ''));
              $searchBlob = strtolower(trim($title . ' ' . $alt . ' ' . $rawUrl));
            ?>
            <button
              type="button"
              class="media-library-item"
              data-media-url="<?= cms_h($rawUrl) ?>"
              data-media-src="<?= cms_h($displayUrl) ?>"
              data-media-search="<?= cms_h($searchBlob) ?>"
            >
              <img src="<?= cms_h($displayUrl) ?>" alt="<?= cms_h($alt !== '' ? $alt : $title) ?>" loading="lazy" decoding="async">
              <span class="media-library-item-title"><?= cms_h($title) ?></span>
              <span class="media-library-item-path"><?= cms_h($rawUrl) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
}

function cms_render_media_picker_assets(array $mediaItems): void
{
    cms_render_media_picker_modal($mediaItems);
    ?>
    <script>
      const mediaPickerModal = document.getElementById('media-library-modal');
      const mediaPickerSearch = document.getElementById('media-library-search');
      const mediaPickerGrid = document.getElementById('media-library-grid');
      let activeMediaField = null;

      window.cmsSyncMediaPreview = (field) => {
        const input = field?.querySelector('.js-media-picker-input');
        const image = field?.querySelector('.js-media-picker-preview');
        const preview = field?.querySelector('.media-picker-preview');
        const empty = field?.querySelector('.js-media-picker-empty');
        const value = (input?.value || '').trim();

        if (!input || !image || !preview) {
          return;
        }

        if (!value) {
          image.hidden = true;
          image.setAttribute('src', '');
          preview.classList.remove('has-image');
          if (empty) {
            empty.hidden = false;
          }
          return;
        }

        image.hidden = false;
        image.setAttribute('src', value.startsWith('http') ? value : `<?= cms_h(cms_url('/')) ?>${value.replace(/^\//, '')}`);
        preview.classList.add('has-image');
        if (empty) {
          empty.hidden = true;
        }
      };

      const openMediaPicker = (field) => {
        activeMediaField = field;
        mediaPickerModal.hidden = false;
        document.body.classList.add('media-library-open');
        if (mediaPickerSearch) {
          mediaPickerSearch.value = '';
          mediaPickerGrid?.querySelectorAll('.media-library-item').forEach((item) => {
            item.hidden = false;
          });
          mediaPickerSearch.focus();
        }
      };

      const closeMediaPicker = () => {
        mediaPickerModal.hidden = true;
        document.body.classList.remove('media-library-open');
        activeMediaField = null;
      };

      document.addEventListener('click', (event) => {
        const openTrigger = event.target.closest('[data-open-media-picker]');
        if (openTrigger) {
          const field = openTrigger.closest('.media-picker-field');
          if (field) {
            openMediaPicker(field);
          }
          return;
        }

        const clearTrigger = event.target.closest('[data-clear-media-picker]');
        if (clearTrigger) {
          const field = clearTrigger.closest('.media-picker-field');
          const input = field?.querySelector('.js-media-picker-input');
          if (input) {
            input.value = '';
            window.cmsSyncMediaPreview(field);
          }
          return;
        }

        const closeTrigger = event.target.closest('[data-close-media-picker]');
        if (closeTrigger) {
          closeMediaPicker();
          return;
        }

        const mediaItem = event.target.closest('.media-library-item');
        if (mediaItem && activeMediaField) {
          const input = activeMediaField.querySelector('.js-media-picker-input');
          if (input) {
            input.value = mediaItem.dataset.mediaUrl || '';
            const image = activeMediaField.querySelector('.js-media-picker-preview');
            if (image) {
              image.setAttribute('src', mediaItem.dataset.mediaSrc || '');
            }
            window.cmsSyncMediaPreview(activeMediaField);
          }
          closeMediaPicker();
        }
      });

      mediaPickerSearch?.addEventListener('input', () => {
        const term = mediaPickerSearch.value.trim().toLowerCase();
        mediaPickerGrid?.querySelectorAll('.media-library-item').forEach((item) => {
          item.hidden = term !== '' && !(item.dataset.mediaSearch || '').includes(term);
        });
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mediaPickerModal && !mediaPickerModal.hidden) {
          closeMediaPicker();
        }
      });

      document.querySelectorAll('.media-picker-field').forEach((field) => window.cmsSyncMediaPreview(field));
    </script>
    <?php
}

function cms_render_page_form(array $page, string $mode, string $actionLabel, ?string $seoAiAdvice = null): void
{
    $sections = cms_page_sections($page);
    $advantages = implode("\n", cms_json_list($page['local_advantages_json'] ?? '[]'));
    $nearbyCities = implode("\n", cms_json_list($page['nearby_cities_json'] ?? '[]'));
  $seoFaq = implode("\n", array_map(static fn (array $faq): string => (string) ($faq['question'] ?? '') . ' | ' . (string) ($faq['answer'] ?? ''), cms_json_objects((string) ($page['seo_faq_json'] ?? '[]'))));
  $seoLinks = implode("\n", array_map(static fn (array $link): string => (string) ($link['label'] ?? '') . ' | ' . (string) ($link['url'] ?? ''), cms_json_objects((string) ($page['seo_internal_links_json'] ?? '[]'))));
  $seoScore = cms_seo_page_score($page);
  $seoKeywordsToIntegrate = $mode === 'local' && !empty($page['id']) ? cms_seo_keywords_for_page($page) : [];
  $seoPrimaryConflicts = $mode === 'local' ? cms_seo_primary_page_conflicts($page) : [];
    $seoAiApplyFields = $mode === 'local' ? [
      ['key' => 'title', 'label' => 'Title SEO', 'target' => 'title', 'tab' => 'seo', 'large' => false, 'priority' => true, 'explain' => 'Un titre SEO doit combiner le mot-clé principal, la ville et une promesse claire.'],
      ['key' => 'meta', 'label' => 'Meta description', 'target' => 'meta_description', 'tab' => 'seo', 'large' => false, 'priority' => true, 'explain' => 'La description doit donner envie de cliquer tout en restant locale et concrète.'],
      ['key' => 'h1', 'label' => 'H1', 'target' => 'h1', 'tab' => 'seo', 'large' => false, 'priority' => true, 'explain' => 'Le H1 doit confirmer immédiatement le sujet et la commune travaillée.'],
      ['key' => 'intro', 'label' => 'Introduction', 'target' => 'intro_html', 'tab' => 'content', 'large' => true, 'priority' => true, 'explain' => 'L’introduction doit expliquer le besoin, la ville et la valeur de votre accompagnement.'],
      ['key' => 'content', 'label' => 'Contenu principal', 'target' => 'section_text', 'tab' => 'local', 'large' => true, 'priority' => false, 'explain' => 'Le contenu principal doit apporter des éléments locaux utiles et rassurants.'],
      ['key' => 'cta', 'label' => 'CTA final', 'target' => 'cta_text', 'tab' => 'faq', 'large' => true, 'priority' => false, 'explain' => 'Le CTA doit transformer la lecture en action simple et adaptée au projet immobilier.'],
      ['key' => 'cta_button', 'label' => 'Texte du bouton CTA', 'target' => 'cta_button_label', 'tab' => 'faq', 'large' => false, 'priority' => false, 'explain' => 'Le bouton doit être court, explicite et orienté action.'],
      ['key' => 'faq', 'label' => 'FAQ', 'target' => 'seo_faq', 'tab' => 'faq', 'large' => true, 'priority' => true, 'explain' => 'La FAQ répond aux questions locales et renforce la longue traîne SEO.'],
      ['key' => 'local_sections', 'label' => 'Sections locales', 'target' => 'local_sections', 'tab' => 'local', 'large' => true, 'priority' => false, 'explain' => 'Une section locale doit citer des critères concrets du secteur, sans rester générique.'],
    ] : [];
    $localPageTypes = $mode === 'local' ? cms_local_page_types() : [];
    $localPageType = (string) ($page['local_page_type'] ?? '');
    $localPageTypeLabel = $localPageTypes[$localPageType] ?? ($localPageType !== '' ? $localPageType : 'Page locale');
    $status = (string) ($page['status'] ?? 'draft');
    $statusLabel = $status === 'published' ? 'Publiée' : 'Brouillon';
    $seoAiChecklistItems = [];
    if ($seoAiAdvice !== null && trim($seoAiAdvice) !== '') {
      foreach (preg_split('/\R+/', trim($seoAiAdvice)) ?: [] as $line) {
        $item = trim(preg_replace('/^[-*•\d.)\s]+/', '', trim($line)) ?? '');
        if ($item === '' || mb_strlen($item) < 18 || preg_match('/^(verdict|pourquoi|actions? prioritaires?)\b/i', $item)) {
          continue;
        }

        $seoAiChecklistItems[] = $item;
        if (count($seoAiChecklistItems) >= 6) {
          break;
        }
      }
    }
    $seoNowItems = $seoAiChecklistItems;
    foreach ($seoScore['checks'] as $check) {
      if (empty($check['ok'])) {
        $seoNowItems[] = (string) $check['label'];
      }
    }
    foreach (array_slice($seoKeywordsToIntegrate, 0, 3) as $keywordSuggestion) {
      $seoNowItems[] = 'Intégrer le mot-clé “' . (string) ($keywordSuggestion['keyword'] ?? '') . '” dans le contenu.';
    }
    $seoNowItems = array_slice(array_values(array_unique(array_filter($seoNowItems))), 0, 5);
    $mediaItems = cms_media_items();
    ?>
    <form method="post" class="admin-form-stack seo-editor-form" novalidate>
      <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
      <input type="hidden" name="section_count" id="section-count" value="<?= count($sections) ?>">

      <header class="seo-edit-sticky-header">
        <div class="seo-edit-header-main">
          <a class="seo-edit-back" href="<?= $mode === 'local' ? '/admin/local-pages' : '/admin/pages' ?>">← Retour à la liste</a>
          <div>
            <p class="eyebrow">SEO local</p>
            <h1><?= cms_h((string) ($page['title'] ?: $actionLabel)) ?></h1>
          </div>
          <div class="seo-edit-meta-row">
            <?php if ($mode === 'local'): ?>
              <span class="status-badge"><?= cms_h((string) ($page['city'] ?: 'Ville à compléter')) ?></span>
              <span class="status-badge"><?= cms_h($localPageTypeLabel) ?></span>
            <?php endif; ?>
            <span class="status-badge status-<?= cms_h($status) ?>"><?= cms_h($statusLabel) ?></span>
            <span class="seo-score-mini score-<?= cms_h($seoScore['score'] >= 75 ? 'ready' : ($seoScore['score'] >= 55 ? 'ok' : 'low')) ?>"><?= (int) $seoScore['score'] ?>/100 · <?= cms_h((string) $seoScore['label']) ?></span>
          </div>
        </div>
        <div class="seo-edit-header-actions">
          <button class="primary-button" type="submit">Enregistrer</button>
          <a class="secondary-button" href="<?= cms_h(cms_url((string) ($page['slug'] ?: '/'))) ?>" target="_blank" rel="noreferrer">Voir la page</a>
          <?php if ($mode === 'local'): ?>
            <button class="secondary-button" type="submit" name="action" value="seo_ai_advice" <?= cms_openai_configured() ? '' : 'disabled' ?>>Conseil IA SEO</button>
          <?php endif; ?>
        </div>
      </header>

      <nav class="seo-edit-tabs" role="tablist" aria-label="Navigation édition SEO">
        <button type="button" class="seo-edit-tab is-active" data-admin-tab="essential">Essentiel</button>
        <button type="button" class="seo-edit-tab" data-admin-tab="content">Contenu</button>
        <button type="button" class="seo-edit-tab" data-admin-tab="seo">SEO</button>
        <button type="button" class="seo-edit-tab" data-admin-tab="local">Sections locales</button>
        <button type="button" class="seo-edit-tab" data-admin-tab="faq">FAQ & CTA</button>
        <button type="button" class="seo-edit-tab" data-admin-tab="advanced">Avancé</button>
      </nav>

      <aside class="seo-edit-sidebar">
        <section class="panel seo-sidebar-card">
          <p class="eyebrow">Ce que vous pouvez appliquer maintenant</p>
          <?php if ($mode === 'local' && $seoAiApplyFields !== []): ?>
            <div class="seo-apply-now-list" data-ai-sidebar-actions>
              <?php foreach (array_slice($seoAiApplyFields, 0, 5) as $field): ?>
                <article class="seo-apply-now-item" data-ai-sidebar-item="<?= cms_h($field['key']) ?>">
                  <strong><?= cms_h($field['label']) ?> proposé</strong>
                  <span data-ai-sidebar-excerpt="<?= cms_h($field['key']) ?>">Suggestion à générer</span>
                  <div class="seo-apply-now-actions"><button class="ghost-button" type="button" data-ai-view="<?= cms_h($field['key']) ?>">Voir</button><button class="secondary-button" type="button" data-ai-quick-apply="<?= cms_h($field['key']) ?>">Appliquer</button></div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p>La base SEO est propre. Relisez le contenu, puis enregistrez vos ajustements.</p>
          <?php endif; ?>
        </section>
        <section class="panel seo-sidebar-card seo-preview-card seo-live-preview">
          <p class="eyebrow">Aperçu Google</p>
          <strong data-seo-preview-title><?= cms_h((string) $page['title']) ?></strong>
          <span data-seo-preview-url><?= cms_h(cms_absolute_url((string) ($page['slug'] ?: '/'))) ?></span>
          <p data-seo-preview-description><?= cms_h((string) $page['meta_description']) ?></p>
        </section>
        <section class="panel seo-sidebar-card">
          <p class="eyebrow">Checklist rapide</p>
          <div class="seo-checklist compact">
            <?php foreach ($seoScore['checks'] as $check): ?>
              <span class="seo-check <?= !empty($check['ok']) ? 'is-ok' : 'is-missing' ?>"><?= !empty($check['ok']) ? 'OK' : 'À améliorer' ?> · <?= cms_h((string) $check['label']) ?></span>
            <?php endforeach; ?>
          </div>
        </section>
        <section class="panel seo-sidebar-card">
          <p class="eyebrow">Actions rapides</p>
          <div class="admin-actions vertical-actions">
            <button class="primary-button" type="submit">Enregistrer</button>
            <?php if ($mode === 'local'): ?>
              <button class="secondary-button" type="submit" name="action" value="seo_ai_test" <?= cms_openai_configured() ? '' : 'disabled' ?>>Tester IA</button>
              <button class="ghost-button" type="button" data-admin-tab-jump="seo">Voir recommandations</button>
            <?php endif; ?>
          </div>
        </section>
      </aside>

      <section class="panel seo-tab-panel is-active" data-admin-tab-panel="essential seo advanced">
        <div class="panel-head">
          <div>
            <p class="eyebrow">SEO local</p>
            <h1><?= cms_h($actionLabel) ?></h1>
          </div>
          <div class="seo-edit-head-actions">
            <?php if ($mode === 'local'): ?>
              <button class="ghost-button" type="submit" name="action" value="seo_ai_test" <?= cms_openai_configured() ? '' : 'disabled' ?>>Tester IA</button>
              <button class="secondary-button" type="submit" name="action" value="seo_ai_advice" <?= cms_openai_configured() ? '' : 'disabled' ?>>Conseil IA SEO</button>
            <?php endif; ?>
            <div class="seo-score-pill score-<?= cms_h($seoScore['score'] >= 75 ? 'ready' : ($seoScore['score'] >= 55 ? 'ok' : 'low')) ?>"><strong><?= (int) $seoScore['score'] ?>/100</strong><span><?= cms_h((string) $seoScore['label']) ?></span></div>
          </div>
        </div>
        <div class="grid two-cols seo-edit-grid">
          <label>
            Titre SEO
            <input name="title" value="<?= cms_h((string) $page['title']) ?>" required data-seo-title-source>
          </label>
          <label>
            Slug / URL
            <input name="slug" value="<?= cms_h((string) $page['slug']) ?>" required data-seo-slug-source>
          </label>
          <label class="full">
            Meta description
            <textarea name="meta_description" rows="3" required data-seo-description-source><?= cms_h((string) $page['meta_description']) ?></textarea>
          </label>
          <label>
            H1
            <input name="h1" value="<?= cms_h((string) $page['h1']) ?>" required data-seo-h1-source>
          </label>
          <label>
            Statut public
            <select name="status">
              <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
              <option value="published" <?= ($page['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
          </label>
          <?php if ($mode === 'local'): ?>
            <label>
              Intention SEO
              <select name="seo_intent">
                <?php foreach (cms_seo_page_intents() as $value => $label): ?>
                  <option value="<?= cms_h($value) ?>" <?= (string) ($page['seo_intent'] ?? '') === $value ? 'selected' : '' ?>><?= cms_h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Statut SEO
              <select name="seo_status">
                <?php foreach (cms_seo_page_statuses() as $value => $label): ?>
                  <option value="<?= cms_h($value) ?>" <?= (string) ($page['seo_status'] ?? 'draft') === $value ? 'selected' : '' ?>><?= cms_h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Mot clé principal
              <input name="seo_focus_keyword" value="<?= cms_h((string) ($page['seo_focus_keyword'] ?? '')) ?>" placeholder="estimation immobilière Arnay-le-Duc">
            </label>
            <label>
              Modèle utilisé
              <input name="seo_template" value="<?= cms_h((string) ($page['seo_template'] ?? '')) ?>" placeholder="estimation-locale">
            </label>
            <label class="full">
              Mots clés secondaires
              <textarea name="seo_secondary_keywords" rows="3" placeholder="Une ligne = un mot clé secondaire"><?= cms_h((string) ($page['seo_secondary_keywords'] ?? '')) ?></textarea>
            </label>
            <label class="toggle-field full">
              <span>Page principale pour cette commune et cette intention</span>
              <input type="checkbox" name="seo_is_primary" value="1" <?= !empty($page['seo_is_primary']) ? 'checked' : '' ?>>
            </label>
          <?php endif; ?>
          <label class="toggle-field">
            <span>Indexable</span>
            <input type="checkbox" name="is_indexable" value="1" <?= (int) ($page['is_indexable'] ?? 1) === 1 ? 'checked' : '' ?>>
          </label>
        </div>
        <?php if ($mode === 'local'): ?>
          <div class="seo-preview-card">
            <p class="eyebrow">Aperçu Google</p>
            <strong data-seo-preview-title><?= cms_h((string) $page['title']) ?></strong>
            <span data-seo-preview-url><?= cms_h(cms_absolute_url((string) ($page['slug'] ?: '/'))) ?></span>
            <p data-seo-preview-description><?= cms_h((string) $page['meta_description']) ?></p>
          </div>
          <div class="seo-live-indicators">
            <span class="seo-check" data-seo-indicator="title">Titre SEO</span>
            <span class="seo-check" data-seo-indicator="description">Meta description</span>
            <span class="seo-check" data-seo-indicator="slug">Slug</span>
            <span class="seo-check" data-seo-indicator="h1">H1</span>
          </div>
          <div class="seo-checklist">
            <?php foreach ($seoScore['checks'] as $check): ?>
              <span class="seo-check <?= !empty($check['ok']) ? 'is-ok' : 'is-missing' ?>"><?= !empty($check['ok']) ? '✓' : '!' ?> <?= cms_h((string) $check['label']) ?></span>
            <?php endforeach; ?>
          </div>
          <?php if (!cms_openai_configured()): ?>
            <div class="seo-editor-alert"><strong>IA SEO non connectée :</strong> ajoutez OPENAI_API_KEY et OPENAI_MODEL dans le .env OVH pour activer le bouton “Conseil IA SEO”.</div>
          <?php endif; ?>
          <?php if ($mode === 'local'): ?>
            <section class="seo-ai-apply-panel" id="seo-ai-apply-panel" aria-labelledby="seo-ai-apply-title">
              <div class="panel-title-row seo-ai-apply-head">
                <div>
                  <p class="eyebrow">Assistant d’édition</p>
                  <h2 id="seo-ai-apply-title">Suggestions IA à appliquer</h2>
                  <p>Comparez le contenu actuel et la proposition, puis appliquez uniquement ce que vous validez.</p>
                </div>
                <div class="admin-actions seo-ai-generator-actions">
                  <button class="primary-button" type="button" data-ai-generate-all>Générer les suggestions IA</button>
                  <button class="secondary-button" type="button" data-ai-generate-missing>Générer seulement les champs manquants</button>
                </div>
              </div>
              <div class="seo-ai-save-warning" id="seo-ai-save-warning" hidden>Modifications en attente d’enregistrement.</div>
              <div class="seo-ai-priority-box">
                <div>
                  <h3>À appliquer en priorité</h3>
                  <p>Les cartes prioritaires s’ouvrent par défaut. Rien n’est remplacé sans action de votre part.</p>
                </div>
                <ol class="seo-ai-priority-list" data-ai-priority-list>
                  <?php foreach (array_values(array_filter($seoAiApplyFields, static fn (array $field): bool => (bool) $field['priority'])) as $index => $field): ?>
                    <li data-ai-priority="<?= cms_h($field['key']) ?>"><span><?= cms_h($field['label']) ?> à vérifier</span><button class="ghost-button" type="button" data-ai-view="<?= cms_h($field['key']) ?>">Voir la suggestion</button></li>
                  <?php endforeach; ?>
                </ol>
              </div>
              <div class="admin-actions seo-ai-bulk-actions">
                <button class="secondary-button" type="button" data-ai-apply-selected>Appliquer les suggestions sélectionnées</button>
                <span class="seo-ai-action-feedback" id="seo-ai-action-feedback" role="status" aria-live="polite" hidden></span>
              </div>
              <div class="seo-ai-apply-card-list">
                <?php foreach ($seoAiApplyFields as $index => $field): ?>
                  <details class="seo-ai-apply-card" data-ai-card="<?= cms_h($field['key']) ?>" data-ai-target="<?= cms_h($field['target']) ?>" data-ai-large="<?= $field['large'] ? '1' : '0' ?>" data-ai-tab="<?= cms_h($field['tab']) ?>" <?= $index < 3 ? 'open' : '' ?>>
                    <summary>
                      <label class="seo-ai-select-field" onclick="event.stopPropagation();"><input type="checkbox" data-ai-select="<?= cms_h($field['key']) ?>"> <span class="sr-only">Sélectionner <?= cms_h($field['label']) ?></span></label>
                      <div>
                        <h3><?= cms_h($field['label']) ?></h3>
                        <p data-ai-card-summary="<?= cms_h($field['key']) ?>">Contenu actuel ↓ suggestion IA ↓ appliquer</p>
                      </div>
                      <div class="seo-ai-card-badges"><span class="seo-ai-status-badge" data-ai-status="<?= cms_h($field['key']) ?>">Suggestion disponible</span><span class="secondary-button as-label">Détail</span></div>
                    </summary>
                    <div class="seo-ai-card-body">
                      <div class="seo-ai-compare-grid">
                        <section class="seo-ai-value-box is-current">
                          <strong>Actuel</strong>
                          <div class="seo-ai-value" data-ai-current="<?= cms_h($field['key']) ?>">Aucun contenu actuellement</div>
                        </section>
                        <section class="seo-ai-value-box is-suggestion">
                          <strong>Suggestion IA</strong>
                          <div class="seo-ai-value" data-ai-suggestion="<?= cms_h($field['key']) ?>">Aucune suggestion générée pour le moment</div>
                        </section>
                      </div>
                      <p class="seo-ai-why"><strong>Pourquoi :</strong> <?= cms_h($field['explain']) ?></p>
                      <?php if ($field['large']): ?>
                        <details class="seo-ai-diff" data-ai-diff-wrapper="<?= cms_h($field['key']) ?>">
                          <summary>Voir les différences</summary>
                          <div class="seo-ai-diff-grid"><div><strong>Version actuelle</strong><div data-ai-diff-current="<?= cms_h($field['key']) ?>"></div></div><div><strong>Version proposée</strong><div data-ai-diff-suggestion="<?= cms_h($field['key']) ?>"></div></div></div>
                        </details>
                      <?php endif; ?>
                      <div class="seo-ai-edit-box" data-ai-edit-box="<?= cms_h($field['key']) ?>" hidden>
                        <label>Modifier la suggestion avant d’appliquer<textarea rows="6" data-ai-edit-value="<?= cms_h($field['key']) ?>"></textarea></label>
                        <button class="primary-button" type="button" data-ai-apply-edited="<?= cms_h($field['key']) ?>">Appliquer ma version</button>
                      </div>
                      <div class="admin-actions seo-ai-card-actions">
                        <button class="primary-button" type="button" data-ai-apply="<?= cms_h($field['key']) ?>">Appliquer</button>
                        <button class="ghost-button" type="button" data-ai-copy="<?= cms_h($field['key']) ?>">Copier</button>
                        <button class="ghost-button" type="button" data-ai-regenerate="<?= cms_h($field['key']) ?>">Régénérer</button>
                        <button class="secondary-button" type="button" data-ai-edit="<?= cms_h($field['key']) ?>">Modifier avant d’appliquer</button>
                      </div>
                      <div class="seo-ai-card-feedback" data-ai-card-feedback="<?= cms_h($field['key']) ?>" hidden></div>
                    </div>
                  </details>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
          <?php if ($seoAiAdvice !== null && trim($seoAiAdvice) !== ''): ?>
            <div class="seo-ai-advice">
              <div class="panel-title-row"><div><h2>Checklist IA actionnable</h2><p>Traitez les recommandations une par une, puis enregistrez la page.</p></div><button class="secondary-button" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('seo-ai-advice-raw').value)">Copier les recommandations</button></div>
              <textarea id="seo-ai-advice-raw" hidden><?= cms_h($seoAiAdvice) ?></textarea>
              <div class="seo-ai-task-list">
                <?php foreach (($seoAiChecklistItems !== [] ? $seoAiChecklistItems : ['Relire les recommandations IA complètes ci-dessous.']) as $index => $item): ?>
                  <article class="seo-ai-task" data-seo-ai-task>
                    <span class="seo-ai-task-check" aria-hidden="true"></span>
                    <div><strong><?= cms_h($item) ?></strong><span><?= $index < 2 ? 'Important' : ($index < 4 ? 'Moyen' : 'Optionnel') ?></span></div>
                    <div class="seo-ai-task-actions"><button class="ghost-button" type="button" data-ai-task-action="apply">Marquer comme fait</button><button class="ghost-button" type="button" data-ai-task-action="ignore">Ignorer</button><button class="secondary-button" type="button" data-ai-task-action="manual">Modifier manuellement</button></div>
                  </article>
                <?php endforeach; ?>
              </div>
              <details class="seo-ai-raw-details"><summary>Voir le diagnostic IA complet</summary><div class="seo-ai-advice-content"><?= nl2br(cms_h($seoAiAdvice)) ?></div></details>
              <div class="admin-actions seo-ai-action-row"><button class="ghost-button seo-ai-apply-button" type="button" data-seo-ai-apply="title">Appliquer le meta title proposé</button><button class="ghost-button seo-ai-apply-button" type="button" data-seo-ai-apply="meta">Appliquer la meta description proposée</button><button class="ghost-button seo-ai-apply-button" type="button" data-seo-ai-apply="faq">Ajouter les FAQ proposées</button><button class="ghost-button seo-ai-apply-button" type="button" data-seo-ai-apply="done">Marquer comme traité</button></div>
              <div class="seo-ai-action-feedback" data-seo-ai-legacy-feedback role="status" aria-live="polite" hidden></div>
            </div>
          <?php endif; ?>
          <?php if ($seoPrimaryConflicts !== []): ?>
            <div class="seo-editor-alert is-warning"><strong>Risque de doublon SEO :</strong> une autre page principale existe déjà pour cette commune et cette intention. Gardez une seule page principale et différenciez les angles secondaires.</div>
          <?php endif; ?>
          <?php if ($seoKeywordsToIntegrate !== []): ?>
            <div class="seo-keyword-suggestions">
              <div class="panel-title-row"><div><h2>Mots-clés à intégrer</h2><p>Requêtes Search Console associées à cette page cible. À utiliser dans le contenu, la FAQ et le maillage interne.</p></div></div>
              <div class="table-wrap seo-compact-table-wrap">
                <table class="admin-table seo-compact-table">
                  <thead><tr><th>Mot clé</th><th>Opportunité</th><th>Perf.</th><th>Action</th></tr></thead>
                  <tbody>
                    <?php foreach ($seoKeywordsToIntegrate as $keywordSuggestion): ?>
                      <?php $keywordSeo = $keywordSuggestion['_seo']; ?>
                      <tr>
                        <td data-label="Mot clé"><strong><?= cms_h((string) $keywordSuggestion['keyword']) ?></strong><small><?= cms_h((string) $keywordSeo['intent_label']) ?> · <?= cms_h((string) ($keywordSeo['city'] ?? 'Secteur')) ?></small></td>
                        <td data-label="Opportunité"><span class="seo-next-action is-<?= cms_h((string) $keywordSeo['opportunity']['tone']) ?>"><?= cms_h((string) $keywordSeo['opportunity']['label']) ?></span></td>
                        <td data-label="Perf."><?= (int) ($keywordSuggestion['clicks'] ?? 0) ?> clics<br><small><?= (int) ($keywordSuggestion['impressions'] ?? 0) ?> impr. · pos. <?= $keywordSuggestion['position'] !== null ? number_format((float) $keywordSuggestion['position'], 1, ',', ' ') : '—' ?></small></td>
                        <td data-label="Action"><span class="seo-next-action is-<?= cms_h((string) $keywordSeo['action']['tone']) ?>"><?= cms_h((string) $keywordSeo['action']['label']) ?></span><small><?= cms_h((string) $keywordSeo['action']['detail']) ?></small></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>

      <?php if ($mode === 'local'): ?>
        <section class="panel seo-tab-panel" data-admin-tab-panel="local essential">
          <div class="grid two-cols">
            <label>
              Ville
              <input name="city" value="<?= cms_h((string) $page['city']) ?>" required>
            </label>
            <label>
              Type de page locale
              <select name="local_page_type" required>
                <?php foreach (cms_local_page_types() as $value => $label): ?>
                  <option value="<?= cms_h($value) ?>" <?= (string) ($page['local_page_type'] ?? '') === $value ? 'selected' : '' ?>><?= cms_h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Atouts locaux
              <textarea name="local_advantages" rows="5" placeholder="Une ligne = un avantage"><?= cms_h($advantages) ?></textarea>
            </label>
            <label>
              Villes proches
              <textarea name="nearby_cities" rows="5" placeholder="Une ligne = une ville"><?= cms_h($nearbyCities) ?></textarea>
            </label>
          </div>
        </section>
      <?php endif; ?>

        <?php if ($mode === 'local'): ?>
          <section class="panel seo-tab-panel" data-admin-tab-panel="seo faq">
            <div class="panel-head compact">
              <div>
                <p class="eyebrow">FAQ & maillage</p>
                <h2>Blocs SEO complémentaires</h2>
              </div>
            </div>
            <div class="grid two-cols">
              <label>
                FAQ locale
                <textarea name="seo_faq" rows="7" placeholder="Question | Réponse"><?= cms_h($seoFaq) ?></textarea>
              </label>
              <label>
                Liens internes
                <textarea name="seo_internal_links" rows="7" placeholder="Libellé | /url"><?= cms_h($seoLinks) ?></textarea>
              </label>
              <label class="full">
                Notes d’optimisation
                <textarea name="seo_notes" rows="4" placeholder="Angles à renforcer, risque de duplication, prochaine action..."><?= cms_h((string) ($page['seo_notes'] ?? '')) ?></textarea>
              </label>
            </div>
          </section>
        <?php endif; ?>

      <section class="panel seo-tab-panel" data-admin-tab-panel="content essential">
        <div class="grid two-cols">
          <label>
            Titre hero
            <input name="hero_title" value="<?= cms_h((string) $page['hero_title']) ?>" required>
          </label>
          <?php cms_render_media_picker_field('Image hero', 'hero_image', (string) ($page['hero_image'] ?? '')); ?>
          <label class="full">
            Sous-titre hero
            <textarea name="hero_subtitle" rows="4" required><?= cms_h((string) $page['hero_subtitle']) ?></textarea>
          </label>
          <label class="full">
            Alt image hero
            <input name="hero_image_alt" value="<?= cms_h((string) $page['hero_image_alt']) ?>">
          </label>
        </div>
      </section>

      <section class="panel seo-tab-panel" data-admin-tab-panel="content">
        <div class="panel-head compact">
          <div>
            <p class="eyebrow">Contenu</p>
            <h2>Introduction</h2>
          </div>
        </div>
        <div class="rich-editor" data-target="intro_html"></div>
        <textarea hidden id="intro_html" name="intro_html"><?= cms_h((string) $page['intro_html']) ?></textarea>
      </section>

      <section class="panel seo-tab-panel" data-admin-tab-panel="local">
        <div class="panel-head compact">
          <div>
            <p class="eyebrow">Blocs</p>
            <h2>Sections de contenu</h2>
          </div>
          <button type="button" class="ghost-button" id="add-section">Ajouter un bloc</button>
        </div>

        <div id="sections-container" class="section-stack">
          <?php foreach ($sections as $index => $section): ?>
            <?php cms_render_section_editor($index, $section); ?>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel seo-tab-panel" data-admin-tab-panel="faq advanced">
        <div class="grid two-cols">
          <label>
            Titre CTA
            <input name="cta_title" value="<?= cms_h((string) $page['cta_title']) ?>" required>
          </label>
          <label>
            Bouton CTA
            <input name="cta_button_label" value="<?= cms_h((string) $page['cta_button_label']) ?>" required>
          </label>
          <label>
            URL CTA
            <input name="cta_button_url" value="<?= cms_h((string) $page['cta_button_url']) ?>" required>
          </label>
          <label class="full">
            Texte CTA
            <div class="rich-editor" data-target="cta_text"></div>
            <textarea hidden id="cta_text" name="cta_text"><?= cms_h((string) $page['cta_text']) ?></textarea>
          </label>
        </div>
      </section>

      <div class="admin-actions sticky-actions">
        <button class="primary-button" type="submit">Enregistrer</button>
        <a class="secondary-button" href="<?= cms_h(cms_url((string) ($page['slug'] ?: '/'))) ?>" target="_blank" rel="noreferrer">Voir la page</a>
      </div>
    </form>

    <template id="section-template">
      <?php cms_render_section_editor('__INDEX__', ['eyebrow' => '', 'title' => '', 'text' => '<p></p>', 'image' => '', 'imageAlt' => '', 'buttonLabel' => '', 'buttonUrl' => '', 'items' => [], 'stats' => []]); ?>
    </template>

    <?php cms_render_media_picker_assets($mediaItems); ?>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    <script>
      const toolbar = [['bold', 'italic', 'underline'], [{ list: 'bullet' }], ['link'], ['clean']];

      const initEditors = (scope = document) => {
        scope.querySelectorAll('.rich-editor').forEach((container) => {
          if (container.dataset.ready === '1') {
            return;
          }

          const targetId = container.dataset.target;
          const textarea = document.getElementById(targetId);
          if (!textarea) {
            return;
          }

          const quill = new Quill(container, { theme: 'snow', modules: { toolbar } });
          quill.root.innerHTML = textarea.value || '<p></p>';
          container.__quill = quill;
          textarea.__quill = quill;
          quill.on('text-change', () => {
            textarea.value = quill.root.innerHTML;
          });
          container.dataset.ready = '1';
        });
      };

      initEditors();

      const container = document.getElementById('sections-container');
      const countField = document.getElementById('section-count');
      const template = document.getElementById('section-template');
      document.getElementById('add-section')?.addEventListener('click', () => {
        const index = Number(countField.value || '0');
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const node = wrapper.firstElementChild;
        if (!node) {
          return;
        }
        container.appendChild(node);
        countField.value = String(index + 1);
        initEditors(node);
        node.querySelectorAll('.media-picker-field').forEach((field) => window.cmsSyncMediaPreview(field));
      });

      container?.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-remove-section]');
        if (!trigger) {
          return;
        }
        event.preventDefault();
        const section = trigger.closest('.section-editor');
        if (section && container.children.length > 1) {
          section.remove();
        }
      });

      const activateAdminTab = (tabName) => {
        document.querySelectorAll('[data-admin-tab]').forEach((tab) => {
          tab.classList.toggle('is-active', tab.dataset.adminTab === tabName);
        });
        document.querySelectorAll('[data-admin-tab-panel]').forEach((panel) => {
          const tabs = (panel.dataset.adminTabPanel || '').split(/\s+/);
          panel.classList.toggle('is-active', tabs.includes(tabName));
        });
      };

      document.querySelectorAll('[data-admin-tab]').forEach((tab) => {
        tab.addEventListener('click', () => activateAdminTab(tab.dataset.adminTab));
      });
      document.querySelectorAll('[data-admin-tab-jump]').forEach((trigger) => {
        trigger.addEventListener('click', () => activateAdminTab(trigger.dataset.adminTabJump));
      });
      activateAdminTab('essential');

      const titleSource = document.querySelector('[data-seo-title-source]');
      const slugSource = document.querySelector('[data-seo-slug-source]');
      const descriptionSource = document.querySelector('[data-seo-description-source]');
      const h1Source = document.querySelector('[data-seo-h1-source]');
      const setIndicator = (name, label, tone) => {
        document.querySelectorAll(`[data-seo-indicator="${name}"]`).forEach((indicator) => {
          indicator.className = `seo-check ${tone === 'ok' ? 'is-ok' : 'is-missing'}`;
          indicator.textContent = label;
        });
      };
      const updateSeoPreview = () => {
        const title = titleSource?.value.trim() || 'Titre SEO à compléter';
        const slug = (slugSource?.value.trim() || '/').replace(/^\/+/, '');
        const description = descriptionSource?.value.trim() || 'Meta description à compléter.';
        document.querySelectorAll('[data-seo-preview-title]').forEach((node) => { node.textContent = title; });
        document.querySelectorAll('[data-seo-preview-url]').forEach((node) => { node.textContent = `https://immobilier-auxois-morvan.fr/${slug}`; });
        document.querySelectorAll('[data-seo-preview-description]').forEach((node) => { node.textContent = description; });

        setIndicator('title', title.length < 35 ? 'Titre SEO · À améliorer' : (title.length > 70 ? 'Titre SEO · Trop long' : 'Titre SEO · OK'), title.length >= 35 && title.length <= 70 ? 'ok' : 'warning');
        setIndicator('description', description.length < 110 ? 'Meta description · Trop courte' : (description.length > 165 ? 'Meta description · Trop longue' : 'Meta description · OK'), description.length >= 110 && description.length <= 165 ? 'ok' : 'warning');
        setIndicator('slug', slug.length >= 8 && !slug.includes(' ') ? 'Slug · OK' : 'Slug · À améliorer', slug.length >= 8 && !slug.includes(' ') ? 'ok' : 'warning');
        setIndicator('h1', h1Source?.value.trim() ? 'H1 · OK' : 'H1 · Manquant', h1Source?.value.trim() ? 'ok' : 'warning');
      };
      [titleSource, slugSource, descriptionSource, h1Source].forEach((field) => field?.addEventListener('input', updateSeoPreview));
      updateSeoPreview();

      const aiSuggestions = new Map();
      const htmlToText = (value) => {
        const node = document.createElement('div');
        node.innerHTML = value || '';
        return (node.textContent || '').replace(/\s+/g, ' ').trim();
      };
      const escapeHtml = (value) => String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
      const asParagraph = (value) => {
        const trimmed = String(value || '').trim();
        return trimmed.startsWith('<') ? trimmed : `<p>${escapeHtml(trimmed).replace(/\n{2,}/g, '</p><p>').replace(/\n/g, '<br>')}</p>`;
      };
      const truncate = (value, length = 120) => {
        const text = htmlToText(value) || String(value || '').replace(/\s+/g, ' ').trim();
        return text.length > length ? `${text.slice(0, length - 1)}…` : text;
      };
      const cleanEmptyValue = (value) => {
        const text = htmlToText(value);
        return text === '' || text === ' ' ? '' : String(value || '').trim();
      };
      const getFirstSectionText = () => document.querySelector('textarea[name="section_text[]"]');
      const getFirstSectionTitle = () => document.querySelector('input[name="section_title[]"]');
      const getFieldNode = (target) => {
        if (target === 'intro_html') return document.getElementById('intro_html');
        if (target === 'section_text') return getFirstSectionText();
        if (target === 'cta_text') return document.getElementById('cta_text');
        if (target === 'local_sections') return getFirstSectionText();
        return document.querySelector(`[name="${target}"]`);
      };
      const getFieldValue = (key) => {
        const card = document.querySelector(`[data-ai-card="${key}"]`);
        const target = card?.dataset.aiTarget || key;
        if (target === 'local_sections') {
          const title = getFirstSectionTitle()?.value || '';
          const text = getFirstSectionText()?.value || '';
          return `${title}${title && text ? '\n' : ''}${htmlToText(text)}`.trim();
        }
        return getFieldNode(target)?.value || '';
      };
      const setRichFieldValue = (field, value) => {
        if (!field) return;
        field.value = value;
        if (field.__quill) {
          field.__quill.root.innerHTML = value || '<p></p>';
        } else {
          const editor = document.querySelector(`.rich-editor[data-target="${field.id}"]`);
          if (editor?.__quill) editor.__quill.root.innerHTML = value || '<p></p>';
        }
        field.dispatchEvent(new Event('input', { bubbles: true }));
      };
      const setFieldValue = (key, value) => {
        const card = document.querySelector(`[data-ai-card="${key}"]`);
        const target = card?.dataset.aiTarget || key;
        if (target === 'local_sections') {
          const sectionTitle = getFirstSectionTitle();
          const sectionText = getFirstSectionText();
          const city = getContext().city;
          if (sectionTitle && !sectionTitle.value.trim()) {
            sectionTitle.value = `Repères locaux à ${city}`;
            sectionTitle.dispatchEvent(new Event('input', { bubbles: true }));
          }
          setRichFieldValue(sectionText, asParagraph(value));
          return sectionText;
        }
        const field = getFieldNode(target);
        if (!field) return null;
        if (['intro_html', 'section_text', 'cta_text'].includes(target)) {
          setRichFieldValue(field, asParagraph(value));
        } else {
          field.value = value;
          field.dispatchEvent(new Event('input', { bubbles: true }));
        }
        return field;
      };
      const getContext = () => {
        const city = (document.querySelector('[name="city"]')?.value || 'Auxois-Morvan').trim() || 'Auxois-Morvan';
        const pageType = document.querySelector('[name="local_page_type"]')?.value || 'marche-local';
        const focus = (document.querySelector('[name="seo_focus_keyword"]')?.value || '').trim();
        const title = titleSource?.value.trim() || '';
        const keyword = focus || (pageType === 'vendre-maison' ? `vendre maison ${city}` : (pageType === 'viager' ? `viager ${city}` : (pageType === 'estimation-immobiliere' ? `estimation immobilière ${city}` : `immobilier ${city}`)));
        return { city, pageType, focus, title, keyword };
      };
      const templateFamily = () => {
        const { pageType } = getContext();
        if (pageType === 'estimation-immobiliere') return 'estimation';
        if (pageType === 'vendre-maison') return 'vente';
        if (pageType === 'viager') return 'viager';
        return 'general';
      };
      const fallbackSuggestion = (key, variant = 0) => {
        const { city, keyword, title } = getContext();
        const family = templateFamily();
        const suffix = variant > 0 ? ' Votre conseiller local.' : '';
        const packs = {
          estimation: {
            title: `Estimation immobilière à ${city} | Avis de valeur local`,
            meta: `Vous souhaitez connaître la valeur de votre maison ou appartement à ${city} ? Obtenez une estimation immobilière locale, personnalisée et adaptée au marché de l’Auxois.`,
            h1: `Estimation immobilière à ${city}`,
            intro: `Vous envisagez de vendre une maison ou un appartement à ${city} ? Avant de fixer un prix de vente, il est essentiel de connaître la valeur réelle de votre bien sur le marché local. En tant que conseiller immobilier du secteur, je vous accompagne avec une estimation personnalisée, basée sur les caractéristiques de votre bien, les ventes récentes et la réalité du marché immobilier local.`,
            content: `À ${city}, le prix d’un bien dépend autant de ses caractéristiques que de son emplacement précis, de son état, des extérieurs, des dépendances et de la demande actuelle. L’objectif est de construire un avis de valeur argumenté, utile pour décider d’un prix de mise en vente cohérent et défendre ce prix auprès des acquéreurs.`,
            cta: `Vous souhaitez connaître la valeur de votre bien à ${city} ? Contactez-moi pour obtenir une estimation personnalisée et échanger sur votre projet de vente.`,
            cta_button: 'Demander mon estimation',
            faq: `Comment obtenir une estimation immobilière à ${city} ? | Une estimation fiable commence par l’analyse du bien, de son emplacement, de son état, des références comparables et de la demande locale.\nUne estimation en ligne suffit-elle à ${city} ? | Elle donne un premier repère, mais un avis de valeur local permet d’ajuster le prix selon les caractéristiques réelles du bien.\nPourquoi faire estimer avant de vendre ? | Cela évite un prix trop haut qui bloque les visites ou trop bas qui fragilise votre projet de vente.`,
            local_sections: `À ${city}, une estimation doit tenir compte des maisons anciennes, des extérieurs, des travaux éventuels, des accès, des services proches et du niveau de demande des acquéreurs. Cette lecture locale permet de positionner le bien avec plus de précision qu’une simple moyenne de prix.`
          },
          vente: {
            title: `Vendre sa maison à ${city} | Estimation et accompagnement`,
            meta: `Vous préparez la vente d’une maison à ${city} ? Bénéficiez d’une estimation locale, d’une stratégie de prix claire et d’un accompagnement jusqu’à la signature.`,
            h1: `Vendre sa maison à ${city}`,
            intro: `Vendre une maison à ${city} demande une préparation précise : estimation, valorisation, diffusion, qualification des acheteurs et suivi des offres. Je vous accompagne pour présenter votre bien avec clarté et construire une stratégie adaptée au marché local.`,
            content: `La réussite d’une vente repose sur un prix cohérent, des arguments solides et une présentation qui rassure les acheteurs. À ${city}, il faut tenir compte des surfaces, des extérieurs, des travaux, de l’environnement et du calendrier de vente pour éviter les visites inutiles et défendre le prix demandé.`,
            cta: `Vous souhaitez vendre votre maison à ${city} ? Parlons de votre projet, de votre calendrier et du prix de vente le plus cohérent.`,
            cta_button: 'Préparer ma vente',
            faq: `Comment vendre une maison à ${city} au bon prix ? | Le prix doit croiser les références locales, l’état du bien, les extérieurs, les travaux et la demande active.\nFaut-il estimer avant de publier une annonce ? | Oui, l’estimation évite de perdre du temps avec un prix mal positionné.\nComment attirer des acheteurs qualifiés ? | Une annonce claire, un prix cohérent et un suivi sérieux des contacts limitent les visites peu pertinentes.`,
            local_sections: `Pour vendre efficacement à ${city}, la section locale doit expliquer les attentes des acheteurs, les atouts du secteur, la place de l’estimation et les étapes d’accompagnement : préparation, diffusion, visites, négociation et signature.`
          },
          viager: {
            title: `Vendre en viager à ${city} | Bouquet, rente et conseil local`,
            meta: `Projet de vente en viager à ${city} ? Comprenez le bouquet, la rente, la sécurité de l’opération et l’accompagnement local adapté à votre patrimoine.`,
            h1: `Vendre en viager à ${city}`,
            intro: `Le viager à ${city} peut être une solution patrimoniale intéressante pour sécuriser un revenu, anticiper l’avenir et transmettre dans de bonnes conditions. Chaque projet demande une étude précise du bien, du bouquet, de la rente et du cadre juridique.`,
            content: `Une vente en viager doit être expliquée avec pédagogie : valeur du bien, bouquet, rente, occupation, sécurité du vendeur et profil des acquéreurs. À ${city}, l’accompagnement local permet d’évaluer la cohérence du projet et de présenter l’opération avec sérieux.`,
            cta: `Vous envisagez un viager à ${city} ? Échangeons sur votre situation et les solutions possibles pour votre projet patrimonial.`,
            cta_button: 'Étudier mon projet viager',
            faq: `Comment vendre en viager à ${city} ? | Il faut évaluer le bien, définir le bouquet, la rente, les conditions d’occupation et sécuriser le cadre de vente.\nLe viager est-il adapté à tous les biens ? | Non, il dépend du bien, de la situation du vendeur et de l’attractivité pour les acquéreurs.\nPourquoi se faire accompagner localement ? | Un interlocuteur local aide à valoriser le bien et à expliquer clairement l’opération.`,
            local_sections: `À ${city}, une section viager doit expliquer simplement le fonctionnement du bouquet, de la rente, de l’occupation et de la sécurité du vendeur, avec un vocabulaire rassurant et patrimonial.`
          },
          general: {
            title: `Immobilier à ${city} | Achat, vente et estimation locale`,
            meta: `Découvrez les repères utiles pour l’immobilier à ${city} : achat, vente, estimation, marché local, quartiers et communes proches.`,
            h1: `Immobilier à ${city}`,
            intro: `Le marché immobilier à ${city} varie selon l’emplacement, le type de bien, l’état général, les extérieurs et la demande locale. Cette page vous aide à mieux comprendre les repères utiles avant un achat, une vente ou une estimation.`,
            content: `Pour analyser l’immobilier local, il faut regarder les biens réellement comparables, les délais de vente, les attentes des acquéreurs, les communes proches et les facteurs qui influencent la valeur. Cette approche permet de prendre une décision plus sereine pour vendre, acheter ou estimer.`,
            cta: `Un projet immobilier à ${city} ? Contactez-moi pour obtenir une lecture locale et des conseils adaptés à votre situation.`,
            cta_button: 'Parler de mon projet',
            faq: `Comment évolue l’immobilier à ${city} ? | Le marché dépend de l’offre, de la demande, des typologies de biens, des accès et de l’état général des logements.\nQuels critères influencent le prix à ${city} ? | L’emplacement, les surfaces, les extérieurs, les travaux et les références comparables sont déterminants.\nPeut-on obtenir une estimation locale ? | Oui, une estimation personnalisée permet d’aller au-delà des moyennes automatiques.`,
            local_sections: `À ${city}, la section locale peut présenter les typologies de biens, les communes proches, les critères de valeur, la demande des acheteurs et les points à vérifier avant de vendre ou d’acheter.`
          }
        };
        const suggestion = packs[family]?.[key] || packs.general[key] || title || keyword;
        return variant > 0 && ['title', 'h1', 'cta_button'].includes(key) === false ? `${suggestion}${suffix}` : suggestion;
      };
      const parseAdviceSuggestion = (key) => {
        const raw = document.getElementById('seo-ai-advice-raw')?.value || '';
        if (!raw.trim()) return '';
        const labels = {
          title: ['Title SEO', 'Titre SEO', 'Meta title'],
          meta: ['Meta description', 'Description SEO'],
          h1: ['H1'],
          intro: ['Introduction'],
          content: ['Contenu principal', 'Contenu'],
          cta: ['CTA final', 'Texte CTA', 'CTA'],
          cta_button: ['Texte du bouton CTA', 'Bouton CTA'],
          faq: ['FAQ', 'Questions fréquentes'],
          local_sections: ['Section locale', 'Sections locales']
        }[key] || [];
        const lines = raw.split(/\r?\n/).map((line) => line.replace(/^[\-*•\d.)\s]+/, '').trim()).filter(Boolean);
        for (let index = 0; index < lines.length; index += 1) {
          const line = lines[index];
          const matched = labels.some((label) => line.toLowerCase().startsWith(label.toLowerCase()) || line.toLowerCase().includes(`${label.toLowerCase()} :`));
          if (!matched) continue;
          const inline = line.replace(new RegExp(`^(?:${labels.map((label) => label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})\\s*[:：-]?\\s*`, 'i'), '').trim();
          if (inline && !labels.includes(inline)) return inline.replace(/^['“”"]|['“”"]$/g, '');
          const next = lines[index + 1] || '';
          if (next) return next.replace(/^['“”"]|['“”"]$/g, '');
        }
        return '';
      };
      const classifyField = (key) => {
        const current = cleanEmptyValue(getFieldValue(key));
        const text = htmlToText(current) || current;
        const suggestion = aiSuggestions.get(key) || '';
        if (!current) return 'Manquant';
        if (suggestion) return 'Suggestion prête';
        if (['meta', 'intro', 'content', 'faq'].includes(key) && text.length < (key === 'meta' ? 110 : 180)) return 'Trop court';
        if (['title', 'h1'].includes(key) && text.length < 25) return 'À améliorer';
        return 'Correct';
      };
      const renderAiCard = (key) => {
        const current = cleanEmptyValue(getFieldValue(key));
        const suggestion = aiSuggestions.get(key) || '';
        const currentText = current ? (htmlToText(current) || current) : 'Aucun contenu actuellement';
        const suggestionText = suggestion ? (htmlToText(suggestion) || suggestion) : 'Aucune suggestion générée pour le moment';
        document.querySelectorAll(`[data-ai-current="${key}"], [data-ai-diff-current="${key}"]`).forEach((node) => { node.textContent = currentText; });
        document.querySelectorAll(`[data-ai-suggestion="${key}"], [data-ai-diff-suggestion="${key}"]`).forEach((node) => { node.textContent = suggestionText; });
        document.querySelectorAll(`[data-ai-status="${key}"]`).forEach((node) => { node.textContent = classifyField(key); });
        document.querySelectorAll(`[data-ai-sidebar-excerpt="${key}"]`).forEach((node) => { node.textContent = suggestion ? truncate(suggestion, 86) : 'Suggestion à générer'; });
        const applyButton = document.querySelector(`[data-ai-apply="${key}"]`);
        if (applyButton) applyButton.textContent = current ? 'Remplacer' : 'Appliquer';
        const regenButton = document.querySelector(`[data-ai-regenerate="${key}"]`);
        if (regenButton) regenButton.textContent = suggestion ? 'Régénérer' : 'Générer une suggestion';
        const summary = document.querySelector(`[data-ai-card-summary="${key}"]`);
        if (summary) summary.textContent = suggestion ? `Proposition prête : ${truncate(suggestion, 96)}` : 'Contenu actuel ↓ suggestion IA ↓ appliquer';
      };
      const renderPriorityList = () => {
        document.querySelectorAll('[data-ai-priority]').forEach((item) => {
          const key = item.dataset.aiPriority;
          const label = item.querySelector('span');
          if (label) label.textContent = `${document.querySelector(`[data-ai-card="${key}"] h3`)?.textContent || 'Champ'} · ${classifyField(key)}`;
        });
      };
      const generateSuggestion = (key, forceVariant = 0) => {
        const fromAdvice = forceVariant === 0 ? parseAdviceSuggestion(key) : '';
        aiSuggestions.set(key, fromAdvice || fallbackSuggestion(key, forceVariant));
        renderAiCard(key);
        renderPriorityList();
      };
      const generateAllSuggestions = (missingOnly = false) => {
        document.querySelectorAll('[data-ai-card]').forEach((card) => {
          const key = card.dataset.aiCard;
          if (!key) return;
          if (missingOnly && cleanEmptyValue(getFieldValue(key))) return;
          generateSuggestion(key);
        });
      };
      const showAiCardFeedback = (key, message, tone = 'success') => {
        const feedback = document.querySelector(`[data-ai-card-feedback="${key}"]`);
        if (feedback) {
          feedback.hidden = false;
          feedback.className = `seo-ai-card-feedback is-${tone}`;
          feedback.textContent = message;
        }
        if (aiFeedback) {
          aiFeedback.hidden = false;
          aiFeedback.className = `seo-ai-action-feedback is-${tone}`;
          aiFeedback.textContent = message;
        }
      };
      const markDirty = () => {
        const warning = document.getElementById('seo-ai-save-warning');
        if (warning) warning.hidden = false;
      };
      const applyAiSuggestion = (key, customValue = null, options = {}) => {
        if (!aiSuggestions.has(key)) generateSuggestion(key);
        const suggestion = customValue ?? aiSuggestions.get(key) ?? '';
        if (!suggestion.trim()) {
          showAiCardFeedback(key, 'Aucune suggestion générée pour le moment.', 'warning');
          return false;
        }
        const current = cleanEmptyValue(getFieldValue(key));
        const isLarge = document.querySelector(`[data-ai-card="${key}"]`)?.dataset.aiLarge === '1';
        if (!options.skipConfirm && isLarge && current && !window.confirm('Ce champ contient déjà du texte. Voulez-vous le remplacer par la suggestion IA ?')) {
          return false;
        }
        const field = setFieldValue(key, suggestion);
        if (field) highlightField(field);
        markDirty();
        const status = document.querySelector(`[data-ai-status="${key}"]`);
        if (status) status.textContent = 'Appliqué — à enregistrer';
        showAiCardFeedback(key, 'Suggestion appliquée. Pensez à enregistrer la page.');
        renderAiCard(key);
        updateSeoPreview();
        return true;
      };
      document.querySelectorAll('[data-ai-view]').forEach((button) => {
        button.addEventListener('click', () => {
          const key = button.dataset.aiView;
          const card = document.querySelector(`[data-ai-card="${key}"]`);
          if (!card) return;
          card.open = true;
          activateAdminTab(card.dataset.aiTab || 'seo');
          window.setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 80);
        });
      });
      document.querySelectorAll('[data-ai-regenerate]').forEach((button) => {
        button.addEventListener('click', () => {
          const key = button.dataset.aiRegenerate;
          const count = Number(button.dataset.aiRegenerateCount || '0') + 1;
          button.dataset.aiRegenerateCount = String(count);
          generateSuggestion(key, count);
          showAiCardFeedback(key, 'Suggestion régénérée pour ce champ.');
        });
      });
      document.querySelectorAll('[data-ai-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
          const key = button.dataset.aiCopy;
          if (!aiSuggestions.has(key)) generateSuggestion(key);
          const value = aiSuggestions.get(key) || '';
          if (!value) {
            showAiCardFeedback(key, 'Aucune suggestion à copier.', 'warning');
            return;
          }
          try {
            await navigator.clipboard?.writeText(htmlToText(value) || value);
            showAiCardFeedback(key, 'Copié');
          } catch {
            showAiCardFeedback(key, 'Copie impossible automatiquement. Sélectionnez la suggestion manuellement.', 'warning');
          }
        });
      });
      document.querySelectorAll('[data-ai-edit]').forEach((button) => {
        button.addEventListener('click', () => {
          const key = button.dataset.aiEdit;
          if (!aiSuggestions.has(key)) generateSuggestion(key);
          const box = document.querySelector(`[data-ai-edit-box="${key}"]`);
          const textarea = document.querySelector(`[data-ai-edit-value="${key}"]`);
          if (box && textarea) {
            textarea.value = htmlToText(aiSuggestions.get(key) || '') || aiSuggestions.get(key) || '';
            box.hidden = false;
            textarea.focus();
          }
        });
      });
      document.querySelectorAll('[data-ai-apply-edited]').forEach((button) => {
        button.addEventListener('click', () => {
          const key = button.dataset.aiApplyEdited;
          const textarea = document.querySelector(`[data-ai-edit-value="${key}"]`);
          if (!textarea) return;
          aiSuggestions.set(key, textarea.value.trim());
          renderAiCard(key);
          applyAiSuggestion(key, textarea.value.trim());
        });
      });
      document.querySelectorAll('[data-ai-apply], [data-ai-quick-apply]').forEach((button) => {
        button.addEventListener('click', () => applyAiSuggestion(button.dataset.aiApply || button.dataset.aiQuickApply));
      });
      document.querySelector('[data-ai-generate-all]')?.addEventListener('click', () => {
        generateAllSuggestions(false);
        showAiCardFeedback('title', 'Toutes les suggestions importantes ont été générées.');
      });
      document.querySelector('[data-ai-generate-missing]')?.addEventListener('click', () => {
        generateAllSuggestions(true);
        showAiCardFeedback('title', 'Suggestions générées pour les champs manquants uniquement.');
      });
      document.querySelector('[data-ai-apply-selected]')?.addEventListener('click', () => {
        const selected = [...document.querySelectorAll('[data-ai-select]:checked')].map((input) => input.dataset.aiSelect).filter(Boolean);
        if (selected.length === 0) {
          if (aiFeedback) {
            aiFeedback.hidden = false;
            aiFeedback.className = 'seo-ai-action-feedback is-warning';
            aiFeedback.textContent = 'Sélectionnez au moins une suggestion avant d’appliquer.';
          }
          return;
        }
        if (!window.confirm(`Appliquer ${selected.length} suggestion(s) sélectionnée(s) ? Vous devrez ensuite enregistrer la page.`)) return;
        selected.forEach((key) => applyAiSuggestion(key, null, { skipConfirm: true }));
      });
      document.querySelectorAll('[data-ai-card]').forEach((card) => renderAiCard(card.dataset.aiCard));
      renderPriorityList();

      document.querySelectorAll('[data-ai-task-action]').forEach((button) => {
        button.addEventListener('click', () => {
          const task = button.closest('[data-seo-ai-task]');
          const action = button.dataset.aiTaskAction;
          if (!task) {
            return;
          }
          task.classList.remove('is-ignored', 'is-manual', 'is-done');
          if (action === 'ignore') {
            task.classList.add('is-ignored');
          } else if (action === 'manual') {
            task.classList.add('is-manual');
            task.scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            task.classList.add('is-done');
          }
        });
      });

      const aiAdviceRaw = document.getElementById('seo-ai-advice-raw');
      const aiFeedback = document.getElementById('seo-ai-action-feedback');
      const cleanAdviceLine = (line) => line.replace(/\*\*/g, '').replace(/^[-*•]\s*/, '').trim();
      const isNumberedAdviceHeading = (line) => /^\d+[.)]\s+\S/.test(cleanAdviceLine(line));
      const findAdviceValue = (labels) => {
        const raw = aiAdviceRaw?.value || '';
        const lines = raw.split(/\r?\n/).map((line) => line.trim());

        for (const label of labels) {
          const escaped = label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          const regex = new RegExp(`^(?:[-*•]?\\s*)?${escaped}\\s*[:：-]\\s*(.+)$`, 'i');
          const match = lines.map((line) => cleanAdviceLine(line).match(regex)).find(Boolean);
          if (match?.[1]) {
            return match[1].trim().replace(/^['“”\"]|['“”\"]$/g, '');
          }
        }

        for (let index = 0; index < lines.length; index += 1) {
          const current = cleanAdviceLine(lines[index]);
          const lower = current.toLowerCase();
          const matched = labels.some((label) => lower.includes(label.toLowerCase()));
          if (!matched) {
            continue;
          }

          const inlineValue = current.replace(/^\d+[.)]\s*/, '').replace(new RegExp(`^(?:${labels.map((label) => label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})(?:\\s+(?:proposée?|recommandée?))?\\s*[:：-]?\\s*`, 'i'), '').trim();
          if (inlineValue && !labels.some((label) => inlineValue.toLowerCase() === label.toLowerCase())) {
            return inlineValue.replace(/^['“\”\"]|['“\”\"]$/g, '');
          }

          for (let next = index + 1; next < lines.length; next += 1) {
            const candidate = cleanAdviceLine(lines[next]);
            if (!candidate) {
              continue;
            }
            if (isNumberedAdviceHeading(candidate)) {
              break;
            }
            return candidate.replace(/^['“\”\"]|['“\”\"]$/g, '');
          }
        }

        return '';
      };
      const findAdviceParagraph = (labels) => {
        const raw = aiAdviceRaw?.value || '';
        const lines = raw.split(/\r?\n/).map((line) => line.trim());

        const direct = findAdviceValue(labels);
        if (direct) {
          return direct;
        }

        for (let index = 0; index < lines.length; index += 1) {
          const current = cleanAdviceLine(lines[index]);
          const lower = current.toLowerCase();
          if (!labels.some((label) => lower.includes(label.toLowerCase()))) {
            continue;
          }

          const paragraph = [];
          for (let next = index + 1; next < lines.length; next += 1) {
            const candidate = cleanAdviceLine(lines[next]);
            if (!candidate) {
              continue;
            }
            if (isNumberedAdviceHeading(candidate)) {
              break;
            }
            paragraph.push(candidate);
          }

          if (paragraph.length > 0) {
            return paragraph.join(' ').replace(/^['“\”\"]|['“\”\"]$/g, '');
          }
        }

        return '';
      };
      const findFaqAdvice = () => {
        const raw = aiAdviceRaw?.value || '';
        const faqMatch = raw.match(/(?:FAQ|questions fréquentes)[\s\S]{0,900}/i);
        if (!faqMatch) {
          return '';
        }

        return faqMatch[0]
          .split(/\r?\n/)
          .map((line) => line.replace(/^[-*•\d.)\s]+/, '').trim())
          .filter((line) => line.includes('?'))
          .slice(0, 4)
          .map((line) => `${line} | Réponse à compléter avec votre expertise locale.`)
          .join('\n');
      };
      const showAiFeedback = (button, message, tone = 'success') => {
        button.classList.add('is-done');
        button.dataset.done = '1';
        button.innerHTML = `✓ ${button.textContent.replace(/^✓\s*/, '')}`;

        if (aiFeedback) {
          aiFeedback.hidden = false;
          aiFeedback.className = `seo-ai-action-feedback is-${tone}`;
          aiFeedback.textContent = message;
        }
      };
      const highlightField = (field) => {
        if (!field) {
          return;
        }

        field.classList.add('seo-field-updated');
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => field.classList.remove('seo-field-updated'), 2200);
      };

      document.querySelectorAll('[data-seo-ai-apply]').forEach((button) => {
        button.addEventListener('click', () => {
          const action = button.dataset.seoAiApply;

          if (action === 'title') {
            const title = findAdviceValue(['Title SEO', 'Titre SEO', 'Meta title', 'Meta title proposé', 'Meta title proposé recommandé', 'Title proposé']);
            const field = document.querySelector('[name="title"]');
            if (title && field) {
              field.value = title;
              field.dispatchEvent(new Event('input', { bubbles: true }));
              highlightField(field);
              showAiFeedback(button, 'Meta title appliqué dans le champ Titre SEO. Pensez à enregistrer la page.');
              return;
            }
            showAiFeedback(button, 'Action marquée comme faite. Aucune ligne “Title SEO : …” détectée automatiquement : recopiez la proposition puis enregistrez.', 'warning');
            return;
          }

          if (action === 'meta') {
            const description = findAdviceParagraph(['Meta description', 'Description SEO', 'Meta description proposée']);
            const field = document.querySelector('[name="meta_description"]');
            if (description && field) {
              field.value = description;
              field.dispatchEvent(new Event('input', { bubbles: true }));
              highlightField(field);
              showAiFeedback(button, 'Meta description appliquée dans le champ correspondant. Pensez à enregistrer la page.');
              return;
            }
            showAiFeedback(button, 'Action marquée comme faite. Aucune ligne “Meta description : …” détectée automatiquement : recopiez la proposition puis enregistrez.', 'warning');
            return;
          }

          if (action === 'faq') {
            const faq = findFaqAdvice();
            const field = document.querySelector('[name="seo_faq"]');
            if (faq && field) {
              field.value = `${field.value.trim()}${field.value.trim() ? '\n' : ''}${faq}`;
              highlightField(field);
              showAiFeedback(button, 'FAQ ajoutées au bloc SEO complémentaire. Relisez les réponses puis enregistrez.');
              return;
            }
            showAiFeedback(button, 'Action marquée comme faite. Aucune FAQ structurée détectée automatiquement : ajoutez les questions proposées manuellement.', 'warning');
            return;
          }

          if (action === 'done') {
            const field = document.querySelector('[name="seo_notes"]');
            const date = new Date().toLocaleDateString('fr-FR');
            if (field) {
              const note = `Conseil IA traité le ${date}.`;
              field.value = `${field.value.trim()}${field.value.trim() ? '\n' : ''}${note}`;
              highlightField(field);
            }
            document.querySelectorAll('[data-seo-ai-apply]').forEach((item) => item.classList.add('is-done'));
            showAiFeedback(button, 'Conseil IA marqué comme traité. Pensez à enregistrer la page pour conserver la note.');
          }
        });
      });
    </script>
    <?php
}

function cms_render_blog_form(array $post, string $actionLabel): void
{
    $mediaItems = cms_media_items();
    ?>
    <form method="post" class="admin-form-stack">
      <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">

      <section class="panel">
        <div class="panel-head compact">
          <div>
            <p class="eyebrow">Blog</p>
            <h1><?= cms_h($actionLabel) ?></h1>
          </div>
          <div class="status-badge status-<?= cms_h((string) ($post['status'] ?? 'draft')) ?>"><?= cms_h((string) ($post['status'] ?? 'draft')) ?></div>
        </div>
        <div class="grid two-cols">
          <label>
            Titre affiché
            <input name="title" value="<?= cms_h((string) ($post['title'] ?? '')) ?>" required>
          </label>
          <label>
            Meta title
            <input name="meta_title" value="<?= cms_h((string) ($post['meta_title'] ?? '')) ?>" required>
          </label>
          <label>
            Slug
            <input name="slug" value="<?= cms_h((string) ($post['slug'] ?? '')) ?>" required>
          </label>
          <label>
            Catégorie
            <input name="category" value="<?= cms_h((string) ($post['category'] ?? '')) ?>" required>
          </label>
          <?php cms_render_media_picker_field('Image mise en avant', 'featured_image', (string) ($post['featured_image'] ?? '')); ?>
          <label>
            Alt image
            <input name="featured_image_alt" value="<?= cms_h((string) ($post['featured_image_alt'] ?? '')) ?>">
          </label>
          <label>
            Statut
            <select name="status">
              <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
              <option value="published" <?= ($post['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
          </label>
          <label class="toggle-field">
            <span>Indexable</span>
            <input type="checkbox" name="is_indexable" value="1" <?= (int) ($post['is_indexable'] ?? 1) === 1 ? 'checked' : '' ?>>
          </label>
          <label class="full">
            Extrait
            <textarea name="excerpt" rows="4" required><?= cms_h((string) ($post['excerpt'] ?? '')) ?></textarea>
          </label>
          <label class="full">
            Meta description
            <textarea name="meta_description" rows="4" required><?= cms_h((string) ($post['meta_description'] ?? '')) ?></textarea>
          </label>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head compact">
          <div>
            <p class="eyebrow">Contenu</p>
            <h2>Corps de l'article</h2>
          </div>
        </div>
        <div class="rich-editor" data-target="content_html"></div>
        <textarea hidden id="content_html" name="content_html"><?= cms_h((string) ($post['content_html'] ?? '')) ?></textarea>
      </section>

      <div class="admin-actions sticky-actions">
        <button class="primary-button" type="submit">Enregistrer l'article</button>
        <a class="secondary-button" href="<?= cms_h(cms_url('/admin/blog')) ?>">Retour à la liste</a>
        <?php if (!empty($post['slug'])): ?>
          <a class="secondary-button" href="<?= cms_h(cms_url('/blog/' . (string) $post['slug'])) ?>" target="_blank" rel="noreferrer">Voir l'article</a>
        <?php endif; ?>
      </div>
    </form>

    <?php cms_render_media_picker_assets($mediaItems); ?>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    <script>
      const blogEditorContainer = document.querySelector('.rich-editor[data-target="content_html"]');
      const blogEditorSource = document.getElementById('content_html');

      if (blogEditorContainer && blogEditorSource) {
        const quill = new Quill(blogEditorContainer, {
          theme: 'snow',
          modules: {
            toolbar: [
              [{ header: [2, 3, false] }],
              ['bold', 'italic', 'underline'],
              [{ list: 'bullet' }, { list: 'ordered' }],
              ['link'],
              ['clean']
            ]
          }
        });

        quill.root.innerHTML = blogEditorSource.value || '<p></p>';
        quill.on('text-change', () => {
          blogEditorSource.value = quill.root.innerHTML;
        });
      }
    </script>
    <?php
}

function cms_render_section_editor($index, array $section): void
{
    $items = implode("\n", $section['items'] ?? []);
    $stats = implode("\n", array_map(static fn ($stat) => trim((string) ($stat['label'] ?? '')) . '|' . trim((string) ($stat['value'] ?? '')), $section['stats'] ?? []));
    $title = trim((string) ($section['title'] ?? ''));
    $summaryTitle = $title !== '' ? $title : 'Nouvelle section';
    $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($section['text'] ?? ''))) ?? '');
    $excerpt = $excerpt !== '' ? mb_substr($excerpt, 0, 120) : 'Aucun texte saisi pour le moment.';
    $isOpen = is_int($index) && $index === 0;
    ?>
    <details class="section-editor seo-section-card" <?= $isOpen ? 'open' : '' ?>>
      <summary class="seo-section-summary">
        <div>
          <p class="eyebrow">Bloc <?= is_numeric($index) ? ((int) $index + 1) : '' ?></p>
          <h3><?= cms_h($summaryTitle) ?></h3>
          <span><?= cms_h($excerpt) ?></span>
        </div>
        <div class="seo-section-summary-actions"><span class="secondary-button as-label">Modifier</span><button type="button" class="danger-link" data-remove-section>Supprimer</button></div>
      </summary>
      <div class="grid two-cols">
        <label>
          Surtitre
          <input name="section_eyebrow[]" value="<?= cms_h((string) ($section['eyebrow'] ?? '')) ?>">
        </label>
        <label>
          Titre
          <input name="section_title[]" value="<?= cms_h((string) ($section['title'] ?? '')) ?>" required>
        </label>
        <?php cms_render_media_picker_field('Image', 'section_image[]', (string) ($section['image'] ?? '')); ?>
        <label>
          Alt image
          <input name="section_image_alt[]" value="<?= cms_h((string) ($section['imageAlt'] ?? '')) ?>">
        </label>
        <label>
          Bouton
          <input name="section_button_label[]" value="<?= cms_h((string) ($section['buttonLabel'] ?? '')) ?>">
        </label>
        <label>
          URL bouton
          <input name="section_button_url[]" value="<?= cms_h((string) ($section['buttonUrl'] ?? '')) ?>">
        </label>
        <label>
          Liste à puces
          <textarea name="section_items[]" rows="5"><?= cms_h($items) ?></textarea>
        </label>
        <label>
          Chiffres clés
          <textarea name="section_stats[]" rows="5"><?= cms_h($stats) ?></textarea>
        </label>
        <label class="full">
          Texte
          <div class="rich-editor" data-target="section-text-<?= cms_h((string) $index) ?>"></div>
          <textarea hidden id="section-text-<?= cms_h((string) $index) ?>" name="section_text[]"><?= cms_h((string) ($section['text'] ?? '')) ?></textarea>
        </label>
      </div>
    </details>
    <?php
}

function cms_render_public_page(array $page, array $settings): void
{
    $snapshot = cms_snapshot();

    if (($page['page_key'] ?? null) === 'accueil') {
        cms_render_homepage($page, $settings, $snapshot);
        return;
    }

    if (($page['page_key'] ?? null) === 'contact') {
        cms_render_contact_page($page, $settings, $snapshot);
        return;
    }

    if (($page['page_key'] ?? null) === 'secteur') {
      cms_render_sector_page($page, $settings, $snapshot);
      return;
    }

    cms_render_standard_public_page($page, $settings, $snapshot);
}

function cms_render_blog_index_page(array $settings): void
{
    $posts = cms_public_blog_posts();
    $categories = array_values(array_unique(array_filter(array_map(static fn (array $post) => (string) ($post['category'] ?? ''), $posts))));
    $heroImage = trim((string) ($posts[0]['featured_image'] ?? '/uploads/our-experience.jpg'));
    $heroAlt = trim((string) ($posts[0]['featured_image_alt'] ?? 'Maison en pierre typique de l’Auxois Morvan'));

    cms_render_public_document_start(
        'Blog immobilier | ' . (string) $settings['site_name'],
        'Conseils, analyses et guides immobiliers pour vendre, acheter ou estimer un bien en Auxois et Morvan.',
      true,
      [],
      ['preload_image' => $heroImage, 'preload_image_sizes' => '(max-width: 1023px) 100vw, 46vw']
    );
    cms_render_public_header($settings, '/blog');
    ?>
    <main>
      <section class="section section-hero">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow">Conseils et analyses</p>
            <h1>Blog immobilier de l'Auxois Morvan</h1>
            <p class="hero-text">Des articles sobres, lisibles et utiles pour comprendre le marché, préparer une vente et avancer dans votre projet immobilier en Auxois et dans le Morvan.</p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Demander une estimation</a>
            </div>
          </div>
          <div class="home-hero-side">
            <div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><?php cms_render_image($heroImage, $heroAlt, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 46vw']); ?><?php endif; ?></div>
            <div class="home-stats-grid">
              <div class="home-stat-card"><p><?= count($posts) ?></p><span>Articles publiés</span></div>
              <div class="home-stat-card"><p><?= cms_h(implode(' · ', array_slice($categories, 0, 3))) ?></p><span>Thématiques</span></div>
              <div class="home-stat-card"><p>Local</p><span>Angle éditorial</span></div>
              <div class="home-stat-card"><p>Pratique</p><span>Contenu orienté action</span></div>
            </div>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Articles</p>
          <h2 class="section-title">Les derniers contenus publiés</h2>
          <p class="section-subtitle">Chaque article peut désormais être piloté directement depuis l'administration OVH, avec image de couverture et contenu enrichi.</p>
          <div class="cards-grid three-cols">
            <?php foreach ($posts as $post): ?>
              <article class="blog-card">
                <a href="<?= cms_h(cms_url('/blog/' . (string) $post['slug'])) ?>">
                  <?php if (!empty($post['featured_image'])): ?>
                    <?php cms_render_image((string) $post['featured_image'], (string) (($post['featured_image_alt'] ?? '') ?: ($post['title'] ?? '')), ['sizes' => '(max-width: 767px) 100vw, 33vw']); ?>
                  <?php endif; ?>
                  <div class="blog-card-body">
                    <div class="blog-meta"><span><?= cms_h((string) $post['category']) ?></span><span class="meta-dot"></span><span><?= cms_h(cms_format_long_date((string) ($post['published_at'] ?? $post['created_at'] ?? ''))) ?></span></div>
                    <h3><?= cms_h((string) $post['title']) ?></h3>
                    <p><?= cms_h((string) $post['excerpt']) ?></p>
                    <span class="card-link-inline">Lire l'article →</span>
                  </div>
                </a>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="cta-band">
            <div>
              <p class="eyebrow">Une question après lecture ?</p>
              <h2>Prolongeons l'échange</h2>
              <div class="richtext"><p>Nous pouvons relier ces conseils à votre bien, votre secteur et votre calendrier avec un échange simple et concret.</p></div>
            </div>
            <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
          </div>
        </div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, cms_snapshot());
}

function cms_render_blog_post_page(array $post, array $settings): void
{
    $metaTitle = trim((string) ($post['meta_title'] ?? '')) ?: (string) ($post['title'] ?? '');
    $metaDescription = trim((string) ($post['meta_description'] ?? '')) ?: (string) ($post['excerpt'] ?? '');
    $image = trim((string) ($post['featured_image'] ?? ''));
  $postUrl = cms_absolute_url('/blog/' . (string) ($post['slug'] ?? ''));
  $articleData = [[
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => (string) ($post['title'] ?? $metaTitle),
    'description' => $metaDescription,
    'image' => $image !== '' ? cms_absolute_url($image) : cms_absolute_url('/uploads/auxois.jpg'),
    'datePublished' => (string) ($post['published_at'] ?? $post['created_at'] ?? ''),
    'dateModified' => (string) (($post['updated_at'] ?? '') ?: ($post['published_at'] ?? $post['created_at'] ?? '')),
    'author' => ['@type' => 'Organization', 'name' => (string) $settings['site_name']],
    'publisher' => ['@type' => 'Organization', 'name' => (string) $settings['site_name'], 'logo' => ['@type' => 'ImageObject', 'url' => cms_absolute_url('/uploads/logo-2.png')]],
    'mainEntityOfPage' => $postUrl,
  ]];

    cms_render_public_document_start(
        $metaTitle . ' | ' . (string) $settings['site_name'],
        $metaDescription,
    (int) ($post['is_indexable'] ?? 1) === 1,
    $articleData,
    ['type' => 'article', 'image' => $image !== '' ? $image : '/uploads/auxois.jpg', 'canonical' => $postUrl, 'preload_image' => $image !== '' ? $image : '/uploads/auxois.jpg', 'preload_image_sizes' => '(max-width: 1023px) 100vw, 50vw']
    );
    cms_render_public_header($settings, '/blog');
    ?>
    <main>
      <section class="section section-hero-inner">
        <div class="shell duo-grid">
          <article class="panel-card">
            <p class="eyebrow"><?= cms_h((string) ($post['category'] ?? 'Article')) ?></p>
            <h1><?= cms_h((string) ($post['title'] ?? 'Article')) ?></h1>
            <p class="panel-copy"><?= cms_h((string) ($post['excerpt'] ?? '')) ?></p>
            <div class="tags-wrap">
              <span><?= cms_h(cms_format_long_date((string) ($post['published_at'] ?? $post['created_at'] ?? ''))) ?></span>
              <span>Auxois &amp; Morvan</span>
              <span>Conseil pratique</span>
            </div>
            <div class="hero-actions">
              <a class="button secondary" href="<?= cms_h(cms_url('/blog')) ?>">Retour au blog</a>
              <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </article>
          <div class="hero-media<?= $image !== '' ? '' : ' no-image' ?>"><?php if ($image !== ''): ?><?php cms_render_image($image, (string) (($post['featured_image_alt'] ?? '') ?: ($post['title'] ?? '')), ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 50vw']); ?><?php endif; ?></div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <article class="panel-card richtext">
            <?= (string) ($post['content_html'] ?? '<p></p>') ?>
          </article>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="cta-band">
            <div>
              <p class="eyebrow">Passer à l'action</p>
              <h2>Parler de votre projet immobilier</h2>
              <div class="richtext"><p>Si cet article vous aide à mieux cadrer votre réflexion, nous pouvons prolonger l'échange autour de votre bien ou de votre secteur.</p></div>
            </div>
            <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
          </div>
        </div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, cms_snapshot());
}

function cms_render_estimation_tunnel_page(array $settings, array $formData = [], array $errors = []): void
{
    static $estimateIcons = null;
    if ($estimateIcons === null) {
        $estimateIcons = [
            // Property type
            'Maison' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.2 12 4l9 7.2" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/><path d="M10 20v-5h4v5" fill="var(--icon-accent)" stroke="var(--icon-accent)" fill-opacity="0.35"/></svg>',
            'Appartement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="7" height="18" rx="1"/><rect x="13" y="8" width="7" height="13" rx="1"/><rect x="6" y="6" width="2" height="2" fill="var(--icon-accent)" stroke="none"/><rect x="6" y="10" width="2" height="2" fill="var(--icon-accent)" stroke="none"/><rect x="6" y="14" width="2" height="2" fill="var(--icon-accent)" stroke="none"/><rect x="15" y="11" width="2" height="2" fill="var(--icon-accent-2)" stroke="none"/><rect x="15" y="15" width="2" height="2" fill="var(--icon-accent-2)" stroke="none"/></svg>',
            'Terrain' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 19 5-7 4 5 3-4 6 6"/><circle cx="17" cy="7" r="2.4" fill="var(--icon-accent)" stroke="var(--icon-accent)"/></svg>',
            'Immeuble' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="9" width="8" height="12" rx="1"/><rect x="13" y="3" width="8" height="18" rx="1"/><rect x="5" y="12" width="1.6" height="1.6" fill="var(--icon-accent)" stroke="none"/><rect x="8" y="12" width="1.6" height="1.6" fill="var(--icon-accent)" stroke="none"/><rect x="5" y="16" width="1.6" height="1.6" fill="var(--icon-accent)" stroke="none"/><rect x="8" y="16" width="1.6" height="1.6" fill="var(--icon-accent)" stroke="none"/><rect x="15" y="6" width="1.6" height="1.6" fill="var(--icon-accent-2)" stroke="none"/><rect x="17.6" y="6" width="1.6" height="1.6" fill="var(--icon-accent-2)" stroke="none"/><rect x="15" y="10" width="1.6" height="1.6" fill="var(--icon-accent-2)" stroke="none"/><rect x="17.6" y="10" width="1.6" height="1.6" fill="var(--icon-accent-2)" stroke="none"/></svg>',
            'Autre' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="1.6" fill="var(--icon-accent)" stroke="none"/><circle cx="12" cy="12" r="1.6" fill="var(--icon-accent-2)" stroke="none"/><circle cx="18" cy="12" r="1.6" fill="currentColor" stroke="none"/></svg>',
            // Rooms
            '1 ou 2' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12V8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/><path d="M3 18v-4h18v4" fill="var(--icon-accent)" fill-opacity="0.25"/><path d="M3 18v2M21 18v2"/></svg>',
            '3' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="1"/><path d="M6 9h12M6 15h12" stroke="var(--icon-accent)" stroke-width="1.4"/><circle cx="14.5" cy="12" r="1" fill="var(--icon-accent-2)" stroke="none"/></svg>',
            '4' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.2 12 4l9 7.2" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/><path d="M9 13h2v2H9zM13 13h2v2h-2z" fill="var(--icon-accent)" stroke="none"/><path d="M10 20v-3h4v3"/></svg>',
            '5 ou plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V11l6-4 6 4v10"/><path d="M15 21v-7h6v7" fill="var(--icon-accent)" fill-opacity="0.3"/><path d="M3 21h18"/><circle cx="9" cy="14" r="1.1" fill="var(--icon-accent-2)" stroke="none"/></svg>',
            'Je ne sais pas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1 1-1.1 1.8v.5" stroke="var(--icon-accent-2)" stroke-width="1.9"/><circle cx="12" cy="17.4" r="0.9" fill="var(--icon-accent)" stroke="none"/></svg>',
            // Condition
            'Neuf / rénové récemment' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 4 1.5 4 4 1.5-4 1.5L12 15l-1.5-4L6.5 9.5 10.5 8 12 4Z" fill="var(--icon-accent)" stroke="var(--icon-accent)" fill-opacity="0.55"/><path d="M19 17 18 19l-2 1 2 1 1 2 1-2 2-1-2-1Z" fill="var(--icon-accent-2)" stroke="var(--icon-accent-2)" fill-opacity="0.55"/></svg>',
            'Bon état' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" fill="var(--icon-accent)" stroke="var(--icon-accent)" fill-opacity="0.35"/><path d="m8.5 12.4 2.5 2.4 4.5-5" stroke="currentColor" stroke-width="2.1"/></svg>',
            'Travaux à prévoir' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a3.5 3.5 0 0 0-4.6 4.6l-6.6 6.6 2.1 2.1 6.6-6.6a3.5 3.5 0 0 0 4.6-4.6l-2.2 2.2-1.7-1.7Z" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.4"/><circle cx="6" cy="18" r="0.9" fill="var(--icon-accent-2)" stroke="none"/></svg>',
            'À rénover entièrement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m13.5 6.5 4-4 3 3-4 4" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="m13.5 6.5-9 9v3h3l9-9" fill="var(--icon-accent)" fill-opacity="0.3"/><path d="m6.5 13.5 3 3"/></svg>',
            // Living surface
            'Moins de 40 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="1" fill="var(--icon-accent)" fill-opacity="0.35" stroke="currentColor"/></svg>',
            '40 – 70 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="1" fill="var(--icon-accent)" fill-opacity="0.35" stroke="currentColor"/><path d="M5 12h14M12 5v14" stroke="var(--icon-accent-2)" stroke-width="1.3"/></svg>',
            '70 – 100 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="1" fill="var(--icon-accent)" fill-opacity="0.3" stroke="currentColor"/><path d="M12 4v16M4 12h16" stroke="var(--icon-accent-2)" stroke-width="1.3"/></svg>',
            '100 – 150 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.5"/><rect x="13" y="4" width="7" height="7" rx="1" fill="var(--icon-accent-2)" stroke="currentColor" fill-opacity="0.5"/><rect x="4" y="13" width="7" height="7" rx="1" fill="var(--icon-accent-2)" stroke="currentColor" fill-opacity="0.5"/><rect x="13" y="13" width="7" height="7" rx="1" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.5"/></svg>',
            'Plus de 150 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" stroke="var(--icon-accent-2)" stroke-width="1.9"/><rect x="9" y="9" width="6" height="6" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.45"/></svg>',
            // Land surface
            'Pas de terrain' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m6 6 12 12" stroke="var(--icon-accent-2)" stroke-width="2"/></svg>',
            'Moins de 500 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="1" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.4"/></svg>',
            '500 – 1 000 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="1" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.4"/></svg>',
            '1 000 – 2 000 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.35"/><path d="M9 3v18M3 9h18" stroke="var(--icon-accent-2)" stroke-width="1.3"/></svg>',
            'Plus de 2 000 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" stroke="var(--icon-accent-2)" stroke-width="1.9"/><rect x="9" y="9" width="6" height="6" fill="var(--icon-accent)" stroke="currentColor" fill-opacity="0.45"/></svg>',
            // Goal
            'Vendre rapidement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m13 3-8 11h6l-1 7 8-11h-6l1-7Z" fill="var(--icon-accent)" stroke="var(--icon-accent-2)" stroke-width="1.7" fill-opacity="0.55"/></svg>',
            'Vendre au meilleur prix' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m4 17 6-6 4 4 6-7" stroke="var(--icon-accent-2)" stroke-width="2"/><path d="M14 8h6v6" stroke="var(--icon-accent-2)" stroke-width="2"/><circle cx="10" cy="11" r="1.4" fill="var(--icon-accent)" stroke="none"/><circle cx="14" cy="15" r="1.4" fill="var(--icon-accent)" stroke="none"/></svg>',
            'Simple curiosité' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h6l1 3h11l-2 9H6L3 5Z" fill="var(--icon-accent)" fill-opacity="0.3"/><circle cx="9" cy="20" r="1.2" fill="var(--icon-accent-2)" stroke="none"/><circle cx="17" cy="20" r="1.2" fill="var(--icon-accent-2)" stroke="none"/></svg>',
            'Projet d’achat / vente' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h7l3 3" stroke="var(--icon-accent)" stroke-width="1.9"/><path d="M21 17h-7l-3-3" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="m17 13 4 4-4 4M7 11 3 7l4-4"/></svg>',
            'Succession' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v6" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="M5 9h14l-1 11H6L5 9Z" fill="var(--icon-accent)" fill-opacity="0.3"/><path d="M9 13v3M15 13v3"/></svg>',
            'Séparation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 12 4-4M3 12l4 4M3 12h7" stroke="var(--icon-accent)" stroke-width="1.9"/><path d="M21 12l-4-4M21 12l-4 4M14 12h7" stroke="var(--icon-accent-2)" stroke-width="1.9"/></svg>',
            'Autre situation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="1.6" fill="var(--icon-accent)" stroke="none"/><circle cx="12" cy="12" r="1.6" fill="var(--icon-accent-2)" stroke="none"/><circle cx="18" cy="12" r="1.6" fill="currentColor" stroke="none"/></svg>',
            // Timeline
            'Dès maintenant' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" fill="var(--icon-accent)" fill-opacity="0.25"/><path d="M12 7v5l3 2" stroke="var(--icon-accent-2)" stroke-width="2"/></svg>',
            'Dans les 3 mois' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 11h18" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="M8 3v4M16 3v4"/><circle cx="9" cy="15" r="1.2" fill="var(--icon-accent)" stroke="none"/><circle cx="13" cy="15" r="1.2" fill="var(--icon-accent)" stroke="none"/><circle cx="17" cy="15" r="1.2" fill="var(--icon-accent)" stroke="none"/></svg>',
            'Dans les 6 mois' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 11h18" stroke="var(--icon-accent-2)" stroke-width="1.9"/><path d="M8 3v4M16 3v4"/><circle cx="8" cy="14" r="0.9" fill="var(--icon-accent)" stroke="none"/><circle cx="12" cy="14" r="0.9" fill="var(--icon-accent)" stroke="none"/><circle cx="16" cy="14" r="0.9" fill="var(--icon-accent)" stroke="none"/><circle cx="8" cy="18" r="0.9" fill="var(--icon-accent)" stroke="none"/><circle cx="12" cy="18" r="0.9" fill="var(--icon-accent)" stroke="none"/><circle cx="16" cy="18" r="0.9" fill="var(--icon-accent)" stroke="none"/></svg>',
            'Plus tard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" fill="var(--icon-accent)" fill-opacity="0.2"/><path d="M12 7v5l3 2" stroke="var(--icon-accent-2)" stroke-width="2"/><path d="M16 4 19 7"/></svg>',
            'Je ne sais pas encore' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1 1-1.1 1.8v.5" stroke="var(--icon-accent-2)" stroke-width="1.9"/><circle cx="12" cy="17.4" r="0.9" fill="var(--icon-accent)" stroke="none"/></svg>',
        ];
    }

    $renderChoiceCard = function (string $field, string $value, string $variant = '') use ($estimateIcons): void {
        $icon = $estimateIcons[$value] ?? $estimateIcons['Autre'];
        $classes = 'estimate-choice-card' . ($variant !== '' ? ' ' . $variant : '');
        ?>
        <button type="button" class="<?= cms_h($classes) ?>" data-choice-field="<?= cms_h($field) ?>" data-choice-value="<?= cms_h($value) ?>">
          <span class="estimate-choice-icon" aria-hidden="true"><?= $icon ?></span>
          <span class="estimate-choice-label"><?= cms_h($value) ?></span>
        </button>
        <?php
    };

    $cityParam = trim((string) ($_GET['ville'] ?? ''));
    $cityLabel = $cityParam !== '' ? $cityParam : 'Mimeure, Arnay-le-Duc, Pouilly-en-Auxois, Autun et Beaune';
    $pageTitle = $cityParam !== ''
        ? 'Estimation immobilière offerte autour de ' . $cityParam
        : 'Estimation immobilière offerte autour de Mimeure, Arnay-le-Duc, Pouilly-en-Auxois, Autun et Beaune';
    $pageDescription = $cityParam !== ''
        ? 'Recevez une première analyse de valeur à ' . $cityParam . ' et alentours. Remplissez ce formulaire en quelques minutes pour être recontacté sous 24h.'
        : 'Vous envisagez de vendre une maison, un appartement ou un terrain ? Remplissez ce formulaire en quelques minutes pour recevoir une première analyse de valeur, basée sur les caractéristiques de votre bien et le marché local.';
    $defaultData = [
        'property_type' => '',
        'room_count' => '',
        'property_condition' => '',
        'living_surface' => '',
        'land_surface' => '',
        'commune' => $cityParam,
        'postal_code' => '',
        'address_details' => '',
        'goal' => '',
        'project_timeline' => '',
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'contact_consent' => 0,
        'outside_area' => 0,
        'utm_source' => '',
        'utm_campaign' => '',
        'utm_content' => '',
        'utm_medium' => '',
        'origin_page' => cms_url('/estimation-en-ligne'),
        'source' => 'formulaire estimation en ligne',
    ];
    $formData = array_merge($defaultData, $formData);
    $structuredData = [[
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $pageTitle,
        'serviceType' => 'Demande d’estimation immobilière en ligne',
        'provider' => [
            '@type' => 'RealEstateAgent',
            'name' => (string) $settings['site_name'],
            'telephone' => (string) ($settings['phone'] ?? ''),
            'email' => (string) ($settings['email'] ?? ''),
        ],
        'areaServed' => $cityLabel,
    ]];

    cms_render_public_document_start($pageTitle . ' | ' . (string) $settings['site_name'], $pageDescription, true, $structuredData);
    cms_render_estimation_header($settings);
    ?>
    <main class="estimate-page">
      <section class="estimate-section">
        <div class="shell estimate-app-shell">
          <div class="estimate-progress-block">
            <div class="estimate-progress-head">
              <span id="estimate-step-label">ÉTAPE 1 SUR 10</span>
              <strong id="estimate-step-percent">10%</strong>
            </div>
            <div class="estimate-progress-track"><span id="estimate-progress-bar"></span></div>
          </div>

          <?php if ($errors): ?>
            <div class="contact-alert error estimate-alert"><?= cms_h(implode(' ', $errors)) ?></div>
          <?php endif; ?>

          <form id="estimation-form" method="post" class="estimate-card estimate-app-card" novalidate>
              <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
              <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden-field" aria-hidden="true">
              <input type="hidden" name="source" value="<?= cms_h((string) $formData['source']) ?>">
              <input type="hidden" name="origin_page" id="estimate-origin-page" value="<?= cms_h((string) $formData['origin_page']) ?>">
              <input type="hidden" name="outside_area" id="estimate-outside-area" value="<?= (int) $formData['outside_area'] === 1 ? '1' : '0' ?>">
              <input type="hidden" name="utm_source" id="estimate-utm-source" value="<?= cms_h((string) $formData['utm_source']) ?>">
              <input type="hidden" name="utm_medium" id="estimate-utm-medium" value="<?= cms_h((string) $formData['utm_medium']) ?>">
              <input type="hidden" name="utm_campaign" id="estimate-utm-campaign" value="<?= cms_h((string) $formData['utm_campaign']) ?>">
              <input type="hidden" name="utm_content" id="estimate-utm-content" value="<?= cms_h((string) $formData['utm_content']) ?>">
              <input type="hidden" name="property_type" value="<?= cms_h((string) $formData['property_type']) ?>">
              <input type="hidden" name="room_count" value="<?= cms_h((string) $formData['room_count']) ?>">
              <input type="hidden" name="property_condition" value="<?= cms_h((string) $formData['property_condition']) ?>">
              <input type="hidden" name="living_surface" value="<?= cms_h((string) $formData['living_surface']) ?>">
              <input type="hidden" name="land_surface" value="<?= cms_h((string) $formData['land_surface']) ?>">
              <input type="hidden" name="goal" value="<?= cms_h((string) $formData['goal']) ?>">
              <input type="hidden" name="project_timeline" value="<?= cms_h((string) $formData['project_timeline']) ?>">

              <section class="estimate-pane" data-step="1" data-field="property_type">
                <h2>Quel type de bien estimez-vous ?</h2>
                <p>Choisissez la catégorie de votre bien immobilier.</p>
                <div class="estimate-choice-grid two-col stacked">
                  <?php foreach (['Appartement', 'Maison', 'Terrain', 'Autre'] as $option) { $renderChoiceCard('property_type', $option, 'is-stacked'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="2" data-field="room_count" hidden>
                <h2>Combien de pièces ?</h2>
                <p>Nombre de pièces principales du bien.</p>
                <div class="estimate-choice-grid two-col stacked">
                  <?php foreach (['1 ou 2', '3', '4', '5 ou plus'] as $option) { $renderChoiceCard('room_count', $option, 'is-stacked'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="3" data-field="property_condition" hidden>
                <h2>Quel est l’état général ?</h2>
                <p>Évaluez l’état actuel de votre bien.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Neuf / rénové récemment', 'Bon état', 'Travaux à prévoir', 'À rénover entièrement'] as $option) { $renderChoiceCard('property_condition', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="4" data-field="living_surface" hidden>
                <h2>Quelle est la surface ?</h2>
                <p>Sélectionnez une fourchette approximative.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Moins de 40 m²', '40 – 70 m²', '70 – 100 m²', '100 – 150 m²', 'Plus de 150 m²'] as $option) { $renderChoiceCard('living_surface', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="5" data-field="land_surface" hidden>
                <h2>Quelle est la surface du terrain ?</h2>
                <p>Si le bien n’a pas de terrain, indiquez-le simplement.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Pas de terrain', 'Moins de 500 m²', '500 – 1 000 m²', '1 000 – 2 000 m²', 'Plus de 2 000 m²'] as $option) { $renderChoiceCard('land_surface', $option, 'align-left' . ($option === 'Pas de terrain' ? ' estimate-choice-no-land' : '')); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="6" data-field="commune" hidden>
                <h2>Où se trouve votre bien ?</h2>
                <p>Tapez les premières lettres de votre commune.</p>
                <div class="estimate-input-stack">
                  <div class="estimate-search-field">
                    <span class="estimate-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                    <input id="estimate-commune-search" name="commune" autocomplete="off" value="<?= cms_h((string) $formData['commune']) ?>" placeholder="Rechercher une commune…" required aria-label="Commune">
                  </div>
                  <input type="hidden" id="estimate-postal-code" name="postal_code" value="<?= cms_h((string) $formData['postal_code']) ?>">
                  <div id="estimate-commune-suggestions" class="estimate-suggestions" hidden></div>
                  <div id="estimate-zone-warning" class="estimate-soft-warning" hidden>Votre commune semble située en dehors de ma zone d’intervention habituelle. Vous pouvez tout de même envoyer votre demande, je vous indiquerai si je peux vous accompagner ou vous orienter vers un conseiller du secteur.</div>
                </div>
              </section>

              <section class="estimate-pane" data-step="7" data-field="address_details" hidden>
                <h2>Quelle est l’adresse<span class="estimate-commune-suffix" data-commune-suffix hidden> à <span data-commune-name></span></span> ?</h2>
                <p>Tapez les premières lettres de votre adresse ou un secteur.</p>
                <div class="estimate-input-stack">
                  <div class="estimate-search-field">
                    <span class="estimate-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                    <input id="estimate-address-details" name="address_details" autocomplete="off" value="<?= cms_h((string) $formData['address_details']) ?>" placeholder="Rechercher votre adresse…" aria-label="Adresse">
                  </div>
                  <div id="estimate-address-suggestions" class="estimate-suggestions" hidden></div>
                </div>
              </section>

              <section class="estimate-pane" data-step="8" data-field="goal" hidden>
                <h2>Quel est votre objectif ?</h2>
                <p>Dites-nous ce qui vous motive.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Vendre au meilleur prix', 'Vendre rapidement', 'Simple curiosité', 'Projet d’achat / vente', 'Succession', 'Séparation', 'Autre situation'] as $option) { $renderChoiceCard('goal', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="9" data-field="project_timeline" hidden>
                <h2>Dans quel délai ?</h2>
                <p>Cette information aide à ajuster la priorité et la précision de l’analyse.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Dès maintenant', 'Dans les 3 mois', 'Dans les 6 mois', 'Plus tard', 'Je ne sais pas encore'] as $option) { $renderChoiceCard('project_timeline', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="10" data-field="contact_step" hidden>
                <div class="estimate-final-intro">
                  <span class="estimate-final-badge">Dernière étape</span>
                  <h2>Vos coordonnées</h2>
                  <p>Un expert vous recontacte sous 24h.</p>
                </div>
                <div class="estimate-contact-card">
                  <div class="estimate-contact-grid">
                    <label>
                      Prénom <span class="required-mark" aria-hidden="true">*</span>
                      <input name="first_name" value="<?= cms_h((string) $formData['first_name']) ?>" autocomplete="given-name" placeholder="Jean" required>
                    </label>
                    <label>
                      Nom <span class="required-mark" aria-hidden="true">*</span>
                      <input name="last_name" value="<?= cms_h((string) $formData['last_name']) ?>" autocomplete="family-name" placeholder="Dupont" required>
                    </label>
                    <label class="full">
                      Email <span class="required-mark" aria-hidden="true">*</span>
                      <span class="estimate-input-with-icon">
                        <span class="estimate-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span>
                        <input type="email" name="email" value="<?= cms_h((string) $formData['email']) ?>" autocomplete="email" placeholder="jean.dupont@email.com" required>
                      </span>
                    </label>
                    <label class="full">
                      Téléphone <span class="required-mark" aria-hidden="true">*</span>
                      <span class="estimate-input-with-icon">
                        <span class="estimate-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A15 15 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg></span>
                        <input type="tel" name="phone" value="<?= cms_h((string) $formData['phone']) ?>" autocomplete="tel" inputmode="numeric" maxlength="14" placeholder="06 12 34 56 78" required>
                      </span>
                    </label>
                  </div>
                  <p class="estimate-rgpd">Vos informations restent confidentielles et ne sont partagées qu'avec votre conseiller Immobilier Auxois Morvan.</p>
                </div>
                <label class="privacy-line estimate-consent-line"><input type="checkbox" name="contact_consent" value="1" <?= (int) $formData['contact_consent'] === 1 ? 'checked' : '' ?> required><span>J’accepte d’être recontacté au sujet de ma demande d’estimation.</span></label>
              </section>

              <div class="estimate-actions is-first-step">
                <button id="estimate-next-button" class="primary-button estimate-next-button" type="button" disabled>Suivant</button>
                <button id="estimate-submit-button" class="primary-button estimate-submit-button" type="submit" hidden>Recevoir mon estimation gratuite</button>
                <button id="estimate-back-button" class="estimate-back-button" type="button" hidden>← Retour</button>
              </div>
          </form>

          <div class="estimate-trust">
            <a class="estimate-trust-rating" href="https://www.immodvisor.com/professionnels/mandataire-immobilier/pro/iad-france-marion-roullier-57080" target="_blank" rel="noopener noreferrer">
              <span class="estimate-trust-stars" aria-hidden="true">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <svg viewBox="0 0 24 24" width="22" height="22" class="estimate-trust-star is-full"><path d="m12 2 3 6.9 7.6.7-5.7 5.1 1.7 7.5L12 18.3 5.4 22.2l1.7-7.5L1.4 9.6 9 8.9 12 2Z"/></svg>
                <?php endfor; ?>
                <svg viewBox="0 0 24 24" width="22" height="22" class="estimate-trust-star is-half">
                  <defs><linearGradient id="trustHalf"><stop offset="50%" stop-color="#f4a72b"/><stop offset="50%" stop-color="#e2e2ee"/></linearGradient></defs>
                  <path fill="url(#trustHalf)" d="m12 2 3 6.9 7.6.7-5.7 5.1 1.7 7.5L12 18.3 5.4 22.2l1.7-7.5L1.4 9.6 9 8.9 12 2Z"/>
                </svg>
              </span>
              <span class="estimate-trust-score"><strong>4,9/5</strong> <span class="estimate-trust-divider">—</span> 41 avis Immodvisor</span>
            </a>

            <div class="estimate-kpi-grid">
              <div class="estimate-kpi-card">
                <span class="estimate-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <strong class="estimate-kpi-value">24h</strong>
                <span class="estimate-kpi-label">Délai de réponse</span>
              </div>
              <div class="estimate-kpi-card">
                <span class="estimate-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5-7-11a7 7 0 0 1 14 0c0 6-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <strong class="estimate-kpi-value">100%</strong>
                <span class="estimate-kpi-label">Local &amp; humain</span>
              </div>
              <div class="estimate-kpi-card">
                <span class="estimate-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16l-1.4 11.2a2 2 0 0 1-2 1.8H7.4a2 2 0 0 1-2-1.8L4 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg></span>
                <strong class="estimate-kpi-value">0€</strong>
                <span class="estimate-kpi-label">Sans engagement</span>
              </div>
            </div>

            <ul class="estimate-trust-features">
              <li>
                <span class="estimate-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                <span>Données sécurisées</span>
              </li>
              <li>
                <span class="estimate-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.4 2.5 2.4 4.5-5"/></svg></span>
                <span>Estimation gratuite</span>
              </li>
              <li>
                <span class="estimate-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 11c0-3 2.7-5 7-5s7 2 7 5v3c0 1.1-.9 2-2 2h-1.5"/><path d="M5 11v3a3 3 0 0 0 3 3h2v-6H8a3 3 0 0 0-3 3Z"/><path d="M14 17v2a2 2 0 0 0 4 0"/><circle cx="16.5" cy="11" r="0.6" fill="currentColor"/></svg></span>
                <span>100% gratuit</span>
              </li>
            </ul>
          </div>
        </div>
      </section>
    </main>
    <script>
      (() => {
        const form = document.getElementById('estimation-form');
        if (!form) {
          return;
        }

        const panes = Array.from(form.querySelectorAll('.estimate-pane'));
        const backButton = document.getElementById('estimate-back-button');
        const nextButton = document.getElementById('estimate-next-button');
        const submitButton = document.getElementById('estimate-submit-button');
        const actionBar = form.querySelector('.estimate-actions');
        const stepLabel = document.getElementById('estimate-step-label');
        const stepPercent = document.getElementById('estimate-step-percent');
        const progressBar = document.getElementById('estimate-progress-bar');
        const communeInput = document.getElementById('estimate-commune-search');
        const postalCodeInput = document.getElementById('estimate-postal-code');
        const suggestionBox = document.getElementById('estimate-commune-suggestions');
        const zoneWarning = document.getElementById('estimate-zone-warning');
        const addressField = document.getElementById('estimate-address-details');
        const addressSuggestionBox = document.getElementById('estimate-address-suggestions');
        const communeSuffixHolders = Array.from(form.querySelectorAll('[data-commune-suffix]'));
        const communeNameHolders = Array.from(form.querySelectorAll('[data-commune-name]'));
        const originPageField = document.getElementById('estimate-origin-page');
        const outsideAreaField = document.getElementById('estimate-outside-area');
        const totalSteps = panes.length;
        const mimeure = { lat: 47.1546, lng: 4.4958 };
        const autoAdvanceSteps = new Set([1, 2, 3, 4, 5, 8, 9]);
        const autoAdvanceDelayMs = 1000;
        let activeStep = 1;
        let suggestionAbortController = null;
        let addressAbortController = null;
        let autoAdvanceTimer = null;
        let suppressNextSuggestion = false;
        let suppressNextAddressSuggestion = false;
        const trackingEndpoint = <?= json_encode(cms_url('/estimation-track'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const viewedSteps = new Set();
        const createTrackingId = () => `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;
        const getStoredTrackingId = (storage, key) => {
          try {
            let value = storage.getItem(key);
            if (!value) {
              value = createTrackingId();
              storage.setItem(key, value);
            }
            return value;
          } catch (error) {
            return createTrackingId();
          }
        };
        const visitorId = getStoredTrackingId(window.localStorage, 'iam_estimation_visitor_id');
        const sessionId = getStoredTrackingId(window.sessionStorage, 'iam_estimation_session_id');

        const triggerTracking = (name, payload = {}) => {
          const trackingParams = new URLSearchParams(window.location.search);
          const body = JSON.stringify({
            event_name: name,
            visitor_id: visitorId,
            session_id: sessionId,
            page_url: window.location.pathname + window.location.search,
            referrer: document.referrer || '',
            utm_source: trackingParams.get('utm_source') || '',
            utm_medium: trackingParams.get('utm_medium') || '',
            utm_campaign: trackingParams.get('utm_campaign') || '',
            utm_content: trackingParams.get('utm_content') || '',
            payload
          });

          if (navigator.sendBeacon) {
            navigator.sendBeacon(trackingEndpoint, new Blob([body], { type: 'application/json' }));
          } else {
            fetch(trackingEndpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body,
              keepalive: true
            }).catch(() => {});
          }

          if (typeof window.gtag === 'function') {
            window.gtag('event', name, payload);
          }

          if (typeof window.fbq === 'function') {
            if (name === 'estimation_form_submitted' || name === 'estimation_lead_created') {
              window.fbq('track', 'Lead', payload);
            }
            window.fbq('trackCustom', name, payload);
          }
        };

        const getField = (name) => form.querySelector(`[name="${name}"]`);
        const getValue = (name) => (getField(name)?.value || '').trim();
        const setValue = (name, value) => {
          const field = getField(name);
          if (field) {
            field.value = value;
          }
        };

        const formatPhoneNumber = (value) => value
          .replace(/\D+/g, '')
          .slice(0, 10)
          .replace(/(.{2})/g, '$1 ')
          .trim();

        const phoneField = getField('phone');

        if (phoneField instanceof HTMLInputElement) {
          phoneField.value = formatPhoneNumber(phoneField.value);
          phoneField.addEventListener('input', () => {
            phoneField.value = formatPhoneNumber(phoneField.value);
          });
        }

        const computeDistanceKm = (lat1, lng1, lat2, lng2) => {
          const toRadians = (value) => (value * Math.PI) / 180;
          const earthRadius = 6371;
          const dLat = toRadians(lat2 - lat1);
          const dLng = toRadians(lng2 - lng1);
          const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2;
          return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        };

        const updateZoneWarning = (distanceKm) => {
          const outOfArea = Number.isFinite(distanceKm) && distanceKm > 60;
          outsideAreaField.value = outOfArea ? '1' : '0';
          zoneWarning.hidden = !outOfArea;
        };

        const renderSuggestions = (items) => {
          suggestionBox.innerHTML = '';
          if (!items.length) {
            suggestionBox.hidden = true;
            return;
          }

          items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'estimate-suggestion-item';
            button.textContent = `${item.nom}${item.codesPostaux?.[0] ? ` (${item.codesPostaux[0]})` : ''}`;
            button.addEventListener('click', () => {
              communeInput.value = item.nom || communeInput.value;
              setValue('commune', item.nom || communeInput.value);
              if (postalCodeInput && item.codesPostaux?.[0]) {
                postalCodeInput.value = item.codesPostaux[0];
              }
              const coordinates = item.centre?.coordinates || [];
              if (coordinates.length === 2) {
                const distance = computeDistanceKm(mimeure.lat, mimeure.lng, Number(coordinates[1]), Number(coordinates[0]));
                updateZoneWarning(distance);
              } else {
                updateZoneWarning(Number.NaN);
              }
              suppressNextSuggestion = true;
              suggestionBox.hidden = true;
              suggestionBox.innerHTML = '';
              if (suggestionAbortController) {
                suggestionAbortController.abort();
                suggestionAbortController = null;
              }
              communeInput.blur();
              updateNavigationState();
            });
            suggestionBox.appendChild(button);
          });

          suggestionBox.hidden = false;
        };

        const fetchSuggestions = async (query) => {
          if (query.length < 2) {
            suggestionBox.hidden = true;
            suggestionBox.innerHTML = '';
            return;
          }

          if (suggestionAbortController) {
            suggestionAbortController.abort();
          }

          suggestionAbortController = new AbortController();

          try {
            const response = await fetch(`https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&boost=population&limit=6&fields=nom,codesPostaux,centre`, {
              signal: suggestionAbortController.signal
            });
            if (!response.ok) {
              throw new Error('lookup-failed');
            }
            const items = await response.json();
            renderSuggestions(Array.isArray(items) ? items : []);
          } catch (error) {
            if (error?.name !== 'AbortError') {
              suggestionBox.hidden = true;
            }
          }
        };

        const isContactStepValid = () => {
          const firstName = getField('first_name');
          const lastName = getField('last_name');
          const email = getField('email');
          const phone = getField('phone');
          const consent = getField('contact_consent');
          return !!firstName?.value.trim()
            && !!lastName?.value.trim()
            && !!email?.value.trim()
            && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())
            && phone?.value.replace(/\D+/g, '').length >= 9
            && !!consent?.checked;
        };

        const isStepApplicable = (stepNumber) => {
          if (getValue('property_type') === 'Terrain' && (stepNumber === 2 || stepNumber === 3 || stepNumber === 4)) {
            return false;
          }
          return true;
        };

        const isStepValid = (stepNumber) => {
          if (!isStepApplicable(stepNumber)) {
            return true;
          }
          switch (stepNumber) {
            case 1:
              return getValue('property_type') !== '';
            case 2:
              return getValue('room_count') !== '';
            case 3:
              return getValue('property_condition') !== '';
            case 4:
              return getValue('living_surface') !== '';
            case 5:
              return getValue('land_surface') !== '';
            case 6:
              return communeInput?.value.trim() !== '';
            case 7:
              return !!addressField?.value.trim();
            case 8:
              return getValue('goal') !== '';
            case 9:
              return getValue('project_timeline') !== '';
            case 10:
              return isContactStepValid();
            default:
              return false;
          }
        };

        const firstIncompleteStep = () => {
          for (let step = 1; step <= totalSteps; step += 1) {
            if (!isStepValid(step)) {
              return step;
            }
          }
          return totalSteps;
        };

        const syncChoiceState = () => {
          form.querySelectorAll('[data-choice-field]').forEach((button) => {
            const targetField = button.getAttribute('data-choice-field');
            const targetValue = button.getAttribute('data-choice-value');
            button.classList.toggle('is-selected', targetField !== null && getValue(targetField) === targetValue);
          });
        };

        const syncLandSurfaceOptions = () => {
          const isTerrain = getValue('property_type') === 'Terrain';
          const noLandButtons = form.querySelectorAll('.estimate-choice-no-land');

          if (isTerrain && getValue('land_surface') === 'Pas de terrain') {
            setValue('land_surface', '');
          }

          noLandButtons.forEach((button) => {
            button.hidden = isTerrain;
          });
        };

        const updateNavigationState = () => {
          panes.forEach((pane, index) => {
            pane.hidden = index + 1 !== activeStep;
          });

          syncLandSurfaceOptions();

          const shouldAutoAdvance = autoAdvanceSteps.has(activeStep);
          const stepIsValid = isStepValid(activeStep);
          const percent = Math.round((activeStep / totalSteps) * 100);
          stepLabel.textContent = `ÉTAPE ${activeStep} SUR ${totalSteps}`;
          stepPercent.textContent = `${percent}%`;
          progressBar.style.width = `${percent}%`;

          backButton.hidden = activeStep === 1;
          nextButton.hidden = activeStep === totalSteps || (activeStep === 1 && shouldAutoAdvance && stepIsValid);
          submitButton.hidden = activeStep !== totalSteps;
          nextButton.disabled = !stepIsValid || shouldAutoAdvance;
          submitButton.disabled = !isContactStepValid();
          actionBar?.classList.toggle('is-first-step', activeStep === 1);
          syncChoiceState();
          updateCommuneSuffix();
          trackStepView();
        };

        const trackStepView = () => {
          if (viewedSteps.has(activeStep)) {
            return;
          }
          viewedSteps.add(activeStep);
          const pane = panes[activeStep - 1];
          triggerTracking('estimation_step_viewed', {
            step_number: activeStep,
            step_field: pane?.dataset.field || ''
          });
        };

        form.querySelectorAll('[data-choice-field]').forEach((button) => {
          button.addEventListener('click', () => {
            const parentPane = button.closest('.estimate-pane');
            const stepNumber = Number(parentPane?.dataset.step || '0');
            const fieldName = button.getAttribute('data-choice-field');
            const fieldValue = button.getAttribute('data-choice-value');
            if (!fieldName || fieldValue === null) {
              return;
            }

            triggerTracking('estimation_choice_clicked', {
              step_number: stepNumber,
              step_field: fieldName,
              choice_value: fieldValue
            });
            setValue(fieldName, fieldValue);
            updateNavigationState();

            if (autoAdvanceTimer) {
              window.clearTimeout(autoAdvanceTimer);
            }

            if (stepNumber === activeStep && autoAdvanceSteps.has(stepNumber) && isStepValid(stepNumber)) {
              triggerTracking('estimation_step_completed', { step_number: stepNumber, step_field: fieldName });
              autoAdvanceTimer = window.setTimeout(() => {
                let nextStep = Math.min(totalSteps, stepNumber + 1);
                while (nextStep < totalSteps && !isStepApplicable(nextStep)) {
                  nextStep += 1;
                }
                activeStep = nextStep;
                updateNavigationState();
              }, autoAdvanceDelayMs);
            }
          });
        });

        const updateCommuneSuffix = () => {
          const name = (communeInput?.value || '').trim();
          communeNameHolders.forEach((node) => { node.textContent = name; });
          communeSuffixHolders.forEach((node) => { node.hidden = name === ''; });
        };

        const renderAddressSuggestions = (items) => {
          if (!addressSuggestionBox) {
            return;
          }
          addressSuggestionBox.innerHTML = '';
          if (!items.length) {
            addressSuggestionBox.hidden = true;
            return;
          }
          items.forEach((item) => {
            const props = item?.properties || {};
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'estimate-suggestion-item';
            button.textContent = props.label || props.name || '';
            button.addEventListener('click', () => {
              const label = props.label || props.name || '';
              if (addressField) {
                addressField.value = label;
                setValue('address_details', label);
              }
              suppressNextAddressSuggestion = true;
              addressSuggestionBox.hidden = true;
              addressSuggestionBox.innerHTML = '';
              if (addressAbortController) {
                addressAbortController.abort();
                addressAbortController = null;
              }
              addressField?.blur();
              updateNavigationState();
            });
            addressSuggestionBox.appendChild(button);
          });
          addressSuggestionBox.hidden = false;
        };

        const fetchAddressSuggestions = async (query) => {
          if (!addressSuggestionBox) {
            return;
          }
          const trimmed = query.trim();
          if (trimmed.length < 3) {
            addressSuggestionBox.hidden = true;
            addressSuggestionBox.innerHTML = '';
            return;
          }
          if (addressAbortController) {
            addressAbortController.abort();
          }
          addressAbortController = new AbortController();
          const params = new URLSearchParams({ q: trimmed, autocomplete: '1', limit: '8' });
          const postcode = (postalCodeInput?.value || '').trim();
          const communeName = (communeInput?.value || '').trim();
          if (postcode) {
            params.set('postcode', postcode);
          }
          if (communeName && !postcode) {
            params.set('q', `${trimmed} ${communeName}`);
          }
          try {
            const response = await fetch(`https://api-adresse.data.gouv.fr/search/?${params.toString()}`, {
              signal: addressAbortController.signal
            });
            if (!response.ok) {
              throw new Error('address-lookup-failed');
            }
            const data = await response.json();
            let features = Array.isArray(data?.features) ? data.features : [];
            if (postcode) {
              features = features.filter((feature) => (feature?.properties?.postcode || '') === postcode);
            } else if (communeName) {
              const target = communeName.toLowerCase();
              features = features.filter((feature) => ((feature?.properties?.city || '') + '').toLowerCase().includes(target));
            }
            renderAddressSuggestions(features.slice(0, 6));
          } catch (error) {
            if (error?.name !== 'AbortError') {
              addressSuggestionBox.hidden = true;
            }
          }
        };

        addressField?.addEventListener('input', () => {
          if (suppressNextAddressSuggestion) {
            suppressNextAddressSuggestion = false;
            return;
          }
          setValue('address_details', addressField.value);
          fetchAddressSuggestions(addressField.value);
          updateNavigationState();
        });
        addressField?.addEventListener('focus', () => {
          if (addressField.value.trim().length >= 3) {
            fetchAddressSuggestions(addressField.value);
          }
        });
        addressField?.addEventListener('blur', () => {
          window.setTimeout(() => {
            if (addressSuggestionBox) {
              addressSuggestionBox.hidden = true;
            }
          }, 150);
        });

        communeInput?.addEventListener('input', () => {
          if (suppressNextSuggestion) {
            suppressNextSuggestion = false;
            return;
          }
          setValue('commune', communeInput.value.trim());
          updateZoneWarning(Number.NaN);
          fetchSuggestions(communeInput.value.trim());
          updateNavigationState();
        });

        communeInput?.addEventListener('blur', () => {
          window.setTimeout(() => {
            suggestionBox.hidden = true;
          }, 120);
        });

        postalCodeInput?.addEventListener('input', updateNavigationState);
        addressField?.addEventListener('input', updateNavigationState);
        form.querySelectorAll('input[name="first_name"], input[name="last_name"], input[name="email"], input[name="phone"], input[name="contact_consent"]').forEach((field) => {
          field.addEventListener('input', updateNavigationState);
          field.addEventListener('change', updateNavigationState);
        });

        nextButton.addEventListener('click', () => {
          if (!isStepValid(activeStep)) {
            return;
          }

          if (autoAdvanceTimer) {
            window.clearTimeout(autoAdvanceTimer);
          }

          triggerTracking('estimation_next_clicked', { step_number: activeStep, step_field: panes[activeStep - 1]?.dataset.field || '' });
          triggerTracking('estimation_step_completed', { step_number: activeStep, step_field: panes[activeStep - 1]?.dataset.field || '' });
          let nextStep = Math.min(totalSteps, activeStep + 1);
          while (nextStep < totalSteps && !isStepApplicable(nextStep)) {
            nextStep += 1;
          }
          activeStep = nextStep;
          updateNavigationState();
        });

        backButton.addEventListener('click', () => {
          if (autoAdvanceTimer) {
            window.clearTimeout(autoAdvanceTimer);
          }

          let prevStep = Math.max(1, activeStep - 1);
          while (prevStep > 1 && !isStepApplicable(prevStep)) {
            prevStep -= 1;
          }
          triggerTracking('estimation_back_clicked', { step_number: activeStep, step_field: panes[activeStep - 1]?.dataset.field || '' });
          activeStep = prevStep;
          updateNavigationState();
        });

        form.addEventListener('submit', (event) => {
          if (!isContactStepValid()) {
            event.preventDefault();
            updateNavigationState();
            return;
          }

          originPageField.value = window.location.pathname + window.location.search;
          submitButton.disabled = true;
          submitButton.textContent = 'Envoi en cours...';
          triggerTracking('estimation_form_submitted', { commune: communeInput?.value.trim() || '', out_of_area: outsideAreaField.value === '1' ? 'yes' : 'no' });
          triggerTracking('estimation_lead_created', { commune: communeInput?.value.trim() || '' });
        });

        const urlParams = new URLSearchParams(window.location.search);
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'].forEach((name) => {
          const field = document.getElementById(`estimate-${name.replace('_', '-')}`);
          if (field && urlParams.get(name) && !field.value) {
            field.value = urlParams.get(name);
          }
        });

        triggerTracking('estimation_page_view', { city_hint: urlParams.get('ville') || 'generic' });
        triggerTracking('estimation_form_started', { city_hint: urlParams.get('ville') || 'generic' });
        activeStep = firstIncompleteStep();
        updateNavigationState();
      })();
    </script>
    <?php
}

function cms_render_estimation_confirmation_page(array $settings): void
{
    cms_render_public_document_start('Demande d’estimation envoyée | ' . (string) $settings['site_name'], 'Confirmation de réception de votre demande d’estimation immobilière.', false);
    cms_render_estimation_header($settings);
    ?>
    <main class="estimate-page">
      <section class="estimate-section">
        <div class="shell estimate-app-shell estimate-confirmation-shell">
          <article class="estimate-card estimate-confirmation-card">
            <p class="eyebrow">Demande envoyée</p>
            <h1>Votre demande a bien été envoyée</h1>
            <p>Merci, j’ai bien reçu les informations concernant votre bien. Je vais analyser votre demande et vous recontacter sous 24h pour vous donner un premier avis de valeur.</p>
            <div class="estimate-reassurance-row confirmation-row">
              <span>Estimation offerte</span>
              <span>Conseiller local</span>
              <span>Données confidentielles</span>
              <span>Réseau IAD</span>
            </div>
            <div class="estimate-actions single-action">
              <a class="primary-button estimate-submit-button" href="<?= cms_h(cms_url('/')) ?>">Retour à l’accueil</a>
            </div>
          </article>
        </div>
      </section>
    </main>
    <script>
      if (typeof window.gtag === 'function') {
        window.gtag('event', 'estimation_form_submitted', { success: true });
      }
      if (typeof window.fbq === 'function') {
        window.fbq('track', 'Lead');
        window.fbq('trackCustom', 'estimation_form_submitted', { success: true });
      }
    </script>
    <?php
}

function cms_render_viager_tunnel_page(array $settings, array $formData = [], array $errors = []): void
{
    $pageTitle = 'Étude viager gratuite autour de Mimeure | ' . (string) $settings['site_name'];
    $pageDescription = 'Vous envisagez de vendre en viager autour de Mimeure, Arnay-le-Duc, Pouilly-en-Auxois, Beaune ou Autun ? Recevez une première étude gratuite, locale et confidentielle.';
    $defaultData = [
        'request_type' => 'viager',
        'property_type' => '',
        'room_count' => '',
        'property_condition' => '',
        'living_surface' => '',
        'land_surface' => '',
        'occupancy_intent' => '',
        'commune' => '',
        'postal_code' => '',
        'address_details' => '',
        'goal' => '',
        'owner_situation' => '',
        'project_timeline' => '',
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'contact_consent' => 0,
        'outside_area' => 0,
        'utm_source' => '',
        'utm_campaign' => '',
        'utm_content' => '',
        'utm_medium' => '',
        'origin_page' => cms_url('/etude-viager-gratuite'),
        'source' => 'formulaire étude viager gratuite',
    ];
    $formData = array_merge($defaultData, $formData);
    $structuredData = [[
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => 'Étude viager gratuite autour de Mimeure',
        'serviceType' => 'Étude viager gratuite et confidentielle',
        'provider' => [
            '@type' => 'RealEstateAgent',
            'name' => (string) $settings['site_name'],
            'telephone' => (string) ($settings['phone'] ?? ''),
            'email' => (string) ($settings['email'] ?? ''),
        ],
        'areaServed' => 'Mimeure, Arnay-le-Duc, Pouilly-en-Auxois, Beaune, Autun et communes dans un rayon d’environ 40 km',
    ]];
    $icons = [
        'Maison' => '⌂', 'Appartement' => '▦', 'Terrain' => '◇', 'Autre' => '•••',
        'Oui, je souhaite rester chez moi' => '✓', 'Non, le logement serait libre' => '⌁', 'Je ne sais pas encore' => '?',
        'Obtenir un capital immédiat' => '€', 'Avoir un revenu mensuel complémentaire' => '+', 'Rester chez moi plus sereinement' => '⌂', 'Préparer ma succession' => '∞', 'Être conseillé sur les options possibles' => 'i',
        'Je suis seul(e) propriétaire' => '1', 'Nous sommes un couple propriétaire' => '2', 'Le bien appartient à plusieurs personnes' => '3', 'C’est dans le cadre d’une succession' => '∞', 'Autre situation' => '•••',
        'Dès maintenant' => '24', 'Dans les 3 mois' => '3', 'Dans les 6 mois' => '6', 'Plus tard' => '…', 'Je veux simplement me renseigner' => 'i',
    ];
    $renderChoiceCard = function (string $field, string $value, string $variant = '') use ($icons): void {
        $classes = 'estimate-choice-card' . ($variant !== '' ? ' ' . $variant : '');
        ?>
        <button type="button" class="<?= cms_h($classes) ?>" data-choice-field="<?= cms_h($field) ?>" data-choice-value="<?= cms_h($value) ?>">
          <span class="estimate-choice-icon viager-choice-icon" aria-hidden="true"><?= cms_h((string) ($icons[$value] ?? '✓')) ?></span>
          <span class="estimate-choice-label"><?= cms_h($value) ?></span>
        </button>
        <?php
    };

    cms_render_public_document_start($pageTitle, $pageDescription, true, $structuredData, ['canonical' => cms_absolute_url('/etude-viager-gratuite')]);
    cms_render_estimation_header($settings, 'Étude viager gratuite');
    ?>
    <main class="estimate-page viager-page">
      <section class="estimate-section">
        <div class="shell estimate-app-shell">
          <div class="estimate-landing-intro viager-landing-intro">
            <h1 class="viager-seo-title">Vendre en viager autour de Mimeure</h1>
            <p>Recevez une première étude gratuite et confidentielle pour savoir si le viager est adapté à votre situation.</p>
          </div>

          <div class="estimate-progress-block">
            <div class="estimate-progress-head">
              <span id="estimate-step-label">ÉTAPE 1 SUR 10</span>
              <strong id="estimate-step-percent">10%</strong>
            </div>
            <div class="estimate-progress-track"><span id="estimate-progress-bar"></span></div>
          </div>

          <?php if ($errors): ?>
            <div class="contact-alert error estimate-alert"><?= cms_h(implode(' ', $errors)) ?></div>
          <?php endif; ?>

          <form id="estimation-form" method="post" class="estimate-card estimate-app-card" novalidate>
              <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
              <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden-field" aria-hidden="true">
              <input type="hidden" name="request_type" value="viager">
              <input type="hidden" name="source" value="<?= cms_h((string) $formData['source']) ?>">
              <input type="hidden" name="origin_page" id="estimate-origin-page" value="<?= cms_h((string) $formData['origin_page']) ?>">
              <input type="hidden" name="outside_area" id="estimate-outside-area" value="<?= (int) $formData['outside_area'] === 1 ? '1' : '0' ?>">
              <input type="hidden" name="utm_source" id="estimate-utm-source" value="<?= cms_h((string) $formData['utm_source']) ?>">
              <input type="hidden" name="utm_medium" id="estimate-utm-medium" value="<?= cms_h((string) $formData['utm_medium']) ?>">
              <input type="hidden" name="utm_campaign" id="estimate-utm-campaign" value="<?= cms_h((string) $formData['utm_campaign']) ?>">
              <input type="hidden" name="utm_content" id="estimate-utm-content" value="<?= cms_h((string) $formData['utm_content']) ?>">
              <input type="hidden" name="property_type" value="<?= cms_h((string) $formData['property_type']) ?>">
              <input type="hidden" name="room_count" value="<?= cms_h((string) $formData['room_count']) ?>">
              <input type="hidden" name="property_condition" value="">
              <input type="hidden" name="living_surface" value="<?= cms_h((string) $formData['living_surface']) ?>">
              <input type="hidden" name="land_surface" value="">
              <input type="hidden" name="occupancy_intent" value="<?= cms_h((string) $formData['occupancy_intent']) ?>">
              <input type="hidden" name="goal" value="<?= cms_h((string) $formData['goal']) ?>">
              <input type="hidden" name="owner_situation" value="<?= cms_h((string) $formData['owner_situation']) ?>">
              <input type="hidden" name="project_timeline" value="<?= cms_h((string) $formData['project_timeline']) ?>">

              <section class="estimate-pane" data-step="1" data-field="property_type">
                <h2>Quel type de bien possédez-vous ?</h2>
                <p>Choisissez la catégorie de votre bien immobilier.</p>
                <div class="estimate-choice-grid two-col stacked">
                  <?php foreach (['Maison', 'Appartement', 'Terrain', 'Autre'] as $option) { $renderChoiceCard('property_type', $option, 'is-stacked'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="2" data-field="room_count" hidden>
                <h2>Combien de pièces ?</h2>
                <p>Nombre de pièces principales du bien.</p>
                <div class="estimate-choice-grid two-col stacked">
                  <?php foreach (['1 ou 2', '3', '4', '5 ou plus'] as $option) { $renderChoiceCard('room_count', $option, 'is-stacked'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="3" data-field="living_surface" hidden>
                <h2>Quelle surface approximative ?</h2>
                <p>Une estimation suffit pour commencer l’étude.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Moins de 60 m²', '60 à 90 m²', '90 à 120 m²', 'Plus de 120 m²', 'Je ne sais pas'] as $option) { $renderChoiceCard('living_surface', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="4" data-field="occupancy_intent" hidden>
                <h2>Souhaitez-vous rester vivre dans le logement ?</h2>
                <p>Cette information permet d’orienter l’étude : viager occupé, libre ou autre solution.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Oui, je souhaite rester chez moi', 'Non, le logement serait libre', 'Je ne sais pas encore'] as $option) { $renderChoiceCard('occupancy_intent', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="5" data-field="goal" hidden>
                <h2>Quel est votre objectif principal ?</h2>
                <p>Dites-nous ce qui vous motive.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Obtenir un capital immédiat', 'Avoir un revenu mensuel complémentaire', 'Rester chez moi plus sereinement', 'Préparer ma succession', 'Être conseillé sur les options possibles'] as $option) { $renderChoiceCard('goal', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="6" data-field="commune" hidden>
                <h2>Où se trouve votre bien ?</h2>
                <p>Tapez les premières lettres de votre commune.</p>
                <div class="estimate-input-stack">
                  <div class="estimate-search-field">
                    <span class="estimate-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                    <input id="estimate-commune-search" name="commune" autocomplete="off" value="<?= cms_h((string) $formData['commune']) ?>" placeholder="Recherche commune" required aria-label="Commune">
                  </div>
                  <input type="hidden" id="estimate-postal-code" name="postal_code" value="<?= cms_h((string) $formData['postal_code']) ?>">
                  <div id="estimate-commune-suggestions" class="estimate-suggestions" hidden></div>
                  <div id="estimate-zone-warning" class="estimate-soft-warning" hidden>Cette commune semble être en dehors de notre secteur principal. Vous pouvez tout de même envoyer votre demande, nous vous recontacterons si nous pouvons vous accompagner.</div>
                </div>
              </section>

              <section class="estimate-pane" data-step="7" data-field="address_details" hidden>
                <h2>Quelle est l’adresse<span class="estimate-commune-suffix" data-commune-suffix hidden> à <span data-commune-name></span></span> ?</h2>
                <p>Tapez les premières lettres de votre adresse ou indiquez simplement le secteur.</p>
                <div class="estimate-input-stack">
                  <div class="estimate-search-field">
                    <span class="estimate-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                    <input id="estimate-address-details" name="address_details" autocomplete="off" value="<?= cms_h((string) $formData['address_details']) ?>" placeholder="Adresse ou secteur" aria-label="Adresse ou secteur">
                  </div>
                  <div id="estimate-address-suggestions" class="estimate-suggestions" hidden></div>
                </div>
              </section>

              <section class="estimate-pane" data-step="8" data-field="owner_situation" hidden>
                <h2>Quelle est votre situation ?</h2>
                <p>Cela nous aide à comprendre le contexte de votre projet.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Je suis seul(e) propriétaire', 'Nous sommes un couple propriétaire', 'Le bien appartient à plusieurs personnes', 'C’est dans le cadre d’une succession', 'Autre situation'] as $option) { $renderChoiceCard('owner_situation', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="9" data-field="project_timeline" hidden>
                <h2>Dans quel délai souhaitez-vous être conseillé ?</h2>
                <p>Cette information aide à ajuster la priorité de votre demande.</p>
                <div class="estimate-choice-grid one-col">
                  <?php foreach (['Dès maintenant', 'Dans les 3 mois', 'Dans les 6 mois', 'Plus tard', 'Je veux simplement me renseigner'] as $option) { $renderChoiceCard('project_timeline', $option, 'align-left'); } ?>
                </div>
              </section>

              <section class="estimate-pane" data-step="10" data-field="contact_step" hidden>
                <div class="estimate-final-intro">
                  <span class="estimate-final-badge">Dernière étape</span>
                  <h2>Vos coordonnées</h2>
                  <p>Un conseiller local vous recontacte sous 24h pour échanger sur votre projet viager.</p>
                </div>
                <div class="estimate-contact-card">
                  <div class="estimate-contact-grid">
                    <label>Prénom <span class="required-mark" aria-hidden="true">*</span><input name="first_name" value="<?= cms_h((string) $formData['first_name']) ?>" autocomplete="given-name" placeholder="Jean" required></label>
                    <label>Nom <span class="required-mark" aria-hidden="true">*</span><input name="last_name" value="<?= cms_h((string) $formData['last_name']) ?>" autocomplete="family-name" placeholder="Dupont" required></label>
                    <label class="full">Email <span class="required-mark" aria-hidden="true">*</span><span class="estimate-input-with-icon"><span class="estimate-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span><input type="email" name="email" value="<?= cms_h((string) $formData['email']) ?>" autocomplete="email" placeholder="jean.dupont@email.com" required></span></label>
                    <label class="full">Téléphone <span class="required-mark" aria-hidden="true">*</span><span class="estimate-input-with-icon"><span class="estimate-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A15 15 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg></span><input type="tel" name="phone" value="<?= cms_h((string) $formData['phone']) ?>" autocomplete="tel" inputmode="numeric" maxlength="14" placeholder="06 12 34 56 78" required></span></label>
                  </div>
                  <p class="estimate-rgpd">Vos informations restent confidentielles et ne sont partagées qu’avec votre conseiller Immobilier Auxois Morvan.</p>
                </div>
                <label class="privacy-line estimate-consent-line"><input type="checkbox" name="contact_consent" value="1" <?= (int) $formData['contact_consent'] === 1 ? 'checked' : '' ?> required><span>J’accepte d’être recontacté au sujet de ma demande d’étude viager.</span></label>
              </section>

              <div class="estimate-actions is-first-step">
                <button id="estimate-next-button" class="primary-button estimate-next-button" type="button" disabled>Suivant</button>
                <button id="estimate-submit-button" class="primary-button estimate-submit-button" type="submit" hidden>Recevoir mon étude viager gratuite</button>
                <button id="estimate-back-button" class="estimate-back-button" type="button" hidden>← Retour</button>
              </div>
          </form>

          <div class="estimate-trust">
            <div class="estimate-kpi-grid viager-kpi-grid">
              <div class="estimate-kpi-card"><strong class="estimate-kpi-value">24h</strong><span class="estimate-kpi-label">Délai de réponse</span></div>
              <div class="estimate-kpi-card"><strong class="estimate-kpi-value">100%</strong><span class="estimate-kpi-label">Local &amp; humain</span></div>
              <div class="estimate-kpi-card"><strong class="estimate-kpi-value">0€</strong><span class="estimate-kpi-label">Sans engagement</span></div>
            </div>
            <ul class="estimate-trust-features">
              <li><span class="estimate-trust-icon" aria-hidden="true">✓</span><span>Données sécurisées</span></li>
              <li><span class="estimate-trust-icon" aria-hidden="true">✓</span><span>Étude gratuite</span></li>
              <li><span class="estimate-trust-icon" aria-hidden="true">✓</span><span>100% confidentiel</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
    <script>
      (() => {
        const form = document.getElementById('estimation-form');
        if (!form) return;

        const panes = Array.from(form.querySelectorAll('.estimate-pane'));
        const backButton = document.getElementById('estimate-back-button');
        const nextButton = document.getElementById('estimate-next-button');
        const submitButton = document.getElementById('estimate-submit-button');
        const actionBar = form.querySelector('.estimate-actions');
        const stepLabel = document.getElementById('estimate-step-label');
        const stepPercent = document.getElementById('estimate-step-percent');
        const progressBar = document.getElementById('estimate-progress-bar');
        const communeInput = document.getElementById('estimate-commune-search');
        const postalCodeInput = document.getElementById('estimate-postal-code');
        const suggestionBox = document.getElementById('estimate-commune-suggestions');
        const zoneWarning = document.getElementById('estimate-zone-warning');
        const addressField = document.getElementById('estimate-address-details');
        const addressSuggestionBox = document.getElementById('estimate-address-suggestions');
        const communeSuffixHolders = Array.from(form.querySelectorAll('[data-commune-suffix]'));
        const communeNameHolders = Array.from(form.querySelectorAll('[data-commune-name]'));
        const originPageField = document.getElementById('estimate-origin-page');
        const outsideAreaField = document.getElementById('estimate-outside-area');
        const totalSteps = panes.length;
        const mimeure = { lat: 47.1546, lng: 4.4958 };
        const autoAdvanceSteps = new Set([1, 2, 3, 4, 5, 8, 9]);
        const localCommunes = [
          { nom: 'Mimeure', codesPostaux: ['21230'], centre: { coordinates: [4.4958, 47.1546] } },
          { nom: 'Arnay-le-Duc', codesPostaux: ['21230'], centre: { coordinates: [4.485, 47.132] } },
          { nom: 'Pouilly-en-Auxois', codesPostaux: ['21320'], centre: { coordinates: [4.555, 47.262] } },
          { nom: 'Bligny-sur-Ouche', codesPostaux: ['21360'], centre: { coordinates: [4.669, 47.107] } },
          { nom: 'Nolay', codesPostaux: ['21340'], centre: { coordinates: [4.634, 46.952] } },
          { nom: 'Beaune', codesPostaux: ['21200'], centre: { coordinates: [4.839, 47.026] } },
          { nom: 'Autun', codesPostaux: ['71400'], centre: { coordinates: [4.299, 46.951] } },
          { nom: 'Saulieu', codesPostaux: ['21210'], centre: { coordinates: [4.229, 47.28] } },
          { nom: 'Vitteaux', codesPostaux: ['21350'], centre: { coordinates: [4.54, 47.397] } },
          { nom: 'Semur-en-Auxois', codesPostaux: ['21140'], centre: { coordinates: [4.334, 47.49] } },
          { nom: 'Épinac', codesPostaux: ['71360'], centre: { coordinates: [4.513, 46.991] } },
          { nom: 'Liernais', codesPostaux: ['21430'], centre: { coordinates: [4.281, 47.206] } },
          { nom: 'Chailly-sur-Armançon', codesPostaux: ['21320'], centre: { coordinates: [4.485, 47.276] } },
          { nom: 'Lacanche', codesPostaux: ['21230'], centre: { coordinates: [4.56, 47.079] } },
          { nom: 'Créancey', codesPostaux: ['21320'], centre: { coordinates: [4.586, 47.247] } },
          { nom: 'Commarin', codesPostaux: ['21320'], centre: { coordinates: [4.647, 47.255] } },
          { nom: 'Sombernon', codesPostaux: ['21540'], centre: { coordinates: [4.704, 47.309] } },
          { nom: 'La Bussière-sur-Ouche', codesPostaux: ['21360'], centre: { coordinates: [4.722, 47.216] } },
          { nom: 'Thoisy-le-Désert', codesPostaux: ['21320'], centre: { coordinates: [4.557, 47.244] } },
          { nom: 'Précy-sous-Thil', codesPostaux: ['21390'], centre: { coordinates: [4.308, 47.39] } },
          { nom: 'Montbard', codesPostaux: ['21500'], centre: { coordinates: [4.337, 47.623] } }
        ];
        let activeStep = 1;
        let suggestionAbortController = null;
        let addressAbortController = null;
        let autoAdvanceTimer = null;
        let suppressNextSuggestion = false;
        let suppressNextAddressSuggestion = false;

        const getField = (name) => form.querySelector(`[name="${name}"]`);
        const getValue = (name) => (getField(name)?.value || '').trim();
        const setValue = (name, value) => { const field = getField(name); if (field) field.value = value; };
        const normalizeText = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const formatPhoneNumber = (value) => value.replace(/\D+/g, '').slice(0, 10).replace(/(.{2})/g, '$1 ').trim();
        const phoneField = getField('phone');
        if (phoneField instanceof HTMLInputElement) {
          phoneField.value = formatPhoneNumber(phoneField.value);
          phoneField.addEventListener('input', () => { phoneField.value = formatPhoneNumber(phoneField.value); });
        }
        const computeDistanceKm = (lat1, lng1, lat2, lng2) => {
          const toRadians = (value) => (value * Math.PI) / 180;
          const earthRadius = 6371;
          const dLat = toRadians(lat2 - lat1);
          const dLng = toRadians(lng2 - lng1);
          const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2;
          return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        };
        const updateZoneWarning = (distanceKm) => {
          const outOfArea = Number.isFinite(distanceKm) && distanceKm > 40;
          outsideAreaField.value = outOfArea ? '1' : '0';
          zoneWarning.hidden = !outOfArea;
        };
        const renderSuggestions = (items) => {
          suggestionBox.innerHTML = '';
          if (!items.length) { suggestionBox.hidden = true; return; }
          items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'estimate-suggestion-item';
            button.textContent = `${item.nom}${item.codesPostaux?.[0] ? ` (${item.codesPostaux[0]})` : ''}`;
            button.addEventListener('click', () => {
              communeInput.value = item.nom || communeInput.value;
              setValue('commune', item.nom || communeInput.value);
              if (postalCodeInput && item.codesPostaux?.[0]) postalCodeInput.value = item.codesPostaux[0];
              const coordinates = item.centre?.coordinates || [];
              if (coordinates.length === 2) {
                updateZoneWarning(computeDistanceKm(mimeure.lat, mimeure.lng, Number(coordinates[1]), Number(coordinates[0])));
              } else {
                updateZoneWarning(Number.NaN);
              }
              suppressNextSuggestion = true;
              suggestionBox.hidden = true;
              suggestionBox.innerHTML = '';
              suggestionAbortController?.abort();
              suggestionAbortController = null;
              communeInput.blur();
              updateNavigationState();
            });
            suggestionBox.appendChild(button);
          });
          suggestionBox.hidden = false;
        };
        const fetchSuggestions = async (query) => {
          if (query.length < 2) { suggestionBox.hidden = true; suggestionBox.innerHTML = ''; return; }
          const localMatches = localCommunes.filter((item) => normalizeText(item.nom).includes(normalizeText(query))).slice(0, 6);
          renderSuggestions(localMatches);
          if (suggestionAbortController) suggestionAbortController.abort();
          suggestionAbortController = new AbortController();
          try {
            const response = await fetch(`https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&boost=population&limit=6&fields=nom,codesPostaux,centre`, { signal: suggestionAbortController.signal });
            if (!response.ok) throw new Error('lookup-failed');
            const apiItems = await response.json();
            const merged = [...localMatches];
            (Array.isArray(apiItems) ? apiItems : []).forEach((item) => {
              if (!merged.some((existing) => normalizeText(existing.nom) === normalizeText(item.nom || ''))) merged.push(item);
            });
            renderSuggestions(merged.slice(0, 6));
          } catch (error) {
            if (error?.name !== 'AbortError' && localMatches.length === 0) suggestionBox.hidden = true;
          }
        };
        const isContactStepValid = () => {
          const firstName = getField('first_name');
          const lastName = getField('last_name');
          const email = getField('email');
          const phone = getField('phone');
          const consent = getField('contact_consent');
          return !!firstName?.value.trim() && !!lastName?.value.trim() && !!email?.value.trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim()) && phone?.value.replace(/\D+/g, '').length >= 9 && !!consent?.checked;
        };
        const isStepValid = (stepNumber) => {
          switch (stepNumber) {
            case 1: return getValue('property_type') !== '';
            case 2: return getValue('room_count') !== '';
            case 3: return getValue('living_surface') !== '';
            case 4: return getValue('occupancy_intent') !== '';
            case 5: return getValue('goal') !== '';
            case 6: return communeInput?.value.trim() !== '';
            case 7: return !!addressField?.value.trim();
            case 8: return getValue('owner_situation') !== '';
            case 9: return getValue('project_timeline') !== '';
            case 10: return isContactStepValid();
            default: return false;
          }
        };
        const firstIncompleteStep = () => {
          for (let step = 1; step <= totalSteps; step += 1) if (!isStepValid(step)) return step;
          return totalSteps;
        };
        const syncChoiceState = () => {
          form.querySelectorAll('[data-choice-field]').forEach((button) => {
            const targetField = button.getAttribute('data-choice-field');
            const targetValue = button.getAttribute('data-choice-value');
            button.classList.toggle('is-selected', targetField !== null && getValue(targetField) === targetValue);
          });
        };
        const updateCommuneSuffix = () => {
          const name = (communeInput?.value || '').trim();
          communeNameHolders.forEach((node) => { node.textContent = name; });
          communeSuffixHolders.forEach((node) => { node.hidden = name === ''; });
        };
        const updateNavigationState = () => {
          panes.forEach((pane, index) => { pane.hidden = index + 1 !== activeStep; });
          const stepIsValid = isStepValid(activeStep);
          const percent = Math.round((activeStep / totalSteps) * 100);
          stepLabel.textContent = `ÉTAPE ${activeStep} SUR ${totalSteps}`;
          stepPercent.textContent = `${percent}%`;
          progressBar.style.width = `${percent}%`;
          backButton.hidden = activeStep === 1;
          nextButton.hidden = activeStep === totalSteps || (activeStep === 1 && autoAdvanceSteps.has(activeStep) && stepIsValid);
          submitButton.hidden = activeStep !== totalSteps;
          nextButton.disabled = !stepIsValid || autoAdvanceSteps.has(activeStep);
          submitButton.disabled = !isContactStepValid();
          actionBar?.classList.toggle('is-first-step', activeStep === 1);
          syncChoiceState();
          updateCommuneSuffix();
        };
        form.querySelectorAll('[data-choice-field]').forEach((button) => {
          button.addEventListener('click', () => {
            const parentPane = button.closest('.estimate-pane');
            const stepNumber = Number(parentPane?.dataset.step || '0');
            const fieldName = button.getAttribute('data-choice-field');
            const fieldValue = button.getAttribute('data-choice-value');
            if (!fieldName || fieldValue === null) return;
            setValue(fieldName, fieldValue);
            updateNavigationState();
            if (autoAdvanceTimer) window.clearTimeout(autoAdvanceTimer);
            if (stepNumber === activeStep && autoAdvanceSteps.has(stepNumber) && isStepValid(stepNumber)) {
              autoAdvanceTimer = window.setTimeout(() => { activeStep = Math.min(totalSteps, stepNumber + 1); updateNavigationState(); }, 650);
            }
          });
        });
        const renderAddressSuggestions = (items) => {
          if (!addressSuggestionBox) return;
          addressSuggestionBox.innerHTML = '';
          if (!items.length) { addressSuggestionBox.hidden = true; return; }
          items.forEach((item) => {
            const props = item?.properties || {};
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'estimate-suggestion-item';
            button.textContent = props.label || props.name || '';
            button.addEventListener('click', () => {
              const label = props.label || props.name || '';
              if (addressField) { addressField.value = label; setValue('address_details', label); }
              suppressNextAddressSuggestion = true;
              addressSuggestionBox.hidden = true;
              addressSuggestionBox.innerHTML = '';
              addressAbortController?.abort();
              addressAbortController = null;
              addressField?.blur();
              updateNavigationState();
            });
            addressSuggestionBox.appendChild(button);
          });
          addressSuggestionBox.hidden = false;
        };
        const fetchAddressSuggestions = async (query) => {
          if (!addressSuggestionBox) return;
          const trimmed = query.trim();
          if (trimmed.length < 3) { addressSuggestionBox.hidden = true; addressSuggestionBox.innerHTML = ''; return; }
          addressAbortController?.abort();
          addressAbortController = new AbortController();
          const params = new URLSearchParams({ q: trimmed, autocomplete: '1', limit: '8' });
          const postcode = (postalCodeInput?.value || '').trim();
          const communeName = (communeInput?.value || '').trim();
          if (postcode) params.set('postcode', postcode);
          if (communeName && !postcode) params.set('q', `${trimmed} ${communeName}`);
          try {
            const response = await fetch(`https://api-adresse.data.gouv.fr/search/?${params.toString()}`, { signal: addressAbortController.signal });
            if (!response.ok) throw new Error('address-lookup-failed');
            const data = await response.json();
            let features = Array.isArray(data?.features) ? data.features : [];
            if (postcode) features = features.filter((feature) => (feature?.properties?.postcode || '') === postcode);
            else if (communeName) features = features.filter((feature) => ((feature?.properties?.city || '') + '').toLowerCase().includes(communeName.toLowerCase()));
            renderAddressSuggestions(features.slice(0, 6));
          } catch (error) {
            if (error?.name !== 'AbortError') addressSuggestionBox.hidden = true;
          }
        };
        addressField?.addEventListener('input', () => {
          if (suppressNextAddressSuggestion) { suppressNextAddressSuggestion = false; return; }
          setValue('address_details', addressField.value);
          fetchAddressSuggestions(addressField.value);
          updateNavigationState();
        });
        addressField?.addEventListener('focus', () => { if (addressField.value.trim().length >= 3) fetchAddressSuggestions(addressField.value); });
        addressField?.addEventListener('blur', () => { window.setTimeout(() => { if (addressSuggestionBox) addressSuggestionBox.hidden = true; }, 150); });
        communeInput?.addEventListener('input', () => {
          if (suppressNextSuggestion) { suppressNextSuggestion = false; return; }
          const typed = communeInput.value.trim();
          setValue('commune', typed);
          updateZoneWarning(Number.NaN);
          fetchSuggestions(typed);
          updateNavigationState();
        });
        communeInput?.addEventListener('blur', () => { window.setTimeout(() => { suggestionBox.hidden = true; }, 120); });
        form.querySelectorAll('input[name="first_name"], input[name="last_name"], input[name="email"], input[name="phone"], input[name="contact_consent"]').forEach((field) => {
          field.addEventListener('input', updateNavigationState);
          field.addEventListener('change', updateNavigationState);
        });
        nextButton.addEventListener('click', () => {
          if (!isStepValid(activeStep)) return;
          if (autoAdvanceTimer) window.clearTimeout(autoAdvanceTimer);
          activeStep = Math.min(totalSteps, activeStep + 1);
          updateNavigationState();
        });
        backButton.addEventListener('click', () => {
          if (autoAdvanceTimer) window.clearTimeout(autoAdvanceTimer);
          activeStep = Math.max(1, activeStep - 1);
          updateNavigationState();
        });
        form.addEventListener('submit', (event) => {
          if (!isContactStepValid()) { event.preventDefault(); updateNavigationState(); return; }
          originPageField.value = window.location.pathname + window.location.search;
          submitButton.disabled = true;
          submitButton.textContent = 'Envoi en cours...';
        });
        const urlParams = new URLSearchParams(window.location.search);
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'].forEach((name) => {
          const field = document.getElementById(`estimate-${name.replace('_', '-')}`);
          if (field && urlParams.get(name) && !field.value) field.value = urlParams.get(name);
        });
        activeStep = firstIncompleteStep();
        updateNavigationState();
      })();
    </script>
    <?php
}

function cms_render_viager_confirmation_page(array $settings): void
{
    cms_render_public_document_start('Demande d’étude viager envoyée | ' . (string) $settings['site_name'], 'Confirmation de réception de votre demande d’étude viager gratuite.', false, [], ['canonical' => cms_absolute_url('/etude-viager-gratuite/confirmation')]);
    cms_render_estimation_header($settings, 'Étude viager gratuite');
    ?>
    <main class="estimate-page viager-page">
      <section class="estimate-section">
        <div class="shell estimate-app-shell estimate-confirmation-shell">
          <article class="estimate-card estimate-confirmation-card">
            <p class="eyebrow">Demande envoyée</p>
            <h1>Votre demande d’étude viager a bien été envoyée</h1>
            <p>Merci, j’ai bien reçu les informations concernant votre projet. Je vais les analyser avec attention et vous recontacter sous 24h pour échanger sur les possibilités adaptées à votre situation : viager occupé, viager libre, vente à terme ou vente classique si cela semble plus pertinent.</p>
            <div class="estimate-reassurance-row confirmation-row">
              <span>Étude offerte</span>
              <span>Conseiller local</span>
              <span>Confidentiel</span>
              <span>Sans engagement</span>
            </div>
            <div class="estimate-actions single-action">
              <a class="primary-button estimate-submit-button" href="<?= cms_h(cms_url('/')) ?>">Retour à l’accueil</a>
            </div>
          </article>
        </div>
      </section>
    </main>
    <script>
      if (typeof window.gtag === 'function') {
        window.gtag('event', 'viager_form_submitted', { success: true });
      }
      if (typeof window.fbq === 'function') {
        window.fbq('track', 'Lead');
        window.fbq('trackCustom', 'viager_form_submitted', { success: true });
      }
    </script>
    <?php
}

function cms_render_viager_seo_page(array $settings, array $snapshot): void
{
    $title = 'Viager en Côte-d’Or et Auxois Morvan | ' . (string) $settings['site_name'];
    $description = 'Vous envisagez une vente en viager en Côte-d’Or, autour de Beaune, Autun, Arnay-le-Duc, Pouilly-en-Auxois ou Semur-en-Auxois ? Demandez une étude gratuite, locale et confidentielle.';
    $ctaUrl = cms_url('/etude-viager-gratuite');
    $ctaLabel = 'Faire mon étude viager gratuite';
    $localLine = 'Beaune · Autun · Arnay-le-Duc · Pouilly-en-Auxois · Semur-en-Auxois';
    $areas = ['Beaune', 'Autun', 'Arnay-le-Duc', 'Pouilly-en-Auxois', 'Semur-en-Auxois', 'Saulieu', 'Vitteaux', 'Bligny-sur-Ouche', 'Nolay', 'Épinac', 'Liernais', 'Sombernon', 'La Bussière-sur-Ouche', 'Auxois Morvan'];
    $audiences = [
        ['title' => 'Propriétaires souhaitant rester chez eux', 'text' => 'Vous souhaitez continuer à vivre dans votre maison ou votre appartement tout en valorisant votre patrimoine.'],
        ['title' => 'Retraités cherchant un complément de revenus', 'text' => 'Le viager peut permettre d’obtenir un capital, une rente ou une combinaison des deux.'],
        ['title' => 'Familles qui veulent anticiper', 'text' => 'Le viager peut aussi être étudié dans un contexte familial, patrimonial ou successoral.'],
        ['title' => 'Propriétaires d’un bien difficile à transmettre ou à entretenir', 'text' => 'Une maison devenue trop lourde à entretenir peut parfois être valorisée autrement grâce à une solution adaptée.'],
    ];
    $forms = [
        ['title' => 'Viager occupé', 'text' => 'Le vendeur cède son bien, mais conserve le droit d’y vivre. C’est la forme la plus connue du viager. Le prix tient compte de l’occupation du logement.'],
        ['title' => 'Viager libre', 'text' => 'L’acquéreur peut disposer du bien immédiatement. Cette solution concerne plutôt les biens déjà libres ou les vendeurs qui souhaitent quitter le logement.'],
        ['title' => 'Vente à terme', 'text' => 'Le prix est payé sur une durée définie à l’avance. Cette solution peut parfois être plus lisible qu’un viager classique selon le projet.'],
    ];
    $studyItems = [
        'la valeur réelle du bien sur le marché local',
        'l’état général du logement',
        'la commune et la demande locale',
        'l’occupation ou non du bien',
        'l’âge et la situation du ou des vendeurs',
        'l’équilibre entre bouquet et rente',
        'la cohérence avec les attentes des acquéreurs',
    ];
    $reasons = [
        ['title' => 'Comprendre vos options', 'text' => 'Viager occupé, viager libre, vente à terme ou vente classique : toutes les solutions ne se valent pas selon votre situation.'],
        ['title' => 'Éviter une mauvaise décision', 'text' => 'Le viager engage le vendeur, l’acquéreur et parfois la famille. Il faut donc prendre le temps de poser les bonnes bases.'],
        ['title' => 'Obtenir une vision réaliste', 'text' => 'Une étude locale permet d’analyser le bien, le marché et la faisabilité du projet.'],
        ['title' => 'Échanger avec un conseiller local', 'text' => 'Vous pouvez poser vos questions simplement, sans engagement et en toute confidentialité.'],
    ];
    $faqs = [
        ['question' => 'Qu’est-ce qu’une vente en viager ?', 'answer' => 'La vente en viager consiste à vendre un bien immobilier en échange d’un bouquet, d’une rente ou d’une combinaison des deux. Selon le type de viager, le vendeur peut continuer à occuper le logement.'],
        ['question' => 'Quelle est la différence entre viager occupé et viager libre ?', 'answer' => 'En viager occupé, le vendeur conserve le droit d’habiter le logement. En viager libre, l’acquéreur peut utiliser ou louer le bien immédiatement.'],
        ['question' => 'Peut-on vendre en viager en Côte-d’Or ou dans l’Auxois Morvan ?', 'answer' => 'Oui, un projet viager peut être étudié autour de Beaune, Autun, Arnay-le-Duc, Pouilly-en-Auxois, Semur-en-Auxois et dans les communes voisines, à condition que le bien et la situation soient adaptés.'],
        ['question' => 'Le viager est-il adapté à tous les propriétaires ?', 'answer' => 'Non. Le viager doit être étudié au cas par cas. L’âge, la situation familiale, la valeur du bien, l’état du logement et les objectifs du vendeur doivent être analysés.'],
        ['question' => 'Peut-on rester chez soi après une vente en viager ?', 'answer' => 'Oui, dans le cadre d’un viager occupé, le vendeur peut conserver le droit de vivre dans le logement selon les conditions prévues dans l’acte de vente.'],
        ['question' => 'Combien coûte une étude viager ?', 'answer' => 'L’étude proposée par Immobilier Auxois Morvan est gratuite et sans engagement.'],
        ['question' => 'Est-ce confidentiel ?', 'answer' => 'Oui. Une demande d’étude viager reste confidentielle et sert uniquement à échanger sur votre projet.'],
    ];
    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => 'Étude viager en Côte-d’Or et Auxois Morvan',
            'serviceType' => 'Accompagnement immobilier local pour projet viager',
            'provider' => ['@id' => cms_absolute_url('/#real-estate-agent')],
            'areaServed' => array_map(static fn (string $area): array => ['@type' => 'Place', 'name' => $area], array_slice($areas, 0, 8)),
            'url' => cms_absolute_url('/viager'),
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => cms_absolute_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Viager', 'item' => cms_absolute_url('/viager')],
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs),
        ],
    ];

    cms_render_public_document_start($title, $description, true, $structuredData, ['canonical' => cms_absolute_url('/viager')]);
    cms_render_public_header($settings, '/viager');
    ?>
    <main class="viager-seo-page">
      <section class="viager-seo-hero">
        <div class="shell viager-seo-hero-grid">
          <div class="viager-seo-hero-copy">
            <p class="eyebrow">Viager local &amp; confidentiel</p>
            <h1>Vendre en viager en Côte-d’Or et Auxois Morvan</h1>
            <h2>Vendre en viager en Côte-d’Or et Auxois Morvan</h2>
            <p class="viager-local-line"><?= cms_h($localLine) ?></p>
            <p class="hero-text">De Beaune à Autun, en passant par Arnay-le-Duc, Pouilly-en-Auxois et Semur-en-Auxois, nous vous aidons à étudier gratuitement et confidentiellement votre projet de vente en viager.</p>
            <div class="viager-seo-actions">
              <a class="button primary" href="<?= cms_h($ctaUrl) ?>"><?= cms_h($ctaLabel) ?></a>
              <a class="button secondary" href="#comprendre-viager">Comprendre le viager</a>
            </div>
          </div>
          <aside class="viager-hero-card" aria-label="Réassurances">
            <div class="viager-hero-card-head">
              <p>Votre première étude</p>
              <strong>Un avis local, clair et confidentiel.</strong>
            </div>
            <div class="viager-hero-card-list">
              <span>Étude gratuite</span>
              <span>Confidentiel</span>
              <span>Conseiller local</span>
              <span>Sans engagement</span>
            </div>
            <div class="viager-hero-card-note">Réponse sous 24h après réception de votre demande.</div>
          </aside>
        </div>
      </section>

      <section id="comprendre-viager" class="viager-seo-section">
        <div class="shell viager-intro-grid">
          <article class="viager-panel is-main">
            <p class="eyebrow">Comprendre simplement</p>
            <h2>Le viager, une solution pour vendre autrement</h2>
            <p>Le viager permet à un propriétaire de vendre son bien tout en conservant, selon la formule choisie, le droit d’y vivre.</p>
            <details class="viager-more-details">
              <summary>En savoir plus</summary>
              <p>En contrepartie, le vendeur peut recevoir un capital initial, appelé bouquet, et/ou une rente versée dans le temps. C’est une solution qui peut permettre de mieux vivre sa retraite, d’anticiper la transmission de son patrimoine ou de rester plus sereinement à domicile.</p>
            </details>
          </article>
          <aside class="viager-note-card">Le viager ne convient pas à toutes les situations. C’est pourquoi une étude personnalisée est indispensable avant de prendre une décision.</aside>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell">
          <div class="viager-section-head"><p class="eyebrow">Profils concernés</p><h2>À qui s’adresse la vente en viager ?</h2></div>
          <div class="viager-card-grid four-cols">
            <?php foreach ($audiences as $item): ?>
              <article class="viager-info-card"><h3><?= cms_h($item['title']) ?></h3><p><?= cms_h($item['text']) ?></p></article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell">
          <article class="viager-family-card">
            <p class="eyebrow">Famille &amp; transmission</p>
            <h2>Et si ma famille se pose des questions ?</h2>
            <p>Le viager peut soulever des interrogations chez les proches, notamment lorsqu’il touche à la maison familiale ou à la succession. Notre rôle est de vous expliquer les options simplement, sans pression, afin que vous puissiez prendre une décision claire et en parler sereinement avec votre famille.</p>
          </article>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell">
          <div class="viager-section-head"><p class="eyebrow">Solutions possibles</p><h2>Viager occupé, viager libre, vente à terme : quelles différences ?</h2></div>
          <div class="viager-card-grid three-cols viager-accordion-grid" data-accordion-group="formes">
            <?php foreach ($forms as $item): ?>
              <details class="viager-info-card viager-mobile-accordion" open>
                <summary><?= cms_h($item['title']) ?></summary>
                <p><?= cms_h($item['text']) ?></p>
              </details>
            <?php endforeach; ?>
          </div>
          <div class="viager-section-cta"><a class="button primary" href="<?= cms_h($ctaUrl) ?>"><?= cms_h($ctaLabel) ?></a></div>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell">
          <article class="viager-example-card">
            <div class="viager-example-copy">
              <p class="eyebrow">Exemple concret</p>
              <h2>Exemple concret : rester chez soi tout en obtenant un complément de revenu</h2>
              <p>Marie, 76 ans, est propriétaire d’une maison estimée à 180 000 €. Elle souhaite continuer à vivre dans sa maison, mais aimerait disposer d’un capital immédiat et d’un revenu complémentaire chaque mois.</p>
              <p>Dans le cadre d’un viager occupé, une étude pourrait par exemple permettre de rester chez elle tout en combinant un bouquet et une rente mensuelle.</p>
              <div class="viager-example-warning">Exemple fictif, non contractuel, donné à titre illustratif. Les montants varient selon la valeur du bien, l’âge du vendeur, l’occupation du logement, l’état du bien, le bouquet souhaité et le marché local.</div>
              <a class="button primary" href="<?= cms_h($ctaUrl) ?>"><?= cms_h($ctaLabel) ?></a>
            </div>
            <div class="viager-example-numbers" aria-label="Chiffres fictifs de l’exemple">
              <div><span>Maison estimée</span><strong>180 000 €</strong></div>
              <div><span>Bouquet possible</span><strong>35 000 €</strong></div>
              <div><span>Rente mensuelle possible</span><strong>450 €</strong></div>
              <div><span>Occupation</span><strong>Marie reste chez elle</strong></div>
            </div>
          </article>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell viager-study-grid">
          <article class="viager-panel">
            <p class="eyebrow">Étude personnalisée</p>
            <h2>Comment se calcule une étude viager ?</h2>
            <p>Avant de parler de bouquet ou de rente, nous commençons par comprendre votre situation : souhaitez-vous rester dans le logement, obtenir un capital immédiat, sécuriser un complément de revenu ou simplement avoir un avis clair avant d’en parler à vos proches ?</p>
            <p>Une étude viager sérieuse ne se résume pas à un calcul automatique. Elle croise le bien, la localisation, la situation du vendeur et l’équilibre possible entre les différentes solutions.</p>
            <div class="viager-note-inline">Nous ne promettons pas une rente automatique en ligne. L’objectif est d’abord de vérifier si le viager est une solution adaptée à votre situation.</div>
          </article>
          <div class="viager-factor-list" data-accordion-group="etude">
            <?php foreach ($studyItems as $index => $item): ?>
              <details class="viager-factor-item viager-mobile-accordion" open>
                <summary><span><?= cms_h(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><?= cms_h($item) ?></summary>
                <p>Ce point est analysé avec prudence pour replacer votre projet dans le marché local et dans votre situation réelle.</p>
              </details>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell viager-local-panel">
          <div><p class="eyebrow">Secteur local</p><h2>Un accompagnement local en Côte-d’Or et Auxois Morvan</h2><p>Immobilier Auxois Morvan accompagne les propriétaires sur un secteur local couvrant notamment Beaune, Autun, Arnay-le-Duc, Pouilly-en-Auxois, Semur-en-Auxois, Saulieu, Vitteaux et les communes voisines. L’objectif est de proposer une approche humaine, claire et confidentielle, adaptée au marché immobilier local.</p></div>
          <div class="viager-area-tags"><?php foreach ($areas as $area): ?><span><?= cms_h($area) ?></span><?php endforeach; ?></div>
        </div>
      </section>

      <section class="viager-seo-section">
        <div class="shell">
          <div class="viager-section-head"><p class="eyebrow">Avant de décider</p><h2>Pourquoi demander une étude avant de décider ?</h2></div>
          <div class="viager-card-grid four-cols viager-accordion-grid" data-accordion-group="raisons">
            <?php foreach ($reasons as $item): ?>
              <details class="viager-info-card viager-mobile-accordion" open>
                <summary><?= cms_h($item['title']) ?></summary>
                <p><?= cms_h($item['text']) ?></p>
              </details>
            <?php endforeach; ?>
          </div>
          <div class="viager-section-cta"><a class="button secondary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Demander une estimation classique</a></div>
        </div>
      </section>

      <section class="viager-seo-section viager-faq-section">
        <div class="shell viager-faq-grid">
          <div><p class="eyebrow">Questions fréquentes</p><h2>FAQ sur le viager en Côte-d’Or et Auxois Morvan</h2><p>Des réponses simples pour cadrer les premières questions, avant une étude personnalisée.</p></div>
          <div class="viager-faq-list" data-accordion-group="faq">
            <?php foreach ($faqs as $faq): ?>
              <details class="viager-faq-item">
                <summary><?= cms_h($faq['question']) ?></summary>
                <p><?= cms_h($faq['answer']) ?></p>
              </details>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="viager-seo-section viager-final-section">
        <div class="shell">
          <div class="viager-final-cta">
            <div><p class="eyebrow">Étude offerte</p><h2>Vous souhaitez savoir si le viager est adapté à votre situation ?</h2><p>Décrivez votre bien et votre projet en quelques étapes. Nous vous recontactons sous 24h pour échanger sur les solutions possibles, gratuitement et sans engagement.</p><span>Gratuit · Confidentiel · Local · Sans engagement</span></div>
            <div class="viager-final-actions"><a class="button primary" href="<?= cms_h($ctaUrl) ?>"><?= cms_h($ctaLabel) ?></a></div>
          </div>
        </div>
      </section>
      <a class="viager-sticky-cta" href="<?= cms_h($ctaUrl) ?>">Étude viager gratuite</a>
    </main>
    <script>
      (() => {
        const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;
        const groupedDetails = Array.from(document.querySelectorAll('.viager-seo-page [data-accordion-group] details'));
        const mobileDetails = Array.from(document.querySelectorAll('.viager-mobile-accordion'));
        const syncDetails = () => {
          mobileDetails.forEach((detail) => { detail.open = !isMobile(); });
        };
        groupedDetails.forEach((detail) => {
          detail.addEventListener('toggle', () => {
            if (!detail.open || !isMobile()) return;
            const group = detail.closest('[data-accordion-group]');
            if (!group) return;
            group.querySelectorAll('details').forEach((other) => {
              if (other !== detail) other.open = false;
            });
          });
        });
      })();
    </script>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_contact_page(array $page, array $settings, array $snapshot): void
{
    $areas = array_slice($snapshot['siteSettings']['coveredAreas'] ?? cms_json_list($settings['covered_areas_json'] ?? '[]'), 0, 8);
    $errors = $page['_contact_errors'] ?? [];
    $success = ($_GET['merci'] ?? '') === '1';
    $mickaelPhoto = trim((string) ($settings['mickael_photo'] ?? ''));
    $marionPhoto = trim((string) ($settings['marion_photo'] ?? ''));

    cms_render_public_document_start((string) $page['title'] . ' | ' . (string) $settings['site_name'], (string) ($page['meta_description'] ?? $settings['baseline']), (int) ($page['is_indexable'] ?? 1) === 1);
    cms_render_public_header($settings, (string) ($page['slug'] ?? '/contact'));
    ?>
    <main class="contact-premium-page">
      <section class="contact-hero">
        <div class="shell contact-hero-grid">
          <div class="contact-hero-copy">
            <p class="eyebrow">Contact direct</p>
            <h1>Parlons simplement de votre projet immobilier.</h1>
            <p class="hero-text">Vendre, acheter, estimer un bien ou préparer une cession : un premier échange permet de comprendre votre situation, votre secteur et la bonne manière d’avancer.</p>
            <div class="contact-direct-card">
              <span>Réponse rapide</span>
              <a href="<?= cms_h('tel:' . preg_replace('/\s+/', '', (string) $settings['phone'])) ?>"><?= cms_h((string) $settings['phone']) ?></a>
              <a href="<?= cms_h('mailto:' . (string) $settings['email']) ?>"><?= cms_h((string) $settings['email']) ?></a>
            </div>
            <div class="contact-advisor-compact-grid">
              <article class="contact-advisor-compact-card">
                <?php if ($marionPhoto !== ''): ?><?php cms_render_image($marionPhoto, (string) $settings['marion_name'], ['sizes' => '56px']); ?><?php else: ?><span><?= cms_h(substr((string) $settings['marion_name'], 0, 1)) ?></span><?php endif; ?>
                <div><strong><?= cms_h((string) $settings['marion_name']) ?></strong><small>Suivi · écoute · coordination</small></div>
              </article>
              <article class="contact-advisor-compact-card">
                <?php if ($mickaelPhoto !== ''): ?><?php cms_render_image($mickaelPhoto, (string) $settings['mickael_name'], ['sizes' => '56px']); ?><?php else: ?><span><?= cms_h(substr((string) $settings['mickael_name'], 0, 1)) ?></span><?php endif; ?>
                <div><strong><?= cms_h((string) $settings['mickael_name']) ?></strong><small>Terrain · stratégie · négociation</small></div>
              </article>
            </div>
          </div>

          <form class="contact-premium-form" method="post" action="<?= cms_h(cms_url('/contact')) ?>">
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden-field" aria-hidden="true">
            <p class="eyebrow">Votre demande</p>
            <h2>Expliquez-nous votre projet</h2>
            <p class="contact-form-lead">Quelques informations suffisent pour vous répondre de manière utile et concrète.</p>

            <?php if ($success): ?>
              <div class="contact-alert success">Merci, votre demande a bien été transmise. Nous revenons vers vous rapidement.</div>
            <?php endif; ?>
            <?php if ($errors): ?>
              <div class="contact-alert error"><?= cms_h(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <div class="contact-fields two">
              <label>Votre projet<select name="project"><option value="">Choisir</option><option value="Vendre">Vendre</option><option value="Acheter">Acheter</option><option value="Estimation">Faire estimer</option><option value="Fonds de commerce">Fonds de commerce</option></select></label>
              <label>Objet<select name="subject" required><option value="">Choisir</option><option value="Premier rendez-vous">Premier rendez-vous</option><option value="Demande d'estimation">Demande d'estimation</option><option value="Recherche de bien">Recherche de bien</option><option value="Autre demande">Autre demande</option></select></label>
            </div>
            <div class="contact-fields two">
              <label>Nom<input name="name" placeholder="Votre nom" required></label>
              <label>Email<input type="email" name="email" placeholder="vous@exemple.fr" required></label>
            </div>
            <div class="contact-fields two">
              <label>Téléphone<input type="tel" name="phone" placeholder="06 12 34 56 78"></label>
              <label>Localisation<input name="location" placeholder="Arnay-le-Duc, Autun..."></label>
            </div>
            <label>Message<textarea name="message" placeholder="Décrivez votre bien, votre recherche, votre secteur ou votre calendrier." required></textarea></label>
            <label class="privacy-line"><input type="checkbox" name="privacy" value="1" required><span>J’accepte d’être recontacté au sujet de ma demande.</span></label>
            <button class="button primary contact-submit" type="submit">Envoyer le message</button>
          </form>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell contact-proof-grid">
          <article class="contact-proof-card is-dark">
            <p class="eyebrow">Ce que nous clarifions</p>
            <h2>Un échange court pour poser les bons repères.</h2>
            <p>Nous prenons le temps de comprendre votre situation avant de proposer une suite : estimation, rendez-vous, conseil secteur, mise en vente ou orientation vers la bonne prestation.</p>
          </article>
          <article class="contact-proof-card">
            <h3>À préparer si vous l’avez</h3>
            <ul>
              <li>Commune et type de bien</li>
              <li>Objectif : vendre, acheter, estimer, transmettre</li>
              <li>Calendrier souhaité</li>
              <li>Questions ou contraintes importantes</li>
            </ul>
          </article>
          <article class="contact-proof-card">
            <h3>Secteur couvert</h3>
            <div class="contact-tags"><?php foreach ($areas as $area): ?><span><?= cms_h((string) $area) ?></span><?php endforeach; ?></div>
          </article>
        </div>
      </section>

      <section class="section section-tight"><div class="shell"><div class="cta-band"><div><p class="eyebrow">Estimation en ligne</p><h2>Vous souhaitez d’abord obtenir un premier avis de valeur ?</h2><div class="richtext"><p>Le formulaire d’estimation vous guide étape par étape pour nous transmettre les informations utiles sur votre bien.</p></div></div><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Demander une estimation</a></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_public_nav_items(bool $blogEnabled = false): array
{
    $items = [
        ['label' => 'Histoire', 'href' => '/histoire'],
        ['label' => 'Secteur', 'href' => '/secteur'],
        ['label' => 'Prestations', 'href' => '/prestations'],
        ['label' => 'Avis clients', 'href' => '/avis'],
    ];

    if ($blogEnabled) {
        $items[] = ['label' => 'Blog', 'href' => '/blog'];
    }

    return $items;
}

function cms_is_active_nav(string $currentPage, string $href): bool
{
    if ($href === '/') {
        return $currentPage === '/';
    }

    if (str_starts_with($href, '/#')) {
        return false;
    }

    return str_starts_with($currentPage, $href);
}

function cms_render_public_document_start(string $title, string $description, bool $indexable = true, array $structuredData = [], array $meta = []): void
{
    $svgFaviconVersion = (string) (@filemtime(__DIR__ . '/../favicon.svg') ?: time());
    $icoFaviconVersion = (string) (@filemtime(__DIR__ . '/../favicon.ico') ?: time());
    $canonicalUrl = (string) ($meta['canonical'] ?? cms_current_canonical_url());
    $settings = cms_settings();
    $siteName = (string) ($settings['site_name'] ?? 'Immobilier Auxois Morvan');
    $ogType = (string) ($meta['type'] ?? 'website');
    $ogImage = (string) ($meta['image'] ?? cms_absolute_url('/uploads/auxois.jpg'));
    $preloadImage = trim((string) ($meta['preload_image'] ?? ''));
    $preloadImageSizes = trim((string) ($meta['preload_image_sizes'] ?? '100vw'));
    $preloadImageSrcset = $preloadImage !== '' ? cms_optimized_image_srcset($preloadImage) : '';
    $extraStylesheets = is_array($meta['stylesheets'] ?? null) ? $meta['stylesheets'] : [];
    if (preg_match('#^https?://#i', $ogImage) !== 1) {
        $ogImage = cms_absolute_url($ogImage);
    }
    $globalStructuredData = [
      [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateAgent',
        '@id' => cms_absolute_url('/#real-estate-agent'),
        'name' => $siteName,
        'url' => cms_absolute_url('/'),
        'logo' => cms_absolute_url('/uploads/logo-2.png'),
        'image' => cms_absolute_url('/uploads/auxois.jpg'),
        'telephone' => (string) ($settings['phone'] ?? ''),
        'email' => (string) ($settings['email'] ?? ''),
        'areaServed' => array_map(static fn (string $area): array => ['@type' => 'Place', 'name' => $area], cms_json_list($settings['covered_areas_json'] ?? '[]')),
        'sameAs' => array_values(array_filter([(string) ($settings['facebook_url'] ?? ''), (string) ($settings['instagram_url'] ?? ''), (string) ($settings['iad_url'] ?? '')])),
      ],
      [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => cms_absolute_url('/#website'),
        'name' => $siteName,
        'url' => cms_absolute_url('/'),
        'publisher' => ['@id' => cms_absolute_url('/#real-estate-agent')],
        'inLanguage' => 'fr-FR',
      ],
    ];
    $structuredData = array_merge($globalStructuredData, $structuredData);
    $googleAnalyticsId = trim((string) (cms_config()['google_analytics_id'] ?? ''));
    if ($googleAnalyticsId !== '' && !preg_match('/^G-[A-Z0-9]+$/', $googleAnalyticsId)) {
        $googleAnalyticsId = '';
    }
    ?>
    <!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <?php if ($googleAnalyticsId !== ''): ?>
          <!-- Google tag (gtag.js) -->
          <script async src="https://www.googletagmanager.com/gtag/js?id=<?= cms_h($googleAnalyticsId) ?>"></script>
          <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', <?= json_encode($googleAnalyticsId, JSON_UNESCAPED_SLASHES) ?>);
          </script>
        <?php endif; ?>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= cms_h($description) ?>">
        <?php if (!$indexable): ?>
          <meta name="robots" content="noindex,nofollow">
        <?php else: ?>
          <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
        <?php endif; ?>
        <link rel="canonical" href="<?= cms_h($canonicalUrl) ?>">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:site_name" content="<?= cms_h($siteName) ?>">
        <meta property="og:type" content="<?= cms_h($ogType) ?>">
        <meta property="og:title" content="<?= cms_h($title) ?>">
        <meta property="og:description" content="<?= cms_h($description) ?>">
        <meta property="og:url" content="<?= cms_h($canonicalUrl) ?>">
        <meta property="og:image" content="<?= cms_h($ogImage) ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?= cms_h($title) ?>">
        <meta name="twitter:description" content="<?= cms_h($description) ?>">
        <meta name="twitter:image" content="<?= cms_h($ogImage) ?>">
        <?php foreach ($structuredData as $block): ?>
          <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
        <?php endforeach; ?>
        <title><?= cms_h($title) ?></title>
        <link rel="icon" type="image/svg+xml" sizes="any" href="<?= cms_h(cms_url('/favicon.svg')) ?>?v=<?= cms_h($svgFaviconVersion) ?>">
        <link rel="alternate icon" type="image/x-icon" href="<?= cms_h(cms_url('/favicon.ico')) ?>?v=<?= cms_h($icoFaviconVersion) ?>">
        <link rel="preload" href="<?= cms_h(cms_url('/assets/fonts/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
        <?php if ($preloadImage !== ''): ?>
          <link rel="preload" as="image" href="<?= cms_h(cms_image_url($preloadImage)) ?>"<?php if ($preloadImageSrcset !== ''): ?> imagesrcset="<?= cms_h($preloadImageSrcset) ?>" imagesizes="<?= cms_h($preloadImageSizes) ?>" type="image/webp"<?php endif; ?> fetchpriority="high">
        <?php endif; ?>
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>?v=<?= cms_h((string) (@filemtime(__DIR__ . '/../assets/site.css') ?: time())) ?>">
        <?php foreach ($extraStylesheets as $stylesheet): ?>
          <?php if (is_array($stylesheet) && !empty($stylesheet['href'])): ?>
            <link rel="stylesheet" href="<?= cms_h((string) $stylesheet['href']) ?>"<?php if (!empty($stylesheet['integrity'])): ?> integrity="<?= cms_h((string) $stylesheet['integrity']) ?>"<?php endif; ?><?php if (array_key_exists('crossorigin', $stylesheet)): ?> crossorigin="<?= cms_h((string) $stylesheet['crossorigin']) ?>"<?php endif; ?>>
          <?php endif; ?>
        <?php endforeach; ?>
      </head>
      <body>
    <?php
}

function cms_render_public_header(array $settings, string $currentPage): void
{
    $blogEnabled = cms_is_blog_public_enabled($settings);
    $navItems = cms_public_nav_items($blogEnabled);
    ?>
    <header class="site-header">
      <div class="shell">
        <nav class="site-header-bar">
          <a class="site-logo-link" href="<?= cms_h(cms_url('/')) ?>" aria-label="Accueil Immobilier Auxois Morvan">
            <?php cms_render_image('/uploads/logo-2.png', 'Immobilier Auxois Morvan', ['class' => 'site-logo', 'loading' => 'eager', 'sizes' => '180px']); ?>
          </a>
          <div class="site-nav desktop-only">
            <?php foreach ($navItems as $item): ?>
              <a class="site-nav-link<?= cms_is_active_nav($currentPage, (string) $item['href']) ? ' is-active' : '' ?>" href="<?= cms_h(cms_url((string) $item['href'])) ?>"><?= cms_h((string) $item['label']) ?></a>
            <?php endforeach; ?>
          </div>
          <a class="site-cta desktop-only" href="<?= cms_h(cms_url('/contact')) ?>">Contactez-nous</a>
          <button
            class="mobile-menu-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="site-mobile-menu"
            aria-label="Ouvrir le menu"
            data-mobile-menu-toggle
          >
            <span></span>
            <span></span>
            <span></span>
          </button>
        </nav>
        <div class="site-mobile-menu" id="site-mobile-menu" hidden>
          <div class="site-mobile-menu-inner">
            <div class="site-mobile-nav">
              <?php foreach ($navItems as $item): ?>
                <a class="site-mobile-nav-link<?= cms_is_active_nav($currentPage, (string) $item['href']) ? ' is-active' : '' ?>" href="<?= cms_h(cms_url((string) $item['href'])) ?>"><?= cms_h((string) $item['label']) ?></a>
              <?php endforeach; ?>
            </div>
            <a class="site-mobile-cta" href="<?= cms_h(cms_url('/contact')) ?>">Contactez-nous</a>
          </div>
        </div>
      </div>
    </header>
    <?php
}

function cms_render_estimation_header(array $settings, string $label = 'Estimation gratuite'): void
{
    ?>
    <header class="estimate-header">
      <div class="shell estimate-header-shell">
        <a class="estimate-header-brand" href="<?= cms_h(cms_url('/')) ?>" aria-label="Retour à l'accueil Immobilier Auxois Morvan">
          <?php cms_render_image('/uploads/logo-2.png', 'Immobilier Auxois Morvan', ['class' => 'estimate-header-logo', 'loading' => 'eager', 'sizes' => '180px']); ?>
        </a>
        <span class="estimate-header-cta"><?= cms_h($label) ?></span>
      </div>
    </header>
    <?php
}

function cms_render_public_footer(array $settings, array $snapshot): void
{
    $areas = array_slice($snapshot['siteSettings']['coveredAreas'] ?? cms_json_list($settings['covered_areas_json'] ?? '[]'), 0, 6);
    $mobileAreas = array_slice($areas, 0, 4);
    $services = $snapshot['services'] ?? [];
    $footerServices = [
      ['title' => 'Estimation gratuite', 'href' => '/estimation-en-ligne'],
      ['title' => 'Vendre', 'href' => '/vendre'],
      ['title' => 'Acheter', 'href' => '/acheter'],
      ['title' => 'Viager', 'href' => '/viager'],
      ['title' => 'Fonds de commerce', 'href' => '/fonds'],
    ];
    $facebookUrl = 'https://www.facebook.com/profile.php?id=61589488680956';
    ?>
    <footer class="site-footer">
      <div class="shell footer-shell">
        <div class="footer-mobile-stack">
          <div class="footer-brand-column">
            <?php cms_render_image('/uploads/logo-2.png', 'Immobilier Auxois Morvan', ['class' => 'footer-logo', 'sizes' => '180px']); ?>
            <p class="footer-copy"><?= cms_h((string) $settings['footer_text']) ?></p>
          </div>
          <div class="footer-contact-card">
            <p class="eyebrow">Contact</p>
            <a href="<?= cms_h('tel:' . preg_replace('/\s+/', '', (string) $settings['phone'])) ?>"><?= cms_h((string) $settings['phone']) ?></a>
            <a class="footer-contact-email" href="<?= cms_h('mailto:' . (string) $settings['email']) ?>"><?= cms_h((string) $settings['email']) ?></a>
          </div>
          <details class="footer-accordion">
            <summary>Prestations</summary>
            <div class="footer-accordion-content">
              <?php foreach ($footerServices as $service): ?>
                <a href="<?= cms_h(cms_url((string) $service['href'])) ?>"><?= cms_h((string) $service['title']) ?></a>
              <?php endforeach; ?>
            </div>
          </details>
          <details class="footer-accordion">
            <summary>Secteur</summary>
            <div class="footer-accordion-content">
              <div class="footer-mobile-tags"><?php foreach ($mobileAreas as $area): ?><span><?= cms_h((string) $area) ?></span><?php endforeach; ?></div>
              <a href="<?= cms_h(cms_url('/secteur')) ?>">Voir tout le secteur</a>
            </div>
          </details>
        </div>

        <div class="footer-top footer-desktop-grid">
          <div class="footer-brand-column">
            <?php cms_render_image('/uploads/logo-2.png', 'Immobilier Auxois Morvan', ['class' => 'footer-logo', 'sizes' => '180px']); ?>
            <p class="footer-copy"><?= cms_h((string) $settings['footer_text']) ?></p>
            <div class="footer-socials">
              <a href="<?= cms_h($facebookUrl) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
              <?php if (!empty($settings['iad_url'])): ?><a href="<?= cms_h((string) $settings['iad_url']) ?>">IAD</a><?php endif; ?>
            </div>
          </div>
          <div class="footer-columns">
            <div>
              <h3>Services</h3>
              <?php foreach ($footerServices as $service): ?>
                <a href="<?= cms_h(cms_url((string) $service['href'])) ?>"><?= cms_h((string) $service['title']) ?></a>
              <?php endforeach; ?>
            </div>
            <div>
              <h3>Secteur</h3>
              <?php foreach (array_slice($areas, 0, 4) as $area): ?>
                <span><?= cms_h((string) $area) ?></span>
              <?php endforeach; ?>
              <a href="<?= cms_h(cms_url('/secteur')) ?>">Voir tout le secteur</a>
            </div>
            <div>
              <h3>Contact</h3>
              <span><?= cms_h((string) $settings['marion_name']) ?></span>
              <span><?= cms_h((string) $settings['mickael_name']) ?></span>
              <span><?= cms_h((string) $settings['main_city']) ?></span>
              <a href="<?= cms_h('tel:' . preg_replace('/\s+/', '', (string) $settings['phone'])) ?>"><?= cms_h((string) $settings['phone']) ?></a>
              <a class="footer-contact-email" href="<?= cms_h('mailto:' . (string) $settings['email']) ?>"><?= cms_h((string) $settings['email']) ?></a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; <?= cms_h(date('Y')) ?> <?= cms_h((string) $settings['site_name']) ?></p>
          <div>
            <a href="<?= cms_h(cms_url('/contact')) ?>">Mentions légales</a>
            <a href="<?= cms_h(cms_url('/contact')) ?>">Politique de confidentialité</a>
          </div>
        </div>
      </div>
    </footer>
    <script>
      (() => {
        const toggle = document.querySelector('[data-mobile-menu-toggle]');
        const menu = document.getElementById('site-mobile-menu');
        const serviceCards = Array.from(document.querySelectorAll('.services-grid > details.service-card'));

        const isMobileServicesAccordion = () => window.matchMedia('(max-width: 767.98px)').matches;

        const syncServiceCardsMode = () => {
          if (serviceCards.length === 0) {
            return;
          }

          if (!isMobileServicesAccordion()) {
            serviceCards.forEach((card) => {
              card.open = true;
            });
            return;
          }

          const activeCard = serviceCards.find((card) => card.open) || serviceCards[0];
          serviceCards.forEach((card) => {
            card.open = card === activeCard;
          });
        };

        serviceCards.forEach((card) => {
          const summary = card.querySelector('summary');
          if (summary) {
            summary.addEventListener('click', (event) => {
              if (isMobileServicesAccordion()) {
                return;
              }

              event.preventDefault();
              serviceCards.forEach((serviceCard) => {
                serviceCard.open = true;
              });
            });
          }

          card.addEventListener('toggle', () => {
            if (!isMobileServicesAccordion()) {
              if (!card.open) {
                card.open = true;
              }
              return;
            }

            if (!card.open || !isMobileServicesAccordion()) {
              return;
            }

            serviceCards.forEach((otherCard) => {
              if (otherCard !== card) {
                otherCard.open = false;
              }
            });
          });
        });

        syncServiceCardsMode();

        if (!toggle || !menu) {
          return;
        }

        const closeMenu = () => {
          toggle.setAttribute('aria-expanded', 'false');
          toggle.setAttribute('aria-label', 'Ouvrir le menu');
          menu.hidden = true;
          document.body.classList.remove('mobile-menu-open');
        };

        const openMenu = () => {
          toggle.setAttribute('aria-expanded', 'true');
          toggle.setAttribute('aria-label', 'Fermer le menu');
          menu.hidden = false;
          document.body.classList.add('mobile-menu-open');
        };

        toggle.addEventListener('click', () => {
          if (menu.hidden) {
            openMenu();
            return;
          }

          closeMenu();
        });

        menu.querySelectorAll('a').forEach((link) => {
          link.addEventListener('click', closeMenu);
        });

        window.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            closeMenu();
          }
        });

        window.addEventListener('resize', () => {
          syncServiceCardsMode();

          if (window.innerWidth >= 1024) {
            closeMenu();
          }
        });
      })();
    </script>
    </body>
    </html>
    <?php
}

function cms_visible_sections(array $page): array
{
  return cms_page_sections($page);
}

function cms_render_homepage(array $page, array $settings, array $snapshot): void
{
    $blogEnabled = cms_is_blog_public_enabled($settings);
    $heroImage = trim((string) ($page['hero_image'] ?? ''));
    $allAreas = $snapshot['siteSettings']['coveredAreas'] ?? cms_json_list($settings['covered_areas_json'] ?? '[]');
    $areaPriority = ['Arnay-le-Duc', 'Pouilly-en-Auxois', 'Autun', 'Saulieu', 'Beaune', 'Dijon', 'Semur-en-Auxois', 'Vitteaux'];
    $featuredAreas = array_values(array_slice(array_values(array_filter($areaPriority, static fn (string $area): bool => in_array($area, $allAreas, true))), 0, 6));
    $humanCities = array_values(array_slice(array_values(array_filter(['Arnay-le-Duc', 'Pouilly-en-Auxois', 'Autun', 'Beaune'], static fn (string $area): bool => in_array($area, $allAreas, true))), 0, 4));
    $services = $snapshot['services'] ?? [];
    $localPageByHref = [];
    foreach (($snapshot['localPages'] ?? []) as $localPage) {
        if (!is_array($localPage)) {
            continue;
        }

        $localPageByHref[(string) ($localPage['href'] ?? '')] = $localPage;
    }

    $localPages = [];
    foreach (['/estimation-immobiliere-arnay-le-duc', '/estimation-immobiliere-pouilly-en-auxois', '/vendre-maison-autun'] as $href) {
        if (isset($localPageByHref[$href])) {
            $localPages[] = $localPageByHref[$href];
        }
    }
    $blogPosts = $blogEnabled ? array_slice($snapshot['blogPosts'] ?? [], 0, 3) : [];
    $testimonials = array_slice($snapshot['testimonials'] ?? [], 0, 3);
    $trustReasons = [
        ['title' => 'Estimation argumentée', 'text' => 'Un avis de valeur lisible, ancré dans le marché local et défendu avec méthode.'],
        ['title' => 'Stratégie de mise en vente', 'text' => 'Un plan d’action clair pour positionner le bien, cibler les bons acquéreurs et tenir le cap.'],
        ['title' => 'Mise en valeur du bien', 'text' => 'Des visuels, un discours et une présentation pensés pour renforcer l’impact dès les premières visites.'],
        ['title' => 'Suivi jusqu’à la signature', 'text' => 'Un accompagnement régulier, humain et structuré, de l’échange initial à l’acte authentique.'],
    ];
    $projectSteps = [
        'Échange sur votre projet',
        'Estimation du bien',
        'Stratégie de mise en vente',
        'Diffusion, visites et suivi',
        'Négociation et signature',
    ];
    $homeTitle = 'Vendre, acheter ou estimer un bien en Auxois-Morvan';
    $homeDescription = 'Mickael Gury et Marion Roullier vous accompagnent localement pour vendre, acheter ou estimer un bien en Auxois-Morvan, avec le réseau IAD, de l’estimation à la signature.';
    $homeSubtitle = 'Mickael Gury & Marion Roullier vous accompagnent localement avec le réseau IAD, de l’estimation à la signature.';
    $mickaelPhoto = trim((string) ($settings['mickael_photo'] ?? ''));
    $marionPhoto = trim((string) ($settings['marion_photo'] ?? ''));
    $appUrl = rtrim((string) (cms_config()['app_url'] ?? ''), '/');
    $homeUrl = $appUrl !== '' ? $appUrl . cms_url('/') : cms_url('/');
    $heroImageUrl = $heroImage !== '' ? cms_url($heroImage) : cms_url('/uploads/auxois.jpg');
    if ($appUrl !== '') {
      $heroImageUrl = $appUrl . $heroImageUrl;
    }
    $structuredData = [[
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateAgent',
        'name' => (string) $settings['site_name'],
        'description' => $homeDescription,
      'url' => $homeUrl,
      'image' => $heroImageUrl,
        'telephone' => (string) ($settings['phone'] ?? ''),
        'email' => (string) ($settings['email'] ?? ''),
        'areaServed' => array_map(static fn (string $area): array => ['@type' => 'City', 'name' => $area], $allAreas),
        'sameAs' => array_values(array_filter([(string) ($settings['facebook_url'] ?? ''), (string) ($settings['instagram_url'] ?? ''), (string) ($settings['iad_url'] ?? '')])),
    ]];

    cms_render_public_document_start($homeTitle . ' | ' . (string) $settings['site_name'], $homeDescription, true, $structuredData, [
      'preload_image' => $heroImage !== '' ? $heroImage : '/uploads/auxois.jpg',
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 46vw',
    ]);
    cms_render_public_header($settings, '/');
    ?>
    <main>
      <section class="section section-hero">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow">Conseillers immobiliers locaux</p>
            <h1><?= cms_h($homeTitle) ?></h1>
            <p class="hero-text"><?= cms_h($homeSubtitle) ?></p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
          <div class="home-hero-side">
            <div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><?php cms_render_image($heroImage, (string) $page['hero_image_alt'], ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 46vw']); ?><?php endif; ?></div>
            <div class="hero-people-card">
              <p class="hero-people-kicker">Accompagnement local</p>
              <div class="hero-people-grid">
                <div class="hero-person"><?php if ($mickaelPhoto !== ''): ?><?php cms_render_image($mickaelPhoto, (string) $settings['mickael_name'], ['sizes' => '44px']); ?><?php else: ?><span><?= cms_h(substr((string) $settings['mickael_name'], 0, 1)) ?></span><?php endif; ?><div><strong><?= cms_h((string) $settings['mickael_name']) ?></strong><small>Conseiller immobilier local</small></div></div>
                <div class="hero-person"><?php if ($marionPhoto !== ''): ?><?php cms_render_image($marionPhoto, (string) $settings['marion_name'], ['sizes' => '44px']); ?><?php else: ?><span><?= cms_h(substr((string) $settings['marion_name'], 0, 1)) ?></span><?php endif; ?><div><strong><?= cms_h((string) $settings['marion_name']) ?></strong><small>Conseillère immobilier locale</small></div></div>
              </div>
            </div>
            <div class="home-stats-grid">
              <div class="home-stat-card"><p>Auxois &amp; Morvan</p><span>Secteur couvert</span></div>
              <div class="home-stat-card"><p>Suivi humain</p><span>Méthode de travail</span></div>
              <div class="home-stat-card mobile-hide"><p><?= cms_h((string) $settings['main_city']) ?></p><span>Ville repère</span></div>
            </div>
          </div>
        </div>
      </section>

      <section id="histoire" class="section section-tight">
        <div class="shell duo-grid home-intro-grid">
          <article class="panel-card home-portraits-panel">
            <p class="eyebrow">Présence locale</p>
            <div class="home-portraits-grid">
              <div class="advisor-card"><?php if ($mickaelPhoto !== ''): ?><?php cms_render_image($mickaelPhoto, (string) $settings['mickael_name'], ['sizes' => '(max-width: 767px) 50vw, 240px']); ?><?php else: ?><span><?= cms_h(substr((string) $settings['mickael_name'], 0, 1)) ?></span><?php endif; ?><h3><?= cms_h((string) $settings['mickael_name']) ?></h3><p>Conseiller immobilier local</p></div>
              <div class="advisor-card"><?php if ($marionPhoto !== ''): ?><?php cms_render_image($marionPhoto, (string) $settings['marion_name'], ['sizes' => '(max-width: 767px) 50vw, 240px']); ?><?php else: ?><span><?= cms_h(substr((string) $settings['marion_name'], 0, 1)) ?></span><?php endif; ?><h3><?= cms_h((string) $settings['marion_name']) ?></h3><p>Conseillère immobilier locale</p></div>
            </div>
          </article>
          <article class="panel-card panel-muted">
            <p class="eyebrow">Accompagnement humain</p>
            <h2>Deux conseillers locaux pour vous accompagner</h2>
            <p class="panel-copy">Basés localement, nous accompagnons vendeurs, acheteurs et porteurs de projets dans l’Auxois, le Morvan et les secteurs voisins. Notre objectif : vous apporter un suivi clair, humain et régulier, avec l’appui du réseau IAD.</p>
            <p class="panel-copy panel-copy-tight">Besoin d’un repère concret pour <a href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">faire estimer un bien</a>, <a href="<?= cms_h(cms_url('/vendre')) ?>">préparer une vente</a>, <a href="<?= cms_h(cms_url('/acheter')) ?>">cadrer une recherche</a> ou <a href="<?= cms_h(cms_url('/contact')) ?>">nous contacter</a> ? Nous avançons avec vous, simplement.</p>
            <div class="tags-wrap"><?php foreach ($humanCities as $area): ?><span><?= cms_h((string) $area) ?></span><?php endforeach; ?></div>
            <ul class="accent-list compact-list">
              <li>Présence de terrain et lecture honnête du marché local</li>
              <li>Suivi clair, humain et régulier</li>
              <li>Coordination jusqu’à la signature</li>
            </ul>
          </article>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Méthode</p>
          <h2 class="section-title">Pourquoi nous confier votre projet ?</h2>
          <p class="section-subtitle">Une approche sobre, locale et structurée pour défendre votre intérêt, limiter les hésitations et garder un cap clair à chaque étape.</p>
          <div class="trust-grid"><?php foreach ($trustReasons as $reason): ?><article class="trust-card"><h3><?= cms_h((string) $reason['title']) ?></h3><p><?= cms_h((string) $reason['text']) ?></p></article><?php endforeach; ?></div>
        </div>
      </section>

      <section id="prestations" class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Services</p>
          <h2 class="section-title">Nos services</h2>
          <p class="section-subtitle">Une présence utile pour vendre, acheter, estimer un bien ou préparer une transmission de fonds de commerce, sans alourdir la lecture de la home.</p>
          <div class="services-grid">
            <?php foreach ($services as $index => $service): ?>
              <details class="service-card" open>
                <summary class="service-card-summary">
                  <div>
                    <p class="card-kicker">Service</p>
                    <h3><?= cms_h((string) $service['title']) ?></h3>
                  </div>
                </summary>
                <div class="service-card-content">
                  <p><?= cms_h((string) $service['description']) ?></p>
                  <ul class="accent-list compact-list"><?php foreach (array_slice(($service['features'] ?? []), 0, 3) as $feature): ?><li><?= cms_h((string) $feature) ?></li><?php endforeach; ?></ul>
                  <a class="card-link" href="<?= cms_h(cms_url((string) $service['href'])) ?>">En savoir plus →</a>
                </div>
              </details>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="panel-card step-panel">
            <p class="eyebrow">Accompagnement</p>
            <h2 class="section-title">Votre projet immobilier, étape par étape</h2>
            <p class="section-subtitle">Un parcours clair pour garder de la visibilité, prendre les bonnes décisions et avancer sans flottement inutile.</p>
            <div class="step-grid"><?php foreach ($projectSteps as $index => $step): ?><article class="step-card"><span class="step-number"><?= cms_h((string) ($index + 1)) ?></span><h3><?= cms_h((string) $step) ?></h3></article><?php endforeach; ?></div>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell stack-lg">
          <div>
            <p class="eyebrow">Secteur local</p>
            <h2 class="section-title">Notre secteur local</h2>
            <p class="section-subtitle">Des repères concrets sur les villes et bassins de vie où nous accompagnons régulièrement des projets immobiliers.</p>
            <p class="home-local-copy">Nous accompagnons les propriétaires, acheteurs et porteurs de projets dans l’Auxois, le Morvan et plus largement en Côte-d’Or et Bourgogne. Notre secteur couvre notamment Arnay-le-Duc, Pouilly-en-Auxois, Saulieu, Autun, Beaune, Dijon, Vitteaux et Semur-en-Auxois, avec une attention particulière aux maisons anciennes, résidences secondaires, biens familiaux, immeubles et <a href="<?= cms_h(cms_url('/fonds')) ?>">fonds de commerce</a>.</p>
            <div class="home-link-row"><a href="<?= cms_h(cms_url('/vendre')) ?>">Vendre</a><a href="<?= cms_h(cms_url('/acheter')) ?>">Acheter</a><a href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Estimation</a><?php if ($blogEnabled): ?><a href="<?= cms_h(cms_url('/blog')) ?>">Conseils</a><?php endif; ?></div>
            <div class="cards-grid three-cols"><?php foreach ($featuredAreas as $index => $area): ?><article class="soft-card area-card<?= $index >= 3 ? ' mobile-hide' : '' ?>"><?php $areaImage = (string) ($snapshot['areaImages'][$area] ?? '/uploads/auxois.jpg'); ?><?php cms_render_image($areaImage, (string) $area, ['sizes' => '(max-width: 767px) 100vw, 33vw']); ?><div><p class="card-kicker">Secteur</p><h3><?= cms_h((string) $area) ?></h3><p><?= cms_h((string) ($snapshot['areaDescriptions'][$area] ?? 'Un secteur suivi avec attention pour ses dynamiques de marché et ses projets de vie.')) ?></p></div></article><?php endforeach; ?></div>
            <div class="section-actions"><a class="button primary" href="<?= cms_h(cms_url('/secteur')) ?>">Voir tout notre secteur</a><span>Arnay-le-Duc, Pouilly-en-Auxois, Autun, Saulieu, Beaune, Dijon, Semur-en-Auxois et Vitteaux.</span></div>
          </div>
          <div>
            <p class="eyebrow">Conseils locaux</p>
            <h2 class="section-title">Nos conseils immobiliers par secteur</h2>
            <p class="section-subtitle">Des pages utiles pour retrouver des repères concrets, ville par ville, sans alourdir la lecture de la home.</p>
            <div class="cards-grid three-cols"><?php foreach ($localPages as $index => $localPage): ?><article class="soft-card local-card<?= $index >= 2 ? ' mobile-hide' : '' ?>"><?php $localImage = (string) ($localPage['image'] ?? '/uploads/auxois.jpg'); ?><?php cms_render_image($localImage, (string) $localPage['title'], ['sizes' => '(max-width: 767px) 100vw, 33vw']); ?><div><p class="card-kicker"><?= cms_h(str_replace('-', ' ', (string) $localPage['pageType'])) ?></p><h3><?= cms_h((string) $localPage['title']) ?></h3><p class="card-city"><?= cms_h((string) $localPage['city']) ?></p><p class="clamp-2-mobile"><?= cms_h((string) $localPage['excerpt']) ?></p><a class="card-link-inline" href="<?= cms_h(cms_url((string) $localPage['href'])) ?>">Voir la page locale →</a></div></article><?php endforeach; ?></div>
            <div class="section-actions"><a class="button secondary" href="<?= cms_h(cms_url('/secteur')) ?>">Voir toutes les pages locales</a></div>
          </div>
        </div>
      </section>

      <section class="section section-tight"><div class="shell"><div class="panel-card iad-panel"><div><p class="eyebrow">Réseau</p><h2>La proximité locale, avec la puissance du réseau IAD</h2><div class="richtext panel-copy"><p>Vous bénéficiez d’un accompagnement de proximité, tout en profitant de la visibilité et des outils du réseau IAD. Un interlocuteur local, avec une diffusion solide et un suivi régulier jusqu’à la signature.</p></div></div><div class="iad-points-grid"><article class="tile-card"><strong>01</strong><span>Diffusion large des biens</span></article><article class="tile-card"><strong>02</strong><span>Suivi humain et régulier</span></article><article class="tile-card"><strong>03</strong><span>Accompagnement jusqu’à la signature</span></article></div></div></div></section>

      <section id="avis-clients" class="section section-tight"><div class="shell"><p class="eyebrow">Avis clients</p><h2 class="section-title">Des retours fondés sur la qualité du suivi</h2><p class="section-subtitle">Des échanges clairs, une présence régulière et une vraie lecture du terrain pour accompagner le projet jusqu’au bout.</p><div class="cards-grid three-cols"><?php foreach ($testimonials as $index => $testimonial): ?><article class="testimonial-card<?= $index >= 2 ? ' mobile-hide' : '' ?>"><div class="dots-row"><?php for ($starIndex = 0; $starIndex < (int) ($testimonial['rating'] ?? 5); $starIndex += 1): ?><span></span><?php endfor; ?></div><p class="testimonial-quote">“<?= cms_h((string) $testimonial['quote']) ?>”</p><div class="testimonial-meta"><strong><?= cms_h((string) $testimonial['author']) ?></strong><span><?= cms_h(implode(' — ', array_filter([(string) ($testimonial['title'] ?? ''), (string) ($testimonial['location'] ?? '')]))) ?></span></div></article><?php endforeach; ?></div></div></section>

      <?php if ($blogEnabled && $blogPosts !== []): ?><section class="section section-tight"><div class="shell"><p class="eyebrow">Blog</p><h2 class="section-title">Derniers articles</h2><p class="section-subtitle">Des contenus utiles pour comprendre le marché local, préparer une vente et cadrer un projet immobilier.</p><div class="cards-grid three-cols"><?php foreach ($blogPosts as $index => $post): ?><article class="blog-card<?= $index >= 2 ? ' mobile-hide' : '' ?>"><a href="<?= cms_h(cms_url((string) $post['href'])) ?>"><?php cms_render_image((string) $post['image'], (string) ($post['imageAlt'] ?? $post['title']), ['sizes' => '(max-width: 767px) 100vw, 33vw']); ?><div class="blog-card-body"><div class="blog-meta"><span><?= cms_h((string) $post['category']) ?></span><span class="meta-dot"></span><span><?= cms_h(cms_format_long_date((string) $post['date'])) ?></span></div><h3><?= cms_h((string) $post['title']) ?></h3><p class="clamp-2-mobile"><?= cms_h((string) $post['excerpt']) ?></p><span class="card-link-inline">Lire l'article →</span></div></a></article><?php endforeach; ?></div><div class="section-actions"><a class="button secondary" href="<?= cms_h(cms_url('/blog')) ?>">Voir tous les articles</a></div></div></section><?php endif; ?>

      <section class="section section-tight"><div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">Projet immobilier</p><h2>Vous avez un projet immobilier ?</h2><div class="richtext"><p>Parlons simplement de votre bien, de votre secteur et de la meilleure stratégie à adopter.</p></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_sector_page(array $page, array $settings, array $snapshot): void
{
  $title = 'Auxois Morvan : acheter, investir, vivre';
    $description = 'Carte interactive, communes phares, tendances immobilières et conseils pour acheter, vendre ou investir en Auxois Morvan.';
    $heroImage = '/uploads/auxois.jpg';
    $cities = [
        ['name' => 'Dijon', 'lat' => 47.3220, 'lng' => 5.0415, 'profile' => 'Métropole régionale, bassin d’emploi majeur, forte tension locative et marché structuré.', 'tag' => 'Métropole & investissement'],
        ['name' => 'Beaune', 'lat' => 47.0260, 'lng' => 4.8400, 'profile' => 'Cité viticole renommée, tourisme international, charme ancien et investissement patrimonial.', 'tag' => 'Patrimoine viticole'],
        ['name' => 'Pouilly-en-Auxois', 'lat' => 47.2632, 'lng' => 4.5557, 'profile' => 'Cadre vert, accès autoroutier rapide, marché équilibré et prix encore attractifs.', 'tag' => 'Accès & cadre vert'],
        ['name' => 'Arnay-le-Duc', 'lat' => 47.1326, 'lng' => 4.4856, 'profile' => 'Cœur historique de l’Auxois, vie locale active et bon rapport qualité/prix.', 'tag' => 'Cœur de secteur'],
        ['name' => 'Saulieu', 'lat' => 47.2817, 'lng' => 4.2284, 'profile' => 'Porte du Morvan, qualité de vie, potentiel touristique et maisons de caractère.', 'tag' => 'Morvan & tourisme'],
        ['name' => 'Autun', 'lat' => 46.9510, 'lng' => 4.2980, 'profile' => 'Ville d’art et d’histoire, dynamisme économique, grandes surfaces à prix modérés.', 'tag' => 'Histoire & grands biens'],
        ['name' => 'Vitteaux', 'lat' => 47.3976, 'lng' => 4.5412, 'profile' => 'Village calme et central, compromis recherché entre campagne et proximité de Dijon.', 'tag' => 'Calme & centralité'],
        ['name' => 'Semur-en-Auxois', 'lat' => 47.4911, 'lng' => 4.3330, 'profile' => 'Cité médiévale attractive, belles pierres, marché résidentiel et patrimonial.', 'tag' => 'Belles pierres'],
    ];

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true, [], [
      'preload_image' => $heroImage,
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 38vw',
      'stylesheets' => [[
        'href' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        'crossorigin' => '',
      ]],
    ]);
    cms_render_public_header($settings, '/secteur');
    ?>
    <main class="sector-premium-page">
      <section class="sector-hero section-hero-inner">
        <div class="shell sector-hero-grid">
          <div class="sector-hero-copy">
            <p class="eyebrow">Acheter · investir · vivre localement</p>
            <h1><?= cms_h($title) ?></h1>
            <p class="hero-text">Entre Beaune, Dijon et Autun, l’Auxois Morvan attire pour son cadre préservé, ses maisons de caractère et ses prix encore lisibles. Voici une lecture claire du territoire pour cibler les communes à suivre.</p>
            <div class="sector-hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Estimer un bien du secteur</a>
              <a class="button secondary" href="#carte-auxois-morvan">Explorer la carte</a>
            </div>
          </div>
          <aside class="sector-hero-media">
            <?php cms_render_image($heroImage, 'Paysages de l’Auxois Morvan', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 38vw']); ?>
            <div class="sector-hero-badge"><strong>8</strong><span>communes clés</span></div>
          </aside>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell sector-intro-grid">
          <article class="sector-intro-card is-main">
            <p class="eyebrow">Un territoire en renaissance</p>
            <h2>Comprendre le marché local</h2>
            <p>L’Auxois Morvan conjugue nature, accessibilité et patrimoine. Pour bien acheter ou vendre, il faut lire finement la commune, l’état du bâti, les accès, les services et le potentiel réel du bien.</p>
            <ul class="sector-card-list">
              <li>Prix encore lisibles selon les villages.</li>
              <li>Demande portée par la qualité de vie et la pierre.</li>
              <li>Arbitrage clé : travaux, jardin, dépendances, mobilité.</li>
            </ul>
          </article>
          <article class="sector-intro-card">
            <p class="sector-card-kicker">Habiter</p>
            <h3>Maison familiale avec extérieur</h3>
            <p>Les recherches portent souvent sur une maison saine, un jardin utilisable, une pièce de vie confortable et des services accessibles sans perdre l’esprit campagne.</p>
            <div class="sector-card-note">À regarder : écoles, chauffage, assainissement, fibre, temps de trajet et coût des travaux.</div>
          </article>
          <article class="sector-intro-card">
            <p class="sector-card-kicker">Patrimoine</p>
            <h3>Pierre, résidence secondaire, charme ancien</h3>
            <p>Maisons de caractère, longères, bâtisses de village et dépendances séduisent les acquéreurs qui veulent un lieu de vie singulier ou un pied-à-terre bourguignon.</p>
            <div class="sector-card-note">Point clé : valoriser l’authenticité sans sous-estimer rénovation, énergie et entretien.</div>
          </article>
          <article class="sector-intro-card">
            <p class="sector-card-kicker">Investir</p>
            <h3>Locatif local et projet de rendement</h3>
            <p>Le marché reste mesuré, mais certains pôles offrent une demande régulière : actifs locaux, mobilité professionnelle, petites surfaces, logements avec stationnement.</p>
            <div class="sector-card-note">À cadrer : loyer réaliste, vacance, fiscalité, budget travaux et attractivité de la commune.</div>
          </article>
        </div>
      </section>

      <section id="carte-auxois-morvan" class="section section-tight">
        <div class="shell">
          <div class="sector-section-head">
            <p class="eyebrow">Carte interactive</p>
            <h2>Visualiser les villes phares du secteur</h2>
            <p>Déplacez-vous sur la carte, zoomez et cliquez sur une commune pour voir son profil immobilier.</p>
          </div>
          <div class="sector-map-layout">
            <div id="auxois-interactive-map" class="sector-map" aria-label="Carte interactive de l’Auxois Morvan"></div>
            <aside class="sector-map-panel">
              <h3>Lecture rapide</h3>
              <p>Le secteur s’organise autour de pôles complémentaires : villes patrimoniales, communes de services, villages calmes et axes rapides vers Dijon ou Beaune.</p>
              <ul>
                <li><strong>Dijon / Beaune</strong> : tension et attractivité.</li>
                <li><strong>Pouilly / Arnay</strong> : équilibre prix, accès et services.</li>
                <li><strong>Saulieu / Autun</strong> : qualité de vie et patrimoine.</li>
              </ul>
            </aside>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell sector-city-section">
          <div class="sector-section-head is-compact">
            <p class="eyebrow">Communes à suivre</p>
            <h2>Où acheter ou investir en Auxois Morvan ?</h2>
          </div>
          <div class="sector-city-grid">
            <?php foreach ($cities as $city): ?>
              <article class="sector-city-card">
                <span><?= cms_h($city['tag']) ?></span>
                <h3><?= cms_h($city['name']) ?></h3>
                <p><?= cms_h($city['profile']) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell sector-insights-grid">
          <article class="sector-panel-card">
            <p class="eyebrow">Tendances locales</p>
            <h2>Tendances de l’immobilier en Auxois Morvan</h2>
            <ul class="sector-check-list">
              <li>Prix maisons souvent entre <strong>1 400 et 2 000 €/m²</strong> selon la commune et l’état du bien.</li>
              <li>Appartements autour de <strong>1 800 €/m²</strong> à Beaune ou Dijon, moins dans les communes rurales.</li>
              <li>Rendement locatif brut estimé entre <strong>4 et 6 %</strong> sur les petites maisons rénovées.</li>
              <li>Demande croissante pour les biens avec jardin, dépendance ou terrain.</li>
            </ul>
          </article>
          <article class="sector-panel-card is-dark">
            <p class="eyebrow">Pourquoi investir ici ?</p>
            <h2>Un secteur lisible, accessible et patrimonial</h2>
            <ol class="sector-number-list">
              <li>Cadre de vie préservé entre patrimoine et nature.</li>
              <li>Accessibilité depuis Dijon, Beaune, Paris ou Lyon.</li>
              <li>Marché encore abordable pour primo-accédants et investisseurs.</li>
              <li>Potentiel touristique avec gîtes, maisons secondaires et biens de charme.</li>
              <li>Rendement stable grâce à la demande locale.</li>
            </ol>
          </article>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell"><div class="cta-band"><div><p class="eyebrow">Passer à l’action</p><h2>Voir si nous intervenons sur votre commune</h2><div class="richtext"><p>Contactez-nous pour vérifier votre secteur, cadrer votre projet et obtenir un premier avis clair.</p></div></div><a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous parler de votre secteur</a></div></div>
      </section>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const mapNode = document.getElementById('auxois-interactive-map');
        if (!mapNode || typeof L === 'undefined') {
          return;
        }

        const cities = <?= json_encode($cities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const map = L.map(mapNode, { scrollWheelZoom: false }).setView([47.18, 4.55], 9);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 18,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const bounds = [];
        cities.forEach((city) => {
          bounds.push([city.lat, city.lng]);
          L.marker([city.lat, city.lng]).addTo(map).bindPopup(`<strong>${city.name}</strong><br>${city.tag}<br><small>${city.profile}</small>`);
        });

        if (bounds.length > 0) {
          map.fitBounds(bounds, { padding: [28, 28] });
        }
      });
    </script>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_local_profile(string $city, string $pageType): array
{
    $profiles = [
        'Arnay-le-Duc' => [
            'territory' => 'entre centre-bourg patrimonial, maisons anciennes, villages du Pays d’Arnay et accès vers Pouilly-en-Auxois, Saulieu et Beaune',
            'market' => 'un marché de proximité où les biens familiaux, les maisons de caractère et les projets de vie plus ruraux demandent une lecture fine du cadre de vie.',
            'buyers' => ['familles recherchant commerces et services', 'acquéreurs attirés par les maisons anciennes', 'projets de résidence secondaire ou de campagne', 'vendeurs souhaitant cadrer un prix réaliste'],
            'propertyTypes' => ['maisons de ville', 'maisons anciennes', 'biens familiaux', 'maisons avec jardin', 'projets de rénovation'],
            'microAreas' => ['centre-bourg', 'villages autour d’Arnay-le-Duc', 'axe Pouilly-en-Auxois', 'direction Saulieu et Morvan'],
            'nearby' => ['Pouilly-en-Auxois', 'Saulieu', 'Beaune', 'Bligny-sur-Ouche'],
        ],
        'Pouilly-en-Auxois' => [
            'territory' => 'au cœur d’un secteur charnière de l’Auxois, marqué par l’accessibilité, le canal de Bourgogne et les liaisons vers Dijon, Beaune et Arnay-le-Duc',
            'market' => 'un marché où la mobilité, la visibilité des axes et la qualité du cadre résidentiel pèsent fortement dans la valeur perçue.',
            'buyers' => ['actifs recherchant un accès rapide aux axes', 'familles attachées aux services de proximité', 'acquéreurs comparant Auxois et couronne dijonnaise', 'vendeurs de maisons avec terrain'],
            'propertyTypes' => ['maisons familiales', 'pavillons avec terrain', 'maisons de village', 'biens proches des axes', 'projets locatifs'],
            'microAreas' => ['centre de Pouilly-en-Auxois', 'canal de Bourgogne', 'axe A6/A38', 'villages du bassin pouillysois'],
            'nearby' => ['Arnay-le-Duc', 'Vitteaux', 'Dijon', 'Sombernon'],
        ],
        'Autun' => [
            'territory' => 'dans un bassin de vie structuré entre patrimoine, quartiers résidentiels, accès au Morvan et attractivité d’une ville centre',
            'market' => 'un marché à lire quartier par quartier, où l’état du bien, la localisation, le stationnement, les extérieurs et le niveau de travaux changent fortement l’analyse.',
            'buyers' => ['familles cherchant une ville de services', 'acquéreurs sensibles au patrimoine autunois', 'projets de vente de maison avec jardin', 'investisseurs et résidences secondaires selon le secteur'],
            'propertyTypes' => ['maisons de ville', 'maisons avec jardin', 'biens anciens', 'pavillons', 'immeubles et projets patrimoniaux'],
            'microAreas' => ['centre historique', 'quartiers résidentiels', 'entrée du Morvan', 'communes du Grand Autunois'],
            'nearby' => ['Saulieu', 'Beaune', 'Arnay-le-Duc', 'Épinac'],
        ],
    ];

    $profile = $profiles[$city] ?? [
        'territory' => 'dans un secteur de l’Auxois-Morvan où la lecture du cadre de vie, des accès et des biens comparables reste essentielle',
        'market' => 'un marché local à analyser selon la typologie du bien, l’environnement immédiat, la demande active et le calendrier du vendeur.',
        'buyers' => ['acquéreurs locaux', 'familles en recherche de résidence principale', 'projets de maison avec extérieur', 'vendeurs souhaitant sécuriser leur positionnement'],
        'propertyTypes' => ['maisons anciennes', 'maisons familiales', 'biens avec terrain', 'projets de rénovation'],
        'microAreas' => ['centre-bourg', 'villages voisins', 'axes de déplacement', 'secteurs résidentiels'],
        'nearby' => [],
    ];

    $isSale = $pageType === 'vendre-maison';
    $profile['intentTitle'] = $isSale ? 'Vendre une maison à ' . $city . ' avec une stratégie locale' : 'Faire estimer un bien à ' . $city . ' avec une lecture locale';
    $profile['intentText'] = $isSale
        ? 'Vendre une maison à ' . $city . ' demande de combiner estimation, présentation du bien, qualification des acquéreurs et suivi des offres. L’objectif est de défendre un prix cohérent tout en gardant un calendrier lisible.'
        : 'Une estimation immobilière à ' . $city . ' doit tenir compte des références comparables, de l’état du bien, de son environnement, de la demande active et du projet du propriétaire.';
    $profile['primaryKeyword'] = $isSale ? 'vendre maison ' . $city : 'estimation immobilière ' . $city;
    $profile['keywords'] = array_values(array_unique([
        'estimation ' . $city,
        'estimation immobilière ' . $city,
        'vendre maison ' . $city,
        'immobilier ' . $city,
        'agence immobilière ' . $city,
        'prix maison ' . $city,
        'conseiller immobilier ' . $city,
    ]));
    $profile['seoTitle'] = $isSale
        ? 'Vendre maison ' . $city . ' | Estimation et immobilier local'
        : 'Estimation immobilière ' . $city . ' | Vendre, prix maison et immobilier local';
    $profile['seoDescription'] = $isSale
        ? 'Vendre une maison à ' . $city . ' : estimation locale, stratégie de prix, valorisation du bien et accompagnement immobilier jusqu’à la signature.'
        : 'Estimation immobilière à ' . $city . ' : avis de valeur local, analyse du marché, prix maison et accompagnement pour vendre dans de bonnes conditions.';
    $profile['faqs'] = $isSale ? [
        ['question' => 'Comment vendre une maison à ' . $city . ' au bon prix ?', 'answer' => 'La première étape consiste à croiser les références comparables, l’état du bien, son emplacement, les extérieurs, les travaux éventuels et la demande active sur ' . $city . ' et ses alentours.'],
        ['question' => 'Faut-il faire estimer sa maison avant de vendre à ' . $city . ' ?', 'answer' => 'Oui. Une estimation locale permet de fixer une stratégie cohérente, d’éviter un prix trop haut qui bloque les visites ou un prix trop bas qui fragilise votre projet.'],
        ['question' => 'Quels éléments influencent l’immobilier à ' . $city . ' ?', 'answer' => 'La localisation précise, la qualité du bâti, les accès, les services, la présence d’un jardin ou de dépendances et le niveau de travaux sont déterminants.'],
    ] : [
        ['question' => 'Comment obtenir une estimation immobilière à ' . $city . ' ?', 'answer' => 'Il faut analyser le bien, son état, ses surfaces, son environnement, les références utiles et la demande actuelle sur ' . $city . ' et les communes proches.'],
        ['question' => 'Une estimation en ligne suffit-elle pour vendre à ' . $city . ' ?', 'answer' => 'Une première estimation en ligne donne un repère, mais un avis de valeur fiable doit intégrer une lecture locale et les spécificités concrètes du bien.'],
        ['question' => 'Quels mots clés décrivent le marché immobilier à ' . $city . ' ?', 'answer' => 'Les recherches fréquentes croisent estimation ' . $city . ', vendre maison ' . $city . ', immobilier ' . $city . ', prix maison et conseiller immobilier local.'],
    ];

    return $profile;
}

function cms_render_local_public_page(array $page, array $settings, array $snapshot): void
{
    $sections = cms_page_sections($page);
    $heroImage = trim((string) ($page['hero_image'] ?? ''));
    $city = trim((string) ($page['city'] ?? '')) ?: (string) ($settings['main_city'] ?? 'Auxois-Morvan');
    $pageType = trim((string) ($page['local_page_type'] ?? ''));
    $profile = cms_local_profile($city, $pageType);
    $advantages = cms_json_list($page['local_advantages_json'] ?? '[]');
    $nearbyCities = cms_json_list($page['nearby_cities_json'] ?? '[]');
    $pageFaqs = cms_json_objects((string) ($page['seo_faq_json'] ?? '[]'));
    $internalLinks = cms_json_objects((string) ($page['seo_internal_links_json'] ?? '[]'));
    $faqs = $pageFaqs !== [] ? $pageFaqs : $profile['faqs'];
    if ($nearbyCities === []) {
        $nearbyCities = $profile['nearby'];
    }

    $slug = (string) ($page['slug'] ?? '/');
    $appUrl = rtrim((string) (cms_config()['app_url'] ?? ''), '/');
    $pageUrl = $appUrl !== '' ? $appUrl . cms_url($slug) : cms_url($slug);
    $imageUrl = $heroImage !== '' ? cms_url($heroImage) : cms_url('/uploads/auxois.jpg');
    if ($appUrl !== '') {
        $imageUrl = $appUrl . $imageUrl;
    }

    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateAgent',
            'name' => (string) $settings['site_name'],
            'url' => $pageUrl,
            'telephone' => (string) ($settings['phone'] ?? ''),
            'email' => (string) ($settings['email'] ?? ''),
            'image' => $imageUrl,
            'areaServed' => ['@type' => 'City', 'name' => $city],
            'keywords' => implode(', ', $profile['keywords']),
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $appUrl !== '' ? $appUrl . cms_url('/') : cms_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $city, 'item' => $pageUrl],
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs),
        ],
    ];

    cms_render_public_document_start($profile['seoTitle'] . ' | ' . (string) $settings['site_name'], $profile['seoDescription'], (int) ($page['is_indexable'] ?? 1) === 1, $structuredData, [
      'preload_image' => $heroImage !== '' ? $heroImage : '/uploads/auxois.jpg',
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 42vw',
    ]);
    cms_render_public_header($settings, $slug);
    ?>
    <main class="local-premium-page">
      <section class="local-hero">
        <div class="shell local-hero-grid">
          <div class="local-hero-copy">
            <p class="eyebrow">Expertise locale</p>
            <h1><?= cms_h((string) $page['hero_title']) ?></h1>
            <div class="hero-text richtext"><?= (string) $page['hero_subtitle'] ?></div>
            <div class="local-keyword-row"><?php foreach (array_slice($profile['keywords'], 0, 4) as $keyword): ?><span><?= cms_h($keyword) ?></span><?php endforeach; ?></div>
            <div class="hero-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne?ville=' . rawurlencode($city))) ?>">Demander une estimation</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Parler du secteur</a></div>
          </div>
          <aside class="local-hero-visual">
            <div class="local-hero-image"><?php if ($heroImage !== ''): ?><?php cms_render_image($heroImage, (string) $page['hero_image_alt'], ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 42vw']); ?><?php endif; ?></div>
            <div class="local-hero-card"><span><?= cms_h($city) ?></span><strong><?= cms_h($profile['primaryKeyword']) ?></strong><p><?= cms_h($profile['territory']) ?></p></div>
          </aside>
        </div>
      </section>

      <section class="local-section local-intent-section">
        <div class="shell local-intent-grid">
          <article class="local-intro-card">
            <p class="eyebrow">Marché local</p>
            <h2><?= cms_h($profile['intentTitle']) ?></h2>
            <p><?= cms_h($profile['intentText']) ?></p>
            <div class="richtext local-original-intro"><?= (string) $page['intro_html'] ?></div>
          </article>
          <aside class="local-side-card">
            <p class="eyebrow">Recherches SEO couvertes</p>
            <div class="local-seo-tags"><?php foreach ($profile['keywords'] as $keyword): ?><span><?= cms_h($keyword) ?></span><?php endforeach; ?></div>
          </aside>
        </div>
      </section>

      <section class="local-section">
        <div class="shell">
          <div class="local-section-head"><p class="eyebrow">Données locales utiles</p><h2>Ce qui influence l’immobilier à <?= cms_h($city) ?></h2><p><?= cms_h($profile['market']) ?></p></div>
          <div class="local-data-grid">
            <article><span>01</span><h3>Typologies recherchées</h3><ul><?php foreach ($profile['propertyTypes'] as $item): ?><li><?= cms_h($item) ?></li><?php endforeach; ?></ul></article>
            <article><span>02</span><h3>Profils d’acquéreurs</h3><ul><?php foreach ($profile['buyers'] as $item): ?><li><?= cms_h($item) ?></li><?php endforeach; ?></ul></article>
            <article><span>03</span><h3>Secteurs à comparer</h3><ul><?php foreach ($profile['microAreas'] as $item): ?><li><?= cms_h($item) ?></li><?php endforeach; ?></ul></article>
          </div>
        </div>
      </section>

      <section class="local-section">
        <div class="shell local-standard-grid">
          <?php foreach ($sections as $index => $section): ?>
            <article class="local-premium-card<?= $index === 0 ? ' is-featured' : '' ?>">
              <?php if (!empty($section['eyebrow'])): ?><p class="eyebrow"><?= cms_h((string) $section['eyebrow']) ?></p><?php endif; ?>
              <h2><?= cms_h((string) ($section['title'] ?? 'Analyse locale')) ?></h2>
              <div class="richtext panel-copy"><?= (string) ($section['text'] ?? '') ?></div>
              <?php if (!empty($section['items'])): ?><ul class="accent-list"><?php foreach ($section['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="local-section">
        <div class="shell local-proof-grid">
          <article class="local-proof-card dark"><p class="eyebrow">Atouts locaux</p><h2>Pourquoi une approche locale change l’analyse</h2><ul><?php foreach ($advantages as $item): ?><li><?= cms_h($item) ?></li><?php endforeach; ?></ul></article>
          <article class="local-proof-card"><p class="eyebrow">Autour de <?= cms_h($city) ?></p><h2>Villes et secteurs proches</h2><div class="tags-wrap"><?php foreach ($nearbyCities as $nearbyCity): ?><span><?= cms_h($nearbyCity) ?></span><?php endforeach; ?></div><p>Ces secteurs proches servent de points de comparaison pour affiner une estimation, comprendre la demande et positionner un bien de manière cohérente.</p></article>
        </div>
      </section>

      <section class="local-section local-faq-section">
        <div class="shell local-faq-grid">
          <div><p class="eyebrow">Questions fréquentes</p><h2>SEO local : estimation, vente et immobilier à <?= cms_h($city) ?></h2></div>
          <div class="local-faq-list"><?php foreach ($faqs as $faq): ?><details><summary><?= cms_h((string) ($faq['question'] ?? '')) ?></summary><p><?= cms_h((string) ($faq['answer'] ?? '')) ?></p></details><?php endforeach; ?></div>
        </div>
      </section>

      <?php if ($internalLinks !== []): ?>
        <section class="local-section local-links-section">
          <div class="shell local-section-head">
            <p class="eyebrow">À lire aussi</p>
            <h2>Continuer votre recherche immobilière locale</h2>
            <div class="tags-wrap local-internal-links"><?php foreach ($internalLinks as $link): ?><a href="<?= cms_h(cms_url((string) ($link['url'] ?? '/'))) ?>"><?= cms_h((string) ($link['label'] ?? 'Lien utile')) ?></a><?php endforeach; ?></div>
          </div>
        </section>
      <?php endif; ?>

      <section class="local-section local-final-cta"><div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">Passer à l’action</p><h2><?= cms_h((string) $page['cta_title']) ?></h2><div class="richtext"><?= (string) $page['cta_text'] ?></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne?ville=' . rawurlencode($city))) ?>">Faire estimer mon bien</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_service_profile(string $pageKey): ?array
{
    $profiles = [
        'vendre' => [
            'eyebrow' => 'Vente immobilière',
            'seoTitle' => 'Vendre un bien en Auxois-Morvan | Estimation, stratégie et immobilier local',
            'seoDescription' => 'Vendre une maison ou un bien en Auxois-Morvan : estimation locale, stratégie de prix, valorisation, diffusion et accompagnement jusqu’à la signature.',
            'primaryKeyword' => 'vendre maison Auxois Morvan',
            'keywords' => ['vendre maison Auxois', 'vendre maison Morvan', 'estimation avant vente', 'immobilier Auxois Morvan', 'prix maison Auxois', 'mandataire immobilier local'],
            'intentTitle' => 'Vendre au bon prix avec une stratégie claire',
            'intentText' => 'Une vente réussie repose sur une estimation juste, une présentation lisible, une diffusion cohérente et un suivi rigoureux des acquéreurs. En Auxois-Morvan, le positionnement doit tenir compte des maisons anciennes, des biens avec terrain, des résidences secondaires et du niveau réel de demande.',
            'signals' => [
                ['title' => 'Prix de départ', 'text' => 'Un avis de valeur argumenté pour éviter un prix trop haut qui bloque les visites ou trop bas qui fragilise votre projet.'],
                ['title' => 'Présentation du bien', 'text' => 'Mise en avant des volumes, extérieurs, dépendances, travaux, environnement et usages possibles.'],
                ['title' => 'Qualification', 'text' => 'Tri des contacts, suivi des visites, lecture des offres et coordination jusqu’à la signature.'],
            ],
            'audiences' => ['Propriétaires de maisons anciennes', 'Vendeurs de résidence principale', 'Familles en changement de vie', 'Biens avec terrain ou dépendances'],
            'process' => ['Évaluation locale', 'Stratégie de prix', 'Préparation du dossier', 'Diffusion ciblée', 'Négociation et signature'],
            'faqs' => [
                ['question' => 'Comment vendre une maison en Auxois-Morvan au bon prix ?', 'answer' => 'Il faut croiser les références récentes, l’état du bien, les extérieurs, les travaux, l’environnement et la demande active sur le secteur.'],
                ['question' => 'Pourquoi faire une estimation avant de vendre ?', 'answer' => 'L’estimation permet de fixer une stratégie de prix crédible et d’éviter une mise en vente qui s’essouffle faute de positionnement réaliste.'],
                ['question' => 'Quels biens se vendent en Auxois et Morvan ?', 'answer' => 'Les maisons de village, maisons familiales, biens de caractère, maisons avec terrain et projets de rénovation attirent des profils variés selon la commune et les accès.'],
            ],
        ],
        'acheter' => [
            'eyebrow' => 'Achat immobilier',
            'seoTitle' => 'Acheter un bien en Auxois-Morvan | Maison, secteur et conseil immobilier local',
            'seoDescription' => 'Acheter une maison ou un bien en Auxois-Morvan : recherche, analyse des secteurs, lecture du marché local et accompagnement des visites.',
            'primaryKeyword' => 'acheter maison Auxois Morvan',
            'keywords' => ['acheter maison Auxois', 'acheter maison Morvan', 'immobilier Auxois', 'immobilier Morvan', 'maison à vendre Auxois', 'conseil achat immobilier'],
            'intentTitle' => 'Acheter avec une vraie lecture du terrain',
            'intentText' => 'Acheter dans l’Auxois ou le Morvan suppose de comparer les communes, les accès, les services, l’état du bâti, les travaux et la cohérence du prix. Un regard local aide à éviter les arbitrages trop rapides et à prioriser les biens réellement adaptés au projet.',
            'signals' => [
                ['title' => 'Secteur', 'text' => 'Comparaison des communes, bassins de vie, axes et services utiles selon votre projet.'],
                ['title' => 'Potentiel du bien', 'text' => 'Lecture de l’état, des travaux, dépendances, extérieurs, performance et usages possibles.'],
                ['title' => 'Décision', 'text' => 'Repères de marché pour comprendre si le prix, le calendrier et les conditions sont cohérents.'],
            ],
            'audiences' => ['Familles cherchant plus d’espace', 'Acquéreurs de résidence secondaire', 'Projets de rénovation', 'Acheteurs comparant Auxois, Morvan et Côte-d’Or'],
            'process' => ['Cadrage du projet', 'Sélection des secteurs', 'Analyse des biens', 'Visites accompagnées', 'Aide à la décision'],
            'faqs' => [
                ['question' => 'Où acheter une maison en Auxois-Morvan ?', 'answer' => 'Le bon secteur dépend de vos usages : résidence principale, secondaire, accès aux services, besoin de terrain, temps de trajet et niveau de travaux accepté.'],
                ['question' => 'Comment savoir si le prix d’un bien est cohérent ?', 'answer' => 'Il faut comparer avec des biens similaires, tenir compte de l’état réel, de la commune, des extérieurs, des travaux et du profil de demande.'],
                ['question' => 'Pourquoi se faire accompagner pour acheter ?', 'answer' => 'Un accompagnement local permet de mieux lire les écarts entre secteurs, d’anticiper les contraintes et de sécuriser les étapes de décision.'],
            ],
        ],
        'estimation' => [
            'eyebrow' => 'Estimation immobilière',
            'seoTitle' => 'Estimation immobilière en Auxois-Morvan | Prix maison et avis de valeur local',
            'seoDescription' => 'Estimation immobilière en Auxois-Morvan : avis de valeur local, analyse du marché, prix maison, potentiel du bien et stratégie avant vente.',
            'primaryKeyword' => 'estimation immobilière Auxois Morvan',
            'keywords' => ['estimation immobilière Auxois', 'estimation immobilière Morvan', 'prix maison Auxois', 'prix maison Morvan', 'avis de valeur immobilier', 'faire estimer sa maison'],
            'intentTitle' => 'Une estimation utile pour décider, vendre ou arbitrer',
            'intentText' => 'Une estimation sérieuse ne se limite pas à une moyenne de prix. Elle doit intégrer les références comparables, la typologie du bien, son état, son environnement, les travaux, la demande actuelle et l’objectif du propriétaire.',
            'signals' => [
                ['title' => 'Références comparables', 'text' => 'Sélection de repères réellement pertinents par secteur, type de bien et état général.'],
                ['title' => 'Spécificités du bien', 'text' => 'Surfaces, terrain, dépendances, rénovation, exposition, accès, cadre et potentiel d’usage.'],
                ['title' => 'Objectif du vendeur', 'text' => 'Vente rapide, meilleur prix, succession, séparation ou simple arbitrage patrimonial.'],
            ],
            'audiences' => ['Propriétaires avant mise en vente', 'Succession ou séparation', 'Projet patrimonial', 'Vendeurs voulant cadrer un calendrier'],
            'process' => ['Collecte des informations', 'Analyse locale', 'Lecture du bien', 'Avis de valeur', 'Conseil de stratégie'],
            'faqs' => [
                ['question' => 'Comment obtenir une estimation immobilière en Auxois-Morvan ?', 'answer' => 'Vous pouvez transmettre les premières informations en ligne, puis l’analyse est affinée selon le bien, son état, sa commune et les références locales.'],
                ['question' => 'Quelle différence entre estimation en ligne et avis de valeur ?', 'answer' => 'L’estimation en ligne donne un premier repère. L’avis de valeur argumenté ajoute la lecture locale, les spécificités du bien et la stratégie adaptée.'],
                ['question' => 'Quels critères influencent le prix d’une maison ?', 'answer' => 'L’emplacement, l’état, les surfaces, le terrain, les dépendances, les travaux, les accès, l’environnement et la demande active influencent fortement le prix.'],
            ],
        ],
        'fonds-de-commerce' => [
            'eyebrow' => 'Fonds de commerce',
            'seoTitle' => 'Vendre un fonds de commerce en Auxois-Morvan | Estimation, dossier et repreneurs',
            'seoDescription' => 'Vendre un fonds de commerce en Auxois-Morvan, Beaune, Dijon et environs : estimation, dossier repreneur, diffusion ciblée et accompagnement confidentiel.',
            'primaryKeyword' => 'vendre fonds de commerce Auxois Morvan',
            'keywords' => ['vendre fonds de commerce Auxois', 'cession fonds de commerce Morvan', 'estimation fonds de commerce', 'commerce à vendre Bourgogne', 'repreneur commerce Côte-d’Or', 'transmission commerce local'],
            'intentTitle' => 'Préparer une transmission lisible et confidentielle',
            'intentText' => 'La vente d’un fonds de commerce exige une lecture à la fois immobilière, économique et humaine. Le dossier doit présenter l’activité clairement, qualifier les repreneurs et protéger le temps du cédant.',
            'signals' => [
                ['title' => 'Valorisation', 'text' => 'Analyse de l’emplacement, de l’activité, du bail, des éléments transmis et du potentiel de reprise.'],
                ['title' => 'Dossier repreneur', 'text' => 'Présentation structurée pour rendre l’activité compréhensible et faciliter les premiers échanges.'],
                ['title' => 'Confidentialité', 'text' => 'Diffusion maîtrisée, qualification des profils et accompagnement des échanges jusqu’à la cession.'],
            ],
            'audiences' => ['Commerçants en transmission', 'Cafés, restaurants et commerces de proximité', 'Activités avec clientèle locale', 'Cédants recherchant discrétion et méthode'],
            'process' => ['Cadrage de la cession', 'Valorisation', 'Préparation du dossier', 'Qualification repreneurs', 'Accompagnement des négociations'],
            'faqs' => [
                ['question' => 'Comment vendre un fonds de commerce en Bourgogne ?', 'answer' => 'Il faut préparer un dossier clair, valoriser l’activité, cadrer les conditions de reprise et qualifier les repreneurs avant de partager les informations sensibles.'],
                ['question' => 'Comment estimer un fonds de commerce ?', 'answer' => 'L’estimation prend en compte l’emplacement, le bail, l’activité, la clientèle, les éléments transmis, le potentiel et le contexte local.'],
                ['question' => 'Pourquoi qualifier les repreneurs ?', 'answer' => 'La qualification protège le temps du cédant, limite les demandes peu sérieuses et permet de concentrer les échanges sur des profils cohérents.'],
            ],
        ],
    ];

    return $profiles[$pageKey] ?? null;
}

function cms_render_service_public_page(array $page, array $settings, array $snapshot, array $profile): void
{
    $sections = cms_page_sections($page);
    $heroImage = trim((string) ($page['hero_image'] ?? ''));
    $slug = (string) ($page['slug'] ?? '/');
  $twoColumnServiceGrid = in_array((string) ($page['page_key'] ?? ''), ['vendre', 'acheter', 'estimation'], true);
    $appUrl = rtrim((string) (cms_config()['app_url'] ?? ''), '/');
    $pageUrl = $appUrl !== '' ? $appUrl . cms_url($slug) : cms_url($slug);
    $imageUrl = $heroImage !== '' ? cms_url($heroImage) : cms_url('/uploads/auxois.jpg');
    if ($appUrl !== '') {
        $imageUrl = $appUrl . $imageUrl;
    }

    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => (string) $page['title'],
            'description' => $profile['seoDescription'],
            'provider' => ['@type' => 'RealEstateAgent', 'name' => (string) $settings['site_name'], 'telephone' => (string) ($settings['phone'] ?? ''), 'email' => (string) ($settings['email'] ?? '')],
            'areaServed' => array_map(static fn (string $area): array => ['@type' => 'Place', 'name' => $area], cms_json_list($settings['covered_areas_json'] ?? '[]')),
            'keywords' => implode(', ', $profile['keywords']),
            'url' => $pageUrl,
            'image' => $imageUrl,
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $profile['faqs']),
        ],
    ];

    cms_render_public_document_start($profile['seoTitle'] . ' | ' . (string) $settings['site_name'], $profile['seoDescription'], (int) ($page['is_indexable'] ?? 1) === 1, $structuredData, [
      'preload_image' => $heroImage !== '' ? $heroImage : '/uploads/auxois.jpg',
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 42vw',
    ]);
    cms_render_public_header($settings, $slug);
    ?>
    <main class="local-premium-page service-premium-page">
      <section class="local-hero service-hero">
        <div class="shell local-hero-grid">
          <div class="local-hero-copy">
            <p class="eyebrow"><?= cms_h($profile['eyebrow']) ?></p>
            <h1><?= cms_h((string) $page['hero_title']) ?></h1>
            <div class="hero-text richtext"><?= (string) $page['hero_subtitle'] ?></div>
            <div class="local-keyword-row"><?php foreach (array_slice($profile['keywords'], 0, 4) as $keyword): ?><span><?= cms_h($keyword) ?></span><?php endforeach; ?></div>
            <div class="hero-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div>
          </div>
          <aside class="local-hero-visual">
            <div class="local-hero-image"><?php if ($heroImage !== ''): ?><?php cms_render_image($heroImage, (string) $page['hero_image_alt'], ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 42vw']); ?><?php endif; ?></div>
            <div class="local-hero-card"><span>Objectif SEO</span><strong><?= cms_h($profile['primaryKeyword']) ?></strong><p><?= cms_h($profile['seoDescription']) ?></p></div>
          </aside>
        </div>
      </section>

      <section class="local-section service-intent-section">
        <div class="shell local-intent-grid">
          <article class="local-intro-card">
            <p class="eyebrow">Approche métier</p>
            <h2><?= cms_h($profile['intentTitle']) ?></h2>
            <p><?= cms_h($profile['intentText']) ?></p>
            <div class="richtext local-original-intro"><?= (string) $page['intro_html'] ?></div>
          </article>
          <aside class="local-side-card">
            <p class="eyebrow">Mots clés travaillés</p>
            <div class="local-seo-tags"><?php foreach ($profile['keywords'] as $keyword): ?><span><?= cms_h($keyword) ?></span><?php endforeach; ?></div>
          </aside>
        </div>
      </section>

      <section class="local-section">
        <div class="shell">
          <div class="local-section-head"><p class="eyebrow">Points de décision</p><h2>Les éléments qui changent vraiment le résultat</h2><p>Chaque projet doit être analysé selon son objectif, son calendrier, son secteur et les attentes réelles des acquéreurs ou repreneurs.</p></div>
          <div class="local-data-grid"><?php foreach ($profile['signals'] as $index => $signal): ?><article><span><?= cms_h(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><h3><?= cms_h($signal['title']) ?></h3><p><?= cms_h($signal['text']) ?></p></article><?php endforeach; ?></div>
        </div>
      </section>

      <section class="local-section">
        <div class="shell local-standard-grid<?= $twoColumnServiceGrid ? ' service-two-column-grid' : '' ?>">
          <?php foreach ($sections as $index => $section): ?>
            <?php
              $sectionEyebrow = trim((string) ($section['eyebrow'] ?? ''));
              if (strcasecmp($sectionEyebrow, 'Migration WordPress') === 0) {
                  $sectionEyebrow = 'Accompagnement';
              }
            ?>
            <article class="local-premium-card<?= $index === 0 ? ' is-featured' : '' ?>">
              <?php if ($sectionEyebrow !== ''): ?><p class="eyebrow"><?= cms_h($sectionEyebrow) ?></p><?php endif; ?>
              <h2><?= cms_h((string) ($section['title'] ?? 'Accompagnement')) ?></h2>
              <div class="richtext panel-copy"><?= (string) ($section['text'] ?? '') ?></div>
              <?php if (!empty($section['items'])): ?><ul class="accent-list"><?php foreach ($section['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="local-section">
        <div class="shell local-proof-grid">
          <article class="local-proof-card dark"><p class="eyebrow">Parcours</p><h2>Une méthode claire, étape par étape</h2><ol class="service-process-list"><?php foreach ($profile['process'] as $index => $step): ?><li><span><?= cms_h(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><?= cms_h($step) ?></li><?php endforeach; ?></ol></article>
          <article class="local-proof-card"><p class="eyebrow">Pour quels projets ?</p><h2>Un accompagnement adapté aux situations réelles</h2><div class="tags-wrap"><?php foreach ($profile['audiences'] as $audience): ?><span><?= cms_h($audience) ?></span><?php endforeach; ?></div><p>Le contenu de cette page est construit pour répondre aux recherches concrètes des propriétaires, acquéreurs ou commerçants en Auxois-Morvan.</p></article>
        </div>
      </section>

      <section class="local-section local-faq-section">
        <div class="shell local-faq-grid">
          <div><p class="eyebrow">Questions fréquentes</p><h2>Réponses aux recherches autour de <?= cms_h($profile['primaryKeyword']) ?></h2></div>
          <div class="local-faq-list"><?php foreach ($profile['faqs'] as $faq): ?><details><summary><?= cms_h($faq['question']) ?></summary><p><?= cms_h($faq['answer']) ?></p></details><?php endforeach; ?></div>
        </div>
      </section>

      <section class="local-section local-final-cta"><div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">Passer à l’action</p><h2><?= cms_h((string) $page['cta_title']) ?></h2><div class="richtext"><?= (string) $page['cta_text'] ?></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url((string) $page['cta_button_url'])) ?>"><?= cms_h((string) $page['cta_button_label']) ?></a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_standard_public_page(array $page, array $settings, array $snapshot): void
{
    if (($page['page_type'] ?? 'main') === 'local') {
        cms_render_local_public_page($page, $settings, $snapshot);
        return;
    }

    $serviceProfile = cms_service_profile((string) ($page['page_key'] ?? ''));
    if ($serviceProfile !== null) {
        cms_render_service_public_page($page, $settings, $snapshot, $serviceProfile);
        return;
    }

    $sections = cms_page_sections($page);
    $heroImage = trim((string) ($page['hero_image'] ?? ''));

    cms_render_public_document_start((string) $page['title'] . ' | ' . (string) $settings['site_name'], (string) ($page['meta_description'] ?? $settings['baseline']), (int) ($page['is_indexable'] ?? 1) === 1);
    cms_render_public_header($settings, (string) ($page['slug'] ?? '/'));
    ?>
    <main>
      <section class="section section-hero section-hero-inner">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow"><?= cms_h((string) (($page['page_type'] ?? 'main') === 'local' ? 'Page locale' : 'Conseil immobilier local')) ?></p>
            <h1><?= cms_h((string) $page['hero_title']) ?></h1>
            <div class="hero-text richtext"><?= (string) $page['hero_subtitle'] ?></div>
            <div class="hero-actions"><a class="button primary" href="<?= cms_h(cms_url((string) $page['cta_button_url'])) ?>"><?= cms_h((string) $page['cta_button_label']) ?></a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div>
          </div>
          <div class="home-hero-side"><div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><?php cms_render_image($heroImage, (string) $page['hero_image_alt'], ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 46vw']); ?><?php endif; ?></div></div>
        </div>
      </section>

      <section class="section section-tight"><div class="shell"><article class="panel-card intro-card richtext"><?= (string) $page['intro_html'] ?></article></div></section>

      <section class="section section-tight"><div class="shell standard-sections"><?php foreach ($sections as $section): ?><article class="panel-card standard-section-card"><div><?php if (!empty($section['eyebrow'])): ?><p class="eyebrow"><?= cms_h((string) $section['eyebrow']) ?></p><?php endif; ?><h2><?= cms_h((string) ($section['title'] ?? 'Section')) ?></h2><div class="richtext panel-copy"><?= (string) ($section['text'] ?? '') ?></div><?php if (!empty($section['items'])): ?><ul class="accent-list"><?php foreach ($section['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?></div><div><?php if (!empty($section['image'])): ?><?php cms_render_image((string) $section['image'], (string) ($section['imageAlt'] ?? ''), ['class' => 'section-image', 'sizes' => '(max-width: 767px) 100vw, 40vw']); ?><?php endif; ?><?php if (!empty($section['stats'])): ?><div class="stats-tiles"><?php foreach ($section['stats'] as $stat): ?><div class="tile-card"><strong><?= cms_h((string) ($stat['value'] ?? '')) ?></strong><span><?= cms_h((string) ($stat['label'] ?? '')) ?></span></div><?php endforeach; ?></div><?php endif; ?></div></article><?php endforeach; ?></div></section>

      <?php if (($page['page_type'] ?? 'main') === 'local'): ?>
        <?php $advantages = cms_json_list($page['local_advantages_json'] ?? '[]'); ?>
        <?php $nearbyCities = cms_json_list($page['nearby_cities_json'] ?? '[]'); ?>
        <section class="section section-tight"><div class="shell duo-grid duo-grid-small"><article class="panel-card"><h2>Atouts locaux</h2><ul class="accent-list"><?php foreach ($advantages as $item): ?><li><?= cms_h($item) ?></li><?php endforeach; ?></ul></article><article class="panel-card"><h2>Villes proches</h2><div class="tags-wrap"><?php foreach ($nearbyCities as $city): ?><span><?= cms_h($city) ?></span><?php endforeach; ?></div></article></div></section>
      <?php endif; ?>

      <section class="section section-tight"><div class="shell"><div class="cta-band"><div><p class="eyebrow">Passer à l’action</p><h2><?= cms_h((string) $page['cta_title']) ?></h2><div class="richtext"><?= (string) $page['cta_text'] ?></div></div><a class="button primary" href="<?= cms_h(cms_url((string) $page['cta_button_url'])) ?>"><?= cms_h((string) $page['cta_button_label']) ?></a></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}
function cms_render_histoire_page(array $settings): void
{
    $snapshot = cms_snapshot();
    $title = 'Notre histoire';
    $description = 'Deux conseillers immobiliers ancrés en Auxois-Morvan : proximité, estimation, valorisation et accompagnement humain jusqu’à la signature.';
    $mickaelPhoto = trim((string) ($settings['mickael_photo'] ?? ''));
    $marionPhoto = trim((string) ($settings['marion_photo'] ?? ''));
    $heroImage = '/uploads/arnay.jpg';
    $methodSteps = [
        ['number' => '01', 'title' => 'Écouter avant de conseiller', 'text' => 'Comprendre votre histoire, votre calendrier, vos contraintes et ce que vous attendez réellement de la vente ou de l’achat.'],
        ['number' => '02', 'title' => 'Lire le marché localement', 'text' => 'Comparer avec les références utiles du secteur, l’état du bien, la demande réelle et les spécificités du village ou de la ville.'],
        ['number' => '03', 'title' => 'Valoriser avec méthode', 'text' => 'Préparer le bien, le présenter clairement, sélectionner les bons supports et qualifier les contacts avec sérieux.'],
        ['number' => '04', 'title' => 'Rester présents jusqu’au bout', 'text' => 'Suivre les échanges, sécuriser les étapes administratives et garder un dialogue simple jusqu’à la signature.'],
    ];

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true, [], [
      'preload_image' => $heroImage,
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 42vw',
    ]);
    cms_render_public_header($settings, '/histoire');
    ?>
    <main class="history-premium-page">
      <section class="history-hero">
        <div class="shell history-hero-grid">
          <div class="history-hero-copy">
            <p class="eyebrow">Notre histoire</p>
            <h1>Une présence locale, deux regards complémentaires</h1>
            <p class="hero-text">Notre rôle n’est pas seulement de vendre un bien. C’est de comprendre une situation, d’éclairer une décision et de défendre une stratégie juste, avec une connaissance concrète de l’Auxois, du Morvan et des villages qui les relient.</p>
            <div class="history-hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Faire connaissance</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Estimer un bien</a>
            </div>
          </div>
          <aside class="history-hero-media">
            <?php cms_render_image($heroImage, 'Village et patrimoine en Auxois-Morvan', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 42vw']); ?>
            <div class="history-hero-note"><strong>Local</strong><span>Auxois · Morvan · Côte-d’Or</span></div>
          </aside>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell history-intro-band">
          <p>Nous accompagnons des projets de vente, d’achat, d’estimation et de transmission avec une conviction simple : un bon conseil immobilier commence toujours par une lecture honnête du terrain et une relation de confiance.</p>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell history-team-layout">
          <article class="history-team-narrative">
            <p class="eyebrow">Vos conseillers</p>
            <h2>Deux profils, une même exigence : être utiles, clairs et présents.</h2>
            <p>Notre complémentarité se voit surtout dans la manière d’accompagner : une lecture précise du terrain, une relation suivie, des conseils francs et une présence régulière à chaque étape.</p>
            <ul>
              <li>Une estimation expliquée, pas seulement annoncée.</li>
              <li>Une stratégie adaptée au bien, à la commune et au calendrier.</li>
              <li>Un suivi simple, humain et réactif jusqu’à la signature.</li>
            </ul>
          </article>
          <div class="history-team-grid" aria-label="Présentation de Mickael Gury et Marion Roullier">
            <article class="history-profile-card">
              <?php if ($mickaelPhoto !== ''): ?><?php cms_render_image($mickaelPhoto, (string) $settings['mickael_name'], ['sizes' => '(max-width: 767px) 100vw, 320px']); ?><?php endif; ?>
              <div>
                <p class="card-kicker">Terrain · stratégie · négociation</p>
                <h3><?= cms_h((string) $settings['mickael_name']) ?></h3>
                <p>Mickael apporte une lecture directe du marché local et du bon positionnement du bien, avec une stratégie claire et réaliste.</p>
              </div>
            </article>
            <article class="history-profile-card is-reverse">
              <?php if ($marionPhoto !== ''): ?><?php cms_render_image($marionPhoto, (string) $settings['marion_name'], ['sizes' => '(max-width: 767px) 100vw, 320px']); ?><?php endif; ?>
              <div>
                <p class="card-kicker">Écoute · suivi · accompagnement</p>
                <h3><?= cms_h((string) $settings['marion_name']) ?></h3>
                <p>Marion veille à la fluidité du parcours : disponibilité, coordination des étapes et qualité de la relation.</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell history-method-panel">
          <div class="history-method-copy">
            <p class="eyebrow">Notre méthode</p>
            <h2>Une méthode simple, mais jamais automatique.</h2>
            <p>Chaque maison, chaque vendeur et chaque commune ont leur réalité. Nous construisons l’accompagnement autour de quatre temps forts, pour éviter les approximations et garder un cap lisible.</p>
          </div>
          <div class="history-method-grid">
            <?php foreach ($methodSteps as $step): ?>
              <article class="history-step-card">
                <span><?= cms_h($step['number']) ?></span>
                <h3><?= cms_h($step['title']) ?></h3>
                <p><?= cms_h($step['text']) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell history-proof-grid">
          <article class="history-local-card">
            <p class="eyebrow">Ancrage local</p>
            <h2>Une connaissance du secteur qui change la qualité du conseil.</h2>
            <p>Une estimation en Auxois-Morvan ne se limite pas à une moyenne au mètre carré. Elle dépend de la commune, de la qualité du bâti, des travaux, des accès, de la rareté du bien et du type d’acquéreur susceptible de se projeter.</p>
            <ul>
              <li>Maisons anciennes et propriétés familiales</li>
              <li>Résidences secondaires et biens de caractère</li>
              <li>Villages, bourgs actifs et secteurs ruraux</li>
            </ul>
          </article>
          <article class="history-image-card"><?php cms_render_image('/uploads/maison-Maconge-20.jpg', 'Maison en pierre en Auxois-Morvan', ['sizes' => '(max-width: 767px) 100vw, 45vw']); ?></article>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell history-values-grid">
          <article><strong>Clarté</strong><span>Dire ce qui est réaliste, expliquer les choix, éviter les discours flous.</span></article>
          <article><strong>Présence</strong><span>Rester disponibles, répondre, relancer et accompagner les étapes sensibles.</span></article>
          <article><strong>Exigence</strong><span>Soigner la présentation, qualifier les contacts et défendre le bon positionnement.</span></article>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell"><div class="cta-band"><div><p class="eyebrow">Faisons connaissance</p><h2>Parlons simplement de votre projet immobilier.</h2><div class="richtext"><p>Un premier échange permet de comprendre votre situation, votre commune, votre calendrier et la meilleure manière d’avancer.</p></div></div><a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_avis_page(array $settings): void
{
    $snapshot = cms_snapshot();
    $testimonials = $snapshot['testimonials'] ?? [];
    $title = 'Avis clients';
    $description = 'Découvrez les retours de propriétaires et acheteurs accompagnés par Immobilier Auxois-Morvan en Auxois, Morvan et Côte-d\'Or.';
    $reviewCount = count($testimonials);
    $ratingSum = 0;
    $fiveStarCount = 0;
    foreach ($testimonials as $testimonial) {
        $rating = (int) ($testimonial['rating'] ?? 5);
        $ratingSum += $rating;
        if ($rating >= 5) {
            $fiveStarCount += 1;
        }
    }
    $averageRating = $reviewCount > 0 ? number_format($ratingSum / $reviewCount, 1, ',', ' ') : '5,0';
    $cleanQuote = static function (string $quote): string {
        return (string) (preg_replace('/^Avis Immodvisor\s*:\s*/u', '', $quote) ?? $quote);
    };
    $featured = $testimonials[0] ?? null;

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true, [], [
      'preload_image' => '/uploads/pouilly.jpg',
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 42vw',
    ]);
    cms_render_public_header($settings, '/avis');
    ?>
    <main class="avis-premium-page">
      <section class="avis-hero">
        <div class="shell avis-hero-grid">
          <div class="avis-hero-copy">
            <p class="eyebrow">Avis clients</p>
            <h1>Des retours concrets, une confiance qui se construit.</h1>
            <p class="hero-text">Les avis clients racontent ce qui compte vraiment dans un projet immobilier : la disponibilité, la clarté des conseils, la réactivité et le sentiment d’être accompagné avec sérieux.</p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
          <aside class="avis-hero-panel">
            <div class="avis-rating-badge">
              <span><?= cms_h($averageRating) ?>/5</span>
              <div class="dots-row"><?php for ($i = 0; $i < 5; $i += 1): ?><span></span><?php endfor; ?></div>
              <p>Note moyenne des avis intégrés</p>
            </div>
            <?php cms_render_image('/uploads/pouilly.jpg', 'Paysage de l\'Auxois-Morvan', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 42vw']); ?>
          </aside>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell avis-kpi-grid">
          <article><strong><?= cms_h((string) $reviewCount) ?></strong><span>avis clients intégrés depuis Immodvisor</span></article>
          <article><strong><?= cms_h((string) $fiveStarCount) ?></strong><span>retours avec la meilleure note</span></article>
          <article><strong>4</strong><span>qualités qui reviennent : écoute, sérieux, suivi, réactivité</span></article>
        </div>
      </section>

      <?php if ($featured !== null): ?>
        <section class="section section-tight">
          <div class="shell avis-featured-layout">
            <article class="avis-featured-card">
              <p class="eyebrow">Avis mis en avant</p>
              <div class="dots-row"><?php for ($i = 0; $i < (int) ($featured['rating'] ?? 5); $i += 1): ?><span></span><?php endfor; ?></div>
              <blockquote>“<?= cms_h($cleanQuote((string) ($featured['quote'] ?? ''))) ?>”</blockquote>
              <footer><strong><?= cms_h((string) ($featured['author'] ?? 'Client')) ?></strong><span><?= cms_h(implode(' — ', array_filter([(string) ($featured['title'] ?? ''), (string) ($featured['location'] ?? '')]))) ?></span></footer>
            </article>
            <article class="avis-proof-card">
              <p class="eyebrow">Ce que les avis soulignent</p>
              <h2>Une qualité d’accompagnement visible dans les retours clients.</h2>
              <ul>
                <li>Des échanges réguliers et une vraie disponibilité.</li>
                <li>Une capacité à sécuriser les étapes, même à distance.</li>
                <li>Une approche humaine dans des contextes parfois sensibles.</li>
              </ul>
            </article>
          </div>
        </section>
      <?php endif; ?>

      <section class="section section-tight">
        <div class="shell avis-section-head">
          <p class="eyebrow">Témoignages</p>
          <h2>Des expériences de vente et d’achat racontées par nos clients.</h2>
          <p>Ces avis reflètent des situations variées : vente rapide, succession, accompagnement à distance, achat ou projet mené dans les délais.</p>
        </div>
        <div class="shell avis-testimonial-grid">
          <?php foreach ($testimonials as $testimonial): ?>
            <article class="avis-testimonial-card">
              <div class="avis-card-head">
                <div class="dots-row"><?php for ($i = 0; $i < (int) ($testimonial['rating'] ?? 5); $i += 1): ?><span></span><?php endfor; ?></div>
                <span><?= cms_h((string) ($testimonial['title'] ?? 'Avis client')) ?></span>
              </div>
              <p>“<?= cms_h($cleanQuote((string) ($testimonial['quote'] ?? ''))) ?>”</p>
              <footer>
                <strong><?= cms_h((string) ($testimonial['author'] ?? 'Client')) ?></strong>
                <span><?= cms_h((string) ($testimonial['location'] ?? '')) ?></span>
              </footer>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell avis-method-band">
          <div>
            <p class="eyebrow">Notre engagement</p>
            <h2>Faire simple, rester présents, expliquer chaque étape.</h2>
          </div>
          <div class="avis-method-list">
            <article><strong>Clarté</strong><span>Des conseils argumentés et des décisions expliquées.</span></article>
            <article><strong>Réactivité</strong><span>Des réponses rapides et un suivi régulier des échanges.</span></article>
            <article><strong>Fiabilité</strong><span>Un cadre sérieux du premier contact à la signature.</span></article>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">À votre tour</p><h2>Et si votre projet devenait le prochain avis positif ?</h2><div class="richtext"><p>Vente, achat ou estimation : prenons le temps d’un échange simple pour définir la meilleure stratégie.</p></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_prestations_page(array $settings): void
{
    $snapshot = cms_snapshot();
    $title = 'Prestations immobilières';
    $description = 'Vente, achat, estimation et fonds de commerce en Auxois-Morvan : un accompagnement immobilier clair, local et structuré.';
    $serviceSections = [
        [
            'number' => '01',
            'kicker' => 'Vendre',
            'title' => 'Vente immobilière',
            'image' => '/uploads/maison-Maconge-20.jpg',
            'alt' => 'Maison en pierre à vendre en Auxois-Morvan',
            'href' => '/vendre',
            'text' => 'Vendre un bien demande plus qu’une annonce. Nous construisons une stratégie de mise en marché : estimation argumentée, présentation soignée, diffusion adaptée, qualification des acquéreurs et négociation suivie.',
            'points' => ['Avis de valeur expliqué', 'Positionnement prix réaliste', 'Diffusion locale et réseau IAD', 'Suivi jusqu’à la signature'],
        ],
        [
            'number' => '02',
            'kicker' => 'Acheter',
            'title' => 'Recherche et achat immobilier',
            'image' => '/uploads/IMG_20240820_194156.jpg',
            'alt' => 'Terrasse et maison en Auxois-Morvan',
            'href' => '/acheter',
            'text' => 'Pour acheter sereinement, il faut comparer les secteurs, lire les prix avec recul et anticiper les contraintes du bien. Nous vous aidons à clarifier vos critères et à décider avec des repères concrets.',
            'points' => ['Lecture du marché local', 'Analyse des atouts et limites', 'Conseil avant l’offre', 'Accompagnement des étapes clés'],
        ],
        [
            'number' => '03',
            'kicker' => 'Estimer',
            'title' => 'Estimation et avis de valeur',
            'image' => '/uploads/estimer.jpg',
            'alt' => 'Analyse et estimation immobilière locale',
            'href' => '/estimation',
            'text' => 'Une estimation utile ne se limite pas à une moyenne au mètre carré. Elle tient compte de la commune, du bâti, des travaux, de la rareté, des accès et de la demande réelle dans votre secteur.',
            'points' => ['Analyse du bien sur place', 'Comparables pertinents', 'Fourchette de valeur lisible', 'Conseil pour vendre au bon moment'],
        ],
        [
            'number' => '04',
            'kicker' => 'Transmettre',
            'title' => 'Fonds de commerce',
            'image' => '/uploads/post-images-03-scaled-1.jpg',
            'alt' => 'Commerce local et transmission professionnelle',
            'href' => '/fonds',
            'text' => 'La cession d’un fonds de commerce exige méthode et discrétion. Nous aidons à présenter l’activité, structurer les informations utiles et qualifier les repreneurs pour fluidifier les échanges.',
            'points' => ['Dossier de présentation', 'Valorisation cohérente', 'Contacts qualifiés', 'Accompagnement confidentiel'],
        ],
    ];
    $methodSteps = [
        ['title' => 'Comprendre', 'text' => 'Votre situation, votre calendrier, vos priorités et le niveau d’accompagnement attendu.'],
        ['title' => 'Analyser', 'text' => 'Le bien, son environnement, la concurrence, les points forts et les éventuels freins.'],
        ['title' => 'Structurer', 'text' => 'Une stratégie claire : prix, présentation, diffusion, qualification et calendrier.'],
        ['title' => 'Suivre', 'text' => 'Des échanges réguliers, des décisions expliquées et une présence jusqu’à la signature.'],
    ];

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true, [], [
      'preload_image' => '/uploads/auxois.jpg',
      'preload_image_sizes' => '(max-width: 1023px) 100vw, 42vw',
    ]);
    cms_render_public_header($settings, '/prestations');
    ?>
    <main class="prestations-premium-page">
      <section class="prestations-hero">
        <div class="shell prestations-hero-grid">
          <div class="prestations-hero-copy">
            <p class="eyebrow">Prestations immobilières</p>
            <h1>Un accompagnement clair, local et structuré.</h1>
            <p class="hero-text">Vente, achat, estimation ou transmission d’un fonds de commerce : chaque projet mérite une lecture précise du terrain, une stratégie lisible et un suivi humain jusqu’à la décision.</p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
          <aside class="prestations-hero-media">
            <?php cms_render_image('/uploads/auxois.jpg', 'Paysage immobilier en Auxois-Morvan', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) 100vw, 42vw']); ?>
            <div class="prestations-hero-card"><strong>4</strong><span>domaines d’accompagnement</span></div>
          </aside>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell prestations-intro-grid">
          <article>
            <span>01</span>
            <strong>Conseil local</strong>
            <p>Une approche adaptée aux communes, aux biens anciens, aux secteurs ruraux et aux bassins de vie de l’Auxois-Morvan.</p>
          </article>
          <article>
            <span>02</span>
            <strong>Méthode claire</strong>
            <p>Des étapes expliquées, un cadre de travail posé et des recommandations compréhensibles.</p>
          </article>
          <article>
            <span>03</span>
            <strong>Suivi humain</strong>
            <p>Un accompagnement régulier, réactif et concret, du premier échange jusqu’à la signature.</p>
          </article>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell prestations-section-head">
          <p class="eyebrow">Nos expertises</p>
          <h2>Quatre prestations pour avancer avec les bons repères.</h2>
          <p>Chaque service répond à une situation précise. L’objectif reste le même : vous aider à prendre une décision immobilière solide, sans discours flou ni promesse artificielle.</p>
        </div>
        <div class="shell prestations-service-list">
          <?php foreach ($serviceSections as $index => $service): ?>
            <article class="prestations-service-row<?= $index % 2 === 1 ? ' is-reverse' : '' ?>">
              <a class="prestations-service-image" href="<?= cms_h(cms_url($service['href'])) ?>">
                <?php cms_render_image($service['image'], $service['alt'], ['sizes' => '(max-width: 767px) 100vw, 40vw']); ?>
              </a>
              <div class="prestations-service-content">
                <span class="prestations-number"><?= cms_h($service['number']) ?></span>
                <p class="eyebrow"><?= cms_h($service['kicker']) ?></p>
                <h3><?= cms_h($service['title']) ?></h3>
                <p><?= cms_h($service['text']) ?></p>
                <ul>
                  <?php foreach ($service['points'] as $point): ?>
                    <li><?= cms_h($point) ?></li>
                  <?php endforeach; ?>
                </ul>
                <a class="card-link" href="<?= cms_h(cms_url($service['href'])) ?>">Découvrir cette prestation →</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell prestations-method-panel">
          <div class="prestations-method-copy">
            <p class="eyebrow">Notre façon de travailler</p>
            <h2>Un cadre simple pour garder le contrôle du projet.</h2>
            <p>Nous privilégions la clarté : comprendre votre besoin, analyser le bien et le marché, structurer une stratégie puis suivre les étapes avec sérieux.</p>
          </div>
          <div class="prestations-method-grid">
            <?php foreach ($methodSteps as $index => $step): ?>
              <article>
                <span><?= cms_h(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                <strong><?= cms_h($step['title']) ?></strong>
                <p><?= cms_h($step['text']) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell prestations-iad-layout">
          <article class="prestations-iad-card">
            <p class="eyebrow">Réseau IAD</p>
            <h2>La force d’un réseau, avec un interlocuteur vraiment local.</h2>
            <p>Vous bénéficiez d’une présence de terrain en Auxois-Morvan et des outils du réseau IAD : visibilité, diffusion, suivi des contacts et accompagnement administratif.</p>
          </article>
          <div class="prestations-iad-points">
            <article><strong>Diffusion</strong><span>Une exposition élargie sans perdre la lecture locale du projet.</span></article>
            <article><strong>Qualification</strong><span>Des contacts suivis, relancés et filtrés pour gagner en efficacité.</span></article>
            <article><strong>Sécurité</strong><span>Un accompagnement régulier dans les étapes sensibles de la transaction.</span></article>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">Échangeons</p><h2>Quelle prestation correspond à votre projet ?</h2><div class="richtext"><p>Un premier échange suffit souvent à clarifier la bonne approche : estimation, vente, achat, conseil local ou transmission.</p></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

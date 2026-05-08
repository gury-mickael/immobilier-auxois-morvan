<?php

declare(strict_types=1);

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
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/admin.css')) ?>">
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

function cms_render_page_form(array $page, string $mode, string $actionLabel): void
{
    $sections = cms_page_sections($page);
    $advantages = implode("\n", cms_json_list($page['local_advantages_json'] ?? '[]'));
    $nearbyCities = implode("\n", cms_json_list($page['nearby_cities_json'] ?? '[]'));
    $mediaItems = cms_media_items();
    ?>
    <form method="post" class="admin-form-stack">
      <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
      <input type="hidden" name="section_count" id="section-count" value="<?= count($sections) ?>">

      <section class="panel">
        <div class="panel-head">
          <div>
            <p class="eyebrow">SEO</p>
            <h1><?= cms_h($actionLabel) ?></h1>
          </div>
          <div class="status-badge status-<?= cms_h((string) $page['status']) ?>"><?= cms_h((string) $page['status']) ?></div>
        </div>
        <div class="grid two-cols">
          <label>
            Titre SEO
            <input name="title" value="<?= cms_h((string) $page['title']) ?>" required>
          </label>
          <label>
            Slug / URL
            <input name="slug" value="<?= cms_h((string) $page['slug']) ?>" required>
          </label>
          <label class="full">
            Meta description
            <textarea name="meta_description" rows="3" required><?= cms_h((string) $page['meta_description']) ?></textarea>
          </label>
          <label>
            H1
            <input name="h1" value="<?= cms_h((string) $page['h1']) ?>" required>
          </label>
          <label class="toggle-field">
            <span>Indexable</span>
            <input type="checkbox" name="is_indexable" value="1" <?= (int) ($page['is_indexable'] ?? 1) === 1 ? 'checked' : '' ?>>
          </label>
        </div>
      </section>

      <?php if ($mode === 'local'): ?>
        <section class="panel">
          <div class="grid two-cols">
            <label>
              Ville
              <input name="city" value="<?= cms_h((string) $page['city']) ?>" required>
            </label>
            <label>
              Type de page locale
              <input name="local_page_type" value="<?= cms_h((string) $page['local_page_type']) ?>" placeholder="estimation-immobiliere" required>
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

      <section class="panel">
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

      <section class="panel">
        <div class="panel-head compact">
          <div>
            <p class="eyebrow">Contenu</p>
            <h2>Introduction</h2>
          </div>
        </div>
        <div class="rich-editor" data-target="intro_html"></div>
        <textarea hidden id="intro_html" name="intro_html"><?= cms_h((string) $page['intro_html']) ?></textarea>
      </section>

      <section class="panel">
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

      <section class="panel">
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
          <label>
            Statut
            <select name="status">
              <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
              <option value="published" <?= ($page['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
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
        const section = trigger.closest('.section-editor');
        if (section && container.children.length > 1) {
          section.remove();
        }
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
    ?>
    <article class="section-editor">
      <div class="panel-head compact">
        <div>
          <p class="eyebrow">Bloc</p>
          <h3>Section</h3>
        </div>
        <button type="button" class="danger-link" data-remove-section>Supprimer</button>
      </div>
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
    </article>
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
        true
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
            <div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><img src="<?= cms_h(cms_url($heroImage)) ?>" alt="<?= cms_h($heroAlt) ?>"><?php endif; ?></div>
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
                    <img src="<?= cms_h(cms_url((string) $post['featured_image'])) ?>" alt="<?= cms_h((string) (($post['featured_image_alt'] ?? '') ?: ($post['title'] ?? ''))) ?>">
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

    cms_render_public_document_start(
        $metaTitle . ' | ' . (string) $settings['site_name'],
        $metaDescription,
        (int) ($post['is_indexable'] ?? 1) === 1
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
          <div class="hero-media<?= $image !== '' ? '' : ' no-image' ?>"><?php if ($image !== ''): ?><img src="<?= cms_h(cms_url($image)) ?>" alt="<?= cms_h((string) (($post['featured_image_alt'] ?? '') ?: ($post['title'] ?? ''))) ?>"><?php endif; ?></div>
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
            'Maison' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.2 12 4l9 7.2"/><path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/><path d="M10 20v-5h4v5"/></svg>',
            'Appartement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="7" height="18" rx="1"/><rect x="13" y="8" width="7" height="13" rx="1"/><path d="M6.5 7h2M6.5 11h2M6.5 15h2M15.5 11h2M15.5 15h2"/></svg>',
            'Terrain' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 19 5-7 4 5 3-4 6 6"/><circle cx="16" cy="8" r="1.6"/></svg>',
            'Immeuble' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="7" height="18" rx="1"/><rect x="13" y="8" width="7" height="13" rx="1"/><path d="M6.5 7h2M6.5 11h2M6.5 15h2M15.5 11h2M15.5 15h2"/></svg>',
            'Autre' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="18" cy="12" r="1.4"/></svg>',
            // Rooms
            '1 ou 2' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12V8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/><path d="M3 18v-4h18v4"/><path d="M3 18v2M21 18v2"/></svg>',
            '3' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="1"/><circle cx="14.5" cy="12" r="0.9" fill="currentColor"/></svg>',
            '4' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.2 12 4l9 7.2"/><path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/><path d="M10 20v-5h4v5"/></svg>',
            '5 ou plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V11l6-4 6 4v10"/><path d="M15 21v-7h6v7"/><path d="M3 21h18"/></svg>',
            'Je ne sais pas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1 1-1.1 1.8v.5"/><path d="M12 17.2v.1"/></svg>',
            // Condition
            'Neuf / rénové récemment' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 4 1.5 4 4 1.5-4 1.5L12 15l-1.5-4L6.5 9.5 10.5 8 12 4Z"/><path d="M19 17 18 19l-2 1 2 1 1 2 1-2 2-1-2-1Z"/></svg>',
            'Bon état' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.4 2.5 2.4 4.5-5"/></svg>',
            'Travaux à prévoir' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a3.5 3.5 0 0 0-4.6 4.6l-6.6 6.6 2.1 2.1 6.6-6.6a3.5 3.5 0 0 0 4.6-4.6l-2.2 2.2-1.7-1.7Z"/></svg>',
            'À rénover entièrement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m13.5 6.5 4-4 3 3-4 4"/><path d="m13.5 6.5-9 9v3h3l9-9"/><path d="m6.5 13.5 3 3"/></svg>',
            // Living surface
            'Moins de 40 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>',
            '40 – 70 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20 20 4"/><path d="M4 20h16V8"/></svg>',
            '70 – 100 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="1"/><path d="M12 4v16M4 12h8"/></svg>',
            '100 – 150 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/></svg>',
            'Plus de 150 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>',
            // Land surface
            'Pas de terrain' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m6 6 12 12"/></svg>',
            'Moins de 500 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="1"/></svg>',
            '500 – 1 000 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="1"/></svg>',
            '1 000 – 2 000 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M9 3v18M3 9h18"/></svg>',
            'Plus de 2 000 m²' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>',
            // Goal
            'Vendre rapidement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m13 3-8 11h6l-1 7 8-11h-6l1-7Z"/></svg>',
            'Vendre au meilleur prix' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m4 17 6-6 4 4 6-7"/><path d="M14 8h6v6"/></svg>',
            'Simple curiosité' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h6l1 3h11l-2 9H6L3 5Z"/><path d="M3 5 2 2"/></svg>',
            'Projet d’achat / vente' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h7l3 3"/><path d="M21 17h-7l-3-3"/><path d="m17 13 4 4-4 4M7 11 3 7l4-4"/></svg>',
            'Succession' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v6"/><path d="M5 9h14l-1 11H6L5 9Z"/><path d="M9 13v3M15 13v3"/></svg>',
            'Séparation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 12 4-4M3 12l4 4M21 12l-4-4M21 12l-4 4M3 12h7M14 12h7"/></svg>',
            'Autre situation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="18" cy="12" r="1.4"/></svg>',
            // Timeline
            'Dès maintenant' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
            'Dans les 3 mois' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>',
            'Dans les 6 mois' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/><path d="M12 15v3"/></svg>',
            'Plus tard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="M16 4 19 7"/></svg>',
            'Je ne sais pas encore' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1 1-1.1 1.8v.5"/><path d="M12 17.2v.1"/></svg>',
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
                  <?php foreach (['Appartement', 'Maison', 'Terrain', 'Immeuble', 'Autre'] as $option) { $renderChoiceCard('property_type', $option, 'is-stacked'); } ?>
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
                  <?php foreach (['Pas de terrain', 'Moins de 500 m²', '500 – 1 000 m²', '1 000 – 2 000 m²', 'Plus de 2 000 m²'] as $option) { $renderChoiceCard('land_surface', $option, 'align-left'); } ?>
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
                <h2>Quelle est l’adresse ?</h2>
                <p>Tapez les premières lettres de votre adresse ou un secteur.</p>
                <div class="estimate-input-stack">
                  <div class="estimate-search-field">
                    <span class="estimate-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                    <input id="estimate-address-details" name="address_details" autocomplete="off" value="<?= cms_h((string) $formData['address_details']) ?>" placeholder="Rechercher votre adresse…" aria-label="Adresse">
                  </div>
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
                        <input type="tel" name="phone" value="<?= cms_h((string) $formData['phone']) ?>" autocomplete="tel" placeholder="06 12 34 56 78" required>
                      </span>
                    </label>
                  </div>
                  <p class="estimate-rgpd">Vos informations restent confidentielles et ne sont partagées qu'avec votre conseiller Immobilier Auxois Morvan.</p>
                </div>
                <label class="privacy-line estimate-consent-line"><input type="checkbox" name="contact_consent" value="1" <?= (int) $formData['contact_consent'] === 1 ? 'checked' : '' ?> required><span>J’accepte d’être recontacté au sujet de ma demande d’estimation.</span></label>
              </section>

              <div class="estimate-actions">
                <button id="estimate-next-button" class="primary-button estimate-next-button" type="button" disabled>Suivant</button>
                <button id="estimate-submit-button" class="primary-button estimate-submit-button" type="submit" hidden>Recevoir mon estimation gratuite</button>
                <button id="estimate-back-button" class="estimate-back-button" type="button">← Retour</button>
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

            <ul class="estimate-trust-features">
              <li>
                <span class="estimate-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                <span>Données<br>sécurisées</span>
              </li>
              <li>
                <span class="estimate-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.4 2.5 2.4 4.5-5"/></svg></span>
                <span>Estimation<br>gratuite</span>
              </li>
              <li>
                <span class="estimate-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 11c0-3 2.7-5 7-5s7 2 7 5v3c0 1.1-.9 2-2 2h-1.5"/><path d="M5 11v3a3 3 0 0 0 3 3h2v-6H8a3 3 0 0 0-3 3Z"/><path d="M14 17v2a2 2 0 0 0 4 0"/><circle cx="16.5" cy="11" r="0.6" fill="currentColor"/></svg></span>
                <span>100%<br>gratuit</span>
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
        const stepLabel = document.getElementById('estimate-step-label');
        const stepPercent = document.getElementById('estimate-step-percent');
        const progressBar = document.getElementById('estimate-progress-bar');
        const communeInput = document.getElementById('estimate-commune-search');
        const postalCodeInput = document.getElementById('estimate-postal-code');
        const suggestionBox = document.getElementById('estimate-commune-suggestions');
        const zoneWarning = document.getElementById('estimate-zone-warning');
        const addressField = document.getElementById('estimate-address-details');
        const originPageField = document.getElementById('estimate-origin-page');
        const outsideAreaField = document.getElementById('estimate-outside-area');
        const totalSteps = panes.length;
        const mimeure = { lat: 47.1546, lng: 4.4958 };
        const autoAdvanceSteps = new Set([1, 2, 3, 4, 5, 8, 9]);
        let activeStep = 1;
        let suggestionAbortController = null;
        let autoAdvanceTimer = null;
        let suppressNextSuggestion = false;

        const triggerTracking = (name, payload = {}) => {
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

        const isStepValid = (stepNumber) => {
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

        const updateNavigationState = () => {
          panes.forEach((pane, index) => {
            pane.hidden = index + 1 !== activeStep;
          });

          const shouldAutoAdvance = autoAdvanceSteps.has(activeStep);
          const percent = Math.round((activeStep / totalSteps) * 100);
          stepLabel.textContent = `ÉTAPE ${activeStep} SUR ${totalSteps}`;
          stepPercent.textContent = `${percent}%`;
          progressBar.style.width = `${percent}%`;

          backButton.hidden = activeStep === 1;
          nextButton.hidden = activeStep === totalSteps || shouldAutoAdvance;
          submitButton.hidden = activeStep !== totalSteps;
          nextButton.disabled = !isStepValid(activeStep);
          submitButton.disabled = !isContactStepValid();
          syncChoiceState();
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

            setValue(fieldName, fieldValue);
            updateNavigationState();

            if (autoAdvanceTimer) {
              window.clearTimeout(autoAdvanceTimer);
            }

            if (stepNumber === activeStep && autoAdvanceSteps.has(stepNumber) && isStepValid(stepNumber)) {
              triggerTracking('estimation_step_completed', { step_number: stepNumber });
              autoAdvanceTimer = window.setTimeout(() => {
                activeStep = Math.min(totalSteps, stepNumber + 1);
                updateNavigationState();
              }, 140);
            }
          });
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

          triggerTracking('estimation_step_completed', { step_number: activeStep });
          activeStep = Math.min(totalSteps, activeStep + 1);
          updateNavigationState();
        });

        backButton.addEventListener('click', () => {
          if (autoAdvanceTimer) {
            window.clearTimeout(autoAdvanceTimer);
          }

          activeStep = Math.max(1, activeStep - 1);
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
    <main>
      <section class="contact-premium-hero">
        <div class="shell contact-premium-grid">
          <form class="contact-premium-form" method="post" action="<?= cms_h(cms_url('/contact')) ?>">
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden-field" aria-hidden="true">
            <p class="eyebrow">Contact direct</p>
            <h1>Parlons de votre projet immobilier</h1>
            <p class="contact-form-lead"><?= cms_h(strip_tags((string) $page['hero_subtitle'])) ?></p>

            <?php if ($success): ?>
              <div class="contact-alert success">Merci, votre demande a bien été transmise. Nous revenons vers vous rapidement.</div>
            <?php endif; ?>
            <?php if ($errors): ?>
              <div class="contact-alert error"><?= cms_h(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <div class="contact-fields three">
              <label>Votre projet<select name="project"><option value="">Choisir</option><option value="Vendre">Vendre</option><option value="Acheter">Acheter</option><option value="Estimation">Faire estimer</option><option value="Fonds de commerce">Fonds de commerce</option></select></label>
              <label>Localisation<input name="location" placeholder="Arnay-le-Duc, Autun..."></label>
              <label>Objet<select name="subject" required><option value="">Choisir</option><option value="Premier rendez-vous">Premier rendez-vous</option><option value="Demande d'estimation">Demande d'estimation</option><option value="Recherche de bien">Recherche de bien</option><option value="Autre demande">Autre demande</option></select></label>
            </div>
            <div class="contact-fields two">
              <label>Nom<input name="name" placeholder="Votre nom" required></label>
              <label>Email<input type="email" name="email" placeholder="vous@exemple.fr" required></label>
              <label>Téléphone<input type="tel" name="phone" placeholder="07.64.86.59.93"></label>
            </div>
            <label>Message<textarea name="message" placeholder="Décrivez votre projet, votre secteur et votre calendrier." required></textarea></label>
            <label class="privacy-line"><input type="checkbox" name="privacy" value="1" required><span>J’accepte d’être recontacté au sujet de ma demande.</span></label>
            <button class="button primary contact-submit" type="submit">Envoyer le message</button>
          </form>

          <aside class="contact-premium-side">
            <div class="contact-side-card featured">
              <p class="eyebrow">Réponse rapide</p>
              <h2>Parlez directement à Marion et Mickael</h2>
              <a class="contact-phone" href="<?= cms_h('tel:' . preg_replace('/\s+/', '', (string) $settings['phone'])) ?>"><?= cms_h((string) $settings['phone']) ?></a>
              <a class="contact-mail" href="<?= cms_h('mailto:' . (string) $settings['email']) ?>"><?= cms_h((string) $settings['email']) ?></a>
            </div>

            <div class="team-photo-card">
              <div>
                <?php if ($marionPhoto !== ''): ?><img src="<?= cms_h(cms_url($marionPhoto)) ?>" alt="<?= cms_h((string) $settings['marion_name']) ?>"><?php else: ?><span><?= cms_h(substr((string) $settings['marion_name'], 0, 1)) ?></span><?php endif; ?>
                <strong><?= cms_h((string) $settings['marion_name']) ?></strong>
              </div>
              <div>
                <?php if ($mickaelPhoto !== ''): ?><img src="<?= cms_h(cms_url($mickaelPhoto)) ?>" alt="<?= cms_h((string) $settings['mickael_name']) ?>"><?php else: ?><span><?= cms_h(substr((string) $settings['mickael_name'], 0, 1)) ?></span><?php endif; ?>
                <strong><?= cms_h((string) $settings['mickael_name']) ?></strong>
              </div>
            </div>

            <div class="contact-side-card">
              <h3>Ce que l’on prépare avec vous</h3>
              <ul class="accent-list compact-list">
                <li>Une estimation claire et argumentée</li>
                <li>Un plan de vente adapté au secteur</li>
                <li>Une recherche qualifiée si vous achetez</li>
                <li>Un rendez-vous simple, au téléphone ou sur place</li>
              </ul>
            </div>

            <div class="contact-side-card">
              <h3>Secteur couvert</h3>
              <div class="contact-tags"><?php foreach ($areas as $area): ?><span><?= cms_h((string) $area) ?></span><?php endforeach; ?></div>
            </div>
          </aside>
        </div>
      </section>

      <section class="section section-tight"><div class="shell"><article class="panel-card intro-card richtext"><?= (string) $page['intro_html'] ?></article></div></section>
      <section class="section section-tight"><div class="shell"><div class="cta-band"><div><p class="eyebrow">Passer à l’action</p><h2><?= cms_h((string) $page['cta_title']) ?></h2><div class="richtext"><?= (string) $page['cta_text'] ?></div></div><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Demander une estimation</a></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_public_nav_items(): array
{
    return [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Histoire', 'href' => '/histoire'],
        ['label' => 'Secteur', 'href' => '/secteur'],
        ['label' => 'Prestations', 'href' => '/prestations'],
        ['label' => 'Avis clients', 'href' => '/avis'],
        ['label' => 'Blog', 'href' => '/blog'],
    ];
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

function cms_render_public_document_start(string $title, string $description, bool $indexable = true, array $structuredData = []): void
{
    ?>
    <!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= cms_h($description) ?>">
        <?php if (!$indexable): ?>
          <meta name="robots" content="noindex,nofollow">
        <?php endif; ?>
        <?php foreach ($structuredData as $block): ?>
          <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
        <?php endforeach; ?>
        <title><?= cms_h($title) ?></title>
        <link rel="icon" type="image/svg+xml" href="<?= cms_h(cms_url('/favicon.svg')) ?>">
        <link rel="icon" href="<?= cms_h(cms_url('/favicon.ico')) ?>">
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>?v=<?= cms_h((string) (@filemtime(__DIR__ . '/../assets/site.css') ?: time())) ?>">
      </head>
      <body>
    <?php
}

function cms_render_public_header(array $settings, string $currentPage): void
{
    ?>
    <header class="site-header">
      <div class="shell">
        <nav class="site-header-bar">
          <a class="site-logo-link" href="<?= cms_h(cms_url('/')) ?>" aria-label="Accueil Immobilier Auxois Morvan">
            <img src="<?= cms_h(cms_url('/uploads/logo-2.png')) ?>" alt="Immobilier Auxois Morvan" class="site-logo">
          </a>
          <div class="site-nav desktop-only">
            <?php foreach (cms_public_nav_items() as $item): ?>
              <a class="site-nav-link<?= cms_is_active_nav($currentPage, (string) $item['href']) ? ' is-active' : '' ?>" href="<?= cms_h(cms_url((string) $item['href'])) ?>"><?= cms_h((string) $item['label']) ?></a>
            <?php endforeach; ?>
          </div>
          <a class="site-cta desktop-only" href="<?= cms_h(cms_url('/contact')) ?>">Contactez-nous</a>
          <a class="mobile-contact" href="<?= cms_h(cms_url('/contact')) ?>">Contact</a>
        </nav>
      </div>
    </header>
    <?php
}

function cms_render_estimation_header(array $settings): void
{
    ?>
    <header class="estimate-header">
      <div class="shell estimate-header-shell">
        <a class="estimate-header-brand" href="<?= cms_h(cms_url('/')) ?>" aria-label="Retour à l'accueil Immobilier Auxois Morvan">
          <img src="<?= cms_h(cms_url('/uploads/logo-2.png')) ?>" alt="Immobilier Auxois Morvan" class="estimate-header-logo">
        </a>
        <span class="estimate-header-divider" aria-hidden="true"></span>
        <span class="estimate-header-agency"><?= cms_h((string) $settings['site_name']) ?></span>
        <a class="estimate-header-cta" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Estimation gratuite</a>
      </div>
    </header>
    <?php
}

function cms_render_public_footer(array $settings, array $snapshot): void
{
    $areas = array_slice($snapshot['siteSettings']['coveredAreas'] ?? cms_json_list($settings['covered_areas_json'] ?? '[]'), 0, 6);
    $mobileAreas = array_slice($areas, 0, 4);
    $services = $snapshot['services'] ?? [];
    ?>
    <footer class="site-footer">
      <div class="shell footer-shell">
        <div class="footer-mobile-stack">
          <div class="footer-brand-column">
            <img src="<?= cms_h(cms_url('/uploads/logo-2.png')) ?>" alt="Immobilier Auxois Morvan" class="footer-logo">
            <p class="footer-copy"><?= cms_h((string) $settings['footer_text']) ?></p>
          </div>
          <div class="footer-contact-card">
            <p class="eyebrow">Contact</p>
            <a href="<?= cms_h('tel:' . preg_replace('/\s+/', '', (string) $settings['phone'])) ?>"><?= cms_h((string) $settings['phone']) ?></a>
            <a href="<?= cms_h('mailto:' . (string) $settings['email']) ?>"><?= cms_h((string) $settings['email']) ?></a>
            <a class="footer-button" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
          </div>
          <details class="footer-accordion">
            <summary>Navigation</summary>
            <div class="footer-accordion-content">
              <a href="<?= cms_h(cms_url('/')) ?>">Accueil</a>
              <a href="<?= cms_h(cms_url('/histoire')) ?>">Histoire</a>
              <a href="<?= cms_h(cms_url('/prestations')) ?>">Prestations</a>
              <a href="<?= cms_h(cms_url('/secteur')) ?>">Secteur</a>
              <a href="<?= cms_h(cms_url('/avis')) ?>">Avis clients</a>
              <a href="<?= cms_h(cms_url('/blog')) ?>">Blog</a>
              <a href="<?= cms_h(cms_url('/contact')) ?>">Contact</a>
            </div>
          </details>
          <details class="footer-accordion">
            <summary>Prestations</summary>
            <div class="footer-accordion-content">
              <?php foreach ($services as $service): ?>
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
            <img src="<?= cms_h(cms_url('/uploads/logo-2.png')) ?>" alt="Immobilier Auxois Morvan" class="footer-logo">
            <p class="footer-copy"><?= cms_h((string) $settings['footer_text']) ?></p>
            <div class="footer-socials">
              <?php if (!empty($settings['facebook_url'])): ?><a href="<?= cms_h((string) $settings['facebook_url']) ?>">Facebook</a><?php endif; ?>
              <?php if (!empty($settings['instagram_url'])): ?><a href="<?= cms_h((string) $settings['instagram_url']) ?>">Instagram</a><?php endif; ?>
              <?php if (!empty($settings['iad_url'])): ?><a href="<?= cms_h((string) $settings['iad_url']) ?>">IAD</a><?php endif; ?>
            </div>
          </div>
          <div class="footer-columns">
            <div>
              <h3>Navigation</h3>
              <a href="<?= cms_h(cms_url('/')) ?>">Accueil</a>
              <a href="<?= cms_h(cms_url('/histoire')) ?>">Histoire</a>
              <a href="<?= cms_h(cms_url('/prestations')) ?>">Prestations</a>
              <a href="<?= cms_h(cms_url('/secteur')) ?>">Secteur</a>
              <a href="<?= cms_h(cms_url('/avis')) ?>">Avis clients</a>
              <a href="<?= cms_h(cms_url('/blog')) ?>">Blog</a>
              <a href="<?= cms_h(cms_url('/contact')) ?>">Contact</a>
            </div>
            <div>
              <h3>Prestations</h3>
              <?php foreach ($services as $service): ?>
                <a href="<?= cms_h(cms_url((string) $service['href'])) ?>"><?= cms_h((string) $service['title']) ?></a>
              <?php endforeach; ?>
            </div>
            <div>
              <h3>Secteur</h3>
              <?php foreach ($areas as $area): ?>
                <span><?= cms_h((string) $area) ?></span>
              <?php endforeach; ?>
              <a href="<?= cms_h(cms_url('/secteur')) ?>">Voir tout le secteur</a>
            </div>
            <div>
              <h3>Conseillers</h3>
              <span><?= cms_h((string) $settings['marion_name']) ?></span>
              <span><?= cms_h((string) $settings['mickael_name']) ?></span>
              <span><?= cms_h((string) $settings['main_city']) ?></span>
              <a href="<?= cms_h(cms_url('/contact')) ?>">Voir la page contact</a>
            </div>
            <div>
              <h3>Contact</h3>
              <a href="<?= cms_h('tel:' . preg_replace('/\s+/', '', (string) $settings['phone'])) ?>"><?= cms_h((string) $settings['phone']) ?></a>
              <a href="<?= cms_h('mailto:' . (string) $settings['email']) ?>"><?= cms_h((string) $settings['email']) ?></a>
              <a class="footer-button" href="<?= cms_h(cms_url('/contact')) ?>">Nous écrire</a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p><?= cms_h((string) $settings['footer_text']) ?></p>
          <div>
            <a href="<?= cms_h(cms_url('/contact')) ?>">Mentions légales</a>
            <a href="<?= cms_h(cms_url('/contact')) ?>">Politique de confidentialité</a>
          </div>
        </div>
      </div>
    </footer>
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
    $blogPosts = array_slice($snapshot['blogPosts'] ?? [], 0, 3);
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
    $homeDescription = 'Mickael Gury et Marion Roulier vous accompagnent localement pour vendre, acheter ou estimer un bien en Auxois-Morvan, avec le réseau IAD, de l’estimation à la signature.';
    $homeSubtitle = 'Mickael Gury & Marion Roulier vous accompagnent localement avec le réseau IAD, de l’estimation à la signature.';
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

    cms_render_public_document_start($homeTitle . ' | ' . (string) $settings['site_name'], $homeDescription, true, $structuredData);
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
            <div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><img src="<?= cms_h(cms_url($heroImage)) ?>" alt="<?= cms_h((string) $page['hero_image_alt']) ?>"><?php endif; ?></div>
            <div class="hero-people-card">
              <p class="hero-people-kicker">Accompagnement local</p>
              <div class="hero-people-grid">
                <div class="hero-person"><?php if ($mickaelPhoto !== ''): ?><img src="<?= cms_h(cms_url($mickaelPhoto)) ?>" alt="<?= cms_h((string) $settings['mickael_name']) ?>" loading="lazy" decoding="async"><?php else: ?><span><?= cms_h(substr((string) $settings['mickael_name'], 0, 1)) ?></span><?php endif; ?><div><strong><?= cms_h((string) $settings['mickael_name']) ?></strong><small>Conseiller immobilier local</small></div></div>
                <div class="hero-person"><?php if ($marionPhoto !== ''): ?><img src="<?= cms_h(cms_url($marionPhoto)) ?>" alt="<?= cms_h((string) $settings['marion_name']) ?>" loading="lazy" decoding="async"><?php else: ?><span><?= cms_h(substr((string) $settings['marion_name'], 0, 1)) ?></span><?php endif; ?><div><strong><?= cms_h((string) $settings['marion_name']) ?></strong><small>Conseillère immobilier locale</small></div></div>
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
              <div class="advisor-card"><?php if ($mickaelPhoto !== ''): ?><img src="<?= cms_h(cms_url($mickaelPhoto)) ?>" alt="<?= cms_h((string) $settings['mickael_name']) ?>" loading="lazy" decoding="async"><?php else: ?><span><?= cms_h(substr((string) $settings['mickael_name'], 0, 1)) ?></span><?php endif; ?><h3><?= cms_h((string) $settings['mickael_name']) ?></h3><p>Conseiller immobilier local</p></div>
              <div class="advisor-card"><?php if ($marionPhoto !== ''): ?><img src="<?= cms_h(cms_url($marionPhoto)) ?>" alt="<?= cms_h((string) $settings['marion_name']) ?>" loading="lazy" decoding="async"><?php else: ?><span><?= cms_h(substr((string) $settings['marion_name'], 0, 1)) ?></span><?php endif; ?><h3><?= cms_h((string) $settings['marion_name']) ?></h3><p>Conseillère immobilier locale</p></div>
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
            <?php foreach ($services as $service): ?><a class="service-card" href="<?= cms_h(cms_url((string) $service['href'])) ?>"><p class="card-kicker">Service</p><h3><?= cms_h((string) $service['title']) ?></h3><p><?= cms_h((string) $service['description']) ?></p><ul class="accent-list compact-list"><?php foreach (array_slice(($service['features'] ?? []), 0, 3) as $feature): ?><li><?= cms_h((string) $feature) ?></li><?php endforeach; ?></ul><span class="card-link">En savoir plus →</span></a><?php endforeach; ?>
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
            <div class="home-link-row"><a href="<?= cms_h(cms_url('/vendre')) ?>">Vendre</a><a href="<?= cms_h(cms_url('/acheter')) ?>">Acheter</a><a href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Estimation</a><a href="<?= cms_h(cms_url('/blog')) ?>">Conseils</a></div>
            <div class="cards-grid three-cols"><?php foreach ($featuredAreas as $index => $area): ?><article class="soft-card area-card<?= $index >= 3 ? ' mobile-hide' : '' ?>"><?php $areaImage = (string) ($snapshot['areaImages'][$area] ?? '/uploads/auxois.jpg'); ?><img src="<?= cms_h(cms_url($areaImage)) ?>" alt="<?= cms_h((string) $area) ?>" loading="lazy" decoding="async"><div><p class="card-kicker">Secteur</p><h3><?= cms_h((string) $area) ?></h3><p><?= cms_h((string) ($snapshot['areaDescriptions'][$area] ?? 'Un secteur suivi avec attention pour ses dynamiques de marché et ses projets de vie.')) ?></p></div></article><?php endforeach; ?></div>
            <div class="section-actions"><a class="button primary" href="<?= cms_h(cms_url('/secteur')) ?>">Voir tout notre secteur</a><span>Arnay-le-Duc, Pouilly-en-Auxois, Autun, Saulieu, Beaune, Dijon, Semur-en-Auxois et Vitteaux.</span></div>
          </div>
          <div>
            <p class="eyebrow">Conseils locaux</p>
            <h2 class="section-title">Nos conseils immobiliers par secteur</h2>
            <p class="section-subtitle">Des pages utiles pour retrouver des repères concrets, ville par ville, sans alourdir la lecture de la home.</p>
            <div class="cards-grid three-cols"><?php foreach ($localPages as $index => $localPage): ?><article class="soft-card local-card<?= $index >= 2 ? ' mobile-hide' : '' ?>"><?php $localImage = (string) ($localPage['image'] ?? '/uploads/auxois.jpg'); ?><img src="<?= cms_h(cms_url($localImage)) ?>" alt="<?= cms_h((string) $localPage['title']) ?>" loading="lazy" decoding="async"><div><p class="card-kicker"><?= cms_h(str_replace('-', ' ', (string) $localPage['pageType'])) ?></p><h3><?= cms_h((string) $localPage['title']) ?></h3><p class="card-city"><?= cms_h((string) $localPage['city']) ?></p><p class="clamp-2-mobile"><?= cms_h((string) $localPage['excerpt']) ?></p><a class="card-link-inline" href="<?= cms_h(cms_url((string) $localPage['href'])) ?>">Voir la page locale →</a></div></article><?php endforeach; ?></div>
            <div class="section-actions"><a class="button secondary" href="<?= cms_h(cms_url('/secteur')) ?>">Voir toutes les pages locales</a></div>
          </div>
        </div>
      </section>

      <section class="section section-tight"><div class="shell"><div class="panel-card iad-panel"><div><p class="eyebrow">Réseau</p><h2>La proximité locale, avec la puissance du réseau IAD</h2><div class="richtext panel-copy"><p>Vous bénéficiez d’un accompagnement de proximité, tout en profitant de la visibilité et des outils du réseau IAD. Un interlocuteur local, avec une diffusion solide et un suivi régulier jusqu’à la signature.</p></div></div><div class="iad-points-grid"><article class="tile-card"><strong>01</strong><span>Diffusion large des biens</span></article><article class="tile-card"><strong>02</strong><span>Suivi humain et régulier</span></article><article class="tile-card"><strong>03</strong><span>Accompagnement jusqu’à la signature</span></article></div></div></div></section>

      <section id="avis-clients" class="section section-tight"><div class="shell"><p class="eyebrow">Avis clients</p><h2 class="section-title">Des retours fondés sur la qualité du suivi</h2><p class="section-subtitle">Des échanges clairs, une présence régulière et une vraie lecture du terrain pour accompagner le projet jusqu’au bout.</p><div class="cards-grid three-cols"><?php foreach ($testimonials as $index => $testimonial): ?><article class="testimonial-card<?= $index >= 2 ? ' mobile-hide' : '' ?>"><div class="dots-row"><?php for ($starIndex = 0; $starIndex < (int) ($testimonial['rating'] ?? 5); $starIndex += 1): ?><span></span><?php endfor; ?></div><p class="testimonial-quote">“<?= cms_h((string) $testimonial['quote']) ?>”</p><div class="testimonial-meta"><strong><?= cms_h((string) $testimonial['author']) ?></strong><span><?= cms_h(implode(' — ', array_filter([(string) ($testimonial['title'] ?? ''), (string) ($testimonial['location'] ?? '')]))) ?></span></div></article><?php endforeach; ?></div></div></section>

      <section class="section section-tight"><div class="shell"><p class="eyebrow">Blog</p><h2 class="section-title">Derniers articles</h2><p class="section-subtitle">Des contenus utiles pour comprendre le marché local, préparer une vente et cadrer un projet immobilier.</p><div class="cards-grid three-cols"><?php foreach ($blogPosts as $index => $post): ?><article class="blog-card<?= $index >= 2 ? ' mobile-hide' : '' ?>"><a href="<?= cms_h(cms_url((string) $post['href'])) ?>"><img src="<?= cms_h(cms_url((string) $post['image'])) ?>" alt="<?= cms_h((string) ($post['imageAlt'] ?? $post['title'])) ?>" loading="lazy" decoding="async"><div class="blog-card-body"><div class="blog-meta"><span><?= cms_h((string) $post['category']) ?></span><span class="meta-dot"></span><span><?= cms_h(cms_format_long_date((string) $post['date'])) ?></span></div><h3><?= cms_h((string) $post['title']) ?></h3><p class="clamp-2-mobile"><?= cms_h((string) $post['excerpt']) ?></p><span class="card-link-inline">Lire l'article →</span></div></a></article><?php endforeach; ?></div><div class="section-actions"><a class="button secondary" href="<?= cms_h(cms_url('/blog')) ?>">Voir tous les articles</a></div></div></section>

      <section class="section section-tight"><div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">Projet immobilier</p><h2>Vous avez un projet immobilier ?</h2><div class="richtext"><p>Parlons simplement de votre bien, de votre secteur et de la meilleure stratégie à adopter.</p></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_standard_public_page(array $page, array $settings, array $snapshot): void
{
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
          <div class="home-hero-side"><div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><img src="<?= cms_h(cms_url($heroImage)) ?>" alt="<?= cms_h((string) $page['hero_image_alt']) ?>"><?php endif; ?></div></div>
        </div>
      </section>

      <section class="section section-tight"><div class="shell"><article class="panel-card intro-card richtext"><?= (string) $page['intro_html'] ?></article></div></section>

      <section class="section section-tight"><div class="shell standard-sections"><?php foreach ($sections as $section): ?><article class="panel-card standard-section-card"><div><?php if (!empty($section['eyebrow'])): ?><p class="eyebrow"><?= cms_h((string) $section['eyebrow']) ?></p><?php endif; ?><h2><?= cms_h((string) ($section['title'] ?? 'Section')) ?></h2><div class="richtext panel-copy"><?= (string) ($section['text'] ?? '') ?></div><?php if (!empty($section['items'])): ?><ul class="accent-list"><?php foreach ($section['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?></div><div><?php if (!empty($section['image'])): ?><img class="section-image" src="<?= cms_h(cms_url((string) $section['image'])) ?>" alt="<?= cms_h((string) ($section['imageAlt'] ?? '')) ?>"><?php endif; ?><?php if (!empty($section['stats'])): ?><div class="stats-tiles"><?php foreach ($section['stats'] as $stat): ?><div class="tile-card"><strong><?= cms_h((string) ($stat['value'] ?? '')) ?></strong><span><?= cms_h((string) ($stat['label'] ?? '')) ?></span></div><?php endforeach; ?></div><?php endif; ?></div></article><?php endforeach; ?></div></section>

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
    $description = 'Découvrez le parcours de Mickael Gury et Marion Roulier, conseillers immobiliers locaux en Auxois-Morvan, et leur méthode de travail.';
    $mickaelPhoto = trim((string) ($settings['mickael_photo'] ?? ''));
    $marionPhoto = trim((string) ($settings['marion_photo'] ?? ''));
    $heroImage = '/uploads/bligny.jpg';

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true);
    cms_render_public_header($settings, '/histoire');
    ?>
    <main>
      <section class="section section-hero section-hero-inner">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow">Notre histoire</p>
            <h1>Deux conseillers locaux pour vous accompagner</h1>
            <p class="hero-text">Mickael Gury et Marion Roulier, ancrés en Auxois-Morvan, vous accompagnent avec une approche humaine, claire et structurée, soutenue par le réseau IAD.</p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous rencontrer</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
            </div>
          </div>
          <div class="home-hero-side">
            <div class="hero-media"><img src="<?= cms_h(cms_url($heroImage)) ?>" alt="Auxois-Morvan"></div>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Présence locale</p>
          <h2 class="section-title">Vos conseillers</h2>
          <p class="section-subtitle">Une connaissance fine du terrain, une lecture honnête du marché local et un suivi régulier, du premier échange à la signature.</p>
          <div class="cards-grid two-cols">
            <article class="soft-card">
              <?php if ($mickaelPhoto !== ''): ?><img src="<?= cms_h(cms_url($mickaelPhoto)) ?>" alt="<?= cms_h((string) $settings['mickael_name']) ?>" loading="lazy" decoding="async"><?php endif; ?>
              <div>
                <p class="card-kicker">Conseiller</p>
                <h3><?= cms_h((string) $settings['mickael_name']) ?></h3>
                <p>Conseiller immobilier local, Mickael accompagne vendeurs et acheteurs en Auxois-Morvan avec rigueur, écoute et un sens du terrain.</p>
              </div>
            </article>
            <article class="soft-card">
              <?php if ($marionPhoto !== ''): ?><img src="<?= cms_h(cms_url($marionPhoto)) ?>" alt="<?= cms_h((string) $settings['marion_name']) ?>" loading="lazy" decoding="async"><?php endif; ?>
              <div>
                <p class="card-kicker">Conseillère</p>
                <h3><?= cms_h((string) $settings['marion_name']) ?></h3>
                <p>Conseillère immobilier locale, Marion porte une attention particulière à la qualité de l’accompagnement, du cadrage initial à l’acte authentique.</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="panel-card panel-muted">
            <p class="eyebrow">Notre méthode</p>
            <h2>Une approche locale, humaine et structurée</h2>
            <p class="panel-copy">Nous croyons à une immobilier de proximité : être disponibles, transparents et précis. Pour chaque projet, nous prenons le temps de comprendre les enjeux, d’analyser le marché local et de définir une stratégie claire.</p>
            <ul class="accent-list">
              <li>Une lecture honnête du marché et des prix</li>
              <li>Un plan d’action sur-mesure pour chaque bien</li>
              <li>Un suivi régulier, sans rupture de communication</li>
              <li>La force du réseau IAD pour une diffusion large</li>
            </ul>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="cta-band cta-band-hero">
            <div>
              <p class="eyebrow">Faisons connaissance</p>
              <h2>Parlons de votre projet</h2>
              <div class="richtext"><p>Un échange simple et sans engagement pour comprendre votre situation et identifier ensemble la meilleure démarche.</p></div>
            </div>
            <div class="cta-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Estimer un bien</a>
            </div>
          </div>
        </div>
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

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true);
    cms_render_public_header($settings, '/avis');
    ?>
    <main>
      <section class="section section-hero section-hero-inner">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow">Ils nous ont fait confiance</p>
            <h1>Avis clients</h1>
            <p class="hero-text">Des retours fondés sur la qualité du suivi, la clarté des échanges et la réussite du projet immobilier.</p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
          <div class="home-hero-side">
            <div class="hero-media"><img src="<?= cms_h(cms_url('/uploads/pouilly.jpg')) ?>" alt="Auxois-Morvan"></div>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Témoignages</p>
          <h2 class="section-title">Ce qu'ils disent de notre accompagnement</h2>
          <div class="cards-grid three-cols">
            <?php foreach ($testimonials as $testimonial): ?>
              <article class="testimonial-card">
                <div class="dots-row"><?php for ($i = 0; $i < (int) ($testimonial['rating'] ?? 5); $i += 1): ?><span></span><?php endfor; ?></div>
                <p class="testimonial-quote">“<?= cms_h((string) $testimonial['quote']) ?>”</p>
                <div class="testimonial-meta">
                  <strong><?= cms_h((string) $testimonial['author']) ?></strong>
                  <span><?= cms_h(implode(' — ', array_filter([(string) ($testimonial['title'] ?? ''), (string) ($testimonial['location'] ?? '')]))) ?></span>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="cta-band cta-band-hero">
            <div>
              <p class="eyebrow">À votre tour</p>
              <h2>Et si on parlait de votre projet ?</h2>
              <div class="richtext"><p>Vendre, acheter, estimer : prenons le temps d'un échange simple pour identifier la meilleure stratégie.</p></div>
            </div>
            <div class="cta-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
        </div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_render_prestations_page(array $settings): void
{
    $snapshot = cms_snapshot();
    $services = $snapshot['services'] ?? [];
    $title = 'Nos prestations';
    $description = 'Vendre, acheter, estimer un bien ou transmettre un fonds de commerce en Auxois-Morvan : découvrez nos prestations immobilières.';

    cms_render_public_document_start($title . ' | ' . (string) $settings['site_name'], $description, true);
    cms_render_public_header($settings, '/prestations');
    ?>
    <main>
      <section class="section section-hero section-hero-inner">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow">Nos services</p>
            <h1>Nos prestations</h1>
            <p class="hero-text">Une présence utile pour vendre, acheter, estimer un bien ou transmettre un fonds de commerce, avec une méthode claire et un suivi humain.</p>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
          <div class="home-hero-side">
            <div class="hero-media"><img src="<?= cms_h(cms_url('/uploads/arnay.jpg')) ?>" alt="Auxois-Morvan"></div>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Services</p>
          <h2 class="section-title">Ce que nous faisons pour vous</h2>
          <p class="section-subtitle">Quatre domaines d'expertise pour répondre concrètement à votre projet immobilier ou commercial.</p>
          <div class="services-grid">
            <?php foreach ($services as $service): ?>
              <a class="service-card" href="<?= cms_h(cms_url((string) $service['href'])) ?>">
                <p class="card-kicker">Service</p>
                <h3><?= cms_h((string) $service['title']) ?></h3>
                <p><?= cms_h((string) $service['description']) ?></p>
                <ul class="accent-list compact-list">
                  <?php foreach (array_slice(($service['features'] ?? []), 0, 4) as $feature): ?>
                    <li><?= cms_h((string) $feature) ?></li>
                  <?php endforeach; ?>
                </ul>
                <span class="card-link">En savoir plus →</span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="panel-card iad-panel">
            <div>
              <p class="eyebrow">Réseau</p>
              <h2>La proximité locale, avec la puissance du réseau IAD</h2>
              <div class="richtext panel-copy"><p>Vous bénéficiez d'un accompagnement de proximité, tout en profitant de la visibilité et des outils du réseau IAD. Un interlocuteur local, avec une diffusion solide et un suivi régulier jusqu'à la signature.</p></div>
            </div>
            <div class="iad-points-grid">
              <article class="tile-card"><strong>01</strong><span>Diffusion large des biens</span></article>
              <article class="tile-card"><strong>02</strong><span>Suivi humain et régulier</span></article>
              <article class="tile-card"><strong>03</strong><span>Accompagnement jusqu'à la signature</span></article>
            </div>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell">
          <div class="cta-band cta-band-hero">
            <div>
              <p class="eyebrow">Échangeons</p>
              <h2>Un projet immobilier en tête ?</h2>
              <div class="richtext"><p>Parlons-en simplement. Nous vous orienterons vers la prestation la plus adaptée à votre situation.</p></div>
            </div>
            <div class="cta-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation-en-ligne')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
        </div>
      </section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

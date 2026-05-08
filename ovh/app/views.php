<?php

declare(strict_types=1);

function cms_render_admin_start(string $title, string $currentNav): void
{
    $admin = cms_current_admin();
    $flash = cms_consume_flash();
    $navigation = [
        '/admin' => 'Tableau de bord',
        '/admin/pages' => 'Pages principales',
        '/admin/local-pages' => 'Pages locales',
        '/admin/media' => 'Images',
      '/admin/settings' => 'Réglages',
        '/admin/users' => 'Utilisateurs',
    ];
    ?>
    <!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title><?= cms_h($title) ?></title>
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/admin.css')) ?>">
      </head>
      <body class="admin-shell">
        <aside class="admin-sidebar">
          <a class="admin-brand" href="<?= cms_h(cms_url('/admin')) ?>">CMS Maison</a>
          <nav class="admin-nav">
            <?php foreach ($navigation as $href => $label): ?>
              <a class="<?= $currentNav === $href ? 'is-active' : '' ?>" href="<?= cms_h(cms_url($href)) ?>"><?= cms_h($label) ?></a>
            <?php endforeach; ?>
          </nav>
          <div class="admin-sidebar-footer">
            <p><?= cms_h((string) ($admin['name'] ?? '')) ?></p>
            <a href="<?= cms_h(cms_url('/admin/logout')) ?>">Se déconnecter</a>
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

    <?php cms_render_media_picker_modal($mediaItems); ?>

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
      const mediaPickerModal = document.getElementById('media-library-modal');
      const mediaPickerSearch = document.getElementById('media-library-search');
      const mediaPickerGrid = document.getElementById('media-library-grid');
      let activeMediaField = null;

      const syncMediaPreview = (field) => {
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
            syncMediaPreview(field);
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
            syncMediaPreview(activeMediaField);
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
        node.querySelectorAll('.media-picker-field').forEach((field) => syncMediaPreview(field));
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

      document.querySelectorAll('.media-picker-field').forEach((field) => syncMediaPreview(field));
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
      <section class="section section-tight"><div class="shell"><div class="cta-band"><div><p class="eyebrow">Passer à l’action</p><h2><?= cms_h((string) $page['cta_title']) ?></h2><div class="richtext"><?= (string) $page['cta_text'] ?></div></div><a class="button primary" href="<?= cms_h(cms_url('/estimation')) ?>">Demander une estimation</a></div></div></section>
    </main>
    <?php
    cms_render_public_footer($settings, $snapshot);
}

function cms_public_nav_items(): array
{
    return [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Histoire', 'href' => '/#histoire'],
        ['label' => 'Secteur', 'href' => '/secteur'],
        ['label' => 'Prestations', 'href' => '/#prestations'],
        ['label' => 'Avis clients', 'href' => '/#avis-clients'],
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

function cms_render_public_document_start(string $title, string $description, bool $indexable = true): void
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
        <title><?= cms_h($title) ?></title>
        <link rel="icon" type="image/svg+xml" href="<?= cms_h(cms_url('/favicon.svg')) ?>">
        <link rel="icon" href="<?= cms_h(cms_url('/favicon.ico')) ?>">
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>">
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
            <img src="<?= cms_h(cms_url('/uploads/cropped-logo.png')) ?>" alt="Immobilier Auxois Morvan" class="site-logo">
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

function cms_render_public_footer(array $settings, array $snapshot): void
{
    $areas = array_slice($snapshot['siteSettings']['coveredAreas'] ?? cms_json_list($settings['covered_areas_json'] ?? '[]'), 0, 6);
    $services = $snapshot['services'] ?? [];
    ?>
    <footer class="site-footer">
      <div class="shell footer-shell">
        <div class="footer-top">
          <div class="footer-brand-column">
            <img src="<?= cms_h(cms_url('/uploads/cropped-logo.png')) ?>" alt="Immobilier Auxois Morvan" class="footer-logo">
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
              <a href="<?= cms_h(cms_url('/#histoire')) ?>">Histoire</a>
              <a href="<?= cms_h(cms_url('/secteur')) ?>">Secteur</a>
              <a href="<?= cms_h(cms_url('/#avis-clients')) ?>">Avis clients</a>
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
    $sections = cms_visible_sections($page);
    $reassurance = $sections[0] ?? null;
    $whyUs = $sections[1] ?? null;
    $iadSupport = $sections[2] ?? null;
    $heroImage = trim((string) ($page['hero_image'] ?? ''));
    $areas = array_slice($snapshot['siteSettings']['coveredAreas'] ?? cms_json_list($settings['covered_areas_json'] ?? '[]'), 0, 6);
    $services = $snapshot['services'] ?? [];
    $localPages = array_slice($snapshot['localPages'] ?? [], 0, 3);
    $blogPosts = array_slice($snapshot['blogPosts'] ?? [], 0, 3);
    $testimonials = $snapshot['testimonials'] ?? [];

    cms_render_public_document_start((string) $page['title'] . ' | ' . (string) $settings['site_name'], (string) ($page['meta_description'] ?? $settings['baseline']), (int) ($page['is_indexable'] ?? 1) === 1);
    cms_render_public_header($settings, '/');
    ?>
    <main>
      <section class="section section-hero">
        <div class="shell home-hero-grid">
          <div class="home-hero-copy">
            <p class="eyebrow">Conseillers immobiliers locaux</p>
            <h1><?= cms_h((string) $page['hero_title']) ?></h1>
            <div class="hero-text richtext"><?= (string) $page['hero_subtitle'] ?></div>
            <div class="hero-actions">
              <a class="button primary" href="<?= cms_h(cms_url('/estimation')) ?>">Faire estimer mon bien</a>
              <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
            </div>
          </div>
          <div class="home-hero-side">
            <div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>"><?php if ($heroImage !== ''): ?><img src="<?= cms_h(cms_url($heroImage)) ?>" alt="<?= cms_h((string) $page['hero_image_alt']) ?>"><?php endif; ?></div>
            <div class="home-stats-grid">
              <div class="home-stat-card"><p><?= cms_h((string) $settings['main_city']) ?></p><span>Ville repère</span></div>
              <div class="home-stat-card"><p>Auxois &amp; Morvan</p><span>Secteur couvert</span></div>
              <div class="home-stat-card"><p>IAD France</p><span>Réseau national</span></div>
              <div class="home-stat-card"><p>Suivi humain</p><span>Méthode de travail</span></div>
            </div>
          </div>
        </div>
      </section>

      <section id="histoire" class="section section-tight">
        <div class="shell duo-grid">
          <article class="panel-card">
            <p class="eyebrow">Immobilier en Auxois et Morvan</p>
            <h2><?= cms_h((string) ($reassurance['title'] ?? 'Un accompagnement cadré, clair et humain')) ?></h2>
            <div class="richtext panel-copy"><?= (string) ($reassurance['text'] ?? $page['intro_html']) ?></div>
            <?php if (!empty($reassurance['items'])): ?><ul class="accent-list"><?php foreach ($reassurance['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?>
          </article>
          <article class="panel-card panel-muted">
            <p class="eyebrow">Qui sommes-nous ?</p>
            <h2><?= cms_h((string) $settings['mickael_name']) ?> &amp; <?= cms_h((string) $settings['marion_name']) ?></h2>
            <p class="panel-copy"><?= cms_h((string) $settings['baseline']) ?></p>
            <div class="mini-grid"><?php foreach (array_slice($areas, 0, 4) as $area): ?><div><?= cms_h((string) $area) ?></div><?php endforeach; ?></div>
          </article>
        </div>
      </section>

      <section id="prestations" class="section section-tight">
        <div class="shell duo-grid duo-grid-small">
          <?php if ($whyUs): ?><article class="panel-card feature-card"><h3><?= cms_h((string) $whyUs['title']) ?></h3><div class="richtext panel-copy"><?= (string) $whyUs['text'] ?></div><?php if (!empty($whyUs['items'])): ?><ul class="accent-list"><?php foreach ($whyUs['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?></article><?php endif; ?>
          <article class="panel-card feature-card"><h3>Une méthode posée, lisible et défendue sur le terrain</h3><div class="richtext panel-copy"><?= (string) $page['intro_html'] ?></div><?php if (!empty($reassurance['items'])): ?><ul class="accent-list"><?php foreach ($reassurance['items'] as $item): ?><li><?= cms_h((string) $item) ?></li><?php endforeach; ?></ul><?php endif; ?></article>
        </div>
      </section>

      <section id="avis-clients" class="section section-tight">
        <div class="shell">
          <p class="eyebrow">Services</p>
          <h2 class="section-title">Nos services</h2>
          <p class="section-subtitle">Une présence utile pour vendre, acheter, estimer un bien ou préparer une transmission de commerce.</p>
          <div class="services-grid">
            <?php foreach ($services as $service): ?><a class="service-card" href="<?= cms_h(cms_url((string) $service['href'])) ?>"><p class="card-kicker">Service</p><h3><?= cms_h((string) $service['title']) ?></h3><p><?= cms_h((string) $service['description']) ?></p><ul class="accent-list compact-list"><?php foreach (($service['features'] ?? []) as $feature): ?><li><?= cms_h((string) $feature) ?></li><?php endforeach; ?></ul><span class="card-link">En savoir plus →</span></a><?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section-tight">
        <div class="shell stack-lg">
          <div>
            <p class="eyebrow">Secteur local</p>
            <h2 class="section-title">Notre secteur local</h2>
            <p class="section-subtitle">Des repères concrets sur les villes et bassins de vie où nous accompagnons régulièrement des projets immobiliers.</p>
            <div class="cards-grid three-cols"><?php foreach ($areas as $area): ?><article class="soft-card area-card"><img src="<?= cms_h(cms_url((string) ($snapshot['areaImages'][$area] ?? '/uploads/auxois.jpg'))) ?>" alt="<?= cms_h((string) $area) ?>"><div><p class="card-kicker">Secteur</p><h3><?= cms_h((string) $area) ?></h3><p><?= cms_h((string) ($snapshot['areaDescriptions'][$area] ?? 'Un secteur suivi avec attention pour ses dynamiques de marché et ses projets de vie.')) ?></p></div></article><?php endforeach; ?></div>
          </div>
          <div>
            <p class="eyebrow">SEO local</p>
            <h2 class="section-title">Quelques pages locales déjà prêtes</h2>
            <p class="section-subtitle">Le site peut accueillir facilement de nouvelles pages ciblées par ville ou intention de recherche.</p>
            <div class="cards-grid three-cols"><?php foreach ($localPages as $localPage): ?><article class="soft-card local-card"><img src="<?= cms_h(cms_url((string) $localPage['image'])) ?>" alt="<?= cms_h((string) $localPage['title']) ?>"><div><p class="card-kicker"><?= cms_h(str_replace('-', ' ', (string) $localPage['pageType'])) ?></p><h3><?= cms_h((string) $localPage['title']) ?></h3><p class="card-city"><?= cms_h((string) $localPage['city']) ?></p><p><?= cms_h((string) $localPage['excerpt']) ?></p><a class="card-link-inline" href="<?= cms_h(cms_url((string) $localPage['href'])) ?>">Voir la page locale →</a></div></article><?php endforeach; ?></div>
          </div>
        </div>
      </section>

      <?php if ($iadSupport): ?><section class="section section-tight"><div class="shell"><div class="panel-card iad-panel"><div><p class="eyebrow"><?= cms_h((string) ($iadSupport['eyebrow'] ?? 'Réseau')) ?></p><h2><?= cms_h((string) $iadSupport['title']) ?></h2><div class="richtext panel-copy"><?= (string) $iadSupport['text'] ?></div></div><div class="stats-tiles"><?php foreach (($iadSupport['stats'] ?? []) as $stat): ?><div class="tile-card"><strong><?= cms_h((string) ($stat['value'] ?? '')) ?></strong><span><?= cms_h((string) ($stat['label'] ?? '')) ?></span></div><?php endforeach; ?></div></div></div></section><?php endif; ?>

      <section class="section section-tight"><div class="shell"><p class="eyebrow">Avis clients</p><h2 class="section-title">Des retours fondés sur la qualité du suivi</h2><p class="section-subtitle">Des échanges clairs, une présence régulière et une vraie lecture du terrain pour accompagner le projet jusqu’au bout.</p><div class="cards-grid three-cols"><?php foreach ($testimonials as $testimonial): ?><article class="testimonial-card"><div class="dots-row"><?php for ($index = 0; $index < (int) ($testimonial['rating'] ?? 5); $index += 1): ?><span></span><?php endfor; ?></div><p class="testimonial-quote">“<?= cms_h((string) $testimonial['quote']) ?>”</p><div class="testimonial-meta"><strong><?= cms_h((string) $testimonial['author']) ?></strong><span><?= cms_h(implode(' — ', array_filter([(string) ($testimonial['title'] ?? ''), (string) ($testimonial['location'] ?? '')]))) ?></span></div></article><?php endforeach; ?></div></div></section>

      <section class="section section-tight"><div class="shell"><p class="eyebrow">Blog</p><h2 class="section-title">Derniers articles</h2><p class="section-subtitle">Des contenus utiles pour comprendre le marché local, préparer une vente et cadrer un projet immobilier.</p><div class="cards-grid three-cols"><?php foreach ($blogPosts as $post): ?><article class="blog-card"><a href="<?= cms_h(cms_url((string) $post['href'])) ?>"><img src="<?= cms_h(cms_url((string) $post['image'])) ?>" alt="<?= cms_h((string) ($post['imageAlt'] ?? $post['title'])) ?>"><div class="blog-card-body"><div class="blog-meta"><span><?= cms_h((string) $post['category']) ?></span><span class="meta-dot"></span><span><?= cms_h(cms_format_long_date((string) $post['date'])) ?></span></div><h3><?= cms_h((string) $post['title']) ?></h3><p><?= cms_h((string) $post['excerpt']) ?></p><span class="card-link-inline">Lire l'article →</span></div></a></article><?php endforeach; ?></div></div></section>

      <section class="section section-tight"><div class="shell"><div class="cta-band cta-band-hero"><div><p class="eyebrow">Parlez de votre projet avec un conseiller local</p><h2><?= cms_h((string) $page['cta_title']) ?></h2><div class="richtext"><?= (string) $page['cta_text'] ?></div></div><div class="cta-actions"><a class="button primary" href="<?= cms_h(cms_url((string) $page['cta_button_url'])) ?>"><?= cms_h((string) $page['cta_button_label']) ?></a><a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a></div></div></div></section>
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
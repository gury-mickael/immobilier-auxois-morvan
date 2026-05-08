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

function cms_render_page_form(array $page, string $mode, string $actionLabel): void
{
    $sections = cms_page_sections($page);
    $advantages = implode("\n", cms_json_list($page['local_advantages_json'] ?? '[]'));
    $nearbyCities = implode("\n", cms_json_list($page['nearby_cities_json'] ?? '[]'));
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
          <label>
            Image hero
            <input name="hero_image" value="<?= cms_h((string) $page['hero_image']) ?>" placeholder="/uploads/cms/nom-image.webp">
          </label>
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
        <label>
          Image
          <input name="section_image[]" value="<?= cms_h((string) ($section['image'] ?? '')) ?>">
        </label>
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
    $sections = cms_page_sections($page);
    $navigation = cms_public_navigation();
    $title = $page['title'] ?: $settings['site_name'];
    $description = $page['meta_description'] ?: $settings['baseline'];
    $heroImage = trim((string) ($page['hero_image'] ?? ''));
    ?>
    <!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= cms_h($description) ?>">
        <?php if ((int) ($page['is_indexable'] ?? 1) !== 1): ?>
          <meta name="robots" content="noindex,nofollow">
        <?php endif; ?>
        <title><?= cms_h($title) ?> | <?= cms_h((string) $settings['site_name']) ?></title>
        <link rel="icon" type="image/svg+xml" href="<?= cms_h(cms_url('/favicon.svg')) ?>">
        <link rel="icon" href="<?= cms_h(cms_url('/favicon.ico')) ?>">
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>">
      </head>
      <body>
        <header class="site-header">
          <div class="shell site-header-inner">
            <a class="site-brand" href="<?= cms_h(cms_url('/')) ?>"><?= cms_h((string) $settings['site_name']) ?></a>
            <nav class="site-nav">
              <?php foreach ($navigation as $item): ?>
                <a href="<?= cms_h(cms_url((string) $item['href'])) ?>"><?= cms_h((string) $item['label']) ?></a>
              <?php endforeach; ?>
            </nav>
            <a class="site-cta" href="<?= cms_h(cms_url((string) $settings['main_cta_url'])) ?>"><?= cms_h((string) $settings['main_cta_label']) ?></a>
          </div>
        </header>

        <main>
          <section class="hero">
            <div class="shell hero-grid">
              <div>
                <p class="eyebrow">Immobilier Auxois Morvan</p>
                <h1><?= cms_h((string) $page['hero_title']) ?></h1>
                <p class="hero-text"><?= nl2br(cms_h((string) $page['hero_subtitle'])) ?></p>
                <div class="hero-actions">
                  <a class="button primary" href="<?= cms_h(cms_url((string) $page['cta_button_url'])) ?>"><?= cms_h((string) $page['cta_button_label']) ?></a>
                  <a class="button secondary" href="<?= cms_h(cms_url('/contact')) ?>">Nous contacter</a>
                </div>
              </div>
              <div class="hero-media<?= $heroImage !== '' ? '' : ' no-image' ?>">
                <?php if ($heroImage !== ''): ?>
                  <img src="<?= cms_h(cms_url($heroImage)) ?>" alt="<?= cms_h((string) $page['hero_image_alt']) ?>">
                <?php else: ?>
                  <div class="hero-placeholder">Ajoutez une image hero depuis l’admin.</div>
                <?php endif; ?>
              </div>
            </div>
          </section>

          <section class="section">
            <div class="shell">
              <article class="intro-card richtext"><?= (string) $page['intro_html'] ?></article>
            </div>
          </section>

          <section class="section">
            <div class="shell content-stack">
              <?php foreach ($sections as $section): ?>
                <article class="content-card">
                  <div>
                    <?php if (!empty($section['eyebrow'])): ?>
                      <p class="eyebrow"><?= cms_h((string) $section['eyebrow']) ?></p>
                    <?php endif; ?>
                    <h2><?= cms_h((string) ($section['title'] ?? 'Section')) ?></h2>
                    <div class="richtext"><?= (string) ($section['text'] ?? '') ?></div>
                    <?php if (!empty($section['items'])): ?>
                      <ul class="bullet-list">
                        <?php foreach ($section['items'] as $item): ?>
                          <li><?= cms_h((string) $item) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                    <?php if (!empty($section['buttonLabel']) && !empty($section['buttonUrl'])): ?>
                      <a class="button tertiary" href="<?= cms_h(cms_url((string) $section['buttonUrl'])) ?>"><?= cms_h((string) $section['buttonLabel']) ?></a>
                    <?php endif; ?>
                  </div>
                  <div>
                    <?php if (!empty($section['image'])): ?>
                      <img class="section-image" src="<?= cms_h(cms_url((string) $section['image'])) ?>" alt="<?= cms_h((string) ($section['imageAlt'] ?? '')) ?>">
                    <?php endif; ?>
                    <?php if (!empty($section['stats'])): ?>
                      <div class="stats-grid">
                        <?php foreach ($section['stats'] as $stat): ?>
                          <div class="stat-card">
                            <strong><?= cms_h((string) ($stat['value'] ?? '')) ?></strong>
                            <span><?= cms_h((string) ($stat['label'] ?? '')) ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <?php if (($page['page_type'] ?? 'main') === 'local'): ?>
            <?php $advantages = cms_json_list($page['local_advantages_json'] ?? '[]'); ?>
            <?php $nearbyCities = cms_json_list($page['nearby_cities_json'] ?? '[]'); ?>
            <section class="section">
              <div class="shell twin-panels">
                <article class="content-card compact-card">
                  <h2>Atouts locaux</h2>
                  <ul class="bullet-list">
                    <?php foreach ($advantages as $item): ?>
                      <li><?= cms_h($item) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </article>
                <article class="content-card compact-card">
                  <h2>Villes proches</h2>
                  <div class="tag-list">
                    <?php foreach ($nearbyCities as $city): ?>
                      <span><?= cms_h($city) ?></span>
                    <?php endforeach; ?>
                  </div>
                </article>
              </div>
            </section>
          <?php endif; ?>

          <section class="section">
            <div class="shell cta-band">
              <div>
                <p class="eyebrow">Passer à l’action</p>
                <h2><?= cms_h((string) $page['cta_title']) ?></h2>
                <div class="richtext"><?= (string) $page['cta_text'] ?></div>
              </div>
              <a class="button primary" href="<?= cms_h(cms_url((string) $page['cta_button_url'])) ?>"><?= cms_h((string) $page['cta_button_label']) ?></a>
            </div>
          </section>
        </main>

        <footer class="site-footer">
          <div class="shell footer-grid">
            <div>
              <strong><?= cms_h((string) $settings['site_name']) ?></strong>
              <p><?= cms_h((string) $settings['footer_text']) ?></p>
            </div>
            <div>
              <p><?= cms_h((string) $settings['mickael_name']) ?> & <?= cms_h((string) $settings['marion_name']) ?></p>
              <p><?= cms_h((string) $settings['phone']) ?></p>
              <p><?= cms_h((string) $settings['email']) ?></p>
            </div>
          </div>
        </footer>
      </body>
    </html>
    <?php
}
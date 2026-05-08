<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$errors = [];

function cms_render_media_card(array $item): void
{
    $previewUrl = cms_media_public_url($item);
    $displayUrl = preg_match('#^https?://#i', $previewUrl) === 1 ? $previewUrl : cms_url($previewUrl);
    $copyUrl = preg_match('#^https?://#i', $previewUrl) === 1 ? $previewUrl : $previewUrl;
    $title = (string) ($item['title'] ?: $item['original_name']);
    $altText = trim((string) ($item['alt_text'] ?? ''));
    $isAvailable = cms_media_is_available($item);
    $searchBlob = strtolower(trim($title . ' ' . $altText . ' ' . $previewUrl));
    ?>
    <article class="media-card<?= $isAvailable ? '' : ' is-missing' ?>" data-media-search="<?= cms_h($searchBlob) ?>">
      <div class="media-preview<?= $isAvailable ? '' : ' is-missing' ?>">
        <?php if ($isAvailable): ?>
          <img src="<?= cms_h($displayUrl) ?>" alt="<?= cms_h($altText !== '' ? $altText : $title) ?>" loading="lazy" decoding="async">
        <?php else: ?>
          <div class="media-card-fallback">
            <strong>Fichier introuvable</strong>
            <span>Le chemin enregistré ne correspond à aucun média présent sur le serveur.</span>
          </div>
        <?php endif; ?>
      </div>
      <div class="media-meta">
        <div class="media-meta-head">
          <strong><?= cms_h($title) ?></strong>
          <span class="media-status<?= $isAvailable ? ' is-ok' : ' is-error' ?>"><?= $isAvailable ? 'Disponible' : 'À corriger' ?></span>
        </div>
        <?php if ($altText !== ''): ?>
          <p class="media-alt-text"><?= cms_h($altText) ?></p>
        <?php endif; ?>
        <div class="media-copy-row">
          <input readonly value="<?= cms_h($copyUrl) ?>" onclick="this.select()">
          <button type="button" class="ghost-button media-copy-button" data-copy="<?= cms_h($copyUrl) ?>">Copier</button>
        </div>
      </div>
    </article>
    <?php
}

$isAjax = isset($_GET['ajax']) || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();

    try {
        $uploadedUrl = cms_store_uploaded_media($_FILES['image'] ?? [], (string) ($_POST['title'] ?? ''), (string) ($_POST['alt_text'] ?? ''));

        if ($isAjax) {
            $item = [
                'public_url' => $uploadedUrl,
                'file_name' => basename($uploadedUrl),
                'title' => (string) ($_POST['title'] ?? ''),
                'original_name' => (string) (($_FILES['image']['name'] ?? basename($uploadedUrl))),
                'alt_text' => (string) ($_POST['alt_text'] ?? ''),
            ];

            ob_start();
            cms_render_media_card($item);
            $cardHtml = (string) ob_get_clean();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'message' => 'Image téléversée.',
                'itemHtml' => $cardHtml,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        cms_flash('success', 'Image téléversée.');
        cms_redirect('/admin/media');
    } catch (Throwable $exception) {
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $errors[] = $exception->getMessage();
    }
}

$mediaItems = cms_media_items();

cms_render_admin_start('Images', '/admin/media');

$missingCount = 0;
foreach ($mediaItems as $m) { if (!cms_media_is_available($m)) { $missingCount++; } }
$availableCount = count($mediaItems) - $missingCount;
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<section class="dashboard-hero">
  <div class="dashboard-hero-inner">
    <div>
      <p class="eyebrow">Médiathèque</p>
      <h1>Vos visuels en un seul endroit</h1>
      <p>Téléversez, organisez et copiez les liens de vos images. Glissez-déposez vos fichiers JPG, PNG ou WebP en direct.</p>
    </div>
    <div class="dashboard-hero-actions">
      <a class="secondary-button" href="#upload-dropzone">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Téléverser
      </a>
    </div>
  </div>
</section>

<section class="kpi-grid">
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">Médias enregistrés</span>
      <span class="kpi-card-icon is-emerald">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= count($mediaItems) ?></div>
    <div class="kpi-card-foot"><span class="kpi-trend is-neutral">Bibliothèque complète</span></div>
  </article>
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">Disponibles</span>
      <span class="kpi-card-icon is-blue">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= $availableCount ?></div>
    <div class="kpi-card-foot"><span class="kpi-trend is-up">Servis sur le site</span></div>
  </article>
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">À corriger</span>
      <span class="kpi-card-icon is-rose">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= $missingCount ?></div>
    <div class="kpi-card-foot"><span class="kpi-trend <?= $missingCount > 0 ? 'is-down' : 'is-neutral' ?>">Fichiers manquants</span></div>
  </article>
</section>

<section class="upload-pro-card" id="upload-dropzone-section">
  <div id="media-feedback" class="flash" hidden></div>

  <form id="media-upload-form" method="post" enctype="multipart/form-data" class="media-upload-layout">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">

    <label class="upload-dropzone-pro" id="upload-dropzone" for="media-file-input">
      <span class="upload-dropzone-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      </span>
      <strong>Glissez votre image ici</strong>
      <span>ou cliquez pour parcourir vos fichiers</span>
      <div class="upload-formats">
        <span>JPG</span><span>PNG</span><span>WEBP</span>
      </div>
      <span id="upload-file-name" class="upload-file-name">Aucun fichier sélectionné</span>
      <input id="media-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
    </label>

    <div class="media-upload-fields">
      <label>
        Titre interne
        <input name="title" placeholder="Ex. façade maison Arnay-le-Duc">
      </label>
      <label>
        Texte alternatif
        <input name="alt_text" placeholder="Décrire précisément l’image pour l’accessibilité">
      </label>

      <div id="upload-progress-card" class="upload-progress-card" hidden>
        <div class="upload-progress-head">
          <strong>Téléversement en cours</strong>
          <span id="upload-progress-value">0%</span>
        </div>
        <div class="upload-progress-track">
          <div id="upload-progress-bar" class="upload-progress-bar"></div>
        </div>
      </div>

      <div class="media-upload-actions">
        <button id="media-submit-button" class="primary-button" type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Téléverser
        </button>
      </div>
    </div>
  </form>
</section>

<section class="panel">
  <div class="media-toolbar">
    <div>
      <h2 style="margin:0">Bibliothèque</h2>
      <p class="lead" style="margin:0.2rem 0 0"><?= count($mediaItems) ?> fichier<?= count($mediaItems) > 1 ? 's' : '' ?> · cliquez sur « Copier » pour récupérer le lien public.</p>
    </div>
    <label class="media-search-input">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" id="media-filter-input" placeholder="Rechercher dans la bibliothèque…">
    </label>
  </div>

  <div id="media-grid" class="media-grid-pro">
    <?php foreach ($mediaItems as $item): ?>
      <?php cms_render_media_card($item); ?>
    <?php endforeach; ?>
  </div>
</section>

<script>
  const form = document.getElementById('media-upload-form');
  const fileInput = document.getElementById('media-file-input');
  const fileName = document.getElementById('upload-file-name');
  const feedback = document.getElementById('media-feedback');
  const dropzone = document.getElementById('upload-dropzone');
  const progressCard = document.getElementById('upload-progress-card');
  const progressBar = document.getElementById('upload-progress-bar');
  const progressValue = document.getElementById('upload-progress-value');
  const submitButton = document.getElementById('media-submit-button');
  const mediaGrid = document.getElementById('media-grid');

  const showFeedback = (type, message) => {
    feedback.hidden = false;
    feedback.className = `flash flash-${type}`;
    feedback.textContent = message;
  };

  const updateFileName = () => {
    const file = fileInput.files?.[0];
    fileName.textContent = file ? `${file.name} · ${Math.round(file.size / 1024)} Ko` : 'Aucun fichier sélectionné';
  };

  const setProgress = (value) => {
    progressCard.hidden = false;
    progressBar.style.width = `${value}%`;
    progressValue.textContent = `${value}%`;
  };

  const bindCopyButtons = (scope = document) => {
    scope.querySelectorAll('[data-copy]').forEach((button) => {
      if (button.dataset.ready === '1') {
        return;
      }

      button.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(button.dataset.copy || '');
          button.textContent = 'Copié';
          window.setTimeout(() => {
            button.textContent = 'Copier';
          }, 1400);
        } catch {
          showFeedback('error', 'Impossible de copier ce lien automatiquement.');
        }
      });

      button.dataset.ready = '1';
    });
  };

  bindCopyButtons();
  updateFileName();

  // Filtre de recherche bibliothèque
  const filterInput = document.getElementById('media-filter-input');
  if (filterInput) {
    filterInput.addEventListener('input', () => {
      const needle = filterInput.value.trim().toLowerCase();
      mediaGrid.querySelectorAll('.media-card').forEach((card) => {
        const blob = card.getAttribute('data-media-search') || '';
        card.style.display = needle === '' || blob.includes(needle) ? '' : 'none';
      });
    });
  }

  fileInput.addEventListener('change', updateFileName);

  ['dragenter', 'dragover'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove('is-dragover');
    });
  });

  dropzone.addEventListener('drop', (event) => {
    const file = event.dataTransfer?.files?.[0];
    if (!file) {
      return;
    }

    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    fileInput.files = dataTransfer.files;
    updateFileName();
  });

  form.addEventListener('submit', (event) => {
    if (!window.XMLHttpRequest || !window.FormData) {
      return;
    }

    event.preventDefault();
    feedback.hidden = true;

    if (!fileInput.files?.length) {
      showFeedback('error', 'Choisissez une image avant de téléverser.');
      return;
    }

    const xhr = new XMLHttpRequest();
    const formData = new FormData(form);

    submitButton.disabled = true;
    submitButton.textContent = 'Téléversement...';
    setProgress(0);

    xhr.open('POST', '<?= cms_h(cms_url('/admin/media?ajax=1')) ?>', true);
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.addEventListener('progress', (progressEvent) => {
      if (!progressEvent.lengthComputable) {
        return;
      }

      setProgress(Math.round((progressEvent.loaded / progressEvent.total) * 100));
    });

    xhr.addEventListener('load', () => {
      submitButton.disabled = false;
      submitButton.textContent = 'Téléverser';

      try {
        const response = JSON.parse(xhr.responseText || '{}');

        if (xhr.status >= 200 && xhr.status < 300 && response.ok) {
          mediaGrid.insertAdjacentHTML('afterbegin', response.itemHtml || '');
          bindCopyButtons(mediaGrid);
          form.reset();
          updateFileName();
          setProgress(100);
          showFeedback('success', response.message || 'Image téléversée.');
          return;
        }

        showFeedback('error', response.message || 'Le téléversement a échoué.');
      } catch {
        showFeedback('error', 'Réponse inattendue du serveur.');
      }
    });

    xhr.addEventListener('error', () => {
      submitButton.disabled = false;
      submitButton.textContent = 'Téléverser';
      showFeedback('error', 'La connexion au serveur a échoué pendant l’envoi.');
    });

    xhr.send(formData);
  });
</script>
<?php cms_render_admin_end(); ?>
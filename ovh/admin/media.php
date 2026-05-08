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
    ?>
    <article class="media-card<?= $isAvailable ? '' : ' is-missing' ?>">
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
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<section class="panel media-panel">
  <div class="panel-head compact media-panel-head">
    <div>
      <p class="eyebrow">Médiathèque</p>
      <h1>Images</h1>
      <p class="lead media-lead">Téléversez vos visuels sans quitter la page, avec progression en direct et ajout immédiat dans la bibliothèque.</p>
    </div>
    <div class="media-stats-card">
      <strong><?= count($mediaItems) ?></strong>
      <span>Médias enregistrés</span>
    </div>
  </div>

  <div id="media-feedback" class="flash" hidden></div>

  <form id="media-upload-form" method="post" enctype="multipart/form-data" class="media-upload-layout">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">

    <label class="upload-dropzone" id="upload-dropzone" for="media-file-input">
      <span class="upload-dropzone-icon">+</span>
      <strong>Glissez votre image ici</strong>
      <span>ou cliquez pour choisir un fichier JPG, PNG ou WebP</span>
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
          <strong>Téléversement</strong>
          <span id="upload-progress-value">0%</span>
        </div>
        <div class="upload-progress-track">
          <div id="upload-progress-bar" class="upload-progress-bar"></div>
        </div>
      </div>

      <div class="media-upload-actions">
        <button id="media-submit-button" class="primary-button" type="submit">Téléverser</button>
      </div>
    </div>
  </form>
</section>

<section id="media-grid" class="media-grid">
  <?php foreach ($mediaItems as $item): ?>
    <?php cms_render_media_card($item); ?>
  <?php endforeach; ?>
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
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();

    try {
        cms_store_uploaded_media($_FILES['image'] ?? [], (string) ($_POST['title'] ?? ''), (string) ($_POST['alt_text'] ?? ''));
        cms_flash('success', 'Image téléversée.');
        cms_redirect('/admin/media');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}

$mediaItems = cms_media_items();

cms_render_admin_start('Images', '/admin/media');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Médiathèque</p>
      <h1>Images</h1>
    </div>
  </div>
  <form method="post" enctype="multipart/form-data" class="admin-form-stack compact-form">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
    <label>
      Fichier image
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
    </label>
    <label>
      Titre interne
      <input name="title">
    </label>
    <label>
      Texte alternatif
      <input name="alt_text">
    </label>
    <button class="primary-button" type="submit">Téléverser</button>
  </form>
</section>

<section class="media-grid">
  <?php foreach ($mediaItems as $item): ?>
    <article class="media-card">
      <img src="<?= cms_h(cms_url((string) $item['public_url'])) ?>" alt="<?= cms_h((string) ($item['alt_text'] ?? '')) ?>">
      <div class="media-meta">
        <strong><?= cms_h((string) ($item['title'] ?: $item['original_name'])) ?></strong>
        <input readonly value="<?= cms_h((string) $item['public_url']) ?>" onclick="this.select()">
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php cms_render_admin_end(); ?>
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$errors = [];
$settings = cms_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();

    $payload = [
        'site_name' => trim((string) ($_POST['site_name'] ?? '')),
        'baseline' => trim((string) ($_POST['baseline'] ?? '')),
        'mickael_name' => trim((string) ($_POST['mickael_name'] ?? '')),
        'marion_name' => trim((string) ($_POST['marion_name'] ?? '')),
        'mickael_photo' => trim((string) ($_POST['mickael_photo'] ?? ($settings['mickael_photo'] ?? ''))),
        'marion_photo' => trim((string) ($_POST['marion_photo'] ?? ($settings['marion_photo'] ?? ''))),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'main_city' => trim((string) ($_POST['main_city'] ?? '')),
        'covered_areas' => trim((string) ($_POST['covered_areas'] ?? '')),
        'facebook_url' => trim((string) ($_POST['facebook_url'] ?? '')),
        'instagram_url' => trim((string) ($_POST['instagram_url'] ?? '')),
        'iad_url' => trim((string) ($_POST['iad_url'] ?? '')),
        'footer_text' => trim((string) ($_POST['footer_text'] ?? '')),
        'main_cta_label' => trim((string) ($_POST['main_cta_label'] ?? '')),
        'main_cta_url' => trim((string) ($_POST['main_cta_url'] ?? '')),
    ];

      try {
        if (($_FILES['mickael_photo_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
          $payload['mickael_photo'] = cms_store_uploaded_media($_FILES['mickael_photo_file'], 'Photo de ' . $payload['mickael_name'], 'Photo de ' . $payload['mickael_name']);
        }

        if (($_FILES['marion_photo_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
          $payload['marion_photo'] = cms_store_uploaded_media($_FILES['marion_photo_file'], 'Photo de ' . $payload['marion_name'], 'Photo de ' . $payload['marion_name']);
        }
      } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
      }

    foreach (['site_name', 'baseline', 'mickael_name', 'marion_name', 'email', 'main_city', 'footer_text', 'main_cta_label', 'main_cta_url'] as $field) {
        if ($payload[$field] === '') {
            $errors[] = 'Tous les champs principaux des réglages doivent être remplis.';
            break;
        }
    }

    if ($errors === []) {
        try {
            cms_save_settings($payload);
            cms_flash('success', 'Les réglages du site ont été enregistrés.');
            cms_redirect('/admin/settings');
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    $settings = array_merge($settings, $payload, [
        'covered_areas_json' => json_encode(cms_parse_lines($payload['covered_areas']), JSON_UNESCAPED_UNICODE),
    ]);
}

$coveredAreas = implode("\n", cms_json_list($settings['covered_areas_json'] ?? '[]'));

cms_render_admin_start('Réglages du site', '/admin/settings');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="admin-form-stack">
  <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">

  <section class="panel">
    <div class="panel-head compact">
      <div>
        <p class="eyebrow">Réglages</p>
        <h1>Identité du site</h1>
      </div>
    </div>
    <div class="grid two-cols">
      <label>
        Nom du site
        <input name="site_name" value="<?= cms_h((string) $settings['site_name']) ?>" required>
      </label>
      <label>
        Ville principale
        <input name="main_city" value="<?= cms_h((string) $settings['main_city']) ?>" required>
      </label>
      <label class="full">
        Baseline
        <textarea name="baseline" rows="3" required><?= cms_h((string) $settings['baseline']) ?></textarea>
      </label>
      <label>
        Mickael
        <input name="mickael_name" value="<?= cms_h((string) $settings['mickael_name']) ?>" required>
      </label>
      <label>
        Marion
        <input name="marion_name" value="<?= cms_h((string) $settings['marion_name']) ?>" required>
      </label>
      <label>
        Photo Mickael
        <input type="hidden" name="mickael_photo" value="<?= cms_h((string) ($settings['mickael_photo'] ?? '')) ?>">
        <?php if (!empty($settings['mickael_photo'])): ?>
          <img class="settings-photo-preview" src="<?= cms_h(cms_url((string) $settings['mickael_photo'])) ?>" alt="Photo actuelle de Mickael">
        <?php endif; ?>
        <input type="file" name="mickael_photo_file" accept="image/jpeg,image/png,image/webp">
      </label>
      <label>
        Photo Marion
        <input type="hidden" name="marion_photo" value="<?= cms_h((string) ($settings['marion_photo'] ?? '')) ?>">
        <?php if (!empty($settings['marion_photo'])): ?>
          <img class="settings-photo-preview" src="<?= cms_h(cms_url((string) $settings['marion_photo'])) ?>" alt="Photo actuelle de Marion">
        <?php endif; ?>
        <input type="file" name="marion_photo_file" accept="image/jpeg,image/png,image/webp">
      </label>
      <label>
        Téléphone
        <input name="phone" value="<?= cms_h((string) $settings['phone']) ?>">
      </label>
      <label>
        Email public
        <input type="email" name="email" value="<?= cms_h((string) $settings['email']) ?>" required>
      </label>
      <label class="full">
        Zones couvertes
        <textarea name="covered_areas" rows="5" placeholder="Une ligne = une zone"><?= cms_h($coveredAreas) ?></textarea>
      </label>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head compact">
      <div>
        <p class="eyebrow">Liens</p>
        <h2>Réseaux et CTA</h2>
      </div>
    </div>
    <div class="grid two-cols">
      <label>
        Facebook
        <input name="facebook_url" value="<?= cms_h((string) $settings['facebook_url']) ?>">
      </label>
      <label>
        Instagram
        <input name="instagram_url" value="<?= cms_h((string) $settings['instagram_url']) ?>">
      </label>
      <label>
        Lien IAD
        <input name="iad_url" value="<?= cms_h((string) $settings['iad_url']) ?>">
      </label>
      <label>
        Libellé CTA principal
        <input name="main_cta_label" value="<?= cms_h((string) $settings['main_cta_label']) ?>" required>
      </label>
      <label class="full">
        URL CTA principale
        <input name="main_cta_url" value="<?= cms_h((string) $settings['main_cta_url']) ?>" required>
      </label>
      <label class="full">
        Texte de pied de page
        <textarea name="footer_text" rows="4" required><?= cms_h((string) $settings['footer_text']) ?></textarea>
      </label>
    </div>
  </section>

  <div class="admin-actions sticky-actions">
    <button class="primary-button" type="submit">Enregistrer les réglages</button>
    <a class="secondary-button" href="<?= cms_h(cms_url('/')) ?>" target="_blank" rel="noreferrer">Voir le site</a>
  </div>
</form>
<?php cms_render_admin_end(); ?>
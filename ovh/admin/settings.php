<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$errors = [];
$settings = cms_settings();
$mediaItems = cms_media_items();

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

$mickaelPhoto = trim((string) ($settings['mickael_photo'] ?? ''));
$marionPhoto = trim((string) ($settings['marion_photo'] ?? ''));
$mickaelPhotoSrc = $mickaelPhoto !== '' ? (preg_match('#^https?://#i', $mickaelPhoto) ? $mickaelPhoto : cms_url($mickaelPhoto)) : '';
$marionPhotoSrc = $marionPhoto !== '' ? (preg_match('#^https?://#i', $marionPhoto) ? $marionPhoto : cms_url($marionPhoto)) : '';

cms_render_admin_start('Réglages du site', '/admin/settings');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<section class="dashboard-hero">
  <div class="dashboard-hero-inner">
    <div>
      <p class="eyebrow">Configuration</p>
      <h1>Réglages du site</h1>
      <p>Identité, contacts, réseaux sociaux et appels à l'action — tout ce qui définit votre présence en ligne.</p>
    </div>
  </div>
</section>

<form method="post" enctype="multipart/form-data" class="settings-grid">
  <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">

  <section class="panel">
    <div class="settings-section-head">
      <span class="settings-section-icon is-emerald">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></svg>
      </span>
      <div class="settings-section-text">
        <h2>Identité du site</h2>
        <p>Nom, baseline et zones d'intervention</p>
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
      <label class="full">
        Zones couvertes
        <textarea name="covered_areas" rows="5" placeholder="Une ligne = une zone"><?= cms_h($coveredAreas) ?></textarea>
      </label>
    </div>
  </section>

  <section class="panel">
    <div class="settings-section-head">
      <span class="settings-section-icon is-blue">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </span>
      <div class="settings-section-text">
        <h2>L'équipe</h2>
        <p>Mickael & Marion — visuels et coordonnées</p>
      </div>
    </div>
    <div class="grid two-cols">
      <label>
        Mickael
        <input name="mickael_name" value="<?= cms_h((string) $settings['mickael_name']) ?>" required>
      </label>
      <label>
        Marion
        <input name="marion_name" value="<?= cms_h((string) $settings['marion_name']) ?>" required>
      </label>
      <?php cms_render_media_picker_field('Photo Mickael', 'mickael_photo', $mickaelPhoto); ?>
      <?php cms_render_media_picker_field('Photo Marion', 'marion_photo', $marionPhoto); ?>
    </div>
    <?php if ($mickaelPhotoSrc !== '' || $marionPhotoSrc !== ''): ?>
      <div class="team-preview">
        <?php if ($mickaelPhotoSrc !== ''): ?>
          <div class="team-card">
            <img src="<?= cms_h($mickaelPhotoSrc) ?>" alt="Mickael">
            <div class="team-card-info">
              <strong><?= cms_h((string) $settings['mickael_name']) ?></strong>
              <small>Conseiller IAD France</small>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($marionPhotoSrc !== ''): ?>
          <div class="team-card">
            <img src="<?= cms_h($marionPhotoSrc) ?>" alt="Marion">
            <div class="team-card-info">
              <strong><?= cms_h((string) $settings['marion_name']) ?></strong>
              <small>Conseillère IAD France</small>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="settings-section-head">
      <span class="settings-section-icon is-amber">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
      </span>
      <div class="settings-section-text">
        <h2>Coordonnées de contact</h2>
        <p>Téléphone et email publics</p>
      </div>
    </div>
    <div class="grid two-cols">
      <label>
        Téléphone
        <input name="phone" value="<?= cms_h((string) $settings['phone']) ?>" placeholder="06 12 34 56 78">
      </label>
      <label>
        Email public
        <input type="email" name="email" value="<?= cms_h((string) $settings['email']) ?>" required>
      </label>
    </div>
  </section>

  <section class="panel">
    <div class="settings-section-head">
      <span class="settings-section-icon is-violet">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
      </span>
      <div class="settings-section-text">
        <h2>Liens & réseaux sociaux</h2>
        <p>Profils, partenaires et appel à l'action principal</p>
      </div>
    </div>
    <div class="grid two-cols">
      <label>
        Facebook
        <input name="facebook_url" value="<?= cms_h((string) $settings['facebook_url']) ?>" placeholder="https://facebook.com/...">
      </label>
      <label>
        Instagram
        <input name="instagram_url" value="<?= cms_h((string) $settings['instagram_url']) ?>" placeholder="https://instagram.com/...">
      </label>
      <label class="full">
        Lien IAD
        <input name="iad_url" value="<?= cms_h((string) $settings['iad_url']) ?>" placeholder="https://www.iadfrance.fr/...">
      </label>
      <label>
        Libellé CTA principal
        <input name="main_cta_label" value="<?= cms_h((string) $settings['main_cta_label']) ?>" required>
      </label>
      <label>
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
    <button class="primary-button" type="submit">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Enregistrer les réglages
    </button>
    <a class="secondary-button" href="<?= cms_h(cms_url('/')) ?>" target="_blank" rel="noreferrer">Voir le site</a>
  </div>
</form>
<?php cms_render_media_picker_assets($mediaItems); ?>
<?php cms_render_admin_end(); ?>

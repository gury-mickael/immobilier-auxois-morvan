<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$id = (int) ($_GET['id'] ?? 0);
$request = $id > 0 ? cms_estimation_request($id) : null;

if (!$request) {
    cms_flash('error', 'Demande d’estimation introuvable.');
    cms_redirect('/admin/estimation-requests');
}

$statuses = cms_estimation_statuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        cms_delete_estimation_request($id);
        cms_flash('success', 'La demande d’estimation a été supprimée.');
        cms_redirect('/admin/estimation-requests');
    }

    cms_update_estimation_request($id, [
        'status' => (string) ($_POST['status'] ?? 'new'),
        'internal_notes' => (string) ($_POST['internal_notes'] ?? ''),
    ]);
    cms_flash('success', 'La demande d’estimation a été mise à jour.');
    cms_redirect('/admin/estimation-request?id=' . $id);
}

$request = cms_estimation_request($id) ?? $request;
$isViager = (string) ($request['request_type'] ?? 'estimation') === 'viager';
$details = [
  'Type de demande' => $isViager ? 'Viager' : 'Estimation',
    'Type de bien' => $request['property_type'],
    'Nombre de pièces' => $request['room_count'],
  'État général' => $request['property_condition'] ?: '—',
    'Surface habitable' => $request['living_surface'],
  'Surface terrain' => $request['land_surface'] ?: '—',
  'Souhait logement' => ($request['occupancy_intent'] ?? '') !== '' ? $request['occupancy_intent'] : '—',
    'Commune' => $request['commune'],
    'Code postal' => $request['postal_code'] ?: '—',
    'Adresse / secteur' => $request['address_details'],
    'Objectif' => $request['goal'],
  'Situation' => ($request['owner_situation'] ?? '') !== '' ? $request['owner_situation'] : '—',
    'Délai envisagé' => $request['project_timeline'],
    'Source' => $request['source'],
    'UTM source' => $request['utm_source'] ?: '—',
    'UTM medium' => $request['utm_medium'] ?: '—',
    'UTM campaign' => $request['utm_campaign'] ?: '—',
    'UTM content' => $request['utm_content'] ?: '—',
    'Page d’origine' => $request['origin_page'] ?: '—',
    'Hors secteur' => (int) $request['outside_area'] === 1 ? 'Oui' : 'Non',
];

cms_render_admin_start('Détail estimation', '/admin/estimation-requests');
?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Lead estimation</p>
      <h1><?= cms_h(trim((string) $request['first_name'] . ' ' . (string) $request['last_name'])) ?><?php if ($isViager): ?> · Viager<?php endif; ?></h1>
      <p class="lead">Créée le <?= cms_h(date('d/m/Y à H:i', strtotime((string) $request['created_at']))) ?></p>
    </div>
    <a class="secondary-button" href="<?= cms_h(cms_url('/admin/estimation-requests')) ?>">Retour à la liste</a>
  </div>

  <div class="admin-detail-grid">
    <article class="panel admin-nested-panel">
      <h2>Coordonnées</h2>
      <div class="admin-detail-list">
        <div><strong>Prénom</strong><span><?= cms_h((string) $request['first_name']) ?></span></div>
        <div><strong>Nom</strong><span><?= cms_h((string) $request['last_name']) ?></span></div>
        <div><strong>Email</strong><span><?= cms_h((string) $request['email']) ?></span></div>
        <div><strong>Téléphone</strong><span><?= cms_h((string) $request['phone']) ?></span></div>
      </div>
    </article>

    <article class="panel admin-nested-panel">
      <h2>Détail du bien et du lead</h2>
      <div class="admin-detail-list">
        <?php foreach ($details as $label => $value): ?>
          <div>
            <strong><?= cms_h((string) $label) ?></strong>
            <span><?= cms_h((string) $value) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </article>
  </div>
</section>

<section class="panel">
  <form method="post" class="admin-form-stack compact-form">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
    <label>
      Statut commercial
      <select name="status">
        <?php foreach ($statuses as $value => $label): ?>
          <option value="<?= cms_h($value) ?>" <?= (string) $request['status'] === $value ? 'selected' : '' ?>><?= cms_h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Notes internes
      <textarea name="internal_notes" rows="8" placeholder="Notes de suivi, compte rendu d’appel, date de relance..."><?= cms_h((string) ($request['internal_notes'] ?? '')) ?></textarea>
    </label>
    <div class="admin-actions">
      <button class="primary-button" type="submit" name="action" value="save">Enregistrer</button>
      <button class="danger-button" type="submit" name="action" value="delete" onclick="return confirm('Supprimer cette demande ?');">Supprimer</button>
    </div>
  </form>
</section>
<?php cms_render_admin_end(); ?>
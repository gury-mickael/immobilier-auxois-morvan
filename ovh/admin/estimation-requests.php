<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
  'request_type' => trim((string) ($_GET['request_type'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'commune' => trim((string) ($_GET['commune'] ?? '')),
    'utm_campaign' => trim((string) ($_GET['utm_campaign'] ?? '')),
];
$requests = cms_estimation_requests($filters);
$statuses = cms_estimation_statuses();

cms_render_admin_start('Demandes d’estimation', '/admin/estimation-requests');
?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Estimations</p>
      <h1>Demandes d’estimation</h1>
      <p class="lead">Suivez les leads issus du tunnel d’estimation, les campagnes UTM et le niveau d’avancement commercial.</p>
    </div>
  </div>

  <form method="get" class="admin-filter-grid">
    <label>
      Recherche
      <input name="search" value="<?= cms_h($filters['search']) ?>" placeholder="Nom, email, téléphone, commune">
    </label>
    <label>
      Statut
      <select name="status">
        <option value="">Tous</option>
        <?php foreach ($statuses as $value => $label): ?>
          <option value="<?= cms_h($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= cms_h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Type de demande
      <select name="request_type">
        <option value="">Toutes</option>
        <option value="estimation" <?= $filters['request_type'] === 'estimation' ? 'selected' : '' ?>>Estimation</option>
        <option value="viager" <?= $filters['request_type'] === 'viager' ? 'selected' : '' ?>>Viager</option>
      </select>
    </label>
    <label>
      Commune
      <input name="commune" value="<?= cms_h($filters['commune']) ?>" placeholder="Ex. Arnay-le-Duc">
    </label>
    <label>
      Campagne UTM
      <input name="utm_campaign" value="<?= cms_h($filters['utm_campaign']) ?>" placeholder="Ex. estimation_autun">
    </label>
    <div class="admin-filter-actions">
      <button class="primary-button" type="submit">Filtrer</button>
      <a class="secondary-button" href="<?= cms_h(cms_url('/admin/estimation-requests')) ?>">Réinitialiser</a>
    </div>
  </form>
</section>

<section class="panel">
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Contact</th>
          <th>Commune</th>
          <th>Bien</th>
          <th>Objectif</th>
          <th>Délai</th>
          <th>Statut</th>
          <th>Campagne</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td><?= cms_h(date('d/m/Y H:i', strtotime((string) $request['created_at']))) ?></td>
            <td>
              <strong><?= cms_h(trim((string) $request['first_name'] . ' ' . (string) $request['last_name'])) ?></strong>
              <?php if ((string) ($request['request_type'] ?? 'estimation') === 'viager'): ?>
                <span class="status-badge">Viager</span>
              <?php endif; ?>
              <div class="lead"><?= cms_h((string) $request['phone']) ?> · <?= cms_h((string) $request['email']) ?></div>
            </td>
            <td>
              <?= cms_h((string) $request['commune']) ?>
              <?php if (!empty($request['postal_code'])): ?>
                <div class="lead"><?= cms_h((string) $request['postal_code']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= cms_h((string) $request['property_type']) ?></td>
            <td><?= cms_h((string) $request['goal']) ?></td>
            <td><?= cms_h((string) $request['project_timeline']) ?></td>
            <td><span class="status-badge status-<?= cms_h((string) $request['status']) ?>"><?= cms_h($statuses[(string) $request['status']] ?? (string) $request['status']) ?></span></td>
            <td>
              <?= cms_h((string) ($request['utm_campaign'] ?: '—')) ?>
              <div class="lead"><?= cms_h((string) ($request['utm_source'] ?: 'direct')) ?></div>
            </td>
            <td><a class="secondary-button" href="<?= cms_h(cms_url('/admin/estimation-request?id=' . (int) $request['id'])) ?>">Voir</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($requests === []): ?>
          <tr>
            <td colspan="9">Aucune demande trouvée avec ces filtres.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php cms_render_admin_end(); ?>
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$admin = cms_require_admin();
$mainPages = cms_main_pages();
$localPages = cms_local_pages();
$blogPosts = cms_blog_posts();
$mediaItems = cms_media_items();
$users = cms_admin_users();
$estimationStatuses = cms_estimation_statuses();
$allEstimationRequests = cms_estimation_requests();
$estimationRequestsCount = cms_estimation_requests_count();
$newEstimationRequestsCount = cms_estimation_requests_count('new');
$recentEstimationRequests = cms_recent_estimation_requests(6);
$estimationAnalyticsPeriods = cms_estimation_analytics_periods();
$estimationAnalyticsPeriod = cms_estimation_analytics_period_key($_GET['estimation_period'] ?? '7d');
$estimationAnalytics = cms_estimation_analytics_stats($estimationAnalyticsPeriod);
$estimationStepLabels = [
  1 => 'Type de bien',
  2 => 'Pièces',
  3 => 'État',
  4 => 'Surface habitable',
  5 => 'Terrain',
  6 => 'Commune',
  7 => 'Adresse',
  8 => 'Objectif',
  9 => 'Calendrier',
  10 => 'Coordonnées',
];

// Stats par statut
$statusCounts = array_fill_keys(array_keys($estimationStatuses), 0);
foreach ($allEstimationRequests as $req) {
    $s = (string) ($req['status'] ?? 'new');
    if (isset($statusCounts[$s])) {
        $statusCounts[$s]++;
    }
}

// Série temporelle : 12 dernières semaines
$weeks = 12;
$series = array_fill(0, $weeks, 0);
$labels = [];
$now = new DateTimeImmutable('monday this week');
for ($i = $weeks - 1; $i >= 0; $i--) {
    $weekStart = $now->modify('-' . $i . ' weeks');
    $labels[] = $weekStart->format('d/m');
}
foreach ($allEstimationRequests as $req) {
    $created = $req['created_at'] ?? null;
    if (!$created) {
        continue;
    }
    try {
        $date = new DateTimeImmutable((string) $created);
    } catch (Throwable) {
        continue;
    }
    $diffDays = (int) (($now->getTimestamp() - $date->getTimestamp()) / 86400);
    $weekIndex = $weeks - 1 - intdiv($diffDays, 7);
    if ($weekIndex >= 0 && $weekIndex < $weeks) {
        $series[$weekIndex]++;
    }
}
$maxSeries = max(1, max($series));

// Taux de transformation
$convertedCount = ($statusCounts['mandate-signed'] ?? 0) + ($statusCounts['valuation-completed'] ?? 0);
$conversionRate = $estimationRequestsCount > 0
    ? round(($convertedCount / $estimationRequestsCount) * 100)
    : 0;

// Demandes 30 derniers jours vs 30 jours d'avant
$thirtyDays = 0;
$prevThirtyDays = 0;
$nowTs = time();
foreach ($allEstimationRequests as $req) {
    $created = $req['created_at'] ?? null;
    if (!$created) {
        continue;
    }
    $ts = strtotime((string) $created);
    if ($ts === false) {
        continue;
    }
    $age = ($nowTs - $ts) / 86400;
    if ($age <= 30) {
        $thirtyDays++;
    } elseif ($age <= 60) {
        $prevThirtyDays++;
    }
}
$trendPct = $prevThirtyDays > 0
    ? round((($thirtyDays - $prevThirtyDays) / $prevThirtyDays) * 100)
    : ($thirtyDays > 0 ? 100 : 0);

// Distribution contenu (donut)
$contentTotal = count($mainPages) + count($localPages) + count($blogPosts);
$contentSlices = [
    ['label' => 'Pages principales', 'value' => count($mainPages), 'color' => '#16a34a'],
    ['label' => 'Pages locales', 'value' => count($localPages), 'color' => '#2563eb'],
    ['label' => 'Articles de blog', 'value' => count($blogPosts), 'color' => '#7c3aed'],
];

cms_render_admin_start('Tableau de bord', '/admin');

$adminFirstName = trim(explode(' ', (string) $admin['name'])[0] ?? 'Admin');
$today = strftime_safe();
?>
<section class="dashboard-hero">
  <div class="dashboard-hero-inner">
    <div>
      <p class="eyebrow">Bonjour, <?= cms_h($adminFirstName) ?> <?= cms_h($today) ?></p>
      <h1>Bienvenue sur votre espace de pilotage</h1>
      <p>Suivez en un coup d'œil l'activité du site, vos contenus et la performance de votre acquisition de leads.</p>
    </div>
    <div class="dashboard-hero-actions">
      <a class="secondary-button" href="<?= cms_h(cms_url('/')) ?>" target="_blank" rel="noreferrer">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Voir le site
      </a>
      <a class="primary-button" href="<?= cms_h(cms_url('/admin/estimation-requests')) ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= $newEstimationRequestsCount ?> nouveau<?= $newEstimationRequestsCount > 1 ? 'x' : '' ?> lead<?= $newEstimationRequestsCount > 1 ? 's' : '' ?>
      </a>
    </div>
  </div>
</section>

<section class="kpi-grid">
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">Demandes 30j</span>
      <span class="kpi-card-icon is-emerald">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= $thirtyDays ?></div>
    <div class="kpi-card-foot">
      <span class="kpi-trend <?= $trendPct > 0 ? 'is-up' : ($trendPct < 0 ? 'is-down' : 'is-neutral') ?>">
        <?= $trendPct > 0 ? '↑' : ($trendPct < 0 ? '↓' : '–') ?> <?= abs($trendPct) ?>%
      </span>
      vs 30j précédents
    </div>
  </article>
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">Total leads</span>
      <span class="kpi-card-icon is-blue">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= $estimationRequestsCount ?></div>
    <div class="kpi-card-foot">
      <span class="kpi-trend is-neutral"><?= $newEstimationRequestsCount ?> à traiter</span>
    </div>
  </article>
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">Taux conversion</span>
      <span class="kpi-card-icon is-violet">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= $conversionRate ?>%</div>
    <div class="kpi-card-foot">
      <span class="kpi-trend <?= $conversionRate >= 30 ? 'is-up' : 'is-neutral' ?>"><?= $convertedCount ?> abouties</span>
    </div>
  </article>
  <article class="kpi-card">
    <div class="kpi-card-head">
      <span class="kpi-card-label">Contenus publiés</span>
      <span class="kpi-card-icon is-amber">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </span>
    </div>
    <div class="kpi-card-value"><?= $contentTotal ?></div>
    <div class="kpi-card-foot">
      <span class="kpi-trend is-neutral"><?= count($mediaItems) ?> médias</span>
    </div>
  </article>
</section>

<section class="panel estimation-analytics-panel">
  <div class="panel-title-row analytics-title-row">
    <div>
      <h2>Performance page estimation</h2>
      <p>Visites et clics du tunnel /estimation-en-ligne</p>
    </div>
    <div class="chip-tabs">
      <?php foreach ($estimationAnalyticsPeriods as $key => $period): ?>
        <a class="chip-tab<?= $estimationAnalyticsPeriod === $key ? ' is-active' : '' ?>" href="<?= cms_h(cms_url('/admin?estimation_period=' . $key)) ?>"><?= cms_h((string) $period['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="analytics-summary-grid">
    <article class="analytics-mini-card"><span>Visiteurs</span><strong><?= (int) $estimationAnalytics['visitors'] ?></strong><small><?= (int) $estimationAnalytics['page_views'] ?> vues page</small></article>
    <article class="analytics-mini-card"><span>Clics étapes</span><strong><?= (int) $estimationAnalytics['choice_clicks'] ?></strong><small><?= (int) $estimationAnalytics['step_completions'] ?> étapes validées</small></article>
    <article class="analytics-mini-card"><span>Demandes reçues</span><strong><?= (int) $estimationAnalytics['leads'] ?></strong><small><?= (int) $estimationAnalytics['form_submits'] ?> envois formulaire</small></article>
    <article class="analytics-mini-card"><span>Conversion</span><strong><?= (int) $estimationAnalytics['conversion_rate'] ?>%</strong><small>Leads / visiteurs</small></article>
  </div>

  <div class="analytics-detail-grid">
    <div class="analytics-block">
      <h3>Funnel par étape</h3>
      <?php
        $funnelByStep = [];
        foreach ($estimationAnalytics['funnel'] as $row) {
            $funnelByStep[(int) $row['step_number']] = (int) $row['visitors'];
        }
        $maxFunnel = max(1, ...array_values($funnelByStep ?: [1]));
      ?>
      <div class="analytics-funnel-list">
        <?php foreach ($estimationStepLabels as $stepNumber => $label):
          $visitors = $funnelByStep[$stepNumber] ?? 0;
          $width = ($visitors / $maxFunnel) * 100;
          ?>
          <div class="analytics-funnel-item">
            <div><span><?= sprintf('%02d', $stepNumber) ?></span><strong><?= cms_h($label) ?></strong><em><?= $visitors ?></em></div>
            <i><b style="width: <?= sprintf('%.1f', $width) ?>%"></b></i>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="analytics-block analytics-side-blocks">
      <div>
        <h3>Choix les plus cliqués</h3>
        <?php if ($estimationAnalytics['choices']): ?>
          <div class="analytics-list">
            <?php foreach ($estimationAnalytics['choices'] as $choice): ?>
              <div><span><?= cms_h((string) ($choice['choice_value'] ?? '')) ?></span><strong><?= (int) $choice['total'] ?></strong></div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="analytics-empty">Pas encore de clic enregistré sur la période.</p>
        <?php endif; ?>
      </div>
      <div>
        <h3>Sources détectées</h3>
        <?php if ($estimationAnalytics['sources']): ?>
          <div class="analytics-list">
            <?php foreach ($estimationAnalytics['sources'] as $source): ?>
              <div><span><?= cms_h((string) ($source['source'] ?? '')) ?></span><strong><?= (int) $source['visitors'] ?></strong></div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="analytics-empty">Aucune visite détectée sur la période.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-layout">
  <div class="panel">
    <div class="panel-title-row">
      <div>
        <h2>Évolution des demandes</h2>
        <p>12 dernières semaines</p>
      </div>
      <div class="chip-tabs">
        <button class="chip-tab is-active" type="button">Hebdo</button>
      </div>
    </div>
    <div class="chart-wrap">
      <?php
      $chartW = 720;
      $chartH = 220;
      $padX = 24;
      $padTop = 16;
      $padBottom = 32;
      $innerW = $chartW - 2 * $padX;
      $innerH = $chartH - $padTop - $padBottom;
      $stepX = $innerW / max(1, $weeks - 1);
      $points = [];
      $areaPoints = [];
      foreach ($series as $i => $val) {
          $x = $padX + $i * $stepX;
          $y = $padTop + $innerH - ($val / $maxSeries) * $innerH;
          $points[] = sprintf('%.1f,%.1f', $x, $y);
      }
      $polyline = implode(' ', $points);
      $area = sprintf('%.1f,%.1f ', $padX, $padTop + $innerH) . $polyline . sprintf(' %.1f,%.1f', $padX + $innerW, $padTop + $innerH);
      ?>
      <svg class="chart-svg" viewBox="0 0 <?= $chartW ?> <?= $chartH ?>" role="img" aria-label="Évolution des demandes par semaine">
        <defs>
          <linearGradient id="areaGradient" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="#16a34a" stop-opacity="0.28"/>
            <stop offset="100%" stop-color="#16a34a" stop-opacity="0"/>
          </linearGradient>
        </defs>
        <?php for ($g = 0; $g <= 4; $g++):
          $gy = $padTop + ($innerH * $g / 4); ?>
          <line x1="<?= $padX ?>" x2="<?= $padX + $innerW ?>" y1="<?= $gy ?>" y2="<?= $gy ?>" stroke="#eef1f6" stroke-width="1"/>
        <?php endfor; ?>
        <polygon points="<?= $area ?>" fill="url(#areaGradient)"/>
        <polyline points="<?= $polyline ?>" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <?php foreach ($series as $i => $val):
          $x = $padX + $i * $stepX;
          $y = $padTop + $innerH - ($val / $maxSeries) * $innerH; ?>
          <circle cx="<?= sprintf('%.1f', $x) ?>" cy="<?= sprintf('%.1f', $y) ?>" r="3.5" fill="#fff" stroke="#16a34a" stroke-width="2"/>
        <?php endforeach; ?>
        <?php foreach ($labels as $i => $lab):
          if ($i % 2 !== 0 && $i !== $weeks - 1) { continue; }
          $x = $padX + $i * $stepX; ?>
          <text x="<?= sprintf('%.1f', $x) ?>" y="<?= $chartH - 8 ?>" font-size="10" fill="#94a3b8" text-anchor="middle" font-family="Inter, sans-serif" font-weight="500"><?= cms_h($lab) ?></text>
        <?php endforeach; ?>
      </svg>
      <div class="chart-legend">
        <span><i style="background:#16a34a"></i> Demandes hebdomadaires</span>
        <span>Pic : <?= $maxSeries ?> · Total période : <?= array_sum($series) ?></span>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-title-row">
      <div>
        <h2>Répartition des contenus</h2>
        <p><?= $contentTotal ?> publications</p>
      </div>
    </div>
    <div class="donut-wrap">
      <?php
      $cx = 60; $cy = 60; $r = 48; $stroke = 14;
      $circumference = 2 * M_PI * $r;
      $offset = 0;
      ?>
      <svg class="donut-svg" viewBox="0 0 120 120" role="img">
        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#eef1f6" stroke-width="<?= $stroke ?>"/>
        <?php foreach ($contentSlices as $slice):
          if ($contentTotal === 0 || $slice['value'] === 0) { continue; }
          $portion = $slice['value'] / $contentTotal;
          $dash = $circumference * $portion;
          $gap = $circumference - $dash;
          ?>
          <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none"
            stroke="<?= cms_h($slice['color']) ?>" stroke-width="<?= $stroke ?>"
            stroke-dasharray="<?= sprintf('%.2f %.2f', $dash, $gap) ?>"
            stroke-dashoffset="<?= sprintf('%.2f', -$offset) ?>"
            transform="rotate(-90 <?= $cx ?> <?= $cy ?>)"
            stroke-linecap="butt"/>
          <?php $offset += $dash;
        endforeach; ?>
        <text x="60" y="58" class="donut-center" text-anchor="middle"><?= $contentTotal ?></text>
        <text x="60" y="74" class="donut-center-label" text-anchor="middle">PUBLICATIONS</text>
      </svg>
      <div class="donut-legend">
        <?php foreach ($contentSlices as $slice): ?>
          <div class="donut-legend-item">
            <i style="background:<?= cms_h($slice['color']) ?>"></i>
            <span><?= cms_h($slice['label']) ?></span>
            <span><?= $slice['value'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-layout">
  <div class="panel">
    <div class="panel-title-row">
      <div>
        <h2>Pipeline des estimations</h2>
        <p>Répartition par statut</p>
      </div>
      <a class="ghost-button" href="<?= cms_h(cms_url('/admin/estimation-requests')) ?>">Tout voir →</a>
    </div>
    <div class="pipeline-list">
      <?php $pipelineMax = max(1, max($statusCounts ?: [1])); ?>
      <?php foreach ($estimationStatuses as $key => $label):
        $count = $statusCounts[$key] ?? 0;
        $pct = ($count / $pipelineMax) * 100;
        ?>
        <div class="pipeline-item">
          <div class="pipeline-item-head">
            <span><?= cms_h($label) ?></span>
            <span><?= $count ?> demande<?= $count > 1 ? 's' : '' ?></span>
          </div>
          <div class="pipeline-bar">
            <div class="pipeline-bar-fill is-<?= cms_h($key) ?>" style="width: <?= sprintf('%.1f', $pct) ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-title-row">
      <div>
        <h2>Activité récente</h2>
        <p>Derniers leads reçus</p>
      </div>
    </div>
    <?php if ($recentEstimationRequests): ?>
      <div class="activity-feed">
        <?php foreach ($recentEstimationRequests as $request):
          $name = trim((string) $request['first_name'] . ' ' . (string) $request['last_name']);
          $when = strtotime((string) $request['created_at']);
          $rel = cms_relative_time($when);
          ?>
          <a class="activity-item" href="<?= cms_h(cms_url('/admin/estimation-request?id=' . (int) $request['id'])) ?>">
            <span class="activity-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <div class="activity-body">
              <strong><?= cms_h($name) ?></strong>
              <small><?= cms_h((string) $request['commune']) ?> · <?= cms_h((string) $request['goal']) ?></small>
            </div>
            <span class="activity-time"><?= cms_h($rel) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="lead">Aucune nouvelle demande pour le moment.</p>
    <?php endif; ?>
  </div>
</section>

<section class="panel">
  <div class="panel-title-row">
    <div>
      <h2>Actions rapides</h2>
      <p>Accès direct aux principales tâches d'édition</p>
    </div>
  </div>
  <div class="quick-actions">
    <a class="quick-action" href="<?= cms_h(cms_url('/admin/pages')) ?>">
      <span class="quick-action-icon is-emerald" style="background:var(--accent-soft);color:var(--accent)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </span>
      <div class="quick-action-text"><strong>Pages principales</strong><small><?= count($mainPages) ?> pages éditoriales</small></div>
      <span class="quick-action-arrow">→</span>
    </a>
    <a class="quick-action" href="<?= cms_h(cms_url('/admin/local-pages')) ?>">
      <span class="quick-action-icon" style="background:var(--info-soft);color:var(--info)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </span>
      <div class="quick-action-text"><strong>Pages locales</strong><small><?= count($localPages) ?> communes couvertes</small></div>
      <span class="quick-action-arrow">→</span>
    </a>
    <a class="quick-action" href="<?= cms_h(cms_url('/admin/blog')) ?>">
      <span class="quick-action-icon" style="background:rgba(139,92,246,0.1);color:#7c3aed">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="7" y1="10" x2="17" y2="10"/><line x1="7" y1="14" x2="13" y2="14"/></svg>
      </span>
      <div class="quick-action-text"><strong>Blog</strong><small><?= count($blogPosts) ?> articles</small></div>
      <span class="quick-action-arrow">→</span>
    </a>
    <a class="quick-action" href="<?= cms_h(cms_url('/admin/media')) ?>">
      <span class="quick-action-icon" style="background:var(--warning-soft);color:var(--warning)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </span>
      <div class="quick-action-text"><strong>Médiathèque</strong><small><?= count($mediaItems) ?> visuels</small></div>
      <span class="quick-action-arrow">→</span>
    </a>
    <a class="quick-action" href="<?= cms_h(cms_url('/admin/settings')) ?>">
      <span class="quick-action-icon" style="background:rgba(15,23,42,0.06);color:var(--ink)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </span>
      <div class="quick-action-text"><strong>Réglages</strong><small>Identité, contacts, liens</small></div>
      <span class="quick-action-arrow">→</span>
    </a>
    <a class="quick-action" href="<?= cms_h(cms_url('/admin/users')) ?>">
      <span class="quick-action-icon" style="background:var(--danger-soft);color:var(--danger)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </span>
      <div class="quick-action-text"><strong>Utilisateurs</strong><small><?= count($users) ?> comptes admin</small></div>
      <span class="quick-action-arrow">→</span>
    </a>
  </div>
</section>
<?php cms_render_admin_end(); ?>

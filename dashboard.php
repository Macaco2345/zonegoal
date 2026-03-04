<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/db/db.php";
require_once __DIR__ . "/includes/matches_feed.php";

require_login();

$username = (string)($_SESSION["username"] ?? "Utilizador");

$leagues = require __DIR__ . "/config/leagues_api_football.php";
$leagues = array_values(array_filter($leagues, fn($l) => !empty($l["id"])));

// feed normal
$feed = zonegoal_build_feed($leagues);
$live = $feed["live"];
$finished = $feed["finished"];
$upcoming = $feed["upcoming"];

$view = "upcoming";
if (count($upcoming) === 0 && count($live) > 0) $view = "live";
if (count($upcoming) === 0 && count($live) === 0 && count($finished) > 0) $view = "finished";

require __DIR__ . "/includes/layout_start.php";
?>

<link rel="stylesheet" href="<?= e(url("css/pages/dashboard.css")) ?>">

<main class="match-list" style="margin:0; width:min(980px, 92vw);">

  <div class="empty-message">
    👤 Olá, <strong><?= e($username) ?></strong> — Favoritos (máx 10) • Atualiza automático
  </div>

  <!-- MY ZONE -->
  <section class="zg-card myzone">
    <div class="myzone-head">
      <div>
        <div class="mz-title">👤 A Minha Zona</div>
        <div class="mz-sub">Resumo rápido • só para utilizadores</div>
      </div>
      <a class="mz-link" href="<?= e(url("index.php")) ?>">Ver todos os jogos</a>
    </div>

    <div class="myzone-grid">
      <!-- esquerda: liga favorita -->
      <div class="mz-left">
        <div class="mz-panel">
          <div class="mz-panel-head">
            <span class="mz-pill">🔥</span>
            <span class="mz-panel-title">A tua liga favorita</span>
          </div>
          <div id="favoriteLeagueContent" class="mz-panel-body">
            <div class="empty-message">A carregar…</div>
          </div>
        </div>
      </div>

      <!-- direita: KPIs -->
      <div class="mz-right">
        <div id="myzoneKpis" class="mz-kpis">
          <div class="empty-message">A carregar resumo…</div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAVORITOS -->
  <div class="competition-row" style="margin-top:14px;">
    <div class="competition-flag">⭐</div>
    <div class="competition-name">Jogos seguidos</div>
  </div>

  <section id="favoritesArea">
    <div class="empty-message">A carregar favoritos…</div>
  </section>

  <div class="subtabs" style="margin-top:18px; position:static; top:auto;">
    <button class="filter-chip <?= $view==="live" ? "active" : "" ?>" data-view="live">Ao Vivo (<?= count($live) ?>)</button>
    <button class="filter-chip <?= $view==="finished" ? "active" : "" ?>" data-view="finished">Finalizado 24h (<?= count($finished) ?>)</button>
    <button class="filter-chip <?= $view==="upcoming" ? "active" : "" ?>" data-view="upcoming">Próximos 24h (<?= count($upcoming) ?>)</button>
  </div>

  <section class="match-section" data-section="live" style="<?= $view==="live" ? "display:block" : "display:none" ?>">
    <?php if (!count($live)) echo '<div class="empty-message">Sem jogos ao vivo.</div>';
    else zonegoal_render_grouped($live, "match-live", "AO VIVO"); ?>
  </section>

  <section class="match-section" data-section="finished" style="<?= $view==="finished" ? "display:block" : "display:none" ?>">
    <?php if (!count($finished)) echo '<div class="empty-message">Sem jogos finalizados.</div>';
    else zonegoal_render_grouped($finished, "match-finished", "FINAL"); ?>
  </section>

  <section class="match-section" data-section="upcoming" style="<?= $view==="upcoming" ? "display:block" : "display:none" ?>">
    <?php if (!count($upcoming)) echo '<div class="empty-message">Sem jogos próximos.</div>';
    else zonegoal_render_grouped($upcoming, "match-upcoming", "PRÓXIMO"); ?>
  </section>

</main>

<div id="toast" class="toast" style="display:none;"></div>

<script>
  window.ZONEGOAL_LOGGED_IN = true;
</script>

<script src="<?= e(url("js/pages/dashboard_refresh.js")) ?>?v=2"></script>
<script src="<?= e(url("js/pages/myzone.js")) ?>?v=2"></script>
<script src="<?= e(url("js/pages/favorite_league.js")) ?>?v=2"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>
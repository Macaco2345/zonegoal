<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$teamId = (int)($_GET["id"] ?? 0);
if ($teamId <= 0) {
  http_response_code(400);
  exit("Equipa inválida.");
}

require __DIR__ . "/includes/layout_start.php";
?>

<link rel="stylesheet" href="<?= e(url("css/pages/team.css")) ?>">

<main class="team-page">

  <div class="competition-row" style="margin-top:0;">
    <div class="competition-flag">🏟️</div>
    <div class="competition-name">Equipa</div>
  </div>

  <div id="teamHero" class="team-hero">
    <div class="empty-message">A carregar equipa…</div>
  </div>

  <div class="team-grid">
    <section class="team-panel">
      <div class="team-panel-title">Informação</div>
      <div id="teamInfo" class="empty-message">—</div>
    </section>

    <section class="team-panel">
      <div class="team-panel-title">Plantel (Top 25)</div>
      <div id="teamSquad" class="empty-message">—</div>
    </section>
  </div>

  <div class="team-tabs">
    <button class="tab-btn active" data-tab="next">Próximos 10</button>
    <button class="tab-btn" data-tab="last">Últimos 10</button>
  </div>

  <section class="team-panel">
    <div class="team-panel-title" id="fxTitle">Próximos 10</div>
    <div id="teamFixtures" class="team-list">
      <div class="empty-message">A carregar…</div>
    </div>
  </section>

  <div class="match-back">
    <a class="match-link" href="<?= e(url("index.php")) ?>">Voltar</a>
  </div>

</main>

<script>
  window.ZG_TEAM_ID = <?= (int)$teamId ?>;
  window.ZG_CONTEXT_LEAGUE = window.ZG_CONTEXT_LEAGUE || 39;
  window.ZG_CONTEXT_SEASON = window.ZG_CONTEXT_SEASON || 2025;
</script>

<script src="<?= e(url("js/pages/team.js")) ?>?v=<?= time() ?>"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>
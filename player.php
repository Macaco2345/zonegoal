<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit("Jogador inválido.");
}

require __DIR__ . "/includes/layout_start.php";
?>

<link rel="stylesheet" href="<?= e(url("css/pages/player.css")) ?>">

<main class="player-page">

  <div class="player-topbar">
    <div class="player-title">
      <div class="player-ico">🧍</div>
      <div>
        <div class="t1">Jogador</div>
        <div class="t2">Perfil e estatísticas</div>
      </div>
    </div>

    <a class="zg-back" href="<?= e(url("index.php")) ?>">Voltar</a>
  </div>

  <section id="playerHeader" class="zg-card">
    <div class="empty-message">A carregar…</div>
  </section>

  <!-- CONTROL + TABS + CONTENT -->
  <section class="zg-card">
    <div class="zg-card-head player-controls">
      <div class="h1">Estatísticas</div>

      <div class="controls">
        <label class="ctl">
          <span>Competição</span>
          <select id="compSelect" class="zg-select"></select>
        </label>

        <div class="tabs" id="playerTabs">
          <button class="tab active" data-tab="summary" type="button">Resumo</button>
          <button class="tab" data-tab="stats" type="button">Estatísticas</button>
          <button class="tab" data-tab="trophies" type="button">Troféus</button>
          <button class="tab" data-tab="career" type="button">Carreira</button>
        </div>
      </div>
    </div>

    <div id="playerTabContent">
      <div class="empty-message">A carregar…</div>
    </div>
  </section>

</main>

<script>
  window.ZG_PLAYER_ID = <?= (int)$id ?>;
</script>
<script src="<?= e(url("js/pages/player.js")) ?>?v=3"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>
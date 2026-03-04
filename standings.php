<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$leagues = require __DIR__ . "/config/leagues_api_football.php";
$leagues = array_values(array_filter($leagues, fn($l) => !empty($l["id"])));

require __DIR__ . "/includes/layout_start.php";
?>
<link rel="stylesheet" href="<?= e(url("css/pages/standings.css")) ?>">

<div class="standings-page">
  <div class="competition-row" style="margin-top:0;">
    <div class="competition-flag">📊</div>
    <div class="competition-name">Tabelas classificativas</div>
  </div>

  <div class="standings-controls">
    <label class="ctrl">
      <span>Liga</span>
      <select id="leagueSelect">
        <?php foreach ($leagues as $l): ?>
          <option value="<?= (int)$l["id"] ?>"><?= e((string)$l["name"]) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="ctrl">
      <span>Época</span>
      <input id="seasonInput" type="number" value="<?= (int)date("Y") ?>">
    </label>

    <button id="loadBtn" class="zg-btn">Carregar</button>
  </div>

  <div id="standingsArea" class="standings-area">
    <div class="empty-message">A carregar…</div>
  </div>
</div>

<script src="<?= e(url("js/pages/standings.js")) ?>?v=1"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>

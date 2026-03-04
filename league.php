<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$leagues = require __DIR__ . "/config/leagues_api_football.php";
$leagues = array_values(array_filter($leagues, fn($l) => !empty($l["id"])));

$defaultLeague = (int)($leagues[0]["id"] ?? 39);
$defaultSeason = 2025;

// Nome default (para label do dropdown)
$defaultLeagueName = "Liga";
foreach ($leagues as $l) {
  if ((int)$l["id"] === $defaultLeague) { $defaultLeagueName = (string)$l["name"]; break; }
}

require __DIR__ . "/includes/layout_start.php";
?>

<link rel="stylesheet" href="<?= e(url("css/pages/league.css")) ?>">

<script>
  window.ZG_CONTEXT_LEAGUE = <?= (int)$defaultLeague ?>;
  window.ZG_CONTEXT_SEASON = <?= (int)$defaultSeason ?>;
</script>

<div class="league-page">

  <!-- HERO -->
  <div class="lg-hero">
    <div class="lg-hero-left">
      <img id="lgHeroLogo" class="lg-hero-logo ph" src="" alt="" style="display:none;">

      <div>
        <div id="lgHeroTitle" class="lg-hero-title">Liga</div>

        <div class="lg-hero-sub">
          <img id="lgHeroFlag" class="lg-flag" src="" alt="" style="display:none;">
          <span id="lgHeroCountry">—</span>
          <span class="dot">•</span>
          <span>Época <span id="lgHeroSeason"><?= (int)$defaultSeason ?></span></span>
        </div>
      </div>
    </div>

    <div class="lg-hero-right">
      <span class="lg-badge lg-blue">Fixtures</span>
      <span class="lg-badge lg-gold">Top 10</span>
    </div>
  </div>

  <!-- CONTROLS -->
  <div class="lg-controls">

    <!-- Dropdown custom (em vez de select nativo) -->
    <label class="ctrl">
      <span>Liga</span>

      <div class="zg-select" id="leagueDropdown">
        <button class="zg-select-btn" type="button" aria-haspopup="listbox" aria-expanded="false">
          <span id="leagueLabel"><?= e($defaultLeagueName) ?></span>
          <span class="zg-caret"></span>
        </button>

        <div class="zg-select-menu" id="leagueMenu" role="listbox">
          <?php foreach ($leagues as $l): ?>
            <?php $isActive = ((int)$l["id"] === $defaultLeague); ?>
            <button
              class="zg-item <?= $isActive ? "active" : "" ?>"
              type="button"
              data-value="<?= (int)$l["id"] ?>"
            >
              <?= e((string)$l["name"]) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <!-- mantém compatibilidade com JS atual -->
        <input type="hidden" id="leagueSelect" value="<?= (int)$defaultLeague ?>">
      </div>
    </label>

    <label class="ctrl">
      <span>Época</span>
      <input id="seasonInput" type="number" value="<?= (int)$defaultSeason ?>">
    </label>

    <button id="loadBtn" class="zg-btn">Carregar</button>
  </div>

  <!-- TABS -->
  <div class="lg-tabs">
    <button class="tab-btn active" data-tab="next">Próximos 10</button>
    <button class="tab-btn" data-tab="last">Últimos 10</button>
  </div>

  <!-- GRID -->
  <div class="lg-grid">

    <!-- FIXTURES -->
    <div class="lg-panel">
      <div class="lg-panel-title" id="panelLeftTitle">Próximos 10</div>

      <div id="fixturesArea" class="lg-list">
        <div class="empty-message">A carregar…</div>
      </div>
    </div>

    <!-- STANDINGS -->
    <div class="lg-panel">
      <div class="lg-panel-title">Standings (completo)</div>

      <div id="standingsMini" class="empty-message">
        A carregar…
      </div>
    </div>

  </div>
</div>

<script src="<?= e(url("js/pages/league.js")) ?>?v=1"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>
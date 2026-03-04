<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set("display_errors","1");
ini_set("display_startup_errors","1");

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/api/football_service.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { http_response_code(400); exit("Jogo inválido (id em falta)."); }

$match = fetchFixtureById($id);
if (!$match) { http_response_code(404); exit("Jogo não encontrado."); }

$L = $match["league"] ?? [];
$H = $match["teams"]["home"] ?? [];
$A = $match["teams"]["away"] ?? [];
$S = $match["fixture"]["status"] ?? [];
$G = $match["goals"] ?? [];

$statusShort = (string)($S["short"] ?? "");
$isLive = in_array($statusShort, ["1H","HT","2H","ET","P","BT"], true);
$isFinished = in_array($statusShort, ["FT","AET","PEN"], true);

$badgeClass = $isLive ? "match-live" : ($isFinished ? "match-finished" : "match-upcoming");

$ts = (int)($match["fixture"]["timestamp"] ?? 0);
$kickoff = $ts ? date("d/m/Y H:i", $ts) : "";

$ZG_TITLE = (string)($H["name"] ?? "Casa") . " vs " . (string)($A["name"] ?? "Fora") . " — ZoneGoal";
$ZG_CSS   = "css/pages/match.css";

require __DIR__ . "/includes/layout_start.php";
?>

<main class="match-page">

  <div class="competition-row" style="margin-top:0;">
    <?php if (!empty($L["logo"])): ?>
      <img class="competition-logo" src="<?= e((string)$L["logo"]) ?>" alt="">
    <?php else: ?>
      <div class="competition-flag">🏆</div>
    <?php endif; ?>

    <div class="competition-name">
      <?= e((string)($L["name"] ?? "Liga")) ?>
      <?php if (!empty($L["round"])): ?> • <?= e((string)$L["round"]) ?><?php endif; ?>
    </div>
  </div>

  <section class="match-scoreboard">
    <div class="team">
      <?php if (!empty($H["logo"])): ?>
        <img class="team-logo-big" src="<?= e((string)$H["logo"]) ?>" alt="">
      <?php endif; ?>
      <div class="name"><?= e((string)($H["name"] ?? "Casa")) ?></div>
    </div>

    <div class="score-center">
      <div class="goals">
        <span><?= is_null($G["home"] ?? null) ? "-" : (int)$G["home"] ?></span>
        <span class="dash">-</span>
        <span><?= is_null($G["away"] ?? null) ? "-" : (int)$G["away"] ?></span>
      </div>

      <div class="meta">
        <span class="<?= e($badgeClass) ?>"><?= e((string)($S["long"] ?? "—")) ?></span>
        <?php if (!is_null($S["elapsed"] ?? null)): ?>
          <span class="elapsed"><?= (int)$S["elapsed"] ?>'</span>
        <?php endif; ?>
        <?php if ($kickoff): ?>
          <span class="kickoff"><?= e($kickoff) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="team right">
      <div class="name"><?= e((string)($A["name"] ?? "Fora")) ?></div>
      <?php if (!empty($A["logo"])): ?>
        <img class="team-logo-big" src="<?= e((string)$A["logo"]) ?>" alt="">
      <?php endif; ?>
    </div>
  </section>

  <div class="match-tabs">
    <button class="tab-btn active" data-tab="stats" type="button">Estatísticas</button>
    <button class="tab-btn" data-tab="timeline" type="button">Cronologia</button>
    <button class="tab-btn" data-tab="lineups" type="button">Equipa titular</button>
  </div>

  <section id="tab-stats" class="tab-content active">
    <div class="loading">A carregar estatísticas…</div>
  </section>

  <section id="tab-timeline" class="tab-content">
    <div class="loading">A carregar cronologia…</div>
  </section>

  <section id="tab-lineups" class="tab-content lineups-container">
    <div class="loading">A carregar equipa titular…</div>
  </section>

  <div class="match-back">
    <a class="match-link" href="/ZoneGoal/index.php">Voltar</a>
  </div>

</main>

<script>
  window.ZONEGOAL = {
    base: "/ZoneGoal",
    matchId: <?= (int)$id ?>,
    homeId: <?= (int)($H["id"] ?? 0) ?>,
    awayId: <?= (int)($A["id"] ?? 0) ?>,
    season: <?= (int)($L["season"] ?? date("Y")) ?>
  };
</script>

<script src="/ZoneGoal/js/pages/match.js?v=3"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>
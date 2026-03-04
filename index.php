<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";
require_once __DIR__ . "/includes/matches_feed.php";

$leagues = require __DIR__ . "/config/leagues_api_football.php";
$leagues = array_values(array_filter($leagues, fn($l) => !empty($l["id"])));
if (!count($leagues)) die("Configura as ligas em config/leagues_api_football.php");

$feed = zonegoal_build_feed($leagues);

$live     = $feed["live"] ?? [];
$finished = $feed["finished"] ?? [];
$upcoming = $feed["upcoming"] ?? [];

$view = "upcoming";
if (!count($upcoming) && count($live)) $view = "live";
if (!count($upcoming) && !count($live) && count($finished)) $view = "finished";

require __DIR__ . "/includes/layout_start.php";
?>

<div class="subtabs" style="position:static; top:auto; padding:0; margin-bottom:14px;">
  <button type="button" onclick="return false;" class="filter-chip <?= $view==="live" ? "active" : "" ?>" data-view="live">
    Ao Vivo (<?= count($live) ?>)
  </button>

  <button type="button" onclick="return false;" class="filter-chip <?= $view==="finished" ? "active" : "" ?>" data-view="finished">
    Finalizado 24h (<?= count($finished) ?>)
  </button>

  <button type="button" onclick="return false;" class="filter-chip <?= $view==="upcoming" ? "active" : "" ?>" data-view="upcoming">
    Próximos 24h (<?= count($upcoming) ?>)
  </button>
</div>



<main class="match-list">

  <div class="empty-message">
    API-Football • Cache ativo • Dados reais
  </div>

  <section class="match-section" data-section="live" style="<?= $view==="live" ? "display:block" : "display:none" ?>">
    <?php
      if (!count($live)) {
        echo '<div class="empty-message">Sem jogos ao vivo neste momento.</div>';
      } else {
        zonegoal_render_grouped($live, "match-live", "AO VIVO", false);
      }
    ?>
  </section>

  <section class="match-section" data-section="finished" style="<?= $view==="finished" ? "display:block" : "display:none" ?>">
    <?php
      if (!count($finished)) {
        echo '<div class="empty-message">Sem jogos finalizados nas últimas 24h.</div>';
      } else {
        zonegoal_render_grouped($finished, "match-finished", "FINAL", false);
      }
    ?>
  </section>

  <section class="match-section" data-section="upcoming" style="<?= $view==="upcoming" ? "display:block" : "display:none" ?>">
    <?php
      if (!count($upcoming)) {
        echo '<div class="empty-message">Sem jogos nas próximas 24h.</div>';
      } else {
        zonegoal_render_grouped($upcoming, "match-upcoming", "PRÓXIMO", false);
      }
    ?>
  </section>

</main>

<div id="toast" class="toast" style="display:none;"></div>

<script>
  window.ZONEGOAL_LOGGED_IN = <?= is_logged_in() ? "true" : "false" ?>;
</script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>

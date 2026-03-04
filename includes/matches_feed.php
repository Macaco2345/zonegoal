<?php
declare(strict_types=1);

/**
 * ZoneGoal - matches_feed.php
 * Núcleo do feed de jogos (reutilizável)
 */

require_once __DIR__ . "/../api/football_service.php";

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

/**
 * Constrói o feed completo para um conjunto de ligas
 */
function zonegoal_build_feed(array $leagues, string $timezone = "Europe/Lisbon"): array
{
  $now = time();
  $from = date("Y-m-d", $now - 86400); // -24h
  $to   = date("Y-m-d", $now + 86400); // +24h

  $live = [];
  $finished = [];
  $upcoming = [];

  foreach ($leagues as $lg) {
    $leagueId = (int)($lg["id"] ?? 0);
    if ($leagueId <= 0) continue;

    // Live (TTL curto)
    $liveItems = fetchLeagueLive($leagueId, $timezone);

    // Intervalo (-24h a +24h)
    $rangeItems = fetchLeagueFixturesRange($leagueId, $from, $to, $timezone);

    // Juntar e remover duplicates pelo fixture id
    $byId = [];

    foreach (array_merge($liveItems, $rangeItems) as $fx) {
      if (!is_array($fx)) continue;
      $fid = (int)($fx["fixture"]["id"] ?? 0);
      if ($fid <= 0) continue;
      $byId[$fid] = $fx;
    }

    foreach ($byId as $fx) {
      $statusShort = (string)($fx["fixture"]["status"]["short"] ?? "");
      $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

      $isLive = in_array($statusShort, ["1H","HT","2H","ET","P","BT"], true);
      $isFinished = in_array($statusShort, ["FT","AET","PEN"], true);

      if ($isLive) {
        $live[] = $fx;
        continue;
      }

      if ($isFinished) {
        // terminou nas últimas 24h
        if ($ts >= ($now - 86400)) $finished[] = $fx;
        continue;
      }

      // Próximos 24h (inclui NS/TBD/etc)
      if ($ts > 0 && $ts <= ($now + 86400) && $ts >= ($now - 3600)) {
        $upcoming[] = $fx;
      }
    }
  }

  // Ordenar por timestamp
  $sortByTs = function(array $a, array $b): int {
    return ((int)($a["fixture"]["timestamp"] ?? 0)) <=> ((int)($b["fixture"]["timestamp"] ?? 0));
  };

  usort($live, $sortByTs);
  usort($finished, $sortByTs);
  usort($upcoming, $sortByTs);

  return [
    "live" => $live,
    "finished" => $finished,
    "upcoming" => $upcoming,
  ];
}

/**
 * Agrupa fixtures por liga/competição
 */
function zonegoal_group_by_league(array $fixtures): array
{
  $groups = [];

  foreach ($fixtures as $fx) {
    if (!is_array($fx)) continue;

    $lid = (int)($fx["league"]["id"] ?? 0);
    $lname = (string)($fx["league"]["name"] ?? "Liga");
    $logo = (string)($fx["league"]["logo"] ?? "");

    $key = $lid > 0 ? (string)$lid : (string)crc32($lname);

    if (!isset($groups[$key])) {
      $groups[$key] = [
        "league_id" => $lid,
        "league_name" => $lname,
        "league_logo" => $logo,
        "items" => [],
      ];
    }

    $groups[$key]["items"][] = $fx;
  }

  return $groups;
}

/**
 * Renderiza fixtures agrupados por competição
 * ✅ Aceita 4º argumento para compatibilidade (index.php antigo)
 */
function zonegoal_render_grouped(
  array $fixtures,
  string $badgeClass,
  string $badgeLabel,
  bool $unused = false
): void
{
  $groups = zonegoal_group_by_league($fixtures);

  foreach ($groups as $g) {
    // Header da competição
    echo '<div class="competition-row">';

    if (!empty($g["league_logo"])) {
      echo '<img class="competition-logo" src="'.h((string)$g["league_logo"]).'" alt="">';
    } else {
      echo '<div class="competition-flag">🏆</div>';
    }

    echo '<div class="competition-name">'.h((string)$g["league_name"]).'</div>';
    echo '</div>';

    // Jogos
    foreach ($g["items"] as $fx) {
      zonegoal_render_match_row($fx, $badgeClass, $badgeLabel);
    }
  }
}

/**
 * Renderiza uma linha de jogo (match-row)
 */
function zonegoal_render_match_row(array $fx, string $badgeClass, string $badgeLabel): void
{
  $fixtureId = (int)($fx["fixture"]["id"] ?? 0);
  $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

  $time = $ts ? date("H:i", $ts) : "--:--";

  $homeName = (string)($fx["teams"]["home"]["name"] ?? "Casa");
  $awayName = (string)($fx["teams"]["away"]["name"] ?? "Fora");

  $homeLogo = (string)($fx["teams"]["home"]["logo"] ?? "");
  $awayLogo = (string)($fx["teams"]["away"]["logo"] ?? "");

  $gHome = $fx["goals"]["home"] ?? null;
  $gAway = $fx["goals"]["away"] ?? null;

  echo '<div class="match-row">';

  // hora + badge
  echo '<div class="match-time">';
  echo '<div class="match-kickoff">'.h($time).'</div>';
  echo '<span class="'.h($badgeClass).'">'.h($badgeLabel).'</span>';
  echo '</div>';

  // equipas
  echo '<div class="match-teams">';

  echo '<div class="team-row">';
  if ($homeLogo) echo '<img class="team-logo" src="'.h($homeLogo).'" alt="">';
  echo '<div class="team-name">'.h($homeName).'</div>';
  echo '</div>';

  echo '<div class="team-row">';
  if ($awayLogo) echo '<img class="team-logo" src="'.h($awayLogo).'" alt="">';
  echo '<div class="team-name">'.h($awayName).'</div>';
  echo '</div>';

  echo '</div>';

  // score
  echo '<div class="match-score">';
  echo '<div class="score-number">'.(is_null($gHome) ? "-" : (string)(int)$gHome).'</div>';
  echo '<div class="score-number">'.(is_null($gAway) ? "-" : (string)(int)$gAway).'</div>';
  echo '</div>';

  // ações
  echo '<div class="match-actions">';
  echo '<a class="match-link" href="match.php?id='.$fixtureId.'">Detalhes</a>';

  // ⭐ aparece sempre
$isFav = isset($GLOBALS["zonegoal_favorites"]) && in_array($fixtureId, (array)$GLOBALS["zonegoal_favorites"], true);

$icon = $isFav ? "⭐" : "☆";
$cls  = $isFav ? "favorite-btn active" : "favorite-btn";

echo '<button class="'.$cls.'" type="button" data-match-id="'.$fixtureId.'" title="Favorito">'.$icon.'</button>';

  echo '</div>';

  echo '</div>';
}

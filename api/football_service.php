<?php
declare(strict_types=1);

require_once __DIR__ . "/api_football.php";

function currentSeasonForLeague(int $leagueId): ?int {
  $data = apisports_get("leagues", ["id" => $leagueId], 86400);
  $resp = $data["response"][0] ?? null;
  if (!is_array($resp)) return null;

  $seasons = $resp["seasons"] ?? null;
  if (!is_array($seasons)) return null;

  foreach ($seasons as $s) {
    if (is_array($s) && !empty($s["current"]) && !empty($s["year"])) {
      return (int)$s["year"];
    }
  }

  $last = end($seasons);
  return (is_array($last) && !empty($last["year"])) ? (int)$last["year"] : null;
}

function fetchLeagueLive(int $leagueId, string $timezone = "Europe/Lisbon"): array {
  $season = currentSeasonForLeague($leagueId);

  $params = [
    "live" => "all",
    "league" => $leagueId,
    "timezone" => $timezone,
  ];
  if ($season !== null) $params["season"] = $season;

  $data = apisports_get("fixtures", $params, 60);
  return $data["response"] ?? [];
}

function fetchLeagueFixturesRange(int $leagueId, string $from, string $to, string $timezone = "Europe/Lisbon"): array {
  $season = currentSeasonForLeague($leagueId);

  $params = [
    "league" => $leagueId,
    "from" => $from,
    "to" => $to,
    "timezone" => $timezone,
  ];
  if ($season !== null) $params["season"] = $season;

  $data = apisports_get("fixtures", $params, 300);
  return $data["response"] ?? [];
}

function fetchFixtureById(int $fixtureId, string $timezone = "Europe/Lisbon"): ?array {
  $data = apisports_get("fixtures", ["id" => $fixtureId, "timezone" => $timezone], 60);
  $resp = $data["response"][0] ?? null;
  return is_array($resp) ? $resp : null;
}

function fetchFixtureStats(int $fixtureId): array {
  $data = apisports_get("fixtures/statistics", ["fixture" => $fixtureId], 60);
  return $data["response"] ?? [];
}

function fetchFixtureEvents(int $fixtureId): array {
  $data = apisports_get("fixtures/events", ["fixture" => $fixtureId], 60);
  return $data["response"] ?? [];
}
function fetchStandings(int $leagueId, int $season): array {
  $data = apisports_get("standings", ["league"=>$leagueId, "season"=>$season], 3600);
  $league = $data["response"][0]["league"] ?? null;
  if (!is_array($league)) return [];

  $rows = $league["standings"][0] ?? [];
  return is_array($rows) ? $rows : [];
}
function fetchLeagueNextFixtures(int $leagueId, int $season, string $timezone = "Europe/Lisbon", int $n = 10): array {
  $data = apisports_get("fixtures", [
    "league" => $leagueId,
    "season" => $season,
    "next" => $n,
    "timezone" => $timezone,
  ], 120);
  return $data["response"] ?? [];
}

function fetchLeagueLastFixtures(int $leagueId, int $season, string $timezone = "Europe/Lisbon", int $n = 10): array {
  $data = apisports_get("fixtures", [
    "league" => $leagueId,
    "season" => $season,
    "last" => $n,
    "timezone" => $timezone,
  ], 300);
  return $data["response"] ?? [];
}
function fetchFixtureLineups(int $fixtureId): array {
  $r = apisports_get("/fixtures/lineups", ["fixture" => $fixtureId]);
  return $r["response"] ?? [];
}



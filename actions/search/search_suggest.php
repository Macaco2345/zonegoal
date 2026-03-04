<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors", "0");

require_once __DIR__ . "/../../api/api_football.php";

function reply(array $json, int $code=200): void {
  http_response_code($code);
  echo json_encode($json, JSON_UNESCAPED_UNICODE);
  exit;
}

$q = trim((string)($_GET["q"] ?? ""));
$league = (int)($_GET["league"] ?? 0);
$season = (int)($_GET["season"] ?? 0);

if (mb_strlen($q) < 2) {
  reply(["ok"=>true,"teams"=>[],"players"=>[]]);
}

if ($season <= 0) $season = (int)date("Y");

try {
  // ===== TEAMS =====
  // 1) tenta com season (rápido/filtrado)
  $teams = apisports_get("teams", [
    "search" => $q,
    "season" => $season
  ], 3600);

  $teamOut = array_slice(($teams["response"] ?? []), 0, 6);
  $teamOut = array_map(fn($x) => $x["team"] ?? $x, $teamOut);

  // 2) fallback: se vier vazio, tenta SEM season (muitas vezes resolve)
  if (empty($teamOut)) {
    $teams2 = apisports_get("teams", [
      "search" => $q
    ], 3600);

    $teamOut = array_slice(($teams2["response"] ?? []), 0, 6);
    $teamOut = array_map(fn($x) => $x["team"] ?? $x, $teamOut);
  }

  // ===== PLAYERS =====
  // Se não houver league, faz search genérico
  $playerParams = ["search"=>$q, "season"=>$season, "page"=>1];
  if ($league > 0) $playerParams["league"] = $league;

  $players = apisports_get("players", $playerParams, 300);

  $playerOut = array_slice(($players["response"] ?? []), 0, 6);
  $playerOut = array_map(fn($x) => $x["player"] ?? $x, $playerOut);

  reply(["ok"=>true,"teams"=>$teamOut,"players"=>$playerOut,"season"=>$season]);

} catch (Throwable $e) {
  reply(["ok"=>false,"error"=>"Erro a pesquisar"], 500);
}
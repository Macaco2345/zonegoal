<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors","0");

require_once __DIR__ . "/../../api/api_football.php";

function reply(array $json, int $code=200): void {
  http_response_code($code);
  echo json_encode($json, JSON_UNESCAPED_UNICODE);
  exit;
}

$id     = (int)($_GET["id"] ?? 0);
$league = (int)($_GET["league"] ?? 0);
$season = (int)($_GET["season"] ?? 0);

if ($id <= 0) reply(["ok"=>false,"error"=>"ID inválido"], 400);
if ($season <= 0) $season = (int)date("Y");

try {
  // TEAM (tenta com league/season se vierem)
  $params = ["id" => $id];
  if ($league > 0) $params["league"] = $league;
  if ($season > 0) $params["season"] = $season;

  $teamResp = apisports_get("teams", $params, 86400);
  $teamObj = $teamResp["response"][0] ?? null;

  // fallback se a API não devolveu com league/season
  if (!$teamObj) {
    $teamResp2 = apisports_get("teams", ["id"=>$id], 86400);
    $teamObj = $teamResp2["response"][0] ?? null;
  }

  if (!$teamObj) reply(["ok"=>false,"error"=>"Equipa não encontrada"], 200);

  // SQUAD
  $sq = apisports_get("players/squads", ["team"=>$id], 86400);
  $squad = $sq["response"][0]["players"] ?? [];

  reply([
    "ok" => true,
    "team" => $teamObj,     // contém team + venue
    "squad" => $squad
  ]);

} catch (Throwable $e) {
  reply(["ok"=>false,"error"=>"Erro team_data"], 500);
}
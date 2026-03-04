<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors","0");

require_once __DIR__ . "/../../api/api_football.php"; // ✅ certo

$q = trim((string)($_GET["q"] ?? ""));
$type = (string)($_GET["type"] ?? "teams"); // teams | players

if ($q === "" || mb_strlen($q) < 2) {
  echo json_encode(["ok"=>true, "data"=>[]]);
  exit;
}

function out(array $x, int $code=200): void {
  http_response_code($code);
  echo json_encode($x);
  exit;
}

try {

  /* =========================
     EQUIPAS (funciona direto)
     ========================= */
  if ($type === "teams") {
    $j = apisports_get("teams", ["search"=>$q], 1800);
    out([
      "ok" => true,
      "data" => $j["response"] ?? [],
      "errors" => $j["errors"] ?? null
    ]);
  }

  /* =========================
     JOGADORES (API exige league/team)
     Vamos procurar pelas ligas do teu projeto
     ========================= */
  if ($type === "players") {

    // ligas do teu config
    $leagues = require __DIR__ . "/../config/leagues_api_football.php";
    $leagueIds = [];
    foreach ($leagues as $l) {
      $id = (int)($l["id"] ?? 0);
      if ($id > 0) $leagueIds[] = $id;
    }

    if (!$leagueIds) {
      out(["ok"=>false, "error"=>"Sem ligas configuradas."], 500);
    }

    $current = (int)date("Y");
    $seasons = [$current, $current - 1]; // suficiente na maioria dos casos

    $results = [];
    $usedSeason = null;
    $usedLeague = null;
    $lastErrors = null;

    // Para performance: cache um pouco (20min)
    $ttl = 1200;

    foreach ($seasons as $season) {
      foreach ($leagueIds as $leagueId) {

        $j = apisports_get("players", [
          "search" => $q,
          "league" => $leagueId,
          "season" => $season
        ], $ttl);

        $resp = $j["response"] ?? [];
        $lastErrors = $j["errors"] ?? $lastErrors;

        if (is_array($resp) && count($resp) > 0) {
          $results = $resp;
          $usedSeason = $season;
          $usedLeague = $leagueId;
          break 2; // achou, sai logo
        }
      }
    }

    out([
      "ok" => true,
      "season" => $usedSeason,
      "league" => $usedLeague,
      "data" => $results,
      "errors" => $lastErrors
    ]);
  }

  out(["ok"=>false, "error"=>"Tipo inválido"], 400);

} catch (Throwable $e) {
  out(["ok"=>false, "error"=>"Erro interno"], 500);
}

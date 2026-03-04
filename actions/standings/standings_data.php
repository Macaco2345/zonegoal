<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

// ⚠️ Evita estragar JSON com warnings/notices em output
error_reporting(E_ALL);
ini_set("display_errors", "0");

require_once __DIR__ . "/../../api/football_service.php";

$league = (int)($_GET["league"] ?? 0);
$season = (int)($_GET["season"] ?? 0);

function reply(array $json, int $code = 200): void {
  http_response_code($code);
  echo json_encode($json, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($league <= 0) {
  reply(["ok" => false, "error" => "Liga inválida"], 400);
}

// se season não vier, usa current
if ($season <= 0) {
  $season = currentSeasonForLeague($league) ?? (int)date("Y");
}

try {
  $json = apisports_get("standings", ["league" => $league, "season" => $season], 3600);
  $leagueObj = $json["response"][0]["league"] ?? null;

  // fallback: season-1 (muito comum)
  if (!is_array($leagueObj) || empty($leagueObj["standings"])) {
    $try = $season - 1;
    $json2 = apisports_get("standings", ["league" => $league, "season" => $try], 3600);
    $leagueObj = $json2["response"][0]["league"] ?? null;
    $season = $try;
  }

  if (!is_array($leagueObj) || empty($leagueObj["standings"])) {
    reply(["ok" => false, "error" => "Sem standings para esta liga/época.", "season_used" => $season], 200);
  }

  reply([
    "ok" => true,
    "meta" => [
      "league" => (string)($leagueObj["name"] ?? ""),
      "country"=> (string)($leagueObj["country"] ?? ""),
      "logo"   => (string)($leagueObj["logo"] ?? ""),
      "season" => $season,
      "groups" => count($leagueObj["standings"] ?? []),
    ],
    "data" => $leagueObj["standings"] ?? []
  ]);

} catch (Throwable $e) {
  reply(["ok" => false, "error" => "Erro standings"], 500);
}
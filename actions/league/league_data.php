<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors", "1");

require_once __DIR__ . "/../api/football_service.php";

$leagueId = (int)($_GET["league"] ?? 0);
if ($leagueId <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Liga inválida"]);
  exit;
}

try {
  // meta da liga
  $j = apisports_get("leagues", ["id"=>$leagueId], 86400);
  $resp = $j["response"][0] ?? null;

  if (!is_array($resp) || empty($resp["league"])) {
    echo json_encode(["ok"=>false,"error"=>"Liga não encontrada","errors"=>$j["errors"] ?? null], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $league = $resp["league"] ?? [];
  $country = $resp["country"] ?? [];

  $season = currentSeasonForLeague($leagueId) ?? (int)date("Y");

  echo json_encode([
    "ok" => true,
    "meta" => [
      "id" => $leagueId,
      "name" => (string)($league["name"] ?? "Liga"),
      "logo" => (string)($league["logo"] ?? ""),
      "type" => (string)($league["type"] ?? ""),
      "country" => (string)($country["name"] ?? ""),
      "country_code" => (string)($country["code"] ?? ""), // às vezes vem vazio
      "flag" => (string)($country["flag"] ?? ""),
      "season" => $season,
    ],
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"Erro interno"]);
}

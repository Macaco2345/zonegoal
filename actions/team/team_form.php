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

$team   = (int)($_GET["team"] ?? 0);
$league = (int)($_GET["league"] ?? 0);
$season = (int)($_GET["season"] ?? 0);
$limit  = (int)($_GET["limit"] ?? 5);

if ($team <= 0) reply(["ok"=>false,"error"=>"Team inválido"], 400);
if ($season <= 0) $season = (int)date("Y");
if ($limit <= 0 || $limit > 10) $limit = 5;

try {
  $params = ["team"=>$team, "season"=>$season, "last"=>$limit];
  if ($league > 0) $params["league"] = $league;

  $j = apisports_get("fixtures", $params, 300);
  $list = $j["response"] ?? [];

  if (!$list && $league > 0) {
    $j2 = apisports_get("fixtures", ["team"=>$team,"season"=>$season,"last"=>$limit], 300);
    $list = $j2["response"] ?? [];
  }

  $form = [];
  foreach ($list as $fx) {
    $fixtureId = (int)($fx["fixture"]["id"] ?? 0);
    $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

    $homeId = (int)($fx["teams"]["home"]["id"] ?? 0);
    $awayId = (int)($fx["teams"]["away"]["id"] ?? 0);

    $hg = $fx["goals"]["home"] ?? null;
    $ag = $fx["goals"]["away"] ?? null;

    if (!is_numeric($hg) || !is_numeric($ag)) continue;
    $hg = (int)$hg; $ag = (int)$ag;

    // W/D/L do ponto de vista do team
    $res = "D";
    if ($homeId === $team) {
      if ($hg > $ag) $res="W";
      elseif ($hg < $ag) $res="L";
    } elseif ($awayId === $team) {
      if ($ag > $hg) $res="W";
      elseif ($ag < $hg) $res="L";
    } else continue;

    // adversário
    if ($homeId === $team) {
      $oppName = (string)($fx["teams"]["away"]["name"] ?? "");
      $oppLogo = (string)($fx["teams"]["away"]["logo"] ?? "");
    } else {
      $oppName = (string)($fx["teams"]["home"]["name"] ?? "");
      $oppLogo = (string)($fx["teams"]["home"]["logo"] ?? "");
    }

    $form[] = [
      "r" => $res,
      "id" => $fixtureId,
      "ts" => $ts,
      "hs" => $hg,
      "as" => $ag,
      "home" => (string)($fx["teams"]["home"]["name"] ?? ""),
      "away" => (string)($fx["teams"]["away"]["name"] ?? ""),
      "oppName" => $oppName,
      "oppLogo" => $oppLogo,
    ];
  }

  reply(["ok"=>true,"form"=>$form,"season"=>$season]);
} catch (Throwable $e) {
  reply(["ok"=>false,"error"=>"Erro a carregar forma"], 500);
}
<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../api/api_football.php";

$team = (int)($_GET["team"] ?? 0);
$season = (int)($_GET["season"] ?? 0);

if ($team <= 0) { echo json_encode(["ok"=>false,"error"=>"Team inválido"]); exit; }
if ($season <= 0) $season = (int)date("Y");

$resp = apisports_get("leagues", [
  "team" => $team,
  "season" => $season
], 3600);

$leagues = $resp["response"] ?? [];

/**
 * Normaliza e remove duplicados (mesma liga)
 */
$out = [];
$seen = [];
foreach ($leagues as $x){
  $lg = $x["league"] ?? [];
  $ct = $x["country"] ?? [];
  $id = (int)($lg["id"] ?? 0);
  if ($id <= 0) continue;
  if (isset($seen[$id])) continue;
  $seen[$id] = true;

  $out[] = [
    "id" => $id,
    "name" => (string)($lg["name"] ?? ""),
    "type" => (string)($lg["type"] ?? ""), // League / Cup
    "logo" => (string)($lg["logo"] ?? ""),
    "country" => (string)($ct["name"] ?? ""),
    "flag" => (string)($ct["flag"] ?? ""),
    "season" => $season,
  ];
}

echo json_encode(["ok"=>true,"data"=>$out], JSON_UNESCAPED_UNICODE);
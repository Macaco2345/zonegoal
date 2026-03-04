<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../api/api_football.php";

$id     = (int)($_GET["id"] ?? 0);
$league = (int)($_GET["league"] ?? 0);   // pode ser 0 (todas)
$season = (int)($_GET["season"] ?? 0);
$mode   = (string)($_GET["mode"] ?? "next"); // next | last

if ($id <= 0) { echo json_encode(["ok"=>false,"error"=>"ID inválido"]); exit; }
if ($season <= 0) $season = (int)date("Y");
if ($mode !== "next" && $mode !== "last") $mode = "next";

$params = [
  "team" => $id,
  "season" => $season,
  "timezone" => "Europe/Lisbon",
];

// se vier league, filtra nessa competição
if ($league > 0) $params["league"] = $league;

// próximos ou últimos 10
$params[($mode === "last") ? "last" : "next"] = 10;

$data = apisports_get("fixtures", $params, 300); // cache 5 min
echo json_encode(["ok"=>true,"data"=>($data["response"] ?? [])], JSON_UNESCAPED_UNICODE);
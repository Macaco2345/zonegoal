<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors","1");

require_once __DIR__ . "/../../api/api_football.php";

$teamId = (int)($_GET["id"] ?? 0);
$season = (int)($_GET["season"] ?? 0);
if ($teamId <= 0) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"ID inválido"]); exit; }
if ($season <= 0) $season = (int)date("Y");

$j = apisports_get("players", ["team"=>$teamId, "season"=>$season], 21600);
echo json_encode(["ok"=>true,"season"=>$season,"data"=>$j["response"] ?? [],"errors"=>$j["errors"] ?? null], JSON_UNESCAPED_UNICODE);

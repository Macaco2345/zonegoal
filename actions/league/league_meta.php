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

$league = (int)($_GET["league"] ?? 0);
$season = (int)($_GET["season"] ?? 0);
if ($league <= 0) reply(["ok"=>false,"error"=>"Liga inválida"], 400);
if ($season <= 0) $season = (int)date("Y");

try {
  $j = apisports_get("leagues", ["id"=>$league, "season"=>$season], 86400);
  $row = $j["response"][0] ?? null;
  if (!$row) reply(["ok"=>false,"error"=>"Liga não encontrada"], 200);

  $lg = $row["league"] ?? [];
  $ct = $row["country"] ?? [];

  reply([
    "ok"=>true,
    "meta"=>[
      "league_id"=>$league,
      "season"=>$season,
      "name"=>(string)($lg["name"] ?? ""),
      "logo"=>(string)($lg["logo"] ?? ""),
      "country"=>(string)($ct["name"] ?? ""),
      "flag"=>(string)($ct["flag"] ?? ""),
    ]
  ]);
} catch (Throwable $e) {
  reply(["ok"=>false,"error"=>"Erro league_meta"], 500);
}
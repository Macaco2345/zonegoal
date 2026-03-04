<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors","1");

require_once __DIR__ . "/../../api/football_service.php";

$id = (int)($_GET["id"] ?? 0);
$season = (int)($_GET["season"] ?? 0);

if ($id <= 0) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"ID inválido"]); exit; }
if ($season <= 0) $season = (int)date("Y");

$seasons = [$season, $season-1, $season-2, $season-3];
$lastErrors = null;

foreach ($seasons as $sy) {
  if ($sy < 2000) continue;

  $j = apisports_get("players", ["id"=>$id, "season"=>$sy], 21600);
  $resp = $j["response"] ?? [];
  $lastErrors = $j["errors"] ?? $lastErrors;

  if (is_array($resp) && count($resp) > 0) {
    $item = $resp[0] ?? null;
    if (is_array($item) && !empty($item["player"])) {
      echo json_encode([
        "ok" => true,
        "season" => $sy,
        "player" => $item["player"],
        "statistics" => $item["statistics"] ?? []
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }
}

echo json_encode([
  "ok"=>false,
  "error"=>"Sem dados do jogador (épocas testadas).",
  "seasons_tried"=>$seasons,
  "errors"=>$lastErrors
], JSON_UNESCAPED_UNICODE);

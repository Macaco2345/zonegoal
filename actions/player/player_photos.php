<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set("display_errors", "1");

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../api/football_service.php";

// Espera POST: player_ids (json array) e season (int)
$rawIds = $_POST["player_ids"] ?? "[]";
$season = isset($_POST["season"]) ? (int)$_POST["season"] : 0;

$ids = json_decode((string)$rawIds, true);
if (!is_array($ids)) $ids = [];

$ids = array_values(array_unique(array_filter(array_map(fn($x) => (int)$x, $ids), fn($x) => $x > 0)));

if ($season <= 0) {
  echo json_encode(["ok" => false, "error" => "season inválida"], JSON_UNESCAPED_UNICODE);
  exit;
}

if (count($ids) === 0) {
  echo json_encode(["ok" => true, "photos" => new stdClass()], JSON_UNESCAPED_UNICODE);
  exit;
}

// Limite de segurança (para não fazer pedidos infinitos)
$ids = array_slice($ids, 0, 40);

$photos = [];

foreach ($ids as $pid) {
  // TTL alto porque foto não muda muito
  $data = apisports_get("players", ["id" => $pid, "season" => $season], 86400);

  $resp = $data["response"][0]["player"]["photo"] ?? null;
  if (is_string($resp) && $resp !== "") {
    $photos[(string)$pid] = $resp;
  }
}

echo json_encode(["ok" => true, "photos" => $photos], JSON_UNESCAPED_UNICODE);

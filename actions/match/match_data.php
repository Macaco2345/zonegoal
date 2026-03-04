<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set("display_errors", "1");

header("Content-Type: application/json; charset=utf-8");

require_once dirname(__DIR__, 2) . "/api/api_football.php";

$fixtureId = (int)($_GET["id"] ?? 0);
$type = (string)($_GET["type"] ?? "");

$allowed = ["stats", "events", "lineups"];
if ($fixtureId <= 0 || !in_array($type, $allowed, true)) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Pedido inválido"], JSON_UNESCAPED_UNICODE);
  exit;
}

$endpoint = "";
$ttl = 60;

if ($type === "stats") {
  $endpoint = "fixtures/statistics";
  $ttl = 60;
} elseif ($type === "events") {
  $endpoint = "fixtures/events";
  $ttl = 60;
} else { // lineups
  $endpoint = "fixtures/lineups";
  $ttl = 120;
}

$data = apisports_get($endpoint, ["fixture" => $fixtureId], $ttl);

echo json_encode([
  "ok" => true,
  "endpoint" => $endpoint,
  "fixture" => $fixtureId,
  "results" => $data["results"] ?? null,
  "errors" => $data["errors"] ?? null,
  "response_count" => is_array($data["response"] ?? null) ? count($data["response"]) : 0,
  "data" => $data["response"] ?? [],
], JSON_UNESCAPED_UNICODE);
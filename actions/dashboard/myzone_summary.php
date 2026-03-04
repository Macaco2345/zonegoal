<?php
declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../db/db.php";
require_once __DIR__ . "/../../api/football_service.php";

function jexit(array $a, int $code=200): void {
  http_response_code($code);
  echo json_encode($a, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION["id_users"])) {
  jexit(["ok"=>false,"error"=>"Não autenticado"], 401);
}

$userId = (int)$_SESSION["id_users"];

$ids = [];
$stmt = $conn->prepare("SELECT match_id FROM favorites_matches WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($mid);
while ($stmt->fetch()) $ids[] = (int)$mid;
$stmt->close();

$total = count($ids);
$live = 0;
$today = 0;
$nextTs = null;
$now = time();
$start = strtotime(date("Y-m-d 00:00:00"));
$end = strtotime(date("Y-m-d 23:59:59"));

foreach ($ids as $id) {
  $fx = fetchFixtureById($id);
  if (!$fx || !isset($fx["fixture"])) continue;

  $short = (string)($fx["fixture"]["status"]["short"] ?? "");
  $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

  if (in_array($short, ["1H","2H","HT","ET","P","BT","LIVE"], true)) $live++;
  if ($ts >= $start && $ts <= $end) $today++;

  if ($ts >= $now && ($nextTs === null || $ts < $nextTs)) $nextTs = $ts;
}

jexit([
  "ok"=>true,
  "kpis"=>[
    "favorites"=>$total,
    "live"=>$live,
    "today"=>$today,
    "nextTs"=>$nextTs
  ]
]);
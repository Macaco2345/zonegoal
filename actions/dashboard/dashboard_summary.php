<?php
declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../db/db.php";
require_once __DIR__ . "/../../api/football_service.php";

function jexit(array $arr, int $code = 200): void {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION["id_users"])) {
  jexit(["ok" => false, "error" => "Não autenticado"], 401);
}

$userId = (int)$_SESSION["id_users"];

// total favoritos
$stmt = $conn->prepare("SELECT COUNT(*) FROM favorites_matches WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($favCount);
$stmt->fetch();
$stmt->close();

// ids favoritos
$ids = [];
$stmt = $conn->prepare("SELECT match_id FROM favorites_matches WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($mid);
while ($stmt->fetch()) $ids[] = (int)$mid;
$stmt->close();

$live = 0;
$today = 0;
$next = null;

$now = time();
$startOfDay = strtotime(date("Y-m-d 00:00:00"));
$endOfDay   = strtotime(date("Y-m-d 23:59:59"));

foreach ($ids as $id) {
  $fx = fetchFixtureById($id);
  if (!$fx || !isset($fx["fixture"])) continue;

  $short = (string)($fx["fixture"]["status"]["short"] ?? "");
  $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

  // ao vivo
  if (in_array($short, ["1H","2H","HT","ET","P","BT","LIVE"], true)) {
    $live++;
  }

  // hoje
  if ($ts >= $startOfDay && $ts <= $endOfDay) {
    $today++;
  }

  // próximo jogo (ts >= agora)
  if ($ts >= $now) {
    if (!$next || $ts < (int)($next["fixture"]["timestamp"] ?? PHP_INT_MAX)) {
      $next = $fx;
    }
  }
}

jexit([
  "ok" => true,
  "summary" => [
    "favorites" => (int)$favCount,
    "live" => (int)$live,
    "today" => (int)$today,
    "next" => $next, // pode ser null
  ]
]);
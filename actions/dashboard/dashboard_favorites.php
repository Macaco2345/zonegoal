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
$tab = (string)($_GET["tab"] ?? "live"); // live|next|finished

$ids = [];
$stmt = $conn->prepare("SELECT match_id FROM favorites_matches WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($mid);
while ($stmt->fetch()) $ids[] = (int)$mid;
$stmt->close();

$rows = [];
$now = time();

foreach ($ids as $id) {
  $fx = fetchFixtureById($id);
  if (!$fx || !isset($fx["fixture"])) continue;

  $short = (string)($fx["fixture"]["status"]["short"] ?? "");
  $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

  $isLive = in_array($short, ["1H","2H","HT","ET","P","BT","LIVE"], true);
  $isFinished = in_array($short, ["FT","AET","PEN"], true);
  $isNext = ($ts >= $now) && !$isLive && !$isFinished;

  if ($tab === "live" && !$isLive) continue;
  if ($tab === "finished" && !$isFinished) continue;
  if ($tab === "next" && !$isNext) continue;

  $rows[] = $fx;
}

usort($rows, function($a,$b){
  return (int)($a["fixture"]["timestamp"] ?? 0) <=> (int)($b["fixture"]["timestamp"] ?? 0);
});

jexit(["ok" => true, "fixtures" => $rows]);
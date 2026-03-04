<?php
declare(strict_types=1);

require_once __DIR__ . "/../../includes/bootstrap.php";
require_once __DIR__ . "/../../api/api_football.php";

header("Content-Type: application/json; charset=utf-8");

$playerId = (int)($_GET["id"] ?? 0);
if ($playerId <= 0) {
  echo json_encode(["ok" => false, "error" => "Jogador inválido."]);
  exit;
}

try {
  // API-Football: trophies?player={id}
  $data = apisports_get("trophies", [
    "player" => $playerId
  ], 60 * 60 * 24); // 24h cache

  $rows = $data["response"] ?? [];
  if (!is_array($rows)) $rows = [];

  $out = [];
  foreach ($rows as $r) {
    $league = trim((string)($r["league"] ?? ""));
    $country = trim((string)($r["country"] ?? ""));
    $season = trim((string)($r["season"] ?? ""));
    $place = trim((string)($r["place"] ?? "")); // Winner / Runner-up etc

    if ($league === "") continue;

    $out[] = [
      "competition" => $league,
      "country" => $country,
      "season" => $season,
      "place" => $place
    ];
  }

  echo json_encode([
    "ok" => true,
    "player_id" => $playerId,
    "trophies" => $out
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
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
  // API-Football: transfers?player={id}
  $data = apisports_get("transfers", [
    "player" => $playerId
  ], 60 * 60 * 24 * 7); // 7 dias cache

  $resp = $data["response"] ?? [];
  if (!is_array($resp) || !count($resp)) {
    echo json_encode(["ok" => true, "player_id" => $playerId, "career" => []], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Normalmente vem: response[0].transfers[]
  $transfers = $resp[0]["transfers"] ?? [];
  if (!is_array($transfers)) $transfers = [];

  $out = [];
  foreach ($transfers as $t) {
    $date = trim((string)($t["date"] ?? ""));
    $type = trim((string)($t["type"] ?? "")); // Transfer / Loan / Free / etc

    $from = $t["teams"]["out"] ?? [];
    $to   = $t["teams"]["in"] ?? [];

    $fromId = (int)($from["id"] ?? 0);
    $toId   = (int)($to["id"] ?? 0);

    $out[] = [
      "date" => $date,
      "type" => $type,
      "from" => [
        "id" => $fromId,
        "name" => (string)($from["name"] ?? ""),
        "logo" => (string)($from["logo"] ?? "")
      ],
      "to" => [
        "id" => $toId,
        "name" => (string)($to["name"] ?? ""),
        "logo" => (string)($to["logo"] ?? "")
      ],
    ];
  }

  echo json_encode([
    "ok" => true,
    "player_id" => $playerId,
    "career" => $out
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
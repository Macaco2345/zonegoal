<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../api/api_football.php";

$team   = (int)($_GET["team"] ?? 0);
$season = (int)($_GET["season"] ?? 2025); // ✅ 25/26

if ($team <= 0) { echo json_encode(["ok"=>false,"error"=>"Team inválido"]); exit; }
if ($season <= 0) $season = 2025;

try {
  /* 1) PLANTEL ATUAL (isto remove quem já saiu) */
  $squadResp = apisports_get("players/squads", ["team" => $team], 3600);
  $squadArr  = $squadResp["response"] ?? [];

  $playersNow = [];
  if (is_array($squadArr) && !empty($squadArr[0]["players"]) && is_array($squadArr[0]["players"])) {
    foreach ($squadArr[0]["players"] as $p) {
      $pid = (int)($p["id"] ?? 0);
      if ($pid > 0) $playersNow[$pid] = true;
    }
  }

  // se não vier plantel, devolve vazio (para não mostrar “jogadores errados”)
  if (empty($playersNow)) {
    echo json_encode([
      "ok" => true,
      "season" => $season,
      "filtered_by_current_squad" => false,
      "data" => [],
      "warn" => "players/squads veio vazio (API)."
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* 2) STATS DA ÉPOCA (25/26) + SOMA TOTAL DE MINUTOS (todas as competições) */
  $out = [];
  $page = 1;

  while (true) {
    $resp = apisports_get("players", [
      "team" => $team,
      "season" => $season,
      "page" => $page
    ], 1800);

    $errors = $resp["errors"] ?? [];
    if (!empty($errors)) {
      echo json_encode(["ok"=>false,"error"=>$errors], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $list = $resp["response"] ?? [];
    if (!is_array($list) || !count($list)) break;

    foreach ($list as $row) {
      $p = $row["player"] ?? [];
      $pid = (int)($p["id"] ?? 0);
      if ($pid <= 0) continue;

      // ✅ só jogadores do plantel atual
      if (empty($playersNow[$pid])) continue;

      $statsArr = $row["statistics"] ?? [];
      if (!is_array($statsArr)) $statsArr = [];

      $totalMinutes = 0;

      // posição principal = a competição onde teve mais minutos
      $bestPos = "";
      $bestPosMinutes = 0;

      foreach ($statsArr as $st) {
        $games = $st["games"] ?? [];
        $m = (int)($games["minutes"] ?? 0);
        if ($m <= 0) continue;

        $totalMinutes += $m;

        $pos = (string)($games["position"] ?? "");
        if ($pos !== "" && $m > $bestPosMinutes) {
          $bestPosMinutes = $m;
          $bestPos = $pos;
        }
      }

      if ($totalMinutes <= 0) continue;

      $out[] = [
        "id" => $pid,
        "name" => (string)($p["name"] ?? ""),
        "photo" => (string)($p["photo"] ?? ""),
        "position_raw" => $bestPos,     // Goalkeeper/Defender/Midfielder/Attacker
        "minutes" => $totalMinutes,
      ];
    }

    $paging = $resp["paging"] ?? [];
    $totalPages = (int)($paging["total"] ?? 1);
    if ($page >= $totalPages) break;
    $page++;
  }

  // ordenar por minutos desc (opcional, ajuda no frontend)
  usort($out, fn($a,$b) => (int)$b["minutes"] <=> (int)$a["minutes"]);

echo json_encode([
  "ok" => true,
  "season" => $season,
  "filtered_by_current_squad" => true,
  "current_squad_count" => count($playersNow),
  "sample_current_ids" => array_slice(array_keys($playersNow), 0, 10),
  "returned_count" => count($out),
  "sample_returned_names" => array_slice(array_map(fn($x)=>$x["name"], $out), 0, 10),
  "data" => $out
], JSON_UNESCAPED_UNICODE);
exit;

} catch (Throwable $e) {
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
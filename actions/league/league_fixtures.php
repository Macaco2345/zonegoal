<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../api/football_service.php";

$league = isset($_GET["league"]) ? (int)$_GET["league"] : 0;
$season = isset($_GET["season"]) ? (int)$_GET["season"] : 0;
$mode   = (string)($_GET["mode"] ?? "next"); // next | last

if ($league <= 0 || $season <= 0) {
  echo json_encode(["ok" => false, "error" => "Parâmetros inválidos"]);
  exit;
}

try {
  if ($mode === "last") $rows = fetchLeagueLastFixtures($league, $season);
  else $rows = fetchLeagueNextFixtures($league, $season);

  echo json_encode(["ok" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(["ok" => false, "error" => "Erro a carregar fixtures"]);
}

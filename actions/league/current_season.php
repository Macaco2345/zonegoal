<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../api/football_service.php";

$league = (int)($_GET["league"] ?? 0);
if ($league <= 0) {
  echo json_encode(["ok"=>false, "error"=>"Liga inválida"]);
  exit;
}

$season = currentSeasonForLeague($league);
echo json_encode(["ok"=>true, "season"=>$season], JSON_UNESCAPED_UNICODE);

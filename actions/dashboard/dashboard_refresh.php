<?php
declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../db/db.php";
require_once __DIR__ . "/../../api/football_service.php";
require_once __DIR__ . "/../../includes/matches_feed.php";

function jexit(array $a, int $code=200): void {
  http_response_code($code);
  echo json_encode($a, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION["id_users"])) {
  jexit(["ok"=>false, "error"=>"Não autenticado"], 401);
}

$userId = (int)$_SESSION["id_users"];

// ids dos favoritos
$ids = [];
$stmt = $conn->prepare("SELECT match_id FROM favorites_matches WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($mid);
while ($stmt->fetch()) $ids[] = (int)$mid;
$stmt->close();

if (!count($ids)) {
  jexit(["ok"=>true, "html"=>'<div class="empty-message">Ainda não tens jogos nos favoritos ⭐</div>']);
}

// buscar fixtures
$fixtures = [];
foreach ($ids as $fid) {
  $fx = fetchFixtureById($fid);
  if (is_array($fx) && isset($fx["fixture"])) $fixtures[] = $fx;
}

if (!count($fixtures)) {
  jexit(["ok"=>true, "html"=>'<div class="empty-message">Sem favoritos válidos (API). Tenta novamente.</div>']);
}

// ✅ Capturar HTML do teu renderer (sem duplicar UI)
ob_start();

// Como são favoritos, mete-os como ativos no renderer (para ficar ⭐)
// e ainda ajuda o main.js: data-from="favorites" vai ser adicionado via JS abaixo
$GLOBALS["zonegoal_favorites"] = $ids;

// Render com badge “SEGUIDO”
zonegoal_render_grouped($fixtures, "match-upcoming", "SEGUIDO");

$html = ob_get_clean();

// ✅ Marca as estrelas como vindo do bloco favoritos (para remover a linha ao clicar)
$html = str_replace(
  'class="favorite-btn active" type="button"',
  'class="favorite-btn active" data-from="favorites" type="button"',
  $html
);

jexit(["ok"=>true, "html"=>$html]);
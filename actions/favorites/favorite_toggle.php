<?php
declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=utf-8");

// ✅ nunca deixar warnings virar HTML
error_reporting(E_ALL);
ini_set("display_errors", "0"); // IMPORTANTe: 0 em produção

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
$matchId = (int)($_POST["match_id"] ?? 0);

if ($matchId <= 0) {
  jexit(["ok" => false, "error" => "match_id inválido"], 400);
}

// ✅ garante que a função existe (evita Fatal)
if (!function_exists("fetchFixtureById")) {
  jexit(["ok" => false, "error" => "fetchFixtureById() não está disponível (football_service.php)"], 500);
}

// Remove favoritos finalizados após X horas
$REMOVE_AFTER_SECONDS = 6 * 3600;

/**
 * @return array<int>
 */
function getFavoriteIds(mysqli $conn, int $userId): array {
  $ids = [];

  // ⚠️ não dependemos de created_at aqui
  $stmt = $conn->prepare("SELECT match_id FROM favorites_matches WHERE user_id=? LIMIT 20");
  if (!$stmt) return $ids;

  $stmt->bind_param("i", $userId);
  $stmt->execute();

  // ✅ fallback sem get_result (mais compatível)
  $stmt->bind_result($mid);
  while ($stmt->fetch()) {
    $ids[] = (int)$mid;
  }
  $stmt->close();

  return $ids;
}

function cleanup_finished_favorites(mysqli $conn, int $userId, int $removeAfterSeconds): void {
  $ids = getFavoriteIds($conn, $userId);
  if (!count($ids)) return;

  $now = time();

  foreach ($ids as $fid) {
    $fx = fetchFixtureById($fid); // pode devolver null/false
    if (!$fx || !is_array($fx)) continue;

    $short = (string)($fx["fixture"]["status"]["short"] ?? "");
    $ts = (int)($fx["fixture"]["timestamp"] ?? 0);

    $isFinished = in_array($short, ["FT","AET","PEN"], true);

    if ($isFinished && $ts > 0 && ($now - $ts) > $removeAfterSeconds) {
      $del = $conn->prepare("DELETE FROM favorites_matches WHERE user_id=? AND match_id=?");
      if (!$del) continue;
      $del->bind_param("ii", $userId, $fid);
      $del->execute();
      $del->close();
    }
  }
}

try {
  cleanup_finished_favorites($conn, $userId, $REMOVE_AFTER_SECONDS);

  // ✅ existe?
  $stmt = $conn->prepare("SELECT 1 FROM favorites_matches WHERE user_id=? AND match_id=? LIMIT 1");
  if (!$stmt) jexit(["ok" => false, "error" => "Erro SQL (select exists)"], 500);

  $stmt->bind_param("ii", $userId, $matchId);
  $stmt->execute();
  $stmt->bind_result($one);
  $exists = (bool)$stmt->fetch();
  $stmt->close();

  // Se já existe -> remover
  if ($exists) {
    $stmt = $conn->prepare("DELETE FROM favorites_matches WHERE user_id=? AND match_id=?");
    if (!$stmt) jexit(["ok" => false, "error" => "Erro SQL (delete)"], 500);

    $stmt->bind_param("ii", $userId, $matchId);
    $stmt->execute();
    $stmt->close();

    jexit(["ok" => true, "action" => "removed"]);
  }

  // Limite máximo 10
  $stmt = $conn->prepare("SELECT COUNT(*) FROM favorites_matches WHERE user_id=?");
  if (!$stmt) jexit(["ok" => false, "error" => "Erro SQL (count)"], 500);

  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($c);
  $stmt->fetch();
  $stmt->close();

  if ((int)$c >= 10) {
    jexit(["ok" => false, "error" => "Máximo 10 favoritos. Remove um para adicionares outro."], 400);
  }

  // Inserir (✅ não usa created_at para não falhar se não existir)
  $stmt = $conn->prepare("INSERT INTO favorites_matches (user_id, match_id) VALUES (?, ?)");
  if (!$stmt) jexit(["ok" => false, "error" => "Erro SQL (insert)"], 500);

  $stmt->bind_param("ii", $userId, $matchId);
  $stmt->execute();
  $stmt->close();

  jexit(["ok" => true, "action" => "added"]);
} catch (Throwable $e) {
  jexit([
    "ok" => false,
    "error" => "Erro interno no servidor.",
    "debug" => $e->getMessage()
  ], 500);
}
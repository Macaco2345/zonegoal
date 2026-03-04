<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/includes/bootstrap.php";
require_once dirname(__DIR__, 2) . "/includes/admin_guard.php";
require_once dirname(__DIR__, 2) . "/db/db.php";
require_once dirname(__DIR__, 2) . "/actions/admin/admin_log_add.php";
zg_require_admin();
header("Content-Type: application/json; charset=utf-8");

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) { echo json_encode(["ok"=>false,"error"=>"ID inválido"]); exit; }

if (!empty($_SESSION["id_users"]) && (int)$_SESSION["id_users"] === $id) {
  echo json_encode(["ok"=>false,"error"=>"Não podes apagar a tua própria conta"]);
  exit;
}

$stmt = $conn->prepare("DELETE FROM favorites_matches WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM users WHERE id_users=?");
$stmt->bind_param("i", $id);
$stmt->execute();

admin_add_log((int)($_SESSION["id_users"] ?? 0), "USER_DELETE", "id={$id}");

echo json_encode(["ok"=>true]);

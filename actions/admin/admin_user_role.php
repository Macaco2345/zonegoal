<?php
declare(strict_types=1);


require_once dirname(__DIR__, 2) . "/includes/bootstrap.php";
require_once dirname(__DIR__, 2) . "/includes/admin_guard.php";
require_once dirname(__DIR__, 2) . "/db/db.php";
require_once dirname(__DIR__, 2) . "/actions/admin/admin_log_add.php";
zg_require_admin();
header("Content-Type: application/json; charset=utf-8");

$id = (int)($_POST["id"] ?? 0);
$role = (string)($_POST["role"] ?? "user");

if ($id <= 0 || !in_array($role, ["user","admin"], true)) {
  echo json_encode(["ok"=>false, "error"=>"Dados inválidos"]);
  exit;
}

$stmt = $conn->prepare("UPDATE users SET role=? WHERE id_users=?");
$stmt->bind_param("si", $role, $id);
$stmt->execute();

admin_add_log((int)($_SESSION["id_users"] ?? 0), "USER_ROLE", "id={$id} role={$role}");

echo json_encode(["ok"=>true]);

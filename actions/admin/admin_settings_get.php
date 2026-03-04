<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/includes/bootstrap.php";
require_once dirname(__DIR__, 2) . "/includes/admin_guard.php";
zg_require_admin();
header("Content-Type: application/json; charset=utf-8");


$path = __DIR__ . "/../config/runtime.json";
if (!file_exists($path)) {
  echo json_encode(["ok"=>true,"api_enabled"=>true,"favorites_limit"=>10,"refresh_interval"=>30]);
  exit;
}
$data = json_decode((string)file_get_contents($path), true);
if (!is_array($data)) $data = [];

echo json_encode([
  "ok"=>true,
  "api_enabled" => !empty($data["api_enabled"]),
  "favorites_limit" => (int)($data["favorites_limit"] ?? 10),
  "refresh_interval" => (int)($data["refresh_interval"] ?? 30),
]);

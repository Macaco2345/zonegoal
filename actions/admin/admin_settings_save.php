<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

$ROOT = dirname(__DIR__, 2);
require_once $ROOT . "/includes/bootstrap.php";
require_once $ROOT . "/includes/admin_guard.php";

zg_require_admin();

$path = $ROOT . "/config/runtime.json";

$api_enabled = (int)($_POST["api_enabled"] ?? 1) === 1;
$favorites_limit = max(1, min(50, (int)($_POST["favorites_limit"] ?? 10)));
$refresh_interval = max(5, min(120, (int)($_POST["refresh_interval"] ?? 30)));

$data = [
  "api_enabled" => $api_enabled,
  "favorites_limit" => $favorites_limit,
  "refresh_interval" => $refresh_interval,
];

$dir = dirname($path);
if (!is_dir($dir)) {
  echo json_encode(["ok"=>false, "error"=>"Pasta config não existe: $dir"], JSON_UNESCAPED_UNICODE);
  exit;
}

// Se o ficheiro não existir, tenta criar
if (!file_exists($path)) {
  @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if (!is_writable($path)) {
  echo json_encode(["ok"=>false, "error"=>"Sem permissões para escrever em: $path"], JSON_UNESCAPED_UNICODE);
  exit;
}

$ok = @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);

if ($ok === false) {
  echo json_encode(["ok"=>false, "error"=>"Falha a guardar runtime.json (permissões/antivírus)"], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(["ok"=>true], JSON_UNESCAPED_UNICODE);

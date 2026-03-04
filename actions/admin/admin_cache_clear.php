<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

$ROOT = dirname(__DIR__, 2);
require_once $ROOT . "/includes/bootstrap.php";
require_once $ROOT . "/includes/admin_guard.php";

zg_require_admin();

$cacheDir = $ROOT . "/cache";
if (!is_dir($cacheDir)) {
  echo json_encode(["ok"=>false, "error"=>"Pasta cache não encontrada: ".$cacheDir], JSON_UNESCAPED_UNICODE);
  exit;
}

$prefix = trim((string)($_POST["prefix"] ?? ""));
$deleted = 0;

$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $f) {
  /** @var SplFileInfo $f */
  if (!$f->isFile()) continue;

  $path = $f->getPathname();
  if (strtolower($f->getExtension()) !== "json") continue;

  if ($prefix !== "" && stripos(basename($path), $prefix) === false) continue;

  if (@unlink($path)) $deleted++;
}

echo json_encode(["ok"=>true, "deleted"=>$deleted], JSON_UNESCAPED_UNICODE);

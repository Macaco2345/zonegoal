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

$prefix = trim((string)($_GET["prefix"] ?? ""));
$files = 0;
$bytes = 0;

$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $f) {
  /** @var SplFileInfo $f */
  if (!$f->isFile()) continue;

  $path = $f->getPathname();

  // só contar json
  if (strtolower($f->getExtension()) !== "json") continue;

  // filtrar por prefixo (baseado no nome do ficheiro)
  if ($prefix !== "" && stripos(basename($path), $prefix) === false) continue;

  $files++;
  $bytes += (int)$f->getSize();
}

function humanBytes(int $b): string {
  $u = ["B","KB","MB","GB"];
  $i = 0;
  $v = (float)$b;
  while ($v >= 1024 && $i < count($u)-1) { $v /= 1024; $i++; }
  return ($i === 0 ? (string)$b : number_format($v, 2)) . " " . $u[$i];
}

echo json_encode([
  "ok" => true,
  "prefix" => $prefix,
  "files" => $files,
  "size" => $bytes,
  "size_h" => humanBytes($bytes),
], JSON_UNESCAPED_UNICODE);

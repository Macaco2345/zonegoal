<?php
declare(strict_types=1);

/**
 * Cache simples em ficheiro JSON.
 * Fica em /cache/ (na raiz do projeto).
 */

function zg_feed_cache_dir(): string {
  $dir = __DIR__ . "/../cache";
  if (!is_dir($dir)) @mkdir($dir, 0777, true);
  return $dir;
}

function zg_feed_cache_path(string $name): string {
  $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
  return zg_feed_cache_dir() . "/" . $safe . ".json";
}

function zg_cache_read(string $file, int $ttlSeconds): ?array {
  if (!is_file($file)) return null;
  if ($ttlSeconds > 0 && (time() - filemtime($file)) > $ttlSeconds) return null;

  $raw = @file_get_contents($file);
  if ($raw === false || $raw === "") return null;

  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function zg_cache_write(string $file, array $data): void {
  @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

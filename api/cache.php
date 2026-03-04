<?php
declare(strict_types=1);

define("CACHE_DIR", __DIR__ . "/../cache");

if (!is_dir(CACHE_DIR)) {
  mkdir(CACHE_DIR, 0777, true);
}

function cache_key(string $key): string {
  return CACHE_DIR . "/" . md5($key) . ".json";
}

function cache_get(string $key, int $ttl): ?array {
  $file = cache_key($key);
  if (!file_exists($file)) return null;
  if ((time() - filemtime($file)) > $ttl) return null;

  $raw = file_get_contents($file);
  $data = json_decode($raw ?: "", true);
  return is_array($data) ? $data : null;
}

function cache_set(string $key, array $data): void {
  $file = cache_key($key);
  file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

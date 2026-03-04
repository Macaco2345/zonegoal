<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/api_keys.php";
require_once __DIR__ . "/cache.php";

/**
 * Lê as configurações dinâmicas do Admin (config/runtime.json)
 * - cache em memória (static) para não ler o ficheiro mil vezes por request
 */
function zg_runtime(): array
{
  static $cfg = null;
  if (is_array($cfg)) return $cfg;

  // defaults
  $cfg = [
    "api_enabled" => true,
    "favorites_limit" => 10,
    "refresh_interval" => 30,
  ];

  $path = __DIR__ . "/../config/runtime.json";
  if (file_exists($path)) {
    $raw = (string)file_get_contents($path);
    $json = json_decode($raw, true);
    if (is_array($json)) {
      $cfg = array_merge($cfg, $json);
    }
  }

  return $cfg;
}

/** Decide se a API está ligada (runtime.json tem prioridade) */
function zg_api_enabled(): bool
{
  $rt = zg_runtime();

  // Se existir no runtime, respeita
  if (array_key_exists("api_enabled", $rt)) {
    return (bool)$rt["api_enabled"];
  }

  // fallback para a constante antiga
  return defined("API_ENABLED") ? (bool)API_ENABLED : true;
}

function apisports_get(string $endpoint, array $params = [], int $cacheSeconds = 60): array
{
  $endpoint = ltrim($endpoint, "/");
  $cacheKey = $endpoint . "?" . http_build_query($params);

  // 1) cache primeiro
  $cached = cache_get($cacheKey, $cacheSeconds);
  if ($cached !== null) return $cached;

  // 2) API desligada (Admin runtime.json tem prioridade)
  if (!zg_api_enabled()) {
    return ["response" => [], "results" => 0, "errors" => ["API desligada (Admin)"]];
  }

  // 3) pedido real
  $url = "https://v3.football.api-sports.io/" . $endpoint;
  if (!empty($params)) $url .= "?" . http_build_query($params);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      "x-apisports-key: " . API_FOOTBALL_KEY,
      "accept: application/json",
    ],
    CURLOPT_TIMEOUT => 20,
  ]);

  $raw = curl_exec($ch);

  if ($raw === false) {
    $err = curl_error($ch);
    curl_close($ch);
    return ["response" => [], "results" => 0, "errors" => [$err]];
  }

  $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return ["response" => [], "results" => 0, "errors" => ["Resposta inválida (HTTP $httpCode)"]];
  }

  // guardar cache
  cache_set($cacheKey, $data);
  return $data;
}

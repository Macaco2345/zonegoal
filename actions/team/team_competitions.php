<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors","0");

require_once __DIR__ . "/../../api/api_football.php";

function reply(array $json, int $code=200): void {
  http_response_code($code);
  echo json_encode($json, JSON_UNESCAPED_UNICODE);
  exit;
}

$team   = (int)($_GET["team"] ?? 0);
$season = (int)($_GET["season"] ?? 0);
$take   = (int)($_GET["take"] ?? 150); // quantos jogos “varrer” para achar competições

if ($team <= 0) reply(["ok"=>false,"error"=>"Team inválido"], 400);
if ($season <= 0) $season = (int)date("Y");
if ($take < 30) $take = 30;
if ($take > 250) $take = 250;

try {
  // puxar um bloco de jogos da época (sem league) para descobrir competições
  $j = apisports_get("fixtures", [
    "team"   => $team,
    "season" => $season,
    // truque: pegas “last” e “next” para cobrir a época inteira sem pedir 500 coisas
    "last"   => (int)floor($take/2),
  ], 300);

  $list = $j["response"] ?? [];

  // se vier pouco, tenta também próximos
  $j2 = apisports_get("fixtures", [
    "team"   => $team,
    "season" => $season,
    "next"   => (int)ceil($take/2),
  ], 300);

  $list2 = $j2["response"] ?? [];
  $all = array_merge($list, $list2);

  $map = []; // league_id => meta
  foreach ($all as $fx) {
    $lg = $fx["league"] ?? null;
    if (!is_array($lg)) continue;

    $lid = (int)($lg["id"] ?? 0);
    if ($lid <= 0) continue;

    if (!isset($map[$lid])) {
      $map[$lid] = [
        "id"      => $lid,
        "name"    => (string)($lg["name"] ?? ""),
        "logo"    => (string)($lg["logo"] ?? ""),
        "country" => (string)($lg["country"] ?? ""),
        "flag"    => (string)($lg["flag"] ?? ""),
        "type"    => (string)($lg["type"] ?? ""), // League / Cup
        "count"   => 0,
        "last_ts" => 0
      ];
    }

    $map[$lid]["count"]++;
    $ts = (int)($fx["fixture"]["timestamp"] ?? 0);
    if ($ts > $map[$lid]["last_ts"]) $map[$lid]["last_ts"] = $ts;
  }

  $comps = array_values($map);

  // ordenar por: mais frequente, depois mais recente
  usort($comps, function($a,$b){
    $c = ($b["count"] ?? 0) <=> ($a["count"] ?? 0);
    if ($c !== 0) return $c;
    return ($b["last_ts"] ?? 0) <=> ($a["last_ts"] ?? 0);
  });

  reply([
    "ok" => true,
    "team" => $team,
    "season" => $season,
    "data" => $comps
  ]);

} catch (Throwable $e) {
  reply(["ok"=>false,"error"=>"Erro a carregar competições"], 500);
}
<?php
declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../db/db.php";
require_once __DIR__ . "/../../api/football_service.php";

if (!isset($_SESSION["id_users"])) {
  echo json_encode(["ok"=>false]);
  exit;
}

$userId = (int)$_SESSION["id_users"];

$stmt = $conn->prepare("
SELECT match_id
FROM favorites_matches
WHERE user_id=?
LIMIT 10
");

$stmt->bind_param("i",$userId);
$stmt->execute();

$stmt->bind_result($mid);

$ids=[];
while($stmt->fetch()) $ids[]=(int)$mid;

$stmt->close();

$leagues=[];

foreach($ids as $id){

  $fx = fetchFixtureById($id);
  if(!$fx) continue;

  $lid = (int)($fx["league"]["id"] ?? 0);
  $lname = (string)($fx["league"]["name"] ?? "");

  if(!$lid) continue;

  if(!isset($leagues[$lid])){
    $leagues[$lid]=[
      "id"=>$lid,
      "name"=>$lname,
      "count"=>0
    ];
  }

  $leagues[$lid]["count"]++;
}

usort($leagues,function($a,$b){
  return $b["count"] <=> $a["count"];
});

$top = $leagues[0] ?? null;

echo json_encode([
  "ok"=>true,
  "league"=>$top
]);
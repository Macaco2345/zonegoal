<?php
declare(strict_types=1);

require_once __DIR__ . "/../../db/db.php";

header("Content-Type: application/json; charset=utf-8");

$teamId = (int)($_GET["team"] ?? 0);
if ($teamId <= 0) {
    echo json_encode(["ok" => false, "error" => "Team inválido"]);
    exit;
}

// Buscar troféus
$stmt = $conn->prepare("
    SELECT id, competition_name, total_titles
    FROM team_trophies
    WHERE team_id = ?
    ORDER BY total_titles DESC
");

$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();

$trophies = [];
while ($row = $result->fetch_assoc()) {
    $trophies[] = $row;
}

if (empty($trophies)) {
    echo json_encode(["ok" => true, "data" => []]);
    exit;
}

// Buscar anos (se existirem)
$data = [];

foreach ($trophies as $t) {

    $years = [];
    $stmtY = $conn->prepare("
        SELECT season_year
        FROM team_trophy_years
        WHERE trophy_id = ?
        ORDER BY season_year ASC
    ");

    $stmtY->bind_param("i", $t["id"]);
    $stmtY->execute();
    $resY = $stmtY->get_result();

    while ($y = $resY->fetch_assoc()) {
        $years[] = (int)$y["season_year"];
    }

    $data[] = [
        "league"  => $t["competition_name"],
        "count"   => (int)$t["total_titles"],
        "seasons" => $years
    ];
}

echo json_encode(["ok" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
exit;
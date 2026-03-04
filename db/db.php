<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = "127.0.0.1";
$dbUser = "root";
$dbPass = "";
$dbName = "zonegoal";
$dbPort = 3306;

try {
  $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
  $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
  http_response_code(500);
  exit("Erro na ligação à BD: " . $e->getMessage());
}

<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

$ROOT = dirname(__DIR__, 2);

require_once $ROOT . "/includes/bootstrap.php";
require_once $ROOT . "/includes/admin_guard.php";
require_once $ROOT . "/db/db.php";

zg_require_admin();

$stmt = $conn->prepare("
  SELECT id_log, admin_id, action, meta, created_at
  FROM admin_logs
  ORDER BY id_log DESC
  LIMIT 200
");
$stmt->execute();

$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);

$html = '<table class="zg-admin-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Admin</th>
      <th>Ação</th>
      <th>Meta</th>
      <th>Data</th>
    </tr>
  </thead>
  <tbody>';

foreach ($rows as $r) {
  $html .= "<tr>
    <td>" . (int)$r["id_log"] . "</td>
    <td>" . (int)$r["admin_id"] . "</td>
    <td>" . e((string)$r["action"]) . "</td>
    <td>" . e((string)$r["meta"]) . "</td>
    <td>" . e((string)$r["created_at"]) . "</td>
  </tr>";
}

$html .= "</tbody></table>";

echo json_encode(["ok" => true, "html" => $html], JSON_UNESCAPED_UNICODE);

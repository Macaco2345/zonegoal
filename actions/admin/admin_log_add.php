<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/db/db.php";

function admin_add_log(int $adminId, string $action, string $meta = ""): void {
  global $conn;
  $st = $conn->prepare("INSERT INTO admin_logs (admin_id, action, meta) VALUES (?,?,?)");
  $st->bind_param("iss", $adminId, $action, $meta);
  $st->execute();
}

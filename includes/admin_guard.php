<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

function zg_is_logged_in(): bool {
  return !empty($_SESSION["id_users"]);
}

function zg_is_admin(): bool {
  return !empty($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

function zg_require_admin(): void {
  if (!zg_is_logged_in()) {
    header("Location: /ZoneGoal/login.php");
    exit;
  }
  if (!zg_is_admin()) {
    http_response_code(403);
    exit("Acesso negado.");
  }
}

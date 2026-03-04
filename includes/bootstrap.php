<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists("e")) {
  function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
  }
}


/**
 * Base URL do projeto (para paths absolutos)
 * Se a pasta do projeto não for /ZoneGoal, muda aqui.
 */
const ZG_BASE = "/ZoneGoal";

/** URL helper */
function url(string $path): string {
  $path = "/" . ltrim($path, "/");
  return ZG_BASE . $path;
}

function is_logged_in(): bool {
  return !empty($_SESSION["id_users"]);
}

function require_login(): void {
  if (!is_logged_in()) {
    header("Location: " . url("login.php"));
    exit;
  }
}

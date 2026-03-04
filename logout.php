<?php
declare(strict_types=1);

session_start();

/* limpar todas as variáveis de sessão */
$_SESSION = [];

/* destruir cookie de sessão (boa prática) */
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

/* destruir sessão */
session_destroy();

/* redirecionar para página principal */
header("Location: index.php");
exit;

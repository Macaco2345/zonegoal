<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set("display_errors", "1");

session_start();
require_once __DIR__ . "/db/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $username = trim((string)($_POST["username"] ?? ""));
  $password = (string)($_POST["password"] ?? "");

  if ($username === "" || $password === "") {
    $error = "Preenche todos os campos.";
  } else {

    // Seguro contra SQL Injection
    $stmt = $conn->prepare(
      "SELECT id_users, username, password, role
       FROM users
       WHERE username = ?
       LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Verificação segura da password
    if ($user && password_verify($password, (string)$user["password"])) {

      // Sessão segura
      session_regenerate_id(true);

      // ✅ CHAVES CERTAS (compatível com index/dashboard)
      $_SESSION["id_users"] = (int)$user["id_users"];
      $_SESSION["username"] = (string)$user["username"];
      $_SESSION["role"]     = (string)$user["role"];

      header("Location: dashboard.php");
      exit;
    }

    $error = "Username ou password incorretos.";
  }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Login - ZoneGoal</title>
  <link rel="stylesheet" href="css/pages/login.css">
</head>

<body class="auth-page">
  <div class="auth-container">

    <h2>ZoneGoal</h2>
    <p>Entrar na conta</p>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Entrar</button>
    </form>

    <span>
      Não tens conta?
      <a href="register.php">Registar</a>
    </span>

  </div>
</body>
</html>

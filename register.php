<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/db/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $username = trim($_POST["username"] ?? "");
  $email    = trim($_POST["email"] ?? "");
  $password = $_POST["password"] ?? "";

  // Validações básicas
  if ($username === "" || $email === "" || $password === "") {
    $error = "Preenche todos os campos.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email inválido.";
  } elseif (strlen($password) < 6) {
    $error = "A password deve ter pelo menos 6 caracteres.";
  } else {

    // Verificar se username ou email já existem
    $stmt = $conn->prepare(
      "SELECT id_users FROM users WHERE username = ? OR email = ? LIMIT 1"
    );
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
      $error = "Username ou email já estão a ser usados.";
    } else {

      // Criar conta
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $role = "user";

      $stmt = $conn->prepare(
        "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)"
      );
      $stmt->bind_param("ssss", $username, $email, $hash, $role);

      if ($stmt->execute()) {
        $success = "Conta criada com sucesso! Já podes fazer login.";
      } else {
        $error = "Erro ao criar conta.";
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Registo - ZoneGoal</title>
  <link rel="stylesheet" href="css/pages/register.css">
</head>

<body class="auth-page">
  <div class="auth-container">

    <h2>ZoneGoal</h2>
    <p>Criar conta</p>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Registar</button>
    </form>

    <span>
      Já tens conta?
      <a href="login.php">Entrar</a>
    </span>

  </div>
</body>
</html>

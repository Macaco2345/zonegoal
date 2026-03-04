<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<header class="topbar">
  <div class="brand-name">ZoneGoal</div>
  <nav class="topbar-nav">
    <a class="topbar-link" href="index.php">Jogos</a>
    <a class="topbar-link" href="standings.php">Standings</a>

    <?php if (!empty($_SESSION["id_users"])): ?>
      <a class="topbar-link" href="dashboard.php">Dashboard</a>
      <a class="topbar-link" href="logout.php">Sair</a>
    <?php else: ?>
      <a class="topbar-link" href="login.php">Entrar</a>
      <a class="topbar-link" href="register.php">Criar conta</a>
    <?php endif; ?>
  </nav>
</header>

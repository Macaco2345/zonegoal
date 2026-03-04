<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

function zg_active(string $page): string {
  $cur = basename($_SERVER["PHP_SELF"] ?? "");
  return $cur === $page ? "active" : "";
}
?>

<aside class="zg-sidebar">

  <div class="zg-side-brand">
    ZoneGoal
  </div>

  <nav class="zg-side-nav">

    <a class="zg-side-link <?= zg_active("index.php") ?>" href="index.php">
      <span class="ico">⚽</span>
      <span>Jogos</span>
    </a>

    <a class="zg-side-link <?= zg_active("standings.php") ?>" href="standings.php">
      <span class="ico">📊</span>
      <span>Standings</span>
    </a>

    <a class="zg-side-link <?= zg_active("league.php") ?>" href="league.php">
      <span class="ico">🏆</span>
      <span>Ligas</span>
    </a>

    <?php if (!empty($_SESSION["id_users"])): ?>

      <a class="zg-side-link <?= zg_active("dashboard.php") ?>" href="dashboard.php">
        <span class="ico">⭐</span>
        <span>Dashboard</span>
      </a>

      <a class="zg-side-link" href="logout.php">
        <span class="ico">🚪</span>
        <span>Sair</span>
      </a>

    <?php else: ?>

      <a class="zg-side-link <?= zg_active("login.php") ?>" href="login.php">
        <span class="ico">🔐</span>
        <span>Entrar</span>
      </a>

      <a class="zg-side-link <?= zg_active("register.php") ?>" href="register.php">
        <span class="ico">🧾</span>
        <span>Criar conta</span>
      </a>

    <?php endif; ?>

  </nav>

  <div class="zg-side-foot">
    <div class="mini">API-Football • Cache</div>
  </div>

</aside>

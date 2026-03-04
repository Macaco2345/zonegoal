<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();


$zg_user = !empty($_SESSION["id_users"]);
$zg_name = (string)($_SESSION["username"] ?? "Visitante");

$ZG_CONTEXT_LEAGUE = $ZG_CONTEXT_LEAGUE ?? 94;
$ZG_CONTEXT_SEASON = $ZG_CONTEXT_SEASON ?? 2025;

$ZG_TITLE  = $ZG_TITLE  ?? "ZoneGoal";
$ZG_ACTIVE = $ZG_ACTIVE ?? basename($_SERVER["PHP_SELF"] ?? "index.php");
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title><?= e($ZG_TITLE) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS GLOBAL (à prova de pastas) -->
  <link rel="stylesheet" href="/ZoneGoal/css/style.css">

  <?php if (!empty($ZG_CSS)): ?>
    <link rel="stylesheet" href="/ZoneGoal/<?= e(ltrim($ZG_CSS, "/")) ?>">
  <?php endif; ?>
</head>

<body class="app-dark">

<div class="zg-shell">

  <!-- SIDEBAR -->
  <aside class="zg-side">

    <!-- LOGO (só logo, sem texto) -->
    <a href="/ZoneGoal/index.php" class="zg-brand" aria-label="ZoneGoal">
      <img src="/ZoneGoal/assets/images/Zonegoal_logo.png" class="zg-logo" alt="ZoneGoal">
    </a>

    <!-- USER CARD -->
    <div class="zg-user">
      <div class="zg-user-name"><?= e($zg_name) ?></div>
<div class="zg-user-role">
  <?=
    !$zg_user ? "Não autenticado" :
    ($_SESSION["role"] === "admin" ? "Administrador" : "Utilizador")
  ?>
</div>

    </div>

    <!-- MENU -->
<nav class="zg-menu">
  <a href="/ZoneGoal/index.php" class="zg-item <?= $ZG_ACTIVE==="index.php" ? "active" : "" ?>">⚽ Jogos</a>
  <a href="/ZoneGoal/standings.php" class="zg-item <?= $ZG_ACTIVE==="standings.php" ? "active" : "" ?>">📊 Standings</a>
  <a href="/ZoneGoal/league.php" class="zg-item <?= $ZG_ACTIVE==="league.php" ? "active" : "" ?>">🏆 Ligas</a>

  <?php if ($zg_user): ?>
    <a href="/ZoneGoal/dashboard.php" class="zg-item <?= $ZG_ACTIVE==="dashboard.php" ? "active" : "" ?>">⭐ Dashboard</a>

    <?php if (!empty($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
      <a href="/ZoneGoal/admin.php" class="zg-item <?= $ZG_ACTIVE==="admin.php" ? "active" : "" ?>">🛠️ Admin</a>
    <?php endif; ?>

    <a href="/ZoneGoal/logout.php" class="zg-item">🚪 Sair</a>
  <?php else: ?>
    <a href="/ZoneGoal/login.php" class="zg-item <?= $ZG_ACTIVE==="login.php" ? "active" : "" ?>">🔐 Entrar</a>
    <a href="/ZoneGoal/register.php" class="zg-item <?= $ZG_ACTIVE==="register.php" ? "active" : "" ?>">🧾 Criar conta</a>
  <?php endif; ?>
</nav>


    <div class="zg-side-foot">
      <div class="mini">API-Football • Cache</div>
    </div>

  </aside>

  <!-- MAIN -->
  <div class="zg-main">

    <!-- TOP (search) -->
    <div class="zg-main-top">
      <div class="zg-search-wrap">
        <input id="zgSearch" type="text" autocomplete="off" placeholder="Pesquisar equipa ou jogador…">
        <div id="zgSearchBox" class="zg-search-box"></div>
      </div>
    </div>

    <script>
      window.ZG_CONTEXT_LEAGUE = <?= (int)$ZG_CONTEXT_LEAGUE ?>;
      window.ZG_CONTEXT_SEASON = <?= (int)$ZG_CONTEXT_SEASON ?>;
    </script>

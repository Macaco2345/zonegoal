<?php
declare(strict_types=1);

// Sempre JSON
header("Content-Type: application/json; charset=utf-8");

// Raiz do projeto: .../ZoneGoal
$ROOT = dirname(__DIR__, 2);

// Bootstrap + guard + DB (paths robustos)
require_once $ROOT . "/includes/bootstrap.php";
require_once $ROOT . "/includes/admin_guard.php";
require_once $ROOT . "/db/db.php";

// Exigir admin (ajusta o nome da função se o teu guard for diferente)
if (function_exists("zg_require_admin")) {
  zg_require_admin();
} else {
  // fallback simples
  if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["ok" => false, "error" => "Acesso negado"]);
    exit;
  }
}

// Função escape (usa a do bootstrap; se não existir, cria safe)
if (!function_exists("e")) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
}

$q = trim((string)($_GET["q"] ?? ""));
$like = "%" . $q . "%";

try {
  if ($q !== "") {
    $stmt = $conn->prepare("
      SELECT id_users, username, email, role, created_at
      FROM users
      WHERE username LIKE ? OR email LIKE ?
      ORDER BY id_users DESC
      LIMIT 50
    ");
    $stmt->bind_param("ss", $like, $like);
  } else {
    $stmt = $conn->prepare("
      SELECT id_users, username, email, role, created_at
      FROM users
      ORDER BY id_users DESC
      LIMIT 50
    ");
  }

  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  // HTML da tabela (o teu admin.js espera j.html)
  ob_start();
  ?>
  <table class="zg-admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Criado</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" style="opacity:.8;">Sem resultados.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $u): ?>
        <tr>
          <td><?= (int)$u["id_users"] ?></td>
          <td><?= e((string)$u["username"]) ?></td>
          <td><?= e((string)$u["email"]) ?></td>
          <td>
            <span class="zg-role <?= ($u["role"] === "admin" ? "is-admin" : "is-user") ?>">
              <?= e((string)$u["role"]) ?>
            </span>
          </td>
          <td><?= e((string)($u["created_at"] ?? "")) ?></td>
          <td class="zg-actions">
            <?php if ((string)$u["role"] !== "admin"): ?>
              <button type="button" class="btn-mini"
                data-action="role" data-id="<?= (int)$u["id_users"] ?>" data-role="admin">Promover</button>
            <?php else: ?>
              <button type="button" class="btn-mini"
                data-action="role" data-id="<?= (int)$u["id_users"] ?>" data-role="user">Rebaixar</button>
            <?php endif; ?>

            <button type="button" class="btn-mini danger"
              data-action="delete" data-id="<?= (int)$u["id_users"] ?>">Apagar</button>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  <?php
  $html = ob_get_clean();

  echo json_encode(["ok" => true, "html" => $html], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $ex) {
  echo json_encode(["ok" => false, "error" => "Erro: " . $ex->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}

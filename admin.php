<?php
declare(strict_types=1);

$ROOT = dirname(__FILE__);

require_once $ROOT . "/includes/bootstrap.php";
require_once $ROOT . "/includes/admin_guard.php";

zg_require_admin();

$ZG_TITLE = "ZoneGoal • Admin";
$ZG_CSS = "css/pages/admin.css";

require __DIR__ . "/includes/layout_start.php";
?>

<div class="admin-wrap">
  <div class="admin-head">
    <h1>Painel Admin</h1>
    <div class="admin-sub">Utilizadores • Cache • Configurações • Logs</div>
  </div>

  <div class="admin-tabs">
    <button type="button" class="admin-tab active" data-tab="users">Utilizadores</button>
    <button type="button" class="admin-tab" data-tab="cache">Cache</button>
    <button type="button" class="admin-tab" data-tab="settings">Configurações</button>
    <button type="button" class="admin-tab" data-tab="logs">Logs</button>
  </div>

  <section class="admin-panel" data-panel="users">
    <div class="admin-card">
      <div class="admin-card-head">
        <div class="title">Utilizadores</div>
        <div class="actions">
          <input id="userSearch" class="admin-input" placeholder="Pesquisar username/email…">
          <button type="button" class="admin-btn" id="btnReloadUsers">Recarregar</button>
        </div>
      </div>
      <div id="usersTable" class="admin-table-wrap">A carregar…</div>
    </div>
  </section>

  <section class="admin-panel" data-panel="cache" style="display:none">
    <div class="admin-card">
      <div class="admin-card-head">
        <div class="title">Cache</div>
        <div class="actions">
          <input id="cachePrefix" class="admin-input" placeholder="Prefixo (ex: fixtures / standings)…">
          <button type="button" class="admin-btn" id="btnCacheStats">Atualizar</button>
          <button type="button" class="admin-btn danger" id="btnCacheClear">Limpar</button>
        </div>
      </div>
      <div id="cacheStats" class="admin-kpis">A carregar…</div>
      <div class="admin-hint">Deixa prefixo vazio para limpar tudo.</div>
    </div>
  </section>

  <section class="admin-panel" data-panel="settings" style="display:none">
    <div class="admin-card">
      <div class="admin-card-head">
        <div class="title">Configurações</div>
        <div class="actions">
          <button type="button" class="admin-btn" id="btnLoadSettings">Carregar</button>
          <button type="button" class="admin-btn" id="btnSaveSettings">Guardar</button>
        </div>
      </div>

      <div class="admin-form">
        <label class="admin-label">
          <span>API Enabled</span>
          <select id="apiEnabled" class="admin-input">
            <option value="1">Ligada</option>
            <option value="0">Desligada</option>
          </select>
        </label>

        <label class="admin-label">
          <span>Limite de favoritos</span>
          <input id="favoritesLimit" class="admin-input" type="number" min="1" max="50" value="10">
        </label>

        <label class="admin-label">
          <span>Refresh interval (segundos)</span>
          <input id="refreshInterval" class="admin-input" type="number" min="5" max="120" value="30">
        </label>

        <div class="admin-hint">Guarda em <code>config/runtime.json</code></div>
      </div>
    </div>
  </section>

  <section class="admin-panel" data-panel="logs" style="display:none">
    <div class="admin-card">
      <div class="admin-card-head">
        <div class="title">Logs</div>
        <div class="actions">
          <button type="button" class="admin-btn" id="btnReloadLogs">Recarregar</button>
        </div>
      </div>

      <div id="logsTable" class="admin-table-wrap">A carregar…</div>
    </div>
  </section>
</div>

<div id="toast" class="toast" style="display:none;"></div>

<script src="/ZoneGoal/js/pages/admin.js"></script>

<?php require __DIR__ . "/includes/layout_end.php"; ?>

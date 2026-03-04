const ADMIN = "/ZoneGoal/actions/admin";
console.log("[ADMIN] base:", ADMIN);

function showToast(msg){
  const t = document.getElementById("toast");
  if(!t) return;
  t.textContent = msg;
  t.style.display = "block";
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(()=> t.style.display="none", 2200);
}

async function fetchJson(url, opts){
  const r = await fetch(url, opts);
  const t = await r.text();

  // DEBUG útil
  console.log("[ADMIN FETCH]", url, "status", r.status);

  try { return JSON.parse(t); }
  catch {
    console.log("[ADMIN JSON PARSE FAIL]", t.slice(0, 300));
    return { ok:false, error:"Resposta inválida", status:r.status, raw:t };
  }
}

function setTab(tab){
  document.querySelectorAll(".admin-tab").forEach(b =>
    b.classList.toggle("active", b.dataset.tab===tab)
  );

  document.querySelectorAll(".admin-panel").forEach(p => {
    p.style.display = (p.dataset.panel===tab ? "block":"none");
  });

  localStorage.setItem("zg_admin_tab", tab);
}

/* ------------------ USERS ------------------ */
async function loadUsers(q=""){
  const box = document.getElementById("usersTable");
  if(!box) return;

  box.textContent = "A carregar…";

  const url = `${ADMIN}/admin_users.php?q=${encodeURIComponent(q)}`;
  const j = await fetchJson(url);

  console.log("[ADMIN] users:", j);

  if(!j.ok){ box.textContent = j.error || "Erro"; return; }
  box.innerHTML = j.html;

  box.querySelectorAll("[data-action='role']").forEach(btn=>{
    btn.addEventListener("click", async ()=>{
      const id = btn.dataset.id;
      const role = btn.dataset.role;

      const res = await fetchJson(`${ADMIN}/admin_user_role.php`, {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`id=${encodeURIComponent(id)}&role=${encodeURIComponent(role)}`
      });

      if(!res.ok){ showToast(res.error||"Erro"); return; }
      showToast("Role atualizada ✅");
      loadUsers(document.getElementById("userSearch")?.value.trim() || "");
    });
  });

  box.querySelectorAll("[data-action='delete']").forEach(btn=>{
    btn.addEventListener("click", async ()=>{
      if(!confirm("Apagar utilizador?")) return;

      const id = btn.dataset.id;
      const res = await fetchJson(`${ADMIN}/admin_user_delete.php`, {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`id=${encodeURIComponent(id)}`
      });

      if(!res.ok){ showToast(res.error||"Erro"); return; }
      showToast("Utilizador apagado ✅");
      loadUsers(document.getElementById("userSearch")?.value.trim() || "");
    });
  });
}

/* ------------------ CACHE ------------------ */
async function loadCacheStats(prefix=""){
  const box = document.getElementById("cacheStats");
  if(!box) return;

  box.textContent = "A carregar…";

  const j = await fetchJson(`${ADMIN}/admin_cache_stats.php?prefix=${encodeURIComponent(prefix)}`);
  if(!j.ok){ box.textContent = j.error || "Erro"; return; }

  box.innerHTML = `
    <div class="kpi"><div class="label">Ficheiros</div><div class="value">${j.files}</div></div>
    <div class="kpi"><div class="label">Tamanho</div><div class="value">${j.size_h}</div></div>
    <div class="kpi"><div class="label">Prefixo</div><div class="value">${j.prefix || "—"}</div></div>
  `;
}

async function clearCache(prefix=""){
  const res = await fetchJson(`${ADMIN}/admin_cache_clear.php`, {
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body:`prefix=${encodeURIComponent(prefix)}`
  });

  if(!res.ok){ showToast(res.error || "Erro"); return; }
  showToast(`Cache limpo ✅ (${res.deleted} ficheiros)`);
  loadCacheStats(prefix);
}

/* ------------------ SETTINGS ------------------ */
async function loadSettings(){
  const j = await fetchJson(`${ADMIN}/admin_settings_get.php`);
  if(!j.ok){ showToast(j.error || "Erro"); return; }

  document.getElementById("apiEnabled").value = j.api_enabled ? "1" : "0";
  document.getElementById("favoritesLimit").value = j.favorites_limit ?? 10;
  document.getElementById("refreshInterval").value = j.refresh_interval ?? 30;

  showToast("Config carregada ✅");
}

async function saveSettings(){
  const api_enabled = document.getElementById("apiEnabled").value;
  const favorites_limit = document.getElementById("favoritesLimit").value;
  const refresh_interval = document.getElementById("refreshInterval").value;

  const j = await fetchJson(`${ADMIN}/admin_settings_save.php`, {
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body:`api_enabled=${encodeURIComponent(api_enabled)}&favorites_limit=${encodeURIComponent(favorites_limit)}&refresh_interval=${encodeURIComponent(refresh_interval)}`
  });

  if(!j.ok){ showToast(j.error || "Erro"); return; }
  showToast("Config guardada ✅");
}

/* ------------------ LOGS ------------------ */
async function loadLogs(){
  const box = document.getElementById("logsTable");
  if(!box) return;

  box.textContent = "A carregar…";

  const j = await fetchJson(`${ADMIN}/admin_logs.php`);
  if(!j.ok){ box.textContent = j.error || "Erro"; return; }

  box.innerHTML = j.html;
}

/* ------------------ INIT ------------------ */
document.addEventListener("DOMContentLoaded", ()=>{
  console.log("[ADMIN] DOM ready ✅");

  // Tabs
  document.querySelectorAll(".admin-tab").forEach(btn=>{
    btn.addEventListener("click", ()=> setTab(btn.dataset.tab));
  });

  setTab(localStorage.getItem("zg_admin_tab") || "users");

  // Users
  document.getElementById("btnReloadUsers")?.addEventListener("click", ()=>{
    console.log("[ADMIN] clique Recarregar Users ✅");
    loadUsers(document.getElementById("userSearch")?.value.trim() || "");
  });

  document.getElementById("userSearch")?.addEventListener("input", (e)=>{
    loadUsers(e.target.value.trim());
  });

  loadUsers("");

  // Cache
  document.getElementById("btnCacheStats")?.addEventListener("click", ()=>{
    loadCacheStats(document.getElementById("cachePrefix")?.value.trim() || "");
  });

  document.getElementById("btnCacheClear")?.addEventListener("click", ()=>{
    clearCache(document.getElementById("cachePrefix")?.value.trim() || "");
  });

  loadCacheStats("");

  // Settings
  document.getElementById("btnLoadSettings")?.addEventListener("click", loadSettings);
  document.getElementById("btnSaveSettings")?.addEventListener("click", saveSettings);

  // Logs
  document.getElementById("btnReloadLogs")?.addEventListener("click", loadLogs);
  loadLogs();
});

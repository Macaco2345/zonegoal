document.addEventListener("DOMContentLoaded", () => {
  const BASE = "/ZoneGoal";
  const favoritesArea = document.getElementById("favoritesArea");
  if (!favoritesArea) return;

  async function fetchJsonSafe(url, options = {}) {
    const res = await fetch(url, { cache: "no-store", ...options });
    const text = await res.text();
    try {
      return { ok: true, data: JSON.parse(text), status: res.status };
    } catch {
      return { ok: false, status: res.status, raw: text.slice(0, 1200) };
    }
  }

  async function loadFavorites() {
    favoritesArea.innerHTML = `<div class="empty-message">A carregar favoritos…</div>`;

    const r = await fetchJsonSafe(`${BASE}/actions/dashboard/dashboard_refresh.php`);
    if (!r.ok) {
      console.error("[dashboard_refresh] NÃO JSON:", r.status, r.raw);
      favoritesArea.innerHTML = `<div class="empty-message">Erro de ligação. (ver console)</div>`;
      return;
    }

    const j = r.data;
    if (!j.ok) {
      console.error("[dashboard_refresh] ok=false:", j);
      favoritesArea.innerHTML = `<div class="empty-message">${j.error || "Erro a carregar favoritos."}</div>`;
      return;
    }

    favoritesArea.innerHTML = j.html || `<div class="empty-message">Sem favoritos.</div>`;
  }

  // para o main.js poder chamar depois do toggle
  window.dashboardRefreshFavorites = loadFavorites;

  loadFavorites();
  setInterval(loadFavorites, 25000);
});
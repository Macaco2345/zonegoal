document.addEventListener("DOMContentLoaded", () => {
  console.log("[ZoneGoal] main.js carregado ✅");
  console.log("[ZoneGoal] forms na página:", document.querySelectorAll("form").length);

  function fetchJsonSafe(url, options = {}) {
    return fetch(url, { cache: "no-store", ...options })
      .then(async (res) => {
        const text = await res.text();
        try {
          return { ok: true, data: JSON.parse(text), status: res.status };
        } catch {
          return { ok: false, status: res.status, raw: text.slice(0, 1200) };
        }
      });
  }

  // Tabs (chips)
  document.addEventListener(
    "click",
    (e) => {
      const chip = e.target.closest(".filter-chip[data-view]");
      if (!chip) return;

      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      const view = chip.dataset.view;

      document.querySelectorAll(".filter-chip[data-view]").forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");

      document.querySelectorAll(".match-section[data-section]").forEach((sec) => {
        sec.style.display = sec.dataset.section === view ? "block" : "none";
      });

      localStorage.setItem("zonegoal_view", view);
    },
    true
  );

  // Favoritos (delegado para funcionar em HTML carregado via AJAX)
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".favorite-btn[data-match-id]");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    if (!window.ZONEGOAL_LOGGED_IN) {
      showToast("Inicia sessão para adicionares aos favoritos ⭐");
      return;
    }

    const matchId = btn.dataset.matchId;
    if (!matchId) return;

    const r = await fetchJsonSafe("/ZoneGoal/actions/favorites/favorite_toggle.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "Accept": "application/json",
      },
      body: "match_id=" + encodeURIComponent(matchId),
    });

    if (!r.ok) {
      console.error("[favorite_toggle] NÃO JSON:", r.status, r.raw);
      showToast("Erro no servidor.");
      return;
    }

    const json = r.data;
    if (!json.ok) {
      showToast(json.error || "Erro ao atualizar favorito.");
      return;
    }

    if (json.action === "added") {
      btn.classList.add("active");
      btn.textContent = "⭐";
      showToast("⭐ Adicionado aos favoritos");
    } else {
      btn.classList.remove("active");
      btn.textContent = "☆";
      showToast("❌ Removido dos favoritos");

      // ✅ Se estava no bloco “Jogos seguidos”, remove a linha logo
      if (btn.dataset.from === "favorites") {
        const row = btn.closest(".match-row");
        if (row) row.remove();
      }
    }

    // ✅ refresh do bloco favoritos (dashboard)
    if (typeof window.dashboardRefreshFavorites === "function") {
      window.dashboardRefreshFavorites();
    }
  });
});

function showToast(message) {
  const toast = document.getElementById("toast");
  if (!toast) return;

  toast.textContent = message;
  toast.style.display = "block";

  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => {
    toast.style.display = "none";
  }, 2200);
}
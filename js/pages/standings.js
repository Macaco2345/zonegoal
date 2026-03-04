console.log("[ZoneGoal] pages/standings.js carregado ✅");

document.addEventListener("DOMContentLoaded", () => {

  const BASE = "/ZoneGoal";

  const $ = (s) => document.querySelector(s);

  const area      = $("#standingsArea");
  const leagueSel = $("#leagueSelect");
  const seasonInp = $("#seasonInput");
  const loadBtn   = $("#loadBtn");

  const esc = (s) => String(s ?? "")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");

  const setLoading = (msg="A carregar…") => {
    if (area) area.innerHTML = `<div class="empty-message">${esc(msg)}</div>`;
  };

  const pickDefaultSeason = () => {
    const opt = leagueSel?.selectedOptions?.[0];

    seasonInp.value = String(
      opt?.dataset?.season
        ? Number(opt.dataset.season)
        : new Date().getFullYear()
    );
  };

  async function fetchJson(url) {
    const res = await fetch(url, { cache: "no-store" });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const txt = await res.text();

    try {
      return JSON.parse(txt);
    } catch {
      console.error("Resposta inválida:", txt);
      throw new Error("JSON inválido");
    }
  }

  function groupTitle(rows, i, total) {
    if (total <= 1) return "";

    const apiName  = rows?.[0]?.group;
    const fallback = `Grupo ${String.fromCharCode(65 + i)}`;

    return `<div class="group-title">${esc(apiName || fallback)}</div>`;
  }

  function render(meta, groups) {

    if (!Array.isArray(groups) || groups.length === 0) {
      if (area) area.innerHTML = `<div class="empty-message">Sem classificação.</div>`;
      return;
    }

    const head = `
      <div class="standings-head">
        ${meta.logo ? `<img class="standings-logo" src="${esc(meta.logo)}">` : ""}
        <div class="standings-title">
          <div class="t1">${esc(meta.league || "Liga")}</div>
          <div class="t2">${esc(meta.country || "")} • Época ${esc(meta.season || "")}</div>
        </div>
      </div>
    `;

    const total = Number(meta.groups || groups.length || 1);

    const tables = groups.map((rows, i) => `
      <div class="standings-table">
        ${groupTitle(rows, i, total)}

        <div class="st-row st-head">
          <div>#</div>
          <div>Equipa</div>
          <div>J</div>
          <div>DG</div>
          <div>PTS</div>
        </div>

        ${(rows || []).map(r => {
          const team = r.team || {};

          return `
            <div class="st-row">
              <div>${esc(r.rank)}</div>

              <div class="tm">
                ${team.logo ? `<img class="tm-logo" src="${esc(team.logo)}">` : ""}
                <span class="tm-name">${esc(team.name)}</span>
              </div>

              <div>${esc(r.all?.played ?? 0)}</div>
              <div>${esc(r.goalsDiff ?? 0)}</div>
              <div class="pts">${esc(r.points ?? 0)}</div>
            </div>
          `;
        }).join("")}
      </div>
    `).join("");

    if (area) area.innerHTML = head + tables;
  }

  async function load() {

    const league = Number(leagueSel?.value || 0);
    const season = Number(seasonInp?.value || 0);

    if (!league || !season) {
      if (area) area.innerHTML = `<div class="empty-message">Escolhe liga e época.</div>`;
      return;
    }

    setLoading("A carregar classificação…");

    try {

      const url = `${BASE}/actions/standings/standings_data.php?league=${league}&season=${season}`;
      const j   = await fetchJson(url);

      if (!j?.ok) {
        throw new Error(j?.error || "Erro API");
      }

      render(j.meta || {}, j.data || []);

    } catch (err) {

      console.error("[ZoneGoal] standings erro:", err);

      if (area) {
        area.innerHTML = `<div class="empty-message">Erro ao carregar standings.</div>`;
      }
    }
  }

  leagueSel?.addEventListener("change", pickDefaultSeason);
  loadBtn?.addEventListener("click", load);

  // Defaults
  if (window.ZG_STANDINGS_DEFAULT?.league) {
    leagueSel.value = String(window.ZG_STANDINGS_DEFAULT.league);
  }

  pickDefaultSeason();

  if (window.ZG_STANDINGS_DEFAULT?.season) {
    seasonInp.value = String(window.ZG_STANDINGS_DEFAULT.season);
  }

  load();
});
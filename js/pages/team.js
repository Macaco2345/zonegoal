document.addEventListener("DOMContentLoaded", () => {
  console.log("[team.js] loaded", location.href);

  const BASE = "/ZoneGoal";
  const $ = (s) => document.querySelector(s);

  const teamId = Number(window.ZG_TEAM_ID || 0);
const season = Number(window.ZG_CONTEXT_SEASON || 2025);

  const hero = $("#teamHero");
  const info = $("#teamInfo");
  const fx = $("#teamFixtures");
  const fxTitle = $("#fxTitle");
  const tabs = document.querySelectorAll(".tab-btn");

  let mode = "next";
  let lastLeagueFallback = 0;

  const esc = (s) =>
    String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  async function fetchJson(url) {
    const res = await fetch(url, {
      cache: "no-store",
      headers: { Accept: "application/json" },
    });
    const txt = await res.text();
    try {
      return JSON.parse(txt);
    } catch {
      console.error("[team] resposta não é JSON:", url, txt.slice(0, 600));
      throw new Error("Resposta não é JSON");
    }
  }

  function fmtDate(ts) {
    if (!ts) return "—";
    const d = new Date(ts * 1000);
    return d.toLocaleString("pt-PT", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function statusBadge(short) {
    const s = String(short || "");
    if (["1H", "HT", "2H", "ET", "P", "BT"].includes(s))
      return `<span class="badge live">LIVE</span>`;
    if (["FT", "AET", "PEN"].includes(s))
      return `<span class="badge fin">FINAL</span>`;
    return `<span class="badge up">AGENDADO</span>`;
  }

  function flagUrl(country) {
    const map = {
      Portugal: "pt",
      Spain: "es",
      France: "fr",
      Germany: "de",
      Italy: "it",
      England: "gb",
      "United Kingdom": "gb",
      Brazil: "br",
      Argentina: "ar",
      Netherlands: "nl",
      Belgium: "be",
      USA: "us",
      "United States": "us",
    };
    const code = map[country] || "";
    return code ? `https://flagcdn.com/w40/${code}.png` : "";
  }

  function addRow(rows, k, vHtml) {
    if (vHtml === null || vHtml === undefined) return;
    const v = String(vHtml).trim();
    if (!v || v === "—") return;
    rows.push([k, vHtml]);
  }

  function renderInfo(rows) {
    if (!info) return;
    info.innerHTML = `
      <div class="info-list">
        ${rows
          .map(
            ([k, v]) => `
          <div class="info-row">
            <div class="info-k">${esc(k)}</div>
            <div class="info-v">${v}</div>
          </div>
        `
          )
          .join("")}
      </div>
    `;
  }

  function setInfoValue(label, html) {
    if (!info) return;
    const row = [...info.querySelectorAll(".info-row")].find(
      (r) => r.querySelector(".info-k")?.textContent?.trim() === label
    );
    if (row) row.querySelector(".info-v").innerHTML = html;
  }

  /* ========= COMPETIÇÕES (hero: só liga principal) ========= */

  function leagueChip(lg) {
    const logo = lg.logo ? `<img class="lgmini" src="${esc(lg.logo)}" alt="">` : "";
    const flag = lg.flag ? `<img class="lgflag" src="${esc(lg.flag)}" alt="">` : "";
    const type = lg.type ? `<span class="lgtype">${esc(lg.type)}</span>` : "";
    return `
      <div class="lgchip" title="${esc(lg.name)}">
        ${logo}${flag}
        <span class="lgname">${esc(lg.name)}</span>
        ${type}
      </div>
    `;
  }

  async function loadLeagues() {
    const wrap = document.getElementById("teamLeagues");
    if (!wrap) return [];

    wrap.innerHTML = `<div class="muted">A carregar competição…</div>`;

    try {
      const j = await fetchJson(
        `${BASE}/actions/team/team_leagues.php?team=${teamId}&season=${season}`
      );
      if (!j.ok) throw new Error(j.error || "Erro team_leagues");

      const list = j.data || [];
      if (!list.length) {
        wrap.innerHTML = `<div class="muted">Sem competição nesta época.</div>`;
        return [];
      }

      // League primeiro
      list.sort((a, b) => (a.type === "League" ? -1 : 1) - (b.type === "League" ? -1 : 1));

      // ✅ só a liga principal no hero
      const main = list.find((x) => x.type === "League") || list[0];
      wrap.innerHTML = `<div class="lgwrap">${leagueChip(main)}</div>`;

      return list;
    } catch (e) {
      console.error(e);
      wrap.innerHTML = `<div class="muted">Erro a carregar competição.</div>`;
      return [];
    }
  }

  /* ========= HERO ========= */

  function renderHero(teamObj) {
    if (!hero) return;

    const team = teamObj?.team || {};
    const venue = teamObj?.venue || {};
    const flag = flagUrl(team.country);

    hero.innerHTML = `
      <div class="team-hero-left">
        <span class="logo-box club">${team.logo ? `<img src="${esc(team.logo)}" alt="">` : ""}</span>

        <div class="team-hero-text">
          <div class="team-name">${esc(team.name || "Equipa")}</div>

          <div class="team-sub">
            ${flag ? `<span class="flag-box"><img src="${esc(flag)}" alt=""></span>` : ""}
            <span>${esc(team.country || "—")}</span>
            ${team.founded ? `<span class="dot">•</span><span>Fundado ${esc(team.founded)}</span>` : ""}
            ${venue.name ? `<span class="dot">•</span><span>${esc(venue.name)}</span>` : ""}
          </div>

          <div class="team-sub sub2">
            <span class="muted">Época ${esc(season)}</span>
          </div>

          <div class="team-leagues" id="teamLeagues"></div>
        </div>
      </div>

      <div class="team-hero-right">
        <span class="badge soft">Team ID ${esc(team.id || teamId)}</span>
        <span class="badge gold">Época ${esc(season)}</span>
      </div>
    `;
  }

  /* ========= PLANTEL (minutos + posições) ========= */

  function renderSquadByPositions(list, squadEl) {
    if (!Array.isArray(list) || !list.length) {
      squadEl.innerHTML = `<div class="empty-message">Sem jogadores com minutos nesta época.</div>`;
      return;
    }

    const posLabelPT = (raw) => {
      const x = String(raw || "").toLowerCase();
      if (x.includes("att")) return "Atacantes";
      if (x.includes("mid")) return "Médios";
      if (x.includes("def")) return "Defesas";
      if (x.includes("goal")) return "Guarda-redes";
      return "Outros";
    };

    const order = ["Atacantes", "Médios", "Defesas", "Guarda-redes", "Outros"];

    const groups = {};
    for (const p of list) {
      const g = posLabelPT(p.position_raw);
      (groups[g] ||= []).push(p);
    }

    for (const k of Object.keys(groups)) {
      groups[k].sort((a, b) => Number(b.minutes || 0) - Number(a.minutes || 0));
    }

    squadEl.innerHTML = `
      <div class="squad-groups">
        ${order
          .filter((k) => (groups[k] || []).length)
          .map(
            (k) => `
          <div class="sg">
            <div class="sg-head">
              <div class="sg-title">${esc(k)}</div>
              <div class="sg-count">${groups[k].length}</div>
            </div>

            <div class="sg-list">
              ${(groups[k] || [])
                .map(
                  (p) => `
                <a class="srow" href="${BASE}/player.php?id=${Number(p.id || 0)}">
                  ${
                    p.photo
                      ? `<img class="pface" src="${esc(p.photo)}" alt="">`
                      : `<div class="pface ph"></div>`
                  }
                  <div class="pname">${esc(p.name || "Jogador")}</div>
                  <div class="pmins">${esc(p.minutes)}'</div>
                </a>
              `
                )
                .join("")}
            </div>
          </div>
        `
          )
          .join("")}
      </div>
    `;
  }

  async function loadSquadByMinutes() {
    const squadEl = document.querySelector("#teamSquad");
    if (!squadEl) return;

    squadEl.innerHTML = `<div class="empty-message">A carregar…</div>`;

    const j = await fetchJson(
      `${BASE}/actions/team/team_players_minutes.php?team=${teamId}&season=${season}`
    );
    if (!j.ok) throw new Error(j.error || "Erro players_minutes");

    const MIN_MINUTES = 30; // podes mudar: 10, 30, 45
const data = (j.data || []).filter(x => Number(x.minutes || 0) >= MIN_MINUTES);
    renderSquadByPositions(data, squadEl);
  }

  /* ========= FIXTURES ========= */

  function renderFixtures(fixtures) {
    if (!fx) return;

    if (!Array.isArray(fixtures) || !fixtures.length) {
      fx.innerHTML = `<div class="empty-message">Sem jogos.</div>`;
      return;
    }

    fx.innerHTML = fixtures
      .map((f) => {
        const id = f?.fixture?.id || 0;
        const ts = f?.fixture?.timestamp || 0;
        const st = f?.fixture?.status?.short || "";
        const hg = f?.goals?.home;
        const ag = f?.goals?.away;

        const home = f?.teams?.home || {};
        const away = f?.teams?.away || {};

        const scoreA = hg === null || hg === undefined ? "—" : hg;
        const scoreB = ag === null || ag === undefined ? "—" : ag;

        return `
          <a class="fx" href="${BASE}/match.php?id=${id}">
            <div class="fx-top">
              <div>${esc(fmtDate(ts))}</div>
              <div>${statusBadge(st)}</div>
            </div>
            <div class="fx-mid">
              <div class="t">
                ${home.logo ? `<span class="logo-box xs"><img src="${esc(home.logo)}" alt=""></span>` : ""}
                <div class="tname">${esc(home.name || "Casa")}</div>
              </div>
              <div class="sc">${esc(scoreA)} - ${esc(scoreB)}</div>
              <div class="t right">
                <div class="tname">${esc(away.name || "Fora")}</div>
                ${away.logo ? `<span class="logo-box xs"><img src="${esc(away.logo)}" alt=""></span>` : ""}
              </div>
            </div>
          </a>
        `;
      })
      .join("");
  }

  /* ========= FORMA ========= */

  function formHtml(form) {
    if (!Array.isArray(form) || !form.length) return `<span class="muted">—</span>`;

    const cls = (r) => (r === "W" ? "w" : r === "D" ? "d" : "l");
    const label = (r) => (r === "W" ? "V" : r === "D" ? "E" : "D");

    return `
      <div class="form-row">
        ${form
          .map((x) => {
            const r = x?.r || "D";
            const title = `${x?.home || ""} ${x?.hs ?? "—"}-${x?.as ?? "—"} ${x?.away || ""}`;
            const logo = x?.oppLogo || "";
            return `
              <a class="form-item f-${cls(r)}" href="${BASE}/match.php?id=${Number(x.id || 0)}" title="${esc(title)}">
                <span class="form-badge">${label(r)}</span>
                <span class="form-opp">${logo ? `<img src="${esc(logo)}" alt="">` : ""}</span>
              </a>
            `;
          })
          .join("")}
      </div>
    `;
  }

  async function loadForm(leagueIdFallback) {
    const tryLeague = async (lg) => {
      const url = `${BASE}/actions/team/team_form.php?team=${teamId}&league=${Number(
        lg || 0
      )}&season=${season}&limit=5`;
      const j = await fetchJson(url);
      if (!j.ok) throw new Error(j.error || "Erro forma");
      return j.form || [];
    };

    try {
      const form = await tryLeague(leagueIdFallback || 0);
      setInfoValue("Forma (últimos 5)", formHtml(form));
    } catch (e) {
      console.warn(e);
      setInfoValue("Forma (últimos 5)", `<span class="muted">—</span>`);
    }
  }

  /* ========= TÍTULOS (agrupados) ========= */

function trophiesHtml(list) {
  if (!Array.isArray(list) || !list.length) {
    return `
      <div class="no-trophies">
        ⚠️ Esta equipa não possui títulos na Primeira Divisão ou competições superiores.
      </div>
    `;
  }

  // dedupe + normalizar type
  const map = new Map();
  list.forEach((item) => {
    const leagueRaw = String(item.league || "").trim();
    if (!leagueRaw) return;

    const key = leagueRaw.toLowerCase();
    const count = Number(item.count || 0) || 0;
    const typeRaw = String(item.type || "").trim();

    if (!map.has(key) || count > (map.get(key).count || 0)) {
      map.set(key, { league: leagueRaw, count, rawType: typeRaw });
    }
  });

  const normalized = [...map.values()].map((it) => {
    const type = String(it.rawType || "").trim().toLowerCase();
    const leagueLower = String(it.league || "").toLowerCase();

    // ✅ exceções nacionais (supertaças nacionais)
    const isNationalOverride =
      leagueLower.includes("trophée des champions") ||
      leagueLower.includes("trophee des champions") ||
      leagueLower.includes("community shield") ||
      leagueLower.includes("supercopa de espa") ||
      leagueLower.includes("supercoppa italiana") ||
      leagueLower.includes("dfl-supercup");

    // ✅ deteção melhorada (por type e por nome)
    const isInternationalByType =
      type.includes("international") ||
      type.includes("inter") ||
      type.includes("intl") ||
      type.includes("uefa") ||
      type.includes("fifa");

    const isInternationalByName =
      leagueLower.includes("campeões") ||
      leagueLower.includes("champions") ||
      leagueLower.includes("liga dos campe") ||
      leagueLower.includes("taça dos campe") ||
      leagueLower.includes("taça uefa") ||
      leagueLower.includes("liga europa") ||
      leagueLower.includes("europa league") ||
      leagueLower.includes("uefa") ||
      leagueLower.includes("europe") ||
      leagueLower.includes("supertaça europe") ||
      leagueLower.includes("uefa super cup") ||
      leagueLower.includes("super cup") ||
      leagueLower.includes("intercontinental") ||
      leagueLower.includes("taça das taças") ||
      leagueLower.includes("cup winners") ||              // ✅ FALTAVA A VÍRGULA AQUI
      leagueLower.includes("iberoamericana") ||
      leagueLower.includes("inter-cities fairs cup") ||   // ✅ Real Madrid
      leagueLower.includes("mundial") ||                  // ✅ “Mundial de Clubes”
      leagueLower.includes("world cup") ||                // ✅ “Club World Cup”
      leagueLower.includes("club world cup") ||           // ✅ específico
      leagueLower.includes("fifa");                       // ✅ FIFA

    // ✅ decisão final (override ganha sempre)
    const isInternational =
      !isNationalOverride && (isInternationalByType || isInternationalByName);

    return {
      league: it.league,
      count: Number(it.count || 0),
      type: isInternational ? "International" : "National",
    };
  });

  const groups = { National: [], International: [] };
  normalized.forEach((t) => {
    if (t.type === "International") groups.International.push(t);
    else groups.National.push(t);
  });

  const sum = (arr) => arr.reduce((acc, x) => acc + Number(x.count || 0), 0);

  const renderSection = (label, arr) => {
    if (!arr.length) return "";
    return `
      <div class="tsec">
        <div class="tsec-head">
          <div class="tsec-title">${esc(label)}</div>
          <div class="tsec-total">${esc(sum(arr))}</div>
        </div>

        <div class="tsec-list">
          ${arr
            .sort((a, b) => Number(b.count || 0) - Number(a.count || 0))
            .map(
              (t) => `
            <div class="trow">
              <div class="tname">${esc(t.league)}</div>
              <div class="tcount">x${esc(t.count)}</div>
            </div>
          `
            )
            .join("")}
        </div>
      </div>
    `;
  };

  return `
    <div class="trophies-wrap">
      ${renderSection("🇵🇹 Nacionais", groups.National)}
      ${renderSection("🌍 Internacionais", groups.International)}
    </div>
  `;
}

  async function loadTeamTrophies() {
    try {
      const j = await fetchJson(`${BASE}/actions/team/team_trophies.php?team=${teamId}`);
      if (!j.ok) throw new Error(j.error || "Erro trophies");
      setInfoValue("Títulos", trophiesHtml(j.data || []));
    } catch (e) {
      console.warn(e);
      setInfoValue("Títulos", `<span class="muted">—</span>`);
    }
  }

  /* ========= TEAM DATA ========= */

  async function loadTeam() {
    const t = await fetchJson(`${BASE}/actions/team/team_data.php?id=${teamId}&season=${season}`);
    if (!t.ok) throw new Error(t.error || "Erro team_data");

    renderHero(t.team);

    const teamObj = t.team || {};
    const team = teamObj.team || {};
    const venue = teamObj.venue || {};

    const rows = [];
    addRow(rows, "Cidade", esc(venue.city || "—"));
    addRow(rows, "Estádio", esc(venue.name || "—"));
    addRow(rows, "Capacidade", esc(venue.capacity || "—"));
    addRow(rows, "Código", esc(team.code || "—"));

    if (team.website) {
      addRow(
        rows,
        "Website",
        `<a class="kv-link" href="${esc(team.website)}" target="_blank" rel="noreferrer">Abrir</a>`
      );
    }

    addRow(rows, "Forma (últimos 5)", `<span class="muted">A carregar…</span>`);
    addRow(rows, "Títulos", `<span class="muted">A carregar…</span>`);

    renderInfo(rows);
    return t;
  }

  async function loadFixtures(leagueIdFallback) {
    if (!fx || !fxTitle) return;

    fxTitle.textContent = mode === "next" ? "Próximos 10" : "Últimos 10";
    fx.innerHTML = `<div class="empty-message">A carregar…</div>`;

    const lg = Number(leagueIdFallback || 0);
    const url = `${BASE}/actions/team/team_fixtures.php?id=${teamId}&league=${lg}&season=${season}&mode=${mode}`;
    const j = await fetchJson(url);
    if (!j.ok) throw new Error(j.error || "Erro fixtures");

    renderFixtures(j.data || []);
  }

  /* ========= TABS ========= */

  tabs.forEach((b) => {
    b.addEventListener("click", () => {
      tabs.forEach((x) => x.classList.remove("active"));
      b.classList.add("active");
      mode = b.dataset.tab || "next";

      // recarrega só fixtures (não precisa de recarregar tudo)
      loadFixtures(lastLeagueFallback).catch(console.error);
    });
  });

  /* ========= INIT ========= */

  async function init() {
    if (!teamId || !hero) return;

    try {
      await loadTeam();
      await loadSquadByMinutes();

      const leagues = await loadLeagues();
      const main = leagues.find((x) => x.type === "League") || leagues[0];
      lastLeagueFallback = Number(main?.id || 0);

      await loadForm(lastLeagueFallback);
      await loadTeamTrophies();
      await loadFixtures(lastLeagueFallback);
    } catch (e) {
      console.error(e);
      if (hero) hero.innerHTML = `<div class="empty-message">Erro a carregar equipa.</div>`;
    }
  }

  init().catch(console.error);
});
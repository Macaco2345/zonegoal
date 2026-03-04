// /js/pages/player.js
document.addEventListener("DOMContentLoaded", () => {
  const BASE = "/ZoneGoal";
  const id = Number(window.ZG_PLAYER_ID || 0);

  const header = document.getElementById("playerHeader");
  const compSelect = document.getElementById("compSelect");
  const tabContent = document.getElementById("playerTabContent");
  const tabsWrap = document.getElementById("playerTabs");

  if (!id || !header || !compSelect || !tabContent || !tabsWrap) return;

  // ✅ chave especial para agregação
  const ALL_KEY = "__all__";

  // ---------------- utils ----------------
  const esc = (s) =>
    String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  async function fetchJson(url) {
    const r = await fetch(url, { cache: "no-store", headers: { Accept: "application/json" } });
    const t = await r.text();
    try {
      return JSON.parse(t);
    } catch {
      console.error("[player] resposta não é JSON:", t.slice(0, 800));
      throw new Error("Resposta não é JSON");
    }
  }

  const n = (v, d = 0) => {
    const x = Number(v);
    return Number.isFinite(x) ? x : d;
  };

  const fmt = (v, digits = 0) => {
    const x = n(v, 0);
    return digits ? x.toFixed(digits) : String(Math.round(x));
  };

  const per90 = (value, minutes) => {
    const mins = n(minutes, 0);
    if (mins <= 0) return 0;
    return (n(value, 0) / mins) * 90;
  };

  const pct = (a, b) => {
    const den = n(b, 0);
    if (den <= 0) return 0;
    return (n(a, 0) / den) * 100;
  };

  function flagUrl(country) {
    const m = {
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
      Colombia: "co",
    };
    const code = m[country] || "";
    return code ? `https://flagcdn.com/w40/${code}.png` : "";
  }

  const chip = (text, kind = "neutral") => `<span class="chip chip-${kind}">${esc(text)}</span>`;

  const kpi = (label, value, hint = "") => `
    <div class="kpi">
      <div class="kpi-k">${esc(label)}</div>
      <div class="kpi-v">${esc(value)}</div>
      ${hint ? `<div class="kpi-h">${esc(hint)}</div>` : ""}
    </div>
  `;

  const metric = (k, v, sub = "") => `
    <div class="m">
      <div class="k">${esc(k)}</div>
      <div class="v">${esc(v ?? "—")}</div>
      ${sub ? `<div class="s">${esc(sub)}</div>` : ""}
    </div>
  `;

  const safeDatePT = (iso) => {
    if (!iso) return "—";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return String(iso);
    return d.toLocaleDateString("pt-PT");
  };

  // ---------------- competition picking ----------------
  function isInternationalLeagueName(name = "") {
    const s = String(name).toLowerCase();
    return (
      s.includes("world cup") ||
      s.includes("qualification") ||
      s.includes("qualifying") ||
      s.includes("friendlies") ||
      s.includes("nations league") ||
      s.includes("copa america") ||
      s.includes("euro") ||
      s.includes("international") ||
      s.includes("qualificação")
    );
  }

  function scoreStat(s) {
    const apps = n(s?.games?.appearences ?? s?.games?.appearances, 0);
    const mins = n(s?.games?.minutes, 0);
    const type = String(s?.league?.type || ""); // League | Cup
    const name = String(s?.league?.name || "");

    let pts = 0;

    if (type.toLowerCase() === "league") pts += 120;
    else pts += 40;

    if (isInternationalLeagueName(name)) pts -= 90;

    pts += Math.min(apps, 50) * 2;
    pts += Math.min(mins, 4500) / 35;

    const big = [
      "premier league",
      "la liga",
      "serie a",
      "bundesliga",
      "ligue 1",
      "primeira liga",
      "liga portugal",
      "eredivisie",
    ];
    if (big.some((x) => name.toLowerCase().includes(x))) pts += 50;

    return pts;
  }

  function pickDefault(stats) {
    if (!Array.isArray(stats) || !stats.length) return null;
    const withGames = stats.filter((s) => n(s?.games?.appearences ?? s?.games?.appearances, 0) > 0);
    const list = withGames.length ? withGames : stats;
    return [...list].sort((a, b) => scoreStat(b) - scoreStat(a))[0] || list[0] || null;
  }

  // ✅ KEY POR IDs (evita “UEFA Super Cup” errada)
  function compKey(s) {
    const lid = n(s?.league?.id, 0);
    const season = String(s?.league?.season ?? "").trim();
    const tid = n(s?.team?.id, 0);
    return `${lid}__${season}__${tid}`;
  }

  // ✅ agrega todas as competições numa só (SEM duplicar)
  function aggregateAllStats(stats) {
    if (!Array.isArray(stats) || !stats.length) return null;

    // dedupe por (league.id + season + team.id)
    const seen = new Set();
    const listRaw = stats.filter((s) => n(s?.games?.appearences ?? s?.games?.appearances, 0) > 0);

    const list = [];
    for (const s of listRaw) {
      const key = compKey(s);
      if (!key || seen.has(key)) continue;
      seen.add(key);
      list.push(s);
    }

    if (!list.length) return null;

    const agg = {
      league: { name: "Todas as competições", season: "—", type: "Mixed", logo: "" },
      team: { id: 0, name: "—", logo: "" },
      games: { appearences: 0, minutes: 0, position: "—", rating: "—" },
      goals: { total: 0, assists: 0 },
      shots: { total: 0, on: 0 },
      cards: { yellow: 0, red: 0 },
    };

    let bestMins = -1;
    let ratingWeighted = 0;
    let ratingMinutes = 0;

    for (const s of list) {
      const apps = n(s?.games?.appearences ?? s?.games?.appearances, 0);
      const mins = n(s?.games?.minutes, 0);

      agg.games.appearences += apps;
      agg.games.minutes += mins;

      agg.goals.total += n(s?.goals?.total, 0);
      agg.goals.assists += n(s?.goals?.assists, 0);

      agg.shots.total += n(s?.shots?.total, 0);
      agg.shots.on += n(s?.shots?.on, 0);

      agg.cards.yellow += n(s?.cards?.yellow, 0);
      agg.cards.red += n(s?.cards?.red, 0);

      // equipa principal = a com mais minutos
      if (mins > bestMins) {
        bestMins = mins;
        agg.team.id = n(s?.team?.id, 0);
        agg.team.name = String(s?.team?.name || "—");
        agg.team.logo = String(s?.team?.logo || "");
        agg.games.position = String(s?.games?.position || "—");
        agg.league.season = String(s?.league?.season ?? "—");
      }

      // rating = média ponderada por minutos
      const rt = Number(s?.games?.rating);
      if (Number.isFinite(rt) && mins > 0) {
        ratingWeighted += rt * mins;
        ratingMinutes += mins;
      }
    }

    if (ratingMinutes > 0) agg.games.rating = (ratingWeighted / ratingMinutes).toFixed(2);
    return agg;
  }

  // ✅ include "Todas" + options com keys seguras
  function buildCompetitionOptions(stats) {
    const items = [];
    const seen = new Set();

    items.push({ key: ALL_KEY, label: "Todas as competições (Total)", score: 999999 });

    (stats || []).forEach((s) => {
      const key = compKey(s);
      if (!key || seen.has(key)) return;
      seen.add(key);

      const league = String(s?.league?.name || "—").trim();
      const season = String(s?.league?.season ?? "—").trim();
      const team = String(s?.team?.name || "").trim();

      items.push({
        key,
        label: `${league} • ${season}${team ? ` • ${team}` : ""}`,
        score: scoreStat(s),
      });
    });

    const all = items.shift();
    items.sort((a, b) => b.score - a.score);
    items.unshift(all);

    compSelect.innerHTML = items.length
      ? items.map((x) => `<option value="${esc(x.key)}">${esc(x.label)}</option>`).join("")
      : `<option value="">—</option>`;

    return items;
  }

  // ---------------- state ----------------
  let PLAYER = {};
  let STATS = [];
  let SELECTED = null;

  let TROPHIES = []; // /actions/player/player_trophies.php
  let CAREER = [];   // /actions/player/player_career.php

  // ---------------- render hero ----------------
  function renderHero(p, s) {
    const flag = flagUrl(p?.nationality);

    const position = s?.games?.position || "—";
    const apps = s ? n(s?.games?.appearences ?? s?.games?.appearances, 0) : 0;
    const minutes = s ? n(s?.games?.minutes, 0) : 0;
    const rating = s?.games?.rating ?? "—";

    const goals = s ? n(s?.goals?.total, 0) : 0;
    const assists = s ? n(s?.goals?.assists, 0) : 0;

    const teamId = s?.team?.id || 0;
    const teamName = s?.team?.name || "—";
    const teamLogo = s?.team?.logo || "";

    const leagueName = s?.league?.name || "—";
    const leagueLogo = s?.league?.logo || "";

    header.innerHTML = `
      <div class="player-hero">
        <div class="p-left">
          ${
            p?.photo
              ? `<img class="p-photo" src="${esc(p.photo)}" alt="">`
              : `<div class="p-photo ph">ZG</div>`
          }

          <div class="p-main">
            <div class="p-name">${esc(p?.name || "Jogador")}</div>

            <div class="p-subline">
              ${flag ? `<img class="flag" src="${flag}" alt="">` : ""}
              <span class="strong">${esc(p?.nationality || "—")}</span>
              <span class="dot">•</span>
              <span>${esc(p?.age ? `${p.age} anos` : "—")}</span>
              ${p?.height ? `<span class="dot">•</span><span>${esc(p.height)}</span>` : ""}
              ${p?.weight ? `<span class="dot">•</span><span>${esc(p.weight)}</span>` : ""}
            </div>

            <div class="p-chips">
              ${chip(position, "gold")}
              ${chip(`Jogos ${apps}`)}
              ${chip(`Min ${minutes}`)}
              ${chip(`G+A ${goals + assists}`, "blue")}
              ${chip(`Rating ${esc(rating)}`, "blue")}
            </div>
          </div>
        </div>

        <div class="p-right">
          <div class="mini">Clube</div>
          ${
            teamId
              ? `<a class="link" href="${BASE}/team.php?id=${Number(teamId)}">
                  ${teamLogo ? `<img class="mini-logo" src="${esc(teamLogo)}" alt="">` : ""}
                  <span>${esc(teamName)}</span>
                </a>`
              : `<div class="muted">—</div>`
          }

          <div class="mini" style="margin-top:10px;">Competição</div>
          <div class="row">
            ${leagueLogo ? `<img class="mini-logo" src="${esc(leagueLogo)}" alt="">` : ""}
            <span class="muted">${esc(leagueName)}</span>
          </div>
        </div>
      </div>
    `;
  }

  // ---------------- tabs ----------------
  let activeTab = "summary";

  function setTab(tab) {
    activeTab = tab;
    [...tabsWrap.querySelectorAll(".tab")].forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.tab === tab);
    });
    renderTabs();
  }

  tabsWrap.addEventListener("click", (e) => {
    const btn = e.target.closest(".tab");
    if (!btn) return;
    setTab(btn.dataset.tab);
  });

  function selectByKey(key) {
    if (key === ALL_KEY) {
      SELECTED = aggregateAllStats(STATS);
      return;
    }
    SELECTED = STATS.find((s) => compKey(s) === key) || null;
  }

  compSelect.addEventListener("change", () => {
    selectByKey(compSelect.value);
    renderHero(PLAYER, SELECTED || pickDefault(STATS));
    renderTabs();
  });

  // ---------------- trophies + career ----------------
  function renderTrophiesTab() {
    const list = Array.isArray(TROPHIES) ? TROPHIES : [];
    if (!list.length) {
      tabContent.innerHTML = `<div class="empty-message">Sem troféus registados na API.</div>`;
      return;
    }

    const winners = list.filter((x) => String(x.place || "").toLowerCase().includes("winner"));
    const runners = list.filter((x) => String(x.place || "").toLowerCase().includes("runner"));

    const renderList = (arr) => `
      <div class="trophy-list">
        ${arr
          .map((x) => {
            const meta = [x.country, x.season, x.place].filter(Boolean).join(" • ");
            return `
              <div class="trophy-row">
                <div class="trophy-name">${esc(x.competition || "—")}</div>
                <div class="trophy-meta">${esc(meta || "—")}</div>
              </div>
            `;
          })
          .join("")}
      </div>
    `;

    tabContent.innerHTML = `
      <div class="zg-card-inner">
        <div class="inner-title">🏆 Vencedor</div>
        ${winners.length ? renderList(winners) : `<div class="muted">—</div>`}
      </div>

      <div class="zg-card-inner" style="margin-top:12px;">
        <div class="inner-title">🥈 Finalista</div>
        ${runners.length ? renderList(runners) : `<div class="muted">—</div>`}
      </div>
    `;
  }

  function renderCareerTab() {
    const list = Array.isArray(CAREER) ? CAREER : [];
    if (!list.length) {
      tabContent.innerHTML = `<div class="empty-message">Sem histórico de transferências na API.</div>`;
      return;
    }

    tabContent.innerHTML = `
      <div class="zg-card-inner">
        <div class="inner-title">📌 Carreira (Transferências)</div>

        <div class="career-list">
          ${list
            .map((t) => {
              const from = t.from || {};
              const to = t.to || {};
              const date = safeDatePT(t.date);
              const type = t.type || "—";

              const fromHtml = from.id
                ? `<a class="link" href="${BASE}/team.php?id=${Number(from.id)}">
                    ${from.logo ? `<img class="mini-logo" src="${esc(from.logo)}" alt="">` : ""}
                    <span>${esc(from.name || "—")}</span>
                  </a>`
                : `<span class="muted">—</span>`;

              const toHtml = to.id
                ? `<a class="link" href="${BASE}/team.php?id=${Number(to.id)}">
                    ${to.logo ? `<img class="mini-logo" src="${esc(to.logo)}" alt="">` : ""}
                    <span>${esc(to.name || "—")}</span>
                  </a>`
                : `<span class="muted">—</span>`;

              return `
                <div class="career-row">
                  <div class="career-date">${esc(date)}</div>
                  <div class="career-move">
                    <div class="career-from">${fromHtml}</div>
                    <div class="career-arrow">→</div>
                    <div class="career-to">${toHtml}</div>
                  </div>
                  <div class="career-type">${esc(type)}</div>
                </div>
              `;
            })
            .join("")}
        </div>
      </div>
    `;
  }

  // ---------------- render tabs ----------------
  function renderTabs() {
    if (activeTab === "trophies") return renderTrophiesTab();
    if (activeTab === "career") return renderCareerTab();

    if (!SELECTED) {
      tabContent.innerHTML = `<div class="empty-message">Sem estatísticas para esta seleção.</div>`;
      return;
    }

    const s = SELECTED;

    const apps = n(s?.games?.appearences ?? s?.games?.appearances, 0);
    const minutes = n(s?.games?.minutes, 0);
    const rating = s?.games?.rating ?? "—";

    const goals = n(s?.goals?.total, 0);
    const assists = n(s?.goals?.assists, 0);

    const shots = n(s?.shots?.total, 0);
    const on = n(s?.shots?.on, 0);

    const y = n(s?.cards?.yellow, 0);
    const r = n(s?.cards?.red, 0);

    const g90 = per90(goals, minutes);
    const a90 = per90(assists, minutes);
    const ga90 = per90(goals + assists, minutes);

    const onPct = pct(on, shots);
    const convPct = pct(goals, shots);

    if (activeTab === "summary") {
      tabContent.innerHTML = `
        <div class="kpi-grid">
          ${kpi("Jogos", fmt(apps))}
          ${kpi("Minutos", fmt(minutes))}
          ${kpi("Rating", esc(rating))}
          ${kpi("G+A", fmt(goals + assists), `G ${fmt(goals)} • A ${fmt(assists)}`)}
        </div>

        <div class="zg-split">
          <div class="zg-card-inner">
            <div class="inner-title">Produção</div>
            <div class="metrics">
              ${metric("Golos", fmt(goals), `${fmt(g90, 2)} /90`)}
              ${metric("Assistências", fmt(assists), `${fmt(a90, 2)} /90`)}
              ${metric("G+A", fmt(goals + assists), `${fmt(ga90, 2)} /90`)}
            </div>
          </div>

          <div class="zg-card-inner">
            <div class="inner-title">Eficiência</div>
            <div class="metrics">
              ${metric("Remates", fmt(shots))}
              ${metric("À baliza", fmt(on), `${fmt(onPct, 1)}%`)}
              ${metric("Conversão", `${fmt(convPct, 1)}%`, "Golos / Remates")}
            </div>
          </div>
        </div>
      `;
      return;
    }

    // stats (inclui disciplina)
    tabContent.innerHTML = `
      <div class="kpi-grid">
        ${kpi("Golos", fmt(goals), `${fmt(g90, 2)} /90`)}
        ${kpi("Assistências", fmt(assists), `${fmt(a90, 2)} /90`)}
        ${kpi("Remates", fmt(shots))}
        ${kpi("À baliza", fmt(on), `${fmt(onPct, 1)}%`)}
      </div>

      <div class="zg-card-inner">
        <div class="inner-title">Detalhe</div>
        <div class="metrics">
          ${metric("Jogos", fmt(apps))}
          ${metric("Minutos", fmt(minutes))}
          ${metric("Rating", esc(rating))}
          ${metric("Conversão", `${fmt(convPct, 1)}%`)}
        </div>
      </div>

      <div class="zg-card-inner" style="margin-top:12px;">
        <div class="inner-title">Disciplina</div>
        <div class="metrics">
          ${metric("Amarelos", fmt(y), `${fmt(per90(y, minutes), 2)} /90`)}
          ${metric("Vermelhos", fmt(r), `${fmt(per90(r, minutes), 2)} /90`)}
        </div>
      </div>
    `;
  }

  // ---------------- init ----------------
  (async () => {
    try {
      // ✅ API-Football usa o ANO INICIAL da época (ex: 2025/26 => 2025)
      const now = new Date();
      const season = (now.getMonth() + 1) >= 7 ? now.getFullYear() : (now.getFullYear() - 1);

      // 1) player main data
      const j = await fetchJson(`${BASE}/actions/player/player_data.php?id=${id}&season=${season}`);
      if (!j?.ok) throw new Error(j?.error || "Erro");

      PLAYER = j.player || {};
      STATS = Array.isArray(j.statistics) ? j.statistics : [];

      // 2) trophies + career
      const [tj, cj] = await Promise.allSettled([
        fetchJson(`${BASE}/actions/player/player_trophies.php?id=${id}`),
        fetchJson(`${BASE}/actions/player/player_career.php?id=${id}`),
      ]);

      TROPHIES = tj.status === "fulfilled" && tj.value?.ok ? (tj.value.trophies || []) : [];
      CAREER = cj.status === "fulfilled" && cj.value?.ok ? (cj.value.career || []) : [];

      // 3) competitions select (com "Todas")
      buildCompetitionOptions(STATS);

      // default = "Todas"
      compSelect.value = ALL_KEY;
      selectByKey(ALL_KEY);

      // 4) render
      renderHero(PLAYER, SELECTED || pickDefault(STATS));
      setTab("summary");

      document.title = `${PLAYER?.name || "Jogador"} — ZoneGoal`;
    } catch (e) {
      console.error(e);
      header.innerHTML = `<div class="empty-message">Erro ao carregar jogador.</div>`;
      tabContent.innerHTML = `<div class="empty-message">${esc(e.message || e)}</div>`;
    }
  })();
});
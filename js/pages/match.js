document.addEventListener("DOMContentLoaded", () => {
  const Z = window.ZONEGOAL || {};
  const BASE = Z.base || "/ZoneGoal";
  const id = Number(Z.matchId || 0);
  if (!id) return;

  const $ = (s) => document.querySelector(s);

  const tabs = {
    stats: $('.tab-btn[data-tab="stats"]'),
    timeline: $('.tab-btn[data-tab="timeline"]'),
    lineups: $('.tab-btn[data-tab="lineups"]'),
  };

  const sections = {
    stats: $("#tab-stats"),
    timeline: $("#tab-timeline"),
    lineups: $("#tab-lineups"),
  };

  const esc = (s) => String(s ?? "")
    .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
    .replaceAll('"',"&quot;").replaceAll("'","&#039;");

  async function fetchJson(url, opts = {}) {
    const res = await fetch(url, { cache: "no-store", ...opts });
    const txt = await res.text();
    try { return JSON.parse(txt); }
    catch { throw new Error("Resposta não é JSON: " + txt.slice(0, 220)); }
  }

  function setTab(tab) {
    document.querySelectorAll(".tab-btn").forEach(b =>
      b.classList.toggle("active", b.dataset.tab === tab)
    );
    document.querySelectorAll(".tab-content").forEach(s =>
      s.classList.toggle("active", s.id === `tab-${tab}`)
    );
  }

  // =========================
  // 1) ESTATÍSTICAS
  // =========================
  const STATS = [
    ["Total Shots", "Remates", "int", "higher"],
    ["Shots on Goal", "Remates à baliza", "int", "higher"],
    ["Ball Possession", "Posse de bola", "percent"],
    ["Fouls", "Faltas", "int", "lower"],
    ["Yellow Cards", "Cartões amarelos", "int", "lower"],
    ["Red Cards", "Cartões vermelhos", "int", "lower"],
    ["Offsides", "Foras de jogo", "int", "lower"],
    ["Corner Kicks", "Cantos", "int", "higher"],
  ];

  const ALIASES = {
    "Total Shots": ["Shots Total"],
    "Shots on Goal": ["Shots on Target"],
    "Ball Possession": ["Possession"],
    "Corner Kicks": ["Corners"],
    "Offsides": ["Offside"],
  };

  const isEmpty = (v) => v === null || v === undefined || v === "" || v === "-";

  const num = (v) => {
    if (isEmpty(v)) return NaN;
    const n = Number(String(v).replace("%","").trim());
    return Number.isFinite(n) ? n : NaN;
  };

  const asInt = (v) => (Number.isFinite(num(v)) ? Math.round(num(v)) : null);
  const asPct = (v) => {
    const n = num(v);
    return Number.isFinite(n) ? Math.max(0, Math.min(100, n)) : null;
  };

  const getStat = (map, key) => {
    if (map.has(key)) return map.get(key);
    for (const alt of (ALIASES[key] || [])) if (map.has(alt)) return map.get(alt);
    return null;
  };

  const bubble = (txt, winCls) => {
    const t = isEmpty(txt) ? "—" : String(txt);
    const cls = ["bub", t === "—" ? "muted" : "", winCls || ""].filter(Boolean).join(" ");
    return `<span class="${cls}">${esc(t)}</span>`;
  };

  const winSide = (L, R, rule) => {
    if (!Number.isFinite(L) || !Number.isFinite(R) || L === R) return ["", ""];
    const leftWin = rule === "lower" ? (L < R) : (L > R);
    return [leftWin ? "win-left" : "", (!leftWin) ? "win-right" : ""];
  };

  const row = (label, left, right, rule = "higher") => {
    const L = num(left), R = num(right);
    const [wl, wr] = winSide(L, R, rule);
    return `
      <div class="srow">
        <div class="sval left">${bubble(left, wl)}</div>
        <div class="slabel">${esc(label)}</div>
        <div class="sval right">${bubble(right, wr)}</div>
      </div>
    `;
  };

  const posRow = (label, l, r) => {
    const L = asPct(l), R = asPct(r);
    const baseL = L ?? 50;
    const baseR = R ?? (100 - baseL);
    const sum = (baseL + baseR) || 100;
    const finalL = Math.round((baseL / sum) * 100);
    const finalR = 100 - finalL;
    const [wl, wr] = winSide(finalL, finalR, "higher");

    return `
      <div class="srow possession">
        <div class="sval left">${bubble(finalL + "%", wl)}</div>
        <div class="slabel">${esc(label)}</div>
        <div class="sval right">${bubble(finalR + "%", wr)}</div>
        <div class="pos-bar">
          <div class="pos-left" style="width:${finalL}%"></div>
          <div class="pos-right" style="width:${finalR}%"></div>
        </div>
      </div>
    `;
  };

  async function loadStats() {
    const mount = sections.stats;
    mount.innerHTML = `<div class="loading">A carregar estatísticas…</div>`;

    try {
      const j = await fetchJson(`${BASE}/actions/match/match_data.php?id=${id}&type=stats`);
      const data = j?.data;

      if (!j?.ok || !Array.isArray(data) || data.length < 2) {
        mount.innerHTML = `<div class="empty">Sem estatísticas disponíveis.</div>`;
        return;
      }

      const [A, B] = data;
      const teamA = A?.team?.name || "Casa", teamB = B?.team?.name || "Fora";
      const logoA = A?.team?.logo || "", logoB = B?.team?.logo || "";

      const mapA = new Map((A?.statistics || []).map(s => [String(s.type || "").trim(), s.value]));
      const mapB = new Map((B?.statistics || []).map(s => [String(s.type || "").trim(), s.value]));

      const rows = STATS.map(([key, label, type, rule]) => {
        const rawA = getStat(mapA, key), rawB = getStat(mapB, key);
        if (isEmpty(rawA) && isEmpty(rawB)) return "";
        if (type === "percent") return posRow(label, rawA, rawB);

        const vA = type === "int" ? asInt(rawA) : num(rawA);
        const vB = type === "int" ? asInt(rawB) : num(rawB);
        const tA = vA === null || Number.isNaN(vA) ? "—" : String(Math.round(vA));
        const tB = vB === null || Number.isNaN(vB) ? "—" : String(Math.round(vB));
        return row(label, tA, tB, rule);
      }).join("") || `<div class="empty">Sem estatísticas essenciais disponíveis.</div>`;

      mount.innerHTML = `
        <div class="stats-panel">
          <div class="stats-title">ESTATÍSTICAS DA EQUIPA</div>
          <div class="stats-teams">
            <div class="team-side">
              ${logoA ? `<img class="mini-logo" src="${esc(logoA)}" alt="">` : ""}
              <div class="team-name-mini">${esc(teamA)}</div>
            </div>
            <div class="team-side right">
              <div class="team-name-mini">${esc(teamB)}</div>
              ${logoB ? `<img class="mini-logo" src="${esc(logoB)}" alt="">` : ""}
            </div>
          </div>
          <div class="stats-list">${rows}</div>
        </div>
      `;
    } catch (e) {
      console.error(e);
      mount.innerHTML = `<div class="error">Erro a carregar estatísticas.</div>`;
    }
  }

  // =========================
  // 2) CRONOLOGIA (Eventos)
  // =========================
  const eventBlock = (m) => {
    m = Number(m || 0);
    if (m === 0) return "PRÉ-JOGO";
    if (m <= 45) return "1ª PARTE";
    if (m <= 90) return "2ª PARTE";
    if (m <= 105) return "PROLONGAMENTO (1ª)";
    if (m <= 120) return "PROLONGAMENTO (2ª)";
    return "PÓS-JOGO";
  };

  const blockDivider = (t) => `
    <div class="ev-divider">
      <span class="ev-divider-line"></span>
      <span class="ev-divider-text">${esc(t)}</span>
      <span class="ev-divider-line"></span>
    </div>
  `;

  const eventIcon = (type, detail) => {
    const tt = String(type || "").toLowerCase();
    const dd = String(detail || "").toLowerCase();
    if (tt.includes("goal")) return "⚽";
    if (tt.includes("card") && dd.includes("yellow")) return "🟨";
    if (tt.includes("card") && dd.includes("red")) return "🟥";
    if (tt.includes("subst")) return "🔁";
    if (tt.includes("var")) return "🎥";
    return "•";
  };

  const formatDetail = (type, detail) => {
    const tt = String(type || "").toLowerCase();
    const dd = String(detail || "").toLowerCase();
    if (tt.includes("goal")) return dd.includes("own") ? "Auto-golo" : dd.includes("penalty") ? "Golo (Penálti)" : "Golo";
    if (tt.includes("card")) return dd.includes("yellow") ? "Cartão amarelo" : dd.includes("red") ? "Cartão vermelho" : "Cartão";
    if (tt.includes("subst")) return "Substituição";
    if (tt.includes("var")) return "VAR";
    return detail || type || "Evento";
  };

  async function loadTimeline() {
    const mount = sections.timeline;
    mount.innerHTML = `<div class="loading">A carregar cronologia…</div>`;

    try {
      const j = await fetchJson(`${BASE}/actions/match/match_data.php?id=${id}&type=events`);
      if (!j?.ok || !Array.isArray(j.data) || !j.data.length) {
        mount.innerHTML = `<div class="empty">Sem eventos disponíveis.</div>`;
        return;
      }

      const events = [...j.data].sort((a, b) =>
        ((a.time?.elapsed || 0) * 100 + (a.time?.extra || 0)) -
        ((b.time?.elapsed || 0) * 100 + (b.time?.extra || 0))
      );

      const homeId = Number(Z.homeId || 0);
      const awayId = Number(Z.awayId || 0);

      const rows = [];
      let last = null;

      for (const ev of events) {
        const elapsed = Number(ev.time?.elapsed || 0);
        const extra = Number(ev.time?.extra || 0);
        const minute = elapsed ? (extra ? `${elapsed}+${extra}'` : `${elapsed}'`) : "—";

        const block = eventBlock(elapsed);
        if (block !== last) { rows.push(blockDivider(block)); last = block; }

        const teamId = Number(ev.team?.id || 0);
        const isHome = homeId && teamId === homeId;
        const isAway = awayId && teamId === awayId;

        const type = ev.type || "", detail = ev.detail || "";
        const teamLogo = ev.team?.logo || "";
        const pName = ev.player?.name || "";
        const aName = ev.assist?.name || "";

        const isSub = String(type).toLowerCase().includes("subst");

        const card = `
          <div class="ev-card2">
            <div class="ev-card2-top">
              ${teamLogo ? `<img class="ev-team-logo" src="${esc(teamLogo)}" alt="">` : ""}
              <span class="ev-icon">${esc(eventIcon(type, detail))}</span>
              <span class="ev-detail">${esc(formatDetail(type, detail))}</span>
            </div>

            <div class="ev-players2">
              ${isSub
                ? `<div class="ev-person sub-in"><span class="ev-arrow up">⬆</span><div class="ev-pname">${esc(pName)}</div></div>
                   <div class="ev-person sub-out"><span class="ev-arrow down">⬇</span><div class="ev-pname">${esc(aName)}</div></div>`
                : `<div class="ev-person"><div class="ev-pname">${esc(pName || "—")}</div></div>
                   ${aName ? `<div class="ev-person assist"><div class="ev-aname">Assist: ${esc(aName)}</div></div>` : ""}`
              }
            </div>
          </div>
        `;

        rows.push(`
          <div class="ev-grid-row">
            <div class="ev-col home">${isHome ? card : ""}</div>
            <div class="ev-time">${esc(minute)}</div>
            <div class="ev-col away">${isAway ? card : ""}</div>
          </div>
        `);
      }

      mount.innerHTML = `<div class="events-grid">${rows.join("")}</div>`;
    } catch (e) {
      console.error(e);
      mount.innerHTML = `<div class="error">Erro a carregar cronologia.</div>`;
    }
  }

  // =========================
  // 3) LINEUPS (Campo + Listas)
  // =========================
const POS_MAP = {
  G: "GR", GK: "GR",
  D: "DEF", DF: "DEF",
  M: "MED", MF: "MED",
  F: "AVA", FW: "AVA",
};

function posShort(p) {
  const k = String(p || "").toUpperCase().trim();
  if (POS_MAP[k]) return POS_MAP[k];
  if (k.includes("GOAL")) return "GR";
  if (k.includes("DEF")) return "DEF";
  if (k.includes("MID")) return "MED";
  if (k.includes("FOR")) return "AVA";
  return "";
}

// ✅ centra as linhas e distribui os jogadores na horizontal
function normalizeGridPositions(players) {
  const withGrid = players
    .map(p => {
      const g = String(p.grid || "");
      if (!g.includes(":")) return null;
      const [r, c] = g.split(":").map(n => Number(n));
      if (!Number.isFinite(r) || !Number.isFinite(c)) return null;
      return { ...p, __r: r, __c: c };
    })
    .filter(Boolean);

  if (!withGrid.length) return players.map(p => ({ ...p, __x: null, __y: null }));

  const byRow = new Map();
  for (const p of withGrid) {
    if (!byRow.has(p.__r)) byRow.set(p.__r, []);
    byRow.get(p.__r).push(p);
  }

  const rows = [...byRow.keys()].sort((a, b) => a - b);
  const rowCount = rows.length;

  const xy = new Map();

  rows.forEach((r, idx) => {
    const list = byRow.get(r).sort((a, b) => a.__c - b.__c);
    const n = list.length;

    // y: distribuição “bonita” por linhas
    const y = ((idx + 1) / (rowCount + 1)) * 100;

    // x: centrado por número de jogadores na linha
    list.forEach((p, i) => {
      const x = ((i + 1) / (n + 1)) * 100;
      xy.set(p.key, { x, y });
    });
  });

  return players.map(p => {
    const pos = xy.get(p.key);
    return { ...p, __x: pos ? pos.x : null, __y: pos ? pos.y : null };
  });
}

function lineupPitch(team) {
  const formation = String(team?.formation || "").trim();
  const startXI = team?.startXI || [];
  const coach = team?.coach?.name || "—";
  const name = team?.team?.name || "—";
  const logo = team?.team?.logo || "";

  const nodes = startXI.map(x => x.player).filter(Boolean);

  // construir lista de jogadores com uma key estável
  const players = nodes.map((p, idx) => ({
    key: String(p.id || "") || `${p.name || "p"}-${idx}`, // ✅ chave para mapear
    name: p.name ?? "—",
    number: p.number ?? "—",
    pos: posShort(p.pos || ""),
    grid: p.grid || ""
  }));

  const normalized = normalizeGridPositions(players);
  const hasXY = normalized.some(p => p.__x != null && p.__y != null);

  const bubbles = normalized.map((p) => {
    const style = hasXY && p.__x != null && p.__y != null
      ? `style="left:${p.__x}%; top:${p.__y}%;"` // ✅ posição real centrada
      : "";

    const cls = hasXY ? "pz-player" : "pz-player pz-player-list";

    return `
      <div class="${cls}" ${style}>
        <div class="pz-num">${esc(p.number)}</div>
        <div class="pz-name">${esc(p.name)}</div>
      </div>
    `;
  }).join("");

  return `
    <div class="pz-team">
      <div class="pz-head">
        <div class="pz-head-left">
          ${logo ? `<img class="pz-logo" src="${esc(logo)}" alt="">` : ""}
          <div class="pz-head-text">
            <div class="pz-team-name">${esc(name)}</div>
            <div class="pz-team-sub">Formação: ${esc(formation || "—")} • Treinador: ${esc(coach)}</div>
          </div>
        </div>
      </div>

      <div class="pz-pitch ${hasXY ? "" : "no-grid"}">
        ${bubbles || `<div class="empty">Sem titulares.</div>`}
      </div>
    </div>
  `;
}

  function benchList(team) {
    const subs = team?.substitutes || [];
    const coach = team?.coach?.name || "—";
    const name = team?.team?.name || "—";
    const logo = team?.team?.logo || "";
    const formation = String(team?.formation || "").trim();

    const rows = subs.map((x) => {
      const p = x.player || {};
      const num = p.number ?? "—";
      const pname = p.name ?? "—";
      const pos = posShort(p.pos || "");
      return `
        <div class="pl-row">
          <div class="pl-badge">${esc(num)}</div>
          <div class="pl-name">${esc(pname)}</div>
          <div class="pl-pos">${esc(pos)}</div>
        </div>
      `;
    }).join("") || `<div class="empty">Sem suplentes.</div>`;

    return `
      <div class="pl-card">
        <div class="pl-head">
          ${logo ? `<img class="pl-logo" src="${esc(logo)}" alt="">` : ""}
          <div class="pl-htext">
            <div class="pl-title">${esc(name)}</div>
            <div class="pl-sub">Formação: ${esc(formation || "—")} • Treinador: ${esc(coach)}</div>
          </div>
        </div>

        <div class="pl-section">
          <div class="pl-section-title">Suplentes</div>
          <div class="pl-list">${rows}</div>
        </div>

        <div class="pl-section coach">
          <div class="pl-section-title">Treinador</div>
          <div class="pl-coach">${esc(coach)}</div>
        </div>
      </div>
    `;
  }

  async function loadLineups() {
    const mount = sections.lineups;
    mount.innerHTML = `<div class="loading">A carregar equipa titular…</div>`;

    try {
      const j = await fetchJson(`${BASE}/actions/match/match_data.php?id=${id}&type=lineups`);
      const data = j?.data;

      if (!j?.ok || !Array.isArray(data) || !data.length) {
        mount.innerHTML = `<div class="empty">Sem lineups disponíveis.</div>`;
        return;
      }

      // normalmente vem [home, away]
      const homeId = Number(Z.homeId || 0);
      const awayId = Number(Z.awayId || 0);

      const home = data.find(x => Number(x?.team?.id || 0) === homeId) || data[0];
      const away = data.find(x => Number(x?.team?.id || 0) === awayId) || data[1] || null;

      const top = `
        <div class="pz-lineups-top">
          ${home ? lineupPitch(home) : ""}
          ${away ? lineupPitch(away) : ""}
        </div>
      `;

      const bottom = `
        <div class="pz-lineups-bottom">
          ${home ? benchList(home) : ""}
          ${away ? benchList(away) : ""}
        </div>
      `;

      mount.innerHTML = `<div class="pz-lineups">${top}${bottom}</div>`;
    } catch (e) {
      console.error(e);
      mount.innerHTML = `<div class="error">Erro a carregar lineups.</div>`;
    }
  }

  // =========================
  // Tabs events
  // =========================
  tabs.stats?.addEventListener("click", () => { setTab("stats"); loadStats(); });
  tabs.timeline?.addEventListener("click", () => { setTab("timeline"); loadTimeline(); });
  tabs.lineups?.addEventListener("click", () => { setTab("lineups"); loadLineups(); });

  // default
  loadStats();
});
console.log("[ZoneGoal] pages/league.js carregado ✅");

document.addEventListener("DOMContentLoaded", () => {
  const BASE = "/ZoneGoal";
  const $ = (s) => document.querySelector(s);

  // Areas
  const fixturesArea  = $("#fixturesArea");
  const standingsMini = $("#standingsMini");

  // Inputs
  const leagueSel = $("#leagueSelect"); // hidden input
  const seasonInp = $("#seasonInput");
  const loadBtn   = $("#loadBtn");

  // Tabs
  const tabs = document.querySelectorAll(".lg-tabs .tab-btn");
  let currentTab = "next";

  // Hero
  const heroLogo    = $("#lgHeroLogo");
  const heroTitle   = $("#lgHeroTitle");
  const heroFlag    = $("#lgHeroFlag");
  const heroCountry = $("#lgHeroCountry");
  const heroSeason  = $("#lgHeroSeason");
  const panelLeftTitle = $("#panelLeftTitle");

  // Dropdown custom
  const dd = $("#leagueDropdown");
  const ddBtn = dd?.querySelector(".zg-select-btn");
  const ddMenu = $("#leagueMenu");
  const ddLabel = $("#leagueLabel");

  const esc = (s) => String(s ?? "")
    .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
    .replaceAll('"',"&quot;").replaceAll("'","&#039;");

  function setFixturesLoading(msg="A carregar…") {
    if (fixturesArea) fixturesArea.innerHTML = `<div class="empty-message">${esc(msg)}</div>`;
  }

  async function fetchJson(url){
    const res = await fetch(url, { cache:"no-store", headers:{ "Accept":"application/json" } });
    const txt = await res.text();
    try { return JSON.parse(txt); }
    catch { throw new Error(txt.slice(0,200)); }
  }

  function fmtDate(ts){
    if (!ts) return "—";
    const d = new Date(ts * 1000);
    return d.toLocaleString("pt-PT", {
      day:"2-digit", month:"2-digit", year:"numeric",
      hour:"2-digit", minute:"2-digit"
    });
  }

  function statusBadge(short){
    const s = String(short || "");
    if (["1H","HT","2H","ET","P","BT"].includes(s)) return `<span class="lg-badge lg-live">LIVE</span>`;
    if (["FT","AET","PEN"].includes(s)) return `<span class="lg-badge lg-fin">FINAL</span>`;
    return `<span class="lg-badge lg-up">AGENDADO</span>`;
  }

  function renderFixtures(fixtures){
    if (!fixturesArea) return;

    if (!Array.isArray(fixtures) || fixtures.length === 0){
      fixturesArea.innerHTML = `<div class="empty-message">Sem jogos.</div>`;
      return;
    }

    fixturesArea.innerHTML = fixtures.map(f => {
      const id = f?.fixture?.id || 0;
      const ts = f?.fixture?.timestamp || 0;
      const st = f?.fixture?.status?.short || "";
      const hg = f?.goals?.home;
      const ag = f?.goals?.away;

      const home = f?.teams?.home || {};
      const away = f?.teams?.away || {};

      const scoreA = (hg === null || hg === undefined) ? "—" : hg;
      const scoreB = (ag === null || ag === undefined) ? "—" : ag;

      return `
        <a class="lg-fx" href="match.php?id=${id}">
          <div class="lg-fx-top">
            <div class="lg-date">${esc(fmtDate(ts))}</div>
            <div>${statusBadge(st)}</div>
          </div>

          <div class="lg-fx-mid">
            <div class="lg-team">
              ${home.logo ? `<img class="lg-logo" src="${esc(home.logo)}" alt="">` : ""}
              <div class="lg-name">${esc(home.name || "Casa")}</div>
            </div>

            <div class="lg-score">${esc(scoreA)} - ${esc(scoreB)}</div>

            <div class="lg-team right">
              <div class="lg-name">${esc(away.name || "Fora")}</div>
              ${away.logo ? `<img class="lg-logo" src="${esc(away.logo)}" alt="">` : ""}
            </div>
          </div>
        </a>
      `;
    }).join("");
  }

  function updateHero(meta){
    if (heroTitle) heroTitle.textContent = meta.league || "Liga";
    if (heroCountry) heroCountry.textContent = meta.country || "—";
    if (heroSeason) heroSeason.textContent = meta.season || seasonInp?.value || "";

    if (heroLogo){
      if (meta.logo){
        heroLogo.src = meta.logo;
        heroLogo.style.display = "block";
        heroLogo.classList.remove("ph");
      } else {
        heroLogo.style.display = "none";
      }
    }

    if (heroFlag){
      if (meta.flag){
        heroFlag.src = meta.flag;
        heroFlag.style.display = "inline-block";
      } else {
        heroFlag.style.display = "none";
      }
    }
  }

  async function loadStandingsMini(league, season){
    if (!standingsMini) return;

    standingsMini.innerHTML = `<div class="empty-message">A carregar…</div>`;

    try{
      const j = await fetchJson(`${BASE}/actions/standings/standings_data.php?league=${league}&season=${season}`);
      if (!j.ok) throw new Error(j.error || "Erro");

      const meta = j.meta || {};
      updateHero(meta);

      const groups = j.data || [];
      const rows = (groups[0] || []);

      if (!rows.length){
        standingsMini.innerHTML = `<div class="empty-message">Sem standings.</div>`;
        return;
      }

      standingsMini.innerHTML = `
        <div class="lg-table lg-scroll">
          <div class="lg-tr head">
            <div>#</div><div>Equipa</div><div>J</div><div>DG</div><div>PTS</div>
          </div>
          ${rows.map(r => `
            <div class="lg-tr">
              <div class="rk">${esc(r.rank)}</div>
              <div class="tm">
                ${r?.team?.logo ? `<img class="tm-logo" src="${esc(r.team.logo)}" alt="">` : ""}
                <span class="tm-name">${esc(r?.team?.name || "")}</span>
              </div>
              <div>${esc(r?.all?.played ?? 0)}</div>
              <div class="dg">${esc(r?.goalsDiff ?? 0)}</div>
              <div class="pts">${esc(r?.points ?? 0)}</div>
            </div>
          `).join("")}
        </div>
      `;
    } catch(e){
      console.warn(e);
      standingsMini.innerHTML = `<div class="empty-message">Erro ao carregar standings.</div>`;
    }
  }

  async function load(){
    const league = Number(leagueSel?.value || 0);
    const season = Number(seasonInp?.value || 0);

    if (!league || !season){
      if (fixturesArea) fixturesArea.innerHTML = `<div class="empty-message">Escolhe liga e época.</div>`;
      return;
    }

    window.ZG_CONTEXT_LEAGUE = league;
    window.ZG_CONTEXT_SEASON = season;

    if (panelLeftTitle) panelLeftTitle.textContent = currentTab === "next" ? "Próximos 10" : "Últimos 10";
    setFixturesLoading(currentTab === "next" ? "A carregar próximos 10…" : "A carregar últimos 10…");

    try{
      const j = await fetchJson(`${BASE}/actions/league/league_fixtures.php?league=${league}&season=${season}&mode=${currentTab}`);
      if (!j.ok) throw new Error(j.error || "Erro");
      renderFixtures(j.data || []);
    } catch(e){
      console.error(e);
      if (fixturesArea) fixturesArea.innerHTML = `<div class="empty-message">Erro ao carregar jogos.</div>`;
    }

    loadStandingsMini(league, season);
  }

  // Tabs
  tabs.forEach(btn => {
    btn.addEventListener("click", () => {
      tabs.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      currentTab = btn.dataset.tab || "next";
      load();
    });
  });

  // Botão carregar
  loadBtn?.addEventListener("click", load);

  // ===== Overlay interno: só dentro da league-page =====
  const page = document.querySelector(".league-page");

  let overlay = page?.querySelector(".zg-dd-overlay");
  if (!overlay && page) {
    overlay = document.createElement("div");
    overlay.className = "zg-dd-overlay";
    page.appendChild(overlay);
  }

  function setDropdownOpen(isOpen){
    if (!dd) return;
    dd.classList.toggle("open", isOpen);
    overlay?.classList.toggle("show", isOpen);
    if (ddBtn) ddBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
  }

  ddBtn?.addEventListener("click", () => {
    setDropdownOpen(!dd.classList.contains("open"));
  });

  overlay?.addEventListener("click", () => setDropdownOpen(false));

  ddMenu?.querySelectorAll(".zg-item").forEach(item => {
    item.addEventListener("click", () => {
      ddMenu.querySelectorAll(".zg-item").forEach(x => x.classList.remove("active"));
      item.classList.add("active");

      const val = item.dataset.value;
      const text = item.textContent.trim();

      if (leagueSel) leagueSel.value = String(val || "");
      if (ddLabel) ddLabel.textContent = text;

      setDropdownOpen(false);
      load();
    });
  });

  // fecha ao scroll para não “ficar pendurado”
  window.addEventListener("scroll", () => setDropdownOpen(false), { passive: true });
  fixturesArea?.addEventListener("scroll", () => setDropdownOpen(false), { passive: true });
  standingsMini?.addEventListener("scroll", () => setDropdownOpen(false), { passive: true });

  // fecha com ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setDropdownOpen(false);
  });

  // Defaults
  if (window.ZG_CONTEXT_LEAGUE && leagueSel) leagueSel.value = String(window.ZG_CONTEXT_LEAGUE);
  if (window.ZG_CONTEXT_SEASON && seasonInp) seasonInp.value = String(window.ZG_CONTEXT_SEASON);

  load();
});
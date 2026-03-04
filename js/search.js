document.addEventListener("DOMContentLoaded", () => {
  const BASE = "/ZoneGoal";

  const input = document.querySelector("#zgSearch");
  const box = document.querySelector("#zgSearchBox");
  const wrap = document.querySelector(".zg-search-wrap");

  if (!input || !box) return;

  let timer = null;

  const esc = (s) => String(s ?? "")
    .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
    .replaceAll('"',"&quot;").replaceAll("'","&#039;");

  function hide(){
    box.style.display = "none";
    box.innerHTML = "";
  }

  function show(html){
    box.innerHTML = html;
    box.style.display = "block";
  }

  async function fetchJson(url){
    const r = await fetch(url, {
      cache: "no-store",
      headers: { "Accept": "application/json" }
    });
    const t = await r.text();

    if (!r.ok) {
      throw new Error(`HTTP ${r.status}: ${t.slice(0,200)}`);
    }

    try { return JSON.parse(t); }
    catch {
      console.error("[ZoneGoal] search response not JSON:", t.slice(0, 400));
      throw new Error("Resposta não é JSON");
    }
  }

  function render(teams, players){
    if ((!teams || !teams.length) && (!players || !players.length)){
      show(`<div class="zg-s-muted">Sem resultados.</div>`);
      return;
    }

    let html = "";

    if (teams?.length){
      html += `<div class="zg-s-head">Equipas</div>`;
      html += teams.map(t => {
        const id = Number(t?.id || 0);
        const name = t?.name || "Equipa";
        const logo = t?.logo || "";
        return `
          <a class="zg-s-item" href="${BASE}/team.php?id=${id}">
            ${logo ? `<img class="zg-s-logo" src="${esc(logo)}" alt="">` : ""}
            <span class="zg-s-name">${esc(name)}</span>
          </a>
        `;
      }).join("");
    }

    if (players?.length){
      html += `<div class="zg-s-head">Jogadores</div>`;
      html += players.map(p => {
        const id = Number(p?.id || 0);
        const name = p?.name || "Jogador";
        const photo = p?.photo || "";
        const nat = p?.nationality || "";
        return `
          <a class="zg-s-item" href="${BASE}/player.php?id=${id}">
            ${photo ? `<img class="zg-s-face" src="${esc(photo)}" alt="">` : ""}
            <span class="zg-s-name">${esc(name)}</span>
            ${nat ? `<span class="zg-s-sub">${esc(nat)}</span>` : ""}
          </a>
        `;
      }).join("");
    }

    show(html);
  }

  async function search(){
    const q = input.value.trim();
    if (q.length < 2){ hide(); return; }

    // contexto (se existir na página). se não existir, manda 0.
    const league = Number(window.ZG_CONTEXT_LEAGUE || 0);
    const season = Number(window.ZG_CONTEXT_SEASON || new Date().getFullYear());

    show(`<div class="zg-s-muted">A procurar…</div>`);

    try{
      const url = `${BASE}/actions/search/search_suggest.php?q=${encodeURIComponent(q)}&league=${league}&season=${season}`;
      const j = await fetchJson(url);

      if (!j?.ok){
        console.warn("[ZoneGoal] search ok=false:", j);
        show(`<div class="zg-s-muted">Erro a pesquisar.</div>`);
        return;
      }

      render(j.teams || [], j.players || []);
    } catch(e){
      console.error("[ZoneGoal] search error:", e);
      show(`<div class="zg-s-muted">Erro a pesquisar.</div>`);
    }
  }

  input.addEventListener("input", () => {
    clearTimeout(timer);
    timer = setTimeout(search, 200);
  });

  input.addEventListener("focus", () => {
    if (input.value.trim().length >= 2) search();
  });

  // fechar ao clicar fora
  document.addEventListener("click", (e) => {
    const target = e.target;
    if (wrap && target.closest(".zg-search-wrap")) return;
    hide();
  });

  // fechar com ESC
  input.addEventListener("keydown", (e) => {
    if (e.key === "Escape") hide();
  });
});
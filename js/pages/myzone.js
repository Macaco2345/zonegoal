document.addEventListener("DOMContentLoaded", () => {
  const kpisEl = document.getElementById("myzoneKpis");
  if (!kpisEl) return;

  const fmt = (ts) => {
    if (!ts) return "—";
    const d = new Date(ts * 1000);
    return d.toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" });
  };

  async function fetchJsonSafe(url, options = {}) {
    const res = await fetch(url, { cache: "no-store", ...options });
    const text = await res.text();
    try { return { ok:true, data: JSON.parse(text), status: res.status }; }
    catch { return { ok:false, status: res.status, raw: text.slice(0, 600) }; }
  }

  function kpi(label, value) {
    return `
      <div class="mz-kpi">
        <div class="k">${label}</div>
        <div class="v">${value}</div>
      </div>
    `;
  }

  async function load() {
    const r = await fetchJsonSafe("/ZoneGoal/actions/dashboard/myzone_summary.php");
    if (!r.ok || !r.data?.ok) {
      console.error("[myzone_summary]", r);
      kpisEl.innerHTML = `<div class="empty-message">Não foi possível carregar o resumo.</div>`;
      return;
    }

    const k = r.data.kpis || {};
    kpisEl.innerHTML = `
      ${kpi("Favoritos", `${k.favorites ?? 0}/10`)}
      ${kpi("Ao vivo", String(k.live ?? 0))}
      ${kpi("Hoje", String(k.today ?? 0))}
      ${kpi("Próximo", fmt(k.nextTs))}
    `;
  }

  load();
  setInterval(load, 30000);
});
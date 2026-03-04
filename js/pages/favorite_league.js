document.addEventListener("DOMContentLoaded", async () => {

  const box = document.getElementById("favoriteLeagueContent");
  if(!box) return;

  const res = await fetch("/ZoneGoal/actions/dashboard/favorite_league.php");
  const json = await res.json();

  if(!json.ok || !json.league){
    box.innerHTML="Sem liga favorita ainda.";
    return;
  }

  const l = json.league;

  box.innerHTML = `
    <div style="font-weight:900;font-size:16px;">
      ${l.name}
    </div>
    <div style="opacity:.7;font-size:12px;margin-top:4px;">
      ${l.count} jogos favoritos nesta liga
    </div>
  `;

});
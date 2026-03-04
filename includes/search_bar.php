<?php
declare(strict_types=1);

$q = isset($_GET["q"]) ? (string)$_GET["q"] : "";
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<div class="zg-top-actions">
  <form class="zg-searchbar" action="search.php" method="get" autocomplete="off">
    <span class="zg-s-ico">🔎</span>
    <input
      id="zgSearchInput"
      class="zg-s-input"
      type="text"
      name="q"
      value="<?= e($q) ?>"
      placeholder="Pesquisar equipa ou jogador…"
      minlength="2"
    >
    <button class="zg-s-btn" type="submit" title="Pesquisar">→</button>

    <!-- dropdown (sugestões) -->
    <div id="zgSearchDrop" class="zg-s-drop" style="display:none;"></div>
  </form>
</div>

<script>
  // opcional: auto-focus quando abres search.php
  if (location.pathname.endsWith("search.php")) {
    setTimeout(() => document.getElementById("zgSearchInput")?.focus(), 50);
  }
</script>
<script src="js/search_bar.js"></script>

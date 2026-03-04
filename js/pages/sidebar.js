document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("zgSidebar");
  const burger = document.getElementById("zgBurger");
  const closeBtn = document.getElementById("zgSideClose");

  if (!sidebar || !burger) return;

  const open = () => {
    document.body.classList.add("zg-side-open");
  };

  const close = () => {
    document.body.classList.remove("zg-side-open");
  };

  burger.addEventListener("click", open);
  closeBtn?.addEventListener("click", close);

  // clicar fora fecha (mobile)
  document.addEventListener("click", (e) => {
    if (!document.body.classList.contains("zg-side-open")) return;
    const isInside = sidebar.contains(e.target) || burger.contains(e.target);
    if (!isInside) close();
  });

  // ESC fecha
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });
});

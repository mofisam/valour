// Global behaviour: mobile sidebar, confirm prompts, helpers.
(function () {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const openBtn = document.getElementById('sidebar-open');

  function setSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full', !open);
    backdrop.classList.toggle('hidden', !open);
  }
  if (openBtn) openBtn.addEventListener('click', () => setSidebar(true));
  if (backdrop) backdrop.addEventListener('click', () => setSidebar(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

  // <form data-confirm="Delete this job?"> asks before submitting
  document.addEventListener('submit', (e) => {
    const msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // Prevent double submits
  document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    e.target.querySelectorAll('button[type=submit]').forEach((b) => {
      setTimeout(() => { b.disabled = true; }, 0);
    });
  });
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[type=submit]').forEach((b) => { b.disabled = false; });
  });

  // Light / dark mode. Saved choice wins; default follows the device setting.
  function applyTheme(mode) {
    const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
    document.dispatchEvent(new CustomEvent('themechange', { detail: { dark } }));
  }
  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    try { localStorage.setItem('ve-theme', next); } catch (err) {}
    applyTheme(next);
  });
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', () => {
    let saved = 'system';
    try { saved = localStorage.getItem('ve-theme') || 'system'; } catch (err) {}
    if (saved === 'system') applyTheme('system');
  });

  // Shared helpers
  window.VE = {
    url: (p) => (window.APP_BASE || '') + '/' + String(p).replace(/^\//, ''),
    currency: window.CURRENCY || '₦',
    money: (n) => (window.CURRENCY || '₦') + Number(n || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
    debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; },
  };
})();

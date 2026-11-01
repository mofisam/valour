// Idle sign-out. After IDLE_SECONDS (default 10 minutes) without clicks, typing, scrolling or touch,
// the person is signed out. A warning with a countdown appears for the last minute.
// While they ARE active, a small keep-alive request tells the server so the session does not expire mid-form.
(function () {
  const limit = (window.IDLE_SECONDS || 600) * 1000;
  const warnAt = Math.max(limit - 60000, limit / 2);
  const pingEvery = 120000;
  let last = Date.now();
  let lastPing = Date.now();
  let modal = null;

  function ping() {
    lastPing = Date.now();
    fetch(VE.url('api/ping'), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then((r) => { if (r.status === 401) window.location = VE.url('login'); })
      .catch(() => {});
  }

  function activity() {
    if (modal) return;                       // once the warning shows, only the button counts
    last = Date.now();
    if (last - lastPing > pingEvery) ping();
  }
  ['mousedown', 'keydown', 'touchstart', 'scroll', 'wheel'].forEach((ev) => document.addEventListener(ev, activity, { passive: true }));
  document.addEventListener('mousemove', VE.debounce(activity, 500), { passive: true });

  function showWarning(secondsLeft) {
    if (!modal) {
      modal = document.createElement('div');
      modal.className = 'fixed inset-0 z-[70] flex items-center justify-center bg-ink/60 p-4';
      modal.setAttribute('role', 'alertdialog');
      modal.setAttribute('aria-modal', 'true');
      modal.innerHTML =
        '<div class="card w-full max-w-sm p-6 shadow-2xl">' +
        '<h2 class="font-display text-xl font-bold">Still there?</h2>' +
        '<p class="mt-2 text-sm text-muted">For your security you will be signed out in <strong class="num" data-count></strong> seconds because there has been no activity.</p>' +
        '<div class="mt-5 flex gap-2"><button type="button" class="btn-primary" data-stay>Stay signed in</button>' +
        '<a class="btn-ghost" href="' + VE.url('session-expired') + '">Sign out now</a></div></div>';
      modal.querySelector('[data-stay]').addEventListener('click', () => {
        modal.remove(); modal = null; last = Date.now(); ping();
      });
      document.body.appendChild(modal);
      modal.querySelector('[data-stay]').focus();
    }
    modal.querySelector('[data-count]').textContent = secondsLeft;
  }

  setInterval(() => {
    const idle = Date.now() - last;
    if (idle >= limit) { window.location = VE.url('session-expired'); return; }
    if (idle >= warnAt) showWarning(Math.ceil((limit - idle) / 1000));
  }, 1000);
})();

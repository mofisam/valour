// Click a status badge to change a job's status from a small drop-down, without opening the job.
(function () {
  let menu = null;
  let current = null;

  function close() {
    if (menu) { menu.remove(); menu = null; }
    if (current) { current.setAttribute('aria-expanded', 'false'); current = null; }
  }

  async function choose(btn, key) {
    if (key === btn.dataset.status) { close(); return; }
    const jobId = btn.dataset.job;
    close();
    btn.disabled = true;
    btn.classList.add('opacity-50');
    try {
      const res = await fetch(VE.url('jobs/' + jobId + '/status'), {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': window.CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ _token: window.CSRF, status: key }),
        credentials: 'same-origin',
      });
      if (res.status === 401) { window.location = VE.url('login'); return; }
      const data = await res.json();
      if (data.ok) {
        btn.dataset.status = data.status;
        btn.querySelector('[data-badge]').innerHTML = data.badge;
        VE.toast(data.message);
      } else {
        VE.toast(data.message || 'Could not change the status.', 'error');
      }
    } catch (e) {
      VE.toast('Could not change the status. Check your connection and try again.', 'error');
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-50');
    }
  }

  function open(btn) {
    close();
    const options = JSON.parse(btn.dataset.options || '[]');
    menu = document.createElement('div');
    menu.setAttribute('role', 'menu');
    menu.className = 'fixed z-[60] min-w-[11rem] overflow-hidden rounded-md border border-line bg-surface py-1 shadow-xl';
    options.forEach((o) => {
      const item = document.createElement('button');
      item.type = 'button';
      item.setAttribute('role', 'menuitem');
      item.className = 'flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-canvas2 focus:bg-canvas2 focus:outline-none';
      const label = document.createElement('span');
      label.textContent = o.label;
      item.appendChild(label);
      if (o.key === btn.dataset.status) {
        const tick = document.createElement('span');
        tick.textContent = '✓';
        tick.className = 'text-sea';
        item.appendChild(tick);
      }
      item.addEventListener('click', () => choose(btn, o.key));
      menu.appendChild(item);
    });
    document.body.appendChild(menu);

    // Position under the badge, flip above if there is no room
    const r = btn.getBoundingClientRect();
    const h = menu.offsetHeight, w = menu.offsetWidth;
    const top = (r.bottom + h + 8 > window.innerHeight) ? Math.max(8, r.top - h - 4) : r.bottom + 4;
    menu.style.top = top + 'px';
    menu.style.left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8)) + 'px';
    btn.setAttribute('aria-expanded', 'true');
    current = btn;
    (menu.querySelector('button') || menu).focus();
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-status-menu]');
    if (btn) { e.preventDefault(); if (current === btn) close(); else open(btn); return; }
    if (menu && !menu.contains(e.target)) close();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  window.addEventListener('scroll', close, true);
  window.addEventListener('resize', close);
})();

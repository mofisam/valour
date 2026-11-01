// Admin reminders: checks for due reminders every minute and shows a pop-up (plus a system
// notification if the admin allowed it). Works while the app is open in a browser tab.
(function () {
  const box = document.getElementById('reminder-toasts');
  if (!box) return;

  const seenKey = 've-reminders-seen';
  const seen = new Set(JSON.parse(sessionStorage.getItem(seenKey) || '[]'));
  const remember = (id) => { seen.add(id); sessionStorage.setItem(seenKey, JSON.stringify([...seen])); };

  function setBadges(data) {
    const map = { 'data-alert-count': data.count, 'data-reminder-count': data.reminders, 'data-notification-count': data.notifications };
    Object.keys(map).forEach((attr) => {
      document.querySelectorAll('[' + attr + ']').forEach((el) => {
        el.textContent = map[attr];
        el.classList.toggle('hidden', !map[attr]);
      });
    });
  }

  async function post(path, body) {
    const form = new URLSearchParams({ _token: window.CSRF, ...(body || {}) });
    const res = await fetch(VE.url(path), {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': window.CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: form,
    });
    return res.json();
  }

  function toast(r) {
    const el = document.createElement('div');
    el.className = 'card border-brass p-4 shadow-xl';
    el.setAttribute('role', 'alert');
    el.innerHTML =
      '<div class="flex items-start justify-between gap-3">' +
        '<div class="min-w-0"><div class="text-xs font-semibold text-brass-600 dark:text-brass">Reminder · ' + r.at + '</div>' +
        '<div class="mt-0.5 font-display font-bold break-words"></div>' +
        '<div class="mt-1 text-sm text-muted break-words" data-notes></div></div>' +
        '<button type="button" class="rounded p-1 text-muted hover:bg-canvas2" data-close aria-label="Dismiss">✕</button>' +
      '</div>' +
      '<div class="mt-3 flex gap-2">' +
        '<button type="button" class="btn-primary btn-sm" data-done>Done</button>' +
        '<button type="button" class="btn-ghost btn-sm" data-snooze>Snooze 10 min</button>' +
        '<a class="btn-ghost btn-sm" href="' + VE.url('reminders') + '">Open</a>' +
      '</div>';
    el.querySelector('.font-display').textContent = r.title;
    const notes = el.querySelector('[data-notes]');
    if (r.notes) notes.textContent = r.notes; else notes.remove();

    const finish = () => { el.remove(); check(); };
    el.querySelector('[data-close]').addEventListener('click', () => el.remove());
    el.querySelector('[data-done]').addEventListener('click', async () => finish(await post('reminders/' + r.id + '/done')));
    el.querySelector('[data-snooze]').addEventListener('click', async () => finish(await post('reminders/' + r.id + '/snooze', { when: '10m' })));
    box.appendChild(el);

    if ('Notification' in window && Notification.permission === 'granted') {
      try { new Notification(r.title, { body: r.notes || 'Reminder due ' + r.at }); } catch (e) {}
    }
  }

  function noteToast(n) {
    const el = document.createElement('div');
    el.className = 'card border-sea p-4 shadow-xl';
    el.setAttribute('role', 'alert');
    el.innerHTML =
      '<div class="flex items-start justify-between gap-3"><div class="min-w-0">' +
        '<div class="text-xs font-semibold text-link">Notification · ' + n.at + '</div>' +
        '<div class="mt-0.5 font-display font-bold break-words" data-t></div>' +
        '<div class="mt-1 text-sm text-muted break-words" data-b></div></div>' +
        '<button type="button" class="rounded p-1 text-muted hover:bg-canvas2" data-close aria-label="Dismiss">✕</button></div>' +
      '<div class="mt-3 flex gap-2"></div>';
    el.querySelector('[data-t]').textContent = n.title;
    const b = el.querySelector('[data-b]');
    if (n.body) b.textContent = n.body; else b.remove();
    const row = el.lastElementChild;
    if (n.link) {
      const a = document.createElement('a');
      a.className = 'btn-primary btn-sm'; a.href = n.link; a.textContent = 'Open';
      a.addEventListener('click', () => { post('notifications/' + n.id + '/read'); });
      row.appendChild(a);
    }
    const dismiss = document.createElement('button');
    dismiss.type = 'button'; dismiss.className = 'btn-ghost btn-sm'; dismiss.textContent = 'Mark read';
    const done = async () => { el.remove(); await post('notifications/' + n.id + '/read'); check(); };
    dismiss.addEventListener('click', done);
    el.querySelector('[data-close]').addEventListener('click', () => el.remove());
    row.appendChild(dismiss);
    box.appendChild(el);
    if ('Notification' in window && Notification.permission === 'granted') {
      try { new Notification(n.title, { body: n.body || '' }); } catch (e) {}
    }
  }

  async function check() {
    try {
      const res = await fetch(VE.url('api/reminders/due'), { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      setBadges(data);
      data.due.forEach((r) => { const k = 'r' + r.id; if (!seen.has(k)) { remember(k); toast(r); } });
      (data.notes || []).forEach((n) => { const k = 'n' + n.id; if (!seen.has(k)) { remember(k); noteToast(n); } });
    } catch (e) { /* offline: try again next tick */ }
  }

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-enable-notifications]');
    if (!btn || !('Notification' in window)) return;
    const result = await Notification.requestPermission();
    btn.textContent = result === 'granted' ? 'Desktop alerts are on' : 'Alerts blocked in this browser';
    btn.disabled = true;
  });

  check();
  setInterval(check, 60000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
})();

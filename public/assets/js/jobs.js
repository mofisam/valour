// Job form: customer autocomplete, dynamic item rows, live totals.
// The server recalculates everything on save; this is for instant feedback only.
(function () {
  const form = document.getElementById('job-form');
  if (!form) return;

  const services = JSON.parse(form.dataset.services || '[]');
  const types = JSON.parse(form.dataset.types || '[]');
  const rowsEl = document.getElementById('item-rows');
  const tpl = document.getElementById('item-row-template');
  const serviceSel = document.getElementById('service_id');
  const perKgBox = document.getElementById('per-kg-box');
  const itemsBox = document.getElementById('items-box');
  const canPrice = form.dataset.canPrice === '1';
  const prices = JSON.parse(form.dataset.prices || '{}');
  const labels = { pending: 'Received', in_use: 'In use', washing: 'Washing', dry_cleaning: 'Dry cleaning', ironing: 'Ironing', ready: 'Ready', delivered: 'Delivered' };
  const hint = document.getElementById('process-hint');
  const kgInput = document.getElementById('total_kg');
  const kgPrice = document.getElementById('price_per_kg');
  const discType = document.getElementById('discount_type');
  const discVal = document.getElementById('discount_value');
  let index = 0;

  const currentService = () => services.find((s) => String(s.id) === String(serviceSel.value));
  const isPerKg = () => (currentService() || {}).pricing_model === 'per_kg';
  const isPerHour = () => (currentService() || {}).pricing_model === 'per_hour';
  const perHourBox = document.getElementById('per-hour-box');
  const hoursInput = document.getElementById('duration_hours');
  const minsInput = document.getElementById('duration_mins');
  const hourRate = document.getElementById('price_per_hour');

  /* ---------- Items ---------- */
  function addRow(data = {}) {
    const html = tpl.innerHTML.replace(/__i__/g, index++);
    const wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    const row = wrap.firstElementChild;
    const sel = row.querySelector('[data-f=type]');
    types.forEach((t) => {
      const o = document.createElement('option');
      o.value = t.id; o.textContent = t.label; o.dataset.price = t.price_per_item;
      sel.appendChild(o);
    });
    if (data.clothing_type_id) sel.value = data.clothing_type_id;
    row.querySelector('[data-f=color]').value = data.color || 'white';
    row.querySelector('[data-f=length]').value = data.length || 'nil';
    if (!canPrice) row.querySelector('[data-f=price]').readOnly = true;
    row.querySelector('[data-f=qty]').value = data.quantity || 1;
    row.querySelector('[data-f=price]').value = Number(data.price_per_unit) > 0 ? data.price_per_unit : '';
    rowsEl.appendChild(row);
    if (row.querySelector('[data-f=price]').value === '') applyDefaultPrice(row, true);
    recalc();
  }

  // Price comes from the SERVICE price list first, then the item's fallback price.
  function listPrice(typeId) {
    const sid = serviceSel.value;
    if (prices[sid] && prices[sid][typeId] != null) return prices[sid][typeId];
    const t = types.find((x) => String(x.id) === String(typeId));
    return t ? t.price_per_item : 0;
  }
  function applyDefaultPrice(row, force) {
    const input = row.querySelector('[data-f=price]');
    if (!canPrice || (!force && input.dataset.touched)) return;
    const typeId = row.querySelector('[data-f=type]').value;
    input.value = typeId ? listPrice(typeId) : 0;
  }

  rowsEl.addEventListener('change', (e) => {
    if (e.target.matches('[data-f=type]')) applyDefaultPrice(e.target.closest('[data-row]'), true);
    recalc();
  });
  rowsEl.addEventListener('input', (e) => { if (e.target.matches('[data-f=price]')) e.target.dataset.touched = '1'; recalc(); });
  rowsEl.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remove]');
    if (!btn) return;
    btn.closest('[data-row]').remove();
    recalc();
  });
  document.getElementById('add-row').addEventListener('click', () => addRow());

  /* ---------- Totals ---------- */
  function recalc() {
    const perHour = isPerHour();
    const perKg = isPerKg() || perHour;   // both are priced by weight/time, so item price columns are hidden
    perKgBox.classList.toggle('hidden', !isPerKg());
    perHourBox.classList.toggle('hidden', !perHour);
    document.getElementById('items-optional').classList.toggle('hidden', !perKg);
    rowsEl.querySelectorAll('[data-price-col]').forEach((el) => el.classList.toggle('hidden', perKg || !canPrice));

    let subtotal = 0, pieces = 0;
    rowsEl.querySelectorAll('[data-row]').forEach((row) => {
      const q = parseInt(row.querySelector('[data-f=qty]').value, 10) || 0;
      const p = parseFloat(row.querySelector('[data-f=price]').value) || 0;
      const line = perKg ? 0 : q * p;
      row.querySelector('[data-f=line]').textContent = VE.money(line);
      subtotal += line; pieces += q;
    });
    if (isPerKg()) subtotal = (parseFloat(kgInput.value) || 0) * (parseFloat(kgPrice.value) || 0);
    if (perHour) subtotal = (((parseInt(hoursInput.value, 10) || 0) * 60 + (parseInt(minsInput.value, 10) || 0)) / 60) * (parseFloat(hourRate.value) || 0);

    let discount = 0;
    const dv = parseFloat(discVal.value) || 0;
    if (discType.value === 'percent') discount = subtotal * Math.min(dv, 100) / 100;
    else if (discType.value === 'fixed') discount = Math.min(dv, subtotal);
    discVal.disabled = !canPrice || discType.value === 'none';

    document.getElementById('sum-pieces').textContent = pieces;
    document.getElementById('sum-subtotal').textContent = VE.money(subtotal);
    document.getElementById('sum-discount').textContent = '−' + VE.money(discount);
    document.getElementById('sum-total').textContent = VE.money(subtotal - discount);
  }

  function showProcess() {
    const s = currentService();
    if (!hint) return;
    hint.textContent = s && s.steps ? 'Process: ' + s.steps.map((k) => labels[k] || k).join(' → ') : '';
  }

  serviceSel.addEventListener('change', () => {
    const s = currentService();
    if (s && s.pricing_model === 'per_kg' && canPrice && (!kgPrice.value || kgPrice.dataset.auto)) { kgPrice.value = s.price_per_kg; kgPrice.dataset.auto = '1'; }
    if (s && s.pricing_model === 'per_hour' && canPrice && (!hourRate.value || hourRate.dataset.auto)) { hourRate.value = s.price_per_hour; hourRate.dataset.auto = '1'; }
    rowsEl.querySelectorAll('[data-row]').forEach((row) => applyDefaultPrice(row, false));
    showProcess();
    recalc();
  });
  [kgInput, kgPrice, discType, discVal, hoursInput, minsInput, hourRate].forEach((el) => el.addEventListener('input', recalc));

  /* ---------- Customer autocomplete ---------- */
  const search = document.getElementById('customer_search');
  const hidden = document.getElementById('customer_id');
  const list = document.getElementById('customer_results');

  const balanceEl = document.getElementById('customer_balance');
  function showBalance(amount) {
    if (!balanceEl) return;
    const n = Number(amount) || 0;
    balanceEl.classList.toggle('hidden', n <= 0);
    balanceEl.textContent = n > 0 ? 'This customer has an unpaid receivable of ' + VE.money(n) + ' from earlier jobs.' : '';
  }
  showBalance(balanceEl ? balanceEl.dataset.owes : 0);

  const runSearch = VE.debounce(async () => {
    const q = search.value.trim();
    hidden.value = ''; showBalance(0);
    if (q.length < 2) { list.classList.add('hidden'); return; }
    try {
      const res = await fetch(VE.url('api/customers/search?q=' + encodeURIComponent(q)), { headers: { Accept: 'application/json' } });
      const data = await res.json();
      list.innerHTML = '';
      if (!data.length) {
        list.innerHTML = '<li class="px-3 py-2 text-sm text-muted">No match. <a class="text-sea underline" href="' + VE.url('customers/create') + '">Add a new customer</a></li>';
      }
      data.forEach((c) => {
        const li = document.createElement('li');
        li.className = 'cursor-pointer px-3 py-2 text-sm hover:bg-canvas2';
        li.textContent = c.full_name + (c.phone_number ? ' · ' + c.phone_number : '') + (Number(c.outstanding) > 0 ? ' · receivable ' + VE.money(c.outstanding) : '');
        li.addEventListener('mousedown', () => {
          hidden.value = c.id; search.value = c.full_name; list.classList.add('hidden');
          showBalance(c.outstanding);
        });
        list.appendChild(li);
      });
      list.classList.remove('hidden');
    } catch (err) { list.classList.add('hidden'); }
  }, 250);
  search.addEventListener('input', runSearch);
  search.addEventListener('blur', () => setTimeout(() => list.classList.add('hidden'), 150));

  /* ---------- Boot ---------- */
  if (!canPrice) { discType.disabled = true; discVal.disabled = true; }
  const initial = JSON.parse(form.dataset.rows || '[]');
  (initial.length ? initial : (isPerKg() || isPerHour() ? [] : [{}])).forEach(addRow);
  showProcess();
  recalc();
})();

// Dashboard charts. Colours come from the CSS theme variables, so charts follow light/dark mode.
(function () {
  if (typeof Chart === 'undefined') return;

  const css = (name) => {
    const v = getComputedStyle(document.documentElement).getPropertyValue('--' + name).trim().split(/\s+/);
    return 'rgb(' + v.join(',') + ')';
  };
  const charts = [];
  const tick = (v) => VE.currency + Number(v).toLocaleString('en-NG');

  function build() {
    charts.splice(0).forEach((c) => c.destroy());
    const text = css('muted'), grid = css('line');
    Chart.defaults.color = text;
    Chart.defaults.font.family = '"Public Sans", system-ui, sans-serif';
    const scales = {
      x: { grid: { display: false }, border: { color: grid } },
      y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { callback: tick } },
    };

    const rev = document.getElementById('revenueChart');
    if (rev) {
      charts.push(new Chart(rev, {
        type: 'bar',
        data: { labels: JSON.parse(rev.dataset.labels), datasets: [{ label: 'Money received', data: JSON.parse(rev.dataset.values), backgroundColor: '#1F9AA6', borderRadius: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => VE.money(c.parsed.y) } } }, scales },
      }));
    }

    const mon = document.getElementById('monthlyChart');
    if (mon) {
      const months = JSON.parse(mon.dataset.months);
      charts.push(new Chart(mon, {
        type: 'bar',
        data: {
          labels: months.map((m) => m.label),
          datasets: [
            { label: 'Revenue', data: months.map((m) => m.revenue), backgroundColor: '#1F9AA6', borderRadius: 3 },
            { label: 'Expenses', data: months.map((m) => m.expenses), backgroundColor: '#B98A2E', borderRadius: 3 },
          ],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + VE.money(c.parsed.y) } } }, scales },
      }));
    }

    const st = document.getElementById('statusChart');
    if (st) {
      const counts = JSON.parse(st.dataset.counts);
      const order = [['pending', '#D9A441'], ['in_use', '#B45FC9'], ['washing', '#4A90C2'], ['dry_cleaning', '#6C7BD6'], ['ironing', '#8E6CC9'], ['ready', '#1F9AA6'], ['delivered', '#3FA06B'], ['cancelled', '#C75050']];
      const rows = order.filter(([k]) => Number(counts[k]) > 0);
      charts.push(new Chart(st, {
        type: 'doughnut',
        data: { labels: rows.map(([k]) => (k[0].toUpperCase() + k.slice(1)).replace('_', ' ')), datasets: [{ data: rows.map(([k]) => Number(counts[k])), backgroundColor: rows.map(([, c]) => c), borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: false } } },
      }));
    }
  }

  build();
  document.addEventListener('themechange', build);
})();

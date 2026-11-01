// Reports charts: revenue over time, revenue vs expenses, jobs over time. Theme-aware.
(function () {
  const dataEl = document.getElementById('trend-data');
  if (!dataEl) return;

  function fail(msg) {
    ['revenueChart', 'compareChart', 'jobsChart'].forEach((id) => {
      const el = document.getElementById(id);
      if (el && el.parentElement) el.parentElement.innerHTML = '<p class="py-10 text-center text-sm text-muted">' + msg + '</p>';
    });
  }
  if (typeof Chart === 'undefined') { fail('Charts could not load. Check your internet connection (the chart library is loaded from the web) and refresh.'); return; }

  let t;
  try { t = JSON.parse(dataEl.textContent); } catch (e) { fail('Chart data could not be read.'); console.error(e); return; }

  let charts = [];
  const css = (n) => 'rgb(' + getComputedStyle(document.documentElement).getPropertyValue('--' + n).trim().split(/\s+/).join(',') + ')';

  function draw() {
    charts.forEach((c) => c.destroy());
    charts = [];
    Chart.defaults.color = css('muted');
    Chart.defaults.font.family = '"Public Sans", system-ui, sans-serif';
    const money = (v) => VE.currency + Number(v).toLocaleString('en-NG');
    const x = { grid: { display: false }, border: { color: css('line') }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 14 } };
    const y = (fmt) => ({ beginAtZero: true, grid: { color: css('line') }, border: { display: false }, ticks: { callback: fmt, precision: 0 } });
    const base = { responsive: true, maintainAspectRatio: false };

    const make = (id, config) => {
      const el = document.getElementById(id);
      if (!el) return;
      try { charts.push(new Chart(el, config)); } catch (e) { console.error('Chart ' + id + ' failed', e); }
    };

    make('revenueChart', {
      type: 'bar',
      data: { labels: t.labels, datasets: [{ label: 'Revenue', data: t.revenue, backgroundColor: '#1F9AA6', borderRadius: 3 }] },
      options: { ...base, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => VE.money(c.parsed.y) } } }, scales: { x, y: y(money) } },
    });
    make('compareChart', {
      type: 'bar',
      data: { labels: t.labels, datasets: [
        { label: 'Revenue', data: t.revenue, backgroundColor: '#1F9AA6', borderRadius: 3 },
        { label: 'Expenses', data: t.expenses, backgroundColor: '#B98A2E', borderRadius: 3 },
      ] },
      options: { ...base, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + VE.money(c.parsed.y) } } }, scales: { x, y: y(money) } },
    });
    make('jobsChart', {
      type: 'bar',
      data: { labels: t.labels, datasets: [{ label: 'Jobs', data: t.jobs, backgroundColor: '#6C7BD6', borderRadius: 3 }] },
      options: { ...base, plugins: { legend: { display: false } }, scales: { x, y: y((v) => v) } },
    });
  }
  draw();
  document.addEventListener('themechange', draw);
})();

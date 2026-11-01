// Bank & Cash page: stacked bars of cash vs bank receipts. Theme-aware.
(function () {
  const el = document.getElementById('cashChart');
  if (!el || typeof Chart === 'undefined') return;
  let chart;
  const css = (n) => 'rgb(' + getComputedStyle(document.documentElement).getPropertyValue('--' + n).trim().split(/\s+/).join(',') + ')';

  function draw() {
    if (chart) chart.destroy();
    const s = JSON.parse(el.dataset.series);
    Chart.defaults.color = css('muted');
    Chart.defaults.font.family = '"Public Sans", system-ui, sans-serif';
    chart = new Chart(el, {
      type: 'bar',
      data: { labels: s.labels, datasets: [
        { label: 'Cash', data: s.cash, backgroundColor: '#3FA06B', borderRadius: 3 },
        { label: 'Bank', data: s.bank, backgroundColor: '#B98A2E', borderRadius: 3 },
      ] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + VE.money(c.parsed.y) } } },
        scales: {
          x: { stacked: true, grid: { display: false }, border: { color: css('line') }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 14 } },
          y: { stacked: true, beginAtZero: true, grid: { color: css('line') }, border: { display: false }, ticks: { callback: (v) => VE.currency + Number(v).toLocaleString('en-NG') } },
        },
      },
    });
  }
  draw();
  document.addEventListener('themechange', draw);
})();

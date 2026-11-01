// Customers list: submit the search box shortly after typing stops.
(function () {
  const input = document.querySelector('[data-live-search]');
  if (!input) return;
  input.addEventListener('input', VE.debounce(() => input.form.submit(), 450));
})();

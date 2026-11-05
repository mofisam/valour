<?php /** Buttons above the invoice (screen only). Needs $job. */ ?>
<div class="noprint">
  <a href="<?= url('/jobs/' . $job['id']) ?>">Back to job</a>
  <button type="button" id="btn-image">Download image</button>
  <button type="button" id="btn-share" style="display:none">Share</button>
  <a href="<?= url('/invoices/' . $job['id'] . '/pdf') ?>">Download PDF</a>
  <button type="button" onclick="window.print()">Print</button>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
(function () {
  var name = <?= json_encode(($job['invoice_number'] ?: 'invoice') . '.png') ?>;
  var imgBtn = document.getElementById('btn-image');
  var shareBtn = document.getElementById('btn-share');

  function render() {
    if (typeof html2canvas === 'undefined') {
      return Promise.reject(new Error('The image tool could not load. Check your internet connection and try again.'));
    }
    return html2canvas(document.querySelector('.doc'), { scale: 2, backgroundColor: '#ffffff', useCORS: true })
      .then(function (canvas) { return new Promise(function (resolve) { canvas.toBlob(resolve, 'image/png'); }); });
  }

  imgBtn.addEventListener('click', function () {
    var label = imgBtn.textContent;
    imgBtn.textContent = 'Preparing…'; imgBtn.disabled = true;
    render().then(function (blob) {
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download = name;
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(a.href); }, 2000);
    }).catch(function (e) { alert(e.message || 'Could not create the image.'); })
      .then(function () { imgBtn.textContent = label; imgBtn.disabled = false; });
  });

  // On phones that support it, offer "Share" so the receipt can go straight to WhatsApp etc.
  if (navigator.canShare && navigator.share) {
    try {
      var probe = new File([''], 'x.png', { type: 'image/png' });
      if (navigator.canShare({ files: [probe] })) { shareBtn.style.display = 'inline-block'; }
    } catch (e) {}
    shareBtn.addEventListener('click', function () {
      shareBtn.disabled = true;
      render().then(function (blob) {
        return navigator.share({ files: [new File([blob], name, { type: 'image/png' })], title: name });
      }).catch(function (e) { if (e && e.name !== 'AbortError') alert(e.message || 'Could not share.'); })
        .then(function () { shareBtn.disabled = false; });
    });
  }
})();
</script>

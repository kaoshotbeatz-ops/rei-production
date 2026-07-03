/* Equity Paradox registration — in-site modal (no external redirects). */
(function () {
  // quantity stepper (product pages)
  var qtyVal = document.getElementById('qtyVal');
  if (qtyVal) {
    document.querySelectorAll('.qty button').forEach(function (b) {
      b.addEventListener('click', function () {
        var n = Math.max(1, (parseInt(qtyVal.textContent, 10) || 1) + parseInt(b.dataset.step, 10));
        qtyVal.textContent = n;
      });
    });
  }

  var modal = document.getElementById('regModal');
  if (!modal) return;
  var form = document.getElementById('regForm'),
      done = document.getElementById('regDone'),
      kicker = document.getElementById('regKicker'),
      prod = document.getElementById('regProd'),
      submitBtn = document.getElementById('regSubmit'),
      doneMsg = document.getElementById('regDoneMsg');
  var current = {};

  function open(d) {
    current = d;
    kicker.textContent = d.kicker || 'Registration';
    var label = d.product + (d.tierText ? (' — ' + d.tierText) : '') + (d.price ? ('  ·  ' + d.price) : '');
    prod.textContent = label;
    submitBtn.textContent = (d.kicker && d.kicker.indexOf('Sponsor') > -1) ? 'Select Tier' : 'Register';
    form.hidden = false; done.hidden = true; form.reset();
    modal.hidden = false; document.body.style.overflow = 'hidden';
    setTimeout(function () { var f = form.querySelector('input'); if (f) f.focus(); }, 50);
  }
  function close() { modal.hidden = true; document.body.style.overflow = ''; }

  document.querySelectorAll('.reg-open').forEach(function (b) {
    b.addEventListener('click', function () {
      var tierSel = b.dataset.tier ? document.querySelector(b.dataset.tier) : null;
      var tierText = tierSel && tierSel.value ? tierSel.value : '';
      open({ kicker: b.dataset.kicker, product: b.dataset.product, price: b.dataset.price, tierText: tierText });
    });
  });

  modal.addEventListener('click', function (e) { if (e.target.hasAttribute('data-close')) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(form), get = function (k) { return (fd.get(k) || '').toString().trim(); };
    var item = current.product + (current.tierText ? (' — ' + current.tierText) : '');
    var subject = 'Conference Registration — ' + item;
    var body = 'REGISTRATION / ' + (current.kicker || '') + '\n' + item + (current.price ? ('  (' + current.price + ')') : '') + '\n\n' +
      'Name: ' + get('first') + ' ' + get('last') + '\nEmail: ' + get('email') + '\nPhone: ' + get('phone') +
      '\nOrganization: ' + get('org') + '\nDietary restrictions: ' + get('diet') + '\nHeard about us: ' + get('referral');
    var mailto = 'mailto:info@racialequityinstitute.org?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    form.hidden = true; done.hidden = false;
    doneMsg.innerHTML = 'Your registration request for <strong>' + item + '</strong> has been recorded. ' +
      'We’ve opened an email so you can send it to our team to confirm and arrange payment.';
    try { window.location.href = mailto; } catch (err) {}
  });
})();

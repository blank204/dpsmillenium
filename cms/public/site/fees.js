/* DPS Millennium — Fees: the first-year calculator and the installment picker.
   Numbers come from the #fee-data block the page prints, so they can never drift
   from the tables below. With no JavaScript the tables still carry every figure. */
(function () {
  'use strict';

  var raw = document.getElementById('fee-data');
  if (!raw) { return; }

  var data;
  try { data = JSON.parse(raw.textContent); } catch (e) { return; }

  function money(n) { return n.toLocaleString('en-GB'); }
  function each(list, fn) { Array.prototype.forEach.call(list, fn); }

  /* ---------- first-year calculator ---------- */
  var calc = document.querySelector('[data-calc]');
  if (calc) {
    var band = 1;
    var zone = 'none';
    var bandButtons = calc.querySelectorAll('[data-band]');
    var zoneButtons = calc.querySelectorAll('[data-transport]');

    function out(name) { return calc.querySelector('[data-out="' + name + '"]'); }

    function render() {
      var oneTime = data.oneTime[band];
      var tuition = data.tuition[band];
      var transport = data.transport[zone] || 0;
      out('oneTime').textContent = money(oneTime);
      out('tuition').textContent = money(tuition);
      out('transport').textContent = transport ? money(transport) : '—';
      out('perInstalment').textContent = money(data.instalment[band]);
      out('total').textContent = money(oneTime + tuition + transport);
    }

    each(bandButtons, function (b) {
      b.addEventListener('click', function () {
        band = parseInt(b.getAttribute('data-band'), 10);
        each(bandButtons, function (o) { o.setAttribute('aria-pressed', o === b ? 'true' : 'false'); });
        render();
      });
    });

    each(zoneButtons, function (b) {
      b.addEventListener('click', function () {
        zone = b.getAttribute('data-transport');
        each(zoneButtons, function (o) { o.setAttribute('aria-pressed', o === b ? 'true' : 'false'); });
        render();
      });
    });

    render();
  }

  /* ---------- installment picker ---------- */
  var select = document.querySelector('[data-band-select]');
  if (select) {
    var amounts = document.querySelectorAll('[data-step-amount]');
    var nameEl = document.querySelector('[data-band-name]');
    var totalEl = document.querySelector('[data-band-total]');

    select.addEventListener('change', function () {
      var i = parseInt(select.value, 10);
      each(amounts, function (a) { a.textContent = 'QAR ' + money(data.instalment[i]); });
      if (nameEl) { nameEl.textContent = data.bands[i]; }
      if (totalEl) { totalEl.textContent = 'QAR ' + money(data.tuition[i]); }
    });
  }
})();

/* DPS Millennium — The Campus: the build journal tabs.
   The markup ships with every stage visible and the tab strip hidden, so with no
   JavaScript the journal reads as an ordinary sequence of photographs. This turns
   it into a tab set. */
(function () {
  'use strict';

  var tabs = document.querySelector('[data-stage-tabs]');
  var panels = document.querySelector('[data-stage-panels]');
  if (!tabs || !panels) { return; }

  var buttons = tabs.querySelectorAll('[data-stage]');
  var figures = panels.querySelectorAll('[data-stage-panel]');
  if (!buttons.length || buttons.length !== figures.length) { return; }

  tabs.hidden = false;
  panels.setAttribute('data-tabbed', '');

  function select(index) {
    Array.prototype.forEach.call(buttons, function (b, i) {
      b.setAttribute('aria-selected', i === index ? 'true' : 'false');
      b.tabIndex = i === index ? 0 : -1;
    });
    Array.prototype.forEach.call(figures, function (f, i) {
      if (i === index) { f.setAttribute('data-on', ''); } else { f.removeAttribute('data-on'); }
    });
  }

  Array.prototype.forEach.call(buttons, function (button, i) {
    button.addEventListener('click', function () { select(i); });
    button.addEventListener('keydown', function (ev) {
      var step = ev.key === 'ArrowRight' ? 1 : (ev.key === 'ArrowLeft' ? -1 : 0);
      if (!step) { return; }
      ev.preventDefault();
      var next = (i + step + buttons.length) % buttons.length;
      buttons[next].focus();
      select(next);
    });
  });

  select(0);
})();

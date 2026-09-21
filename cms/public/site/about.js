/* DPS Millennium — About: the crest quadrant picker.
   Hovering or pressing one of the four readings dims the rest of the shield so the
   quarter being described is the only one lit. Without JavaScript the crest simply
   shows whole, which is the correct resting state anyway. */
(function () {
  'use strict';

  var stack = document.querySelector('[data-crest]');
  var buttons = document.querySelectorAll('[data-pick]');
  if (!stack || !buttons.length) { return; }

  function show(q) {
    if (q) { stack.setAttribute('data-active', q); } else { stack.removeAttribute('data-active'); }
    Array.prototype.forEach.call(buttons, function (b) {
      b.setAttribute('aria-pressed', b.getAttribute('data-pick') === q ? 'true' : 'false');
    });
  }

  Array.prototype.forEach.call(buttons, function (button) {
    var q = button.getAttribute('data-pick');
    button.setAttribute('aria-pressed', 'false');
    button.addEventListener('mouseenter', function () { show(q); });
    button.addEventListener('focus', function () { show(q); });
    // Tapping latches, so a touch user can read the description with the
    // quarter still lit rather than losing it the moment they lift a finger.
    button.addEventListener('click', function () {
      show(stack.getAttribute('data-active') === q ? null : q);
    });
  });
})();

/* Shows only the grade inputs that match the chosen mode, and disables the hidden
   ones so they are not submitted. The post editor has its own copy of this inside
   editor.js, so this file stands down when that form is on the page. */
(function () {
  'use strict';
  if (document.getElementById('post-form')) { return; }

  var radios = document.querySelectorAll('input[name="grades[mode]"]');
  if (!radios.length) { return; }
  var boxes = document.querySelectorAll('[data-grade-mode]');

  function sync() {
    var checked = document.querySelector('input[name="grades[mode]"]:checked');
    var mode = checked ? checked.value : 'none';
    Array.prototype.forEach.call(boxes, function (box) {
      box.hidden = box.getAttribute('data-grade-mode') !== mode;
      Array.prototype.forEach.call(box.querySelectorAll('input,select'), function (i) { i.disabled = box.hidden; });
    });
  }

  Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', sync); });
  sync();
})();

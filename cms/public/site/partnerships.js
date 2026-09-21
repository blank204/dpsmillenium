/* DPS Millennium — Partnerships: the enquiry basket.
 *
 * The prototype let a reader tick areas of interest as they went, and carried the
 * list into the enquiry form. There is no form to post to, so the same list is
 * written into the body of a mailto: instead — the visitor still only has to say
 * who they are, and the school still receives what they picked.
 */
(function () {
  'use strict';

  var buttons = document.querySelectorAll('[data-pick]');
  var countEl = document.querySelector('[data-pick-count]');
  var listEl = document.querySelector('[data-pick-list]');
  var link = document.querySelector('[data-enquiry-link]');
  if (!buttons.length || !link) { return; }

  var base = link.getAttribute('href');
  var picked = [];

  function body() {
    var lines = ['Hello,', '', 'I would like to talk about working with DPS Millennium.', ''];
    if (picked.length) {
      lines.push('Areas I am interested in:');
      picked.forEach(function (p) { lines.push('  - ' + p); });
      lines.push('');
    }
    lines.push('Name:', 'Organisation:', 'Phone:', '', 'What I would like to explore:', '');
    return lines.join('\n');
  }

  function render() {
    if (countEl) {
      countEl.textContent = picked.length
        ? '— ' + picked.length + ' selected'
        : '— nothing selected yet';
    }

    if (listEl) {
      listEl.innerHTML = '';
      picked.forEach(function (name) {
        var li = document.createElement('li');
        var b = document.createElement('button');
        b.type = 'button';
        b.title = 'Remove';
        b.textContent = name;
        var x = document.createElement('span');
        x.setAttribute('aria-hidden', 'true');
        x.textContent = '×';
        b.appendChild(x);
        b.addEventListener('click', function () { toggle(name); });
        li.appendChild(b);
        listEl.appendChild(li);
      });
    }

    Array.prototype.forEach.call(buttons, function (btn) {
      var on = picked.indexOf(btn.getAttribute('data-pick')) !== -1;
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      var mark = btn.querySelector('.pickbtn-mark');
      if (mark) { mark.textContent = on ? '✓' : '+'; }
    });

    link.setAttribute('href', base + '&body=' + encodeURIComponent(body()));
  }

  function toggle(name) {
    var i = picked.indexOf(name);
    if (i === -1) { picked.push(name); } else { picked.splice(i, 1); }
    render();
  }

  Array.prototype.forEach.call(buttons, function (btn) {
    btn.addEventListener('click', function () { toggle(btn.getAttribute('data-pick')); });
  });

  render();
})();

/* DPS Millennium — public site behaviour.
 *
 * Claude Design's prototypes were rendered by a React preview runtime that is not
 * shippable (it needs React on the page and renders client-side, which would leave
 * search engines an empty shell). The markup and styling port over verbatim; this
 * file supplies the handful of things that runtime used to do, as progressive
 * enhancement over server-rendered HTML.
 *
 * Plain ES5-ish, no build step, no dependencies. Everything degrades to a working
 * page if it does not run.
 */
(function () {
  'use strict';

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function each(list, fn) { Array.prototype.forEach.call(list, fn); }

  /* ---------- style-hover / style-active -------------------------------------
     The prototype wrote hover styling as an attribute on the element. Parse it
     once, remember what the element looked like, and swap on the way in and out. */
  function parseDecls(text) {
    var out = [];
    (text || '').split(';').forEach(function (bit) {
      var i = bit.indexOf(':');
      if (i < 1) { return; }
      out.push([bit.slice(0, i).trim(), bit.slice(i + 1).trim()]);
    });
    return out;
  }

  function bindStateStyles(el, attr, onEvents, offEvents) {
    var decls = parseDecls(el.getAttribute(attr));
    if (!decls.length) { return; }
    var previous = null;

    function on() {
      if (previous) { return; }
      previous = decls.map(function (d) { return [d[0], el.style.getPropertyValue(d[0])]; });
      decls.forEach(function (d) { el.style.setProperty(d[0], d[1]); });
    }
    function off() {
      if (!previous) { return; }
      previous.forEach(function (p) {
        if (p[1]) { el.style.setProperty(p[0], p[1]); } else { el.style.removeProperty(p[0]); }
      });
      previous = null;
    }

    onEvents.forEach(function (e) { el.addEventListener(e, on); });
    offEvents.forEach(function (e) { el.addEventListener(e, off); });
  }

  each(document.querySelectorAll('[style-hover]'), function (el) {
    bindStateStyles(el, 'style-hover', ['mouseenter', 'focus'], ['mouseleave', 'blur']);
  });
  each(document.querySelectorAll('[style-active]'), function (el) {
    bindStateStyles(el, 'style-active', ['pointerdown'], ['pointerup', 'pointerleave', 'blur']);
  });

  /* ---------- scroll reveals -------------------------------------------------
     data-anim marks a block that should arrive as it comes into view. The CSS
     holds the from-state on [data-anim]:not([data-seen]); this just sets the flag. */
  var animated = document.querySelectorAll('[data-anim]');
  if (reduced || !('IntersectionObserver' in window)) {
    each(animated, function (el) { el.setAttribute('data-seen', ''); });
  } else {
    var seen = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        entry.target.setAttribute('data-seen', '');
        seen.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
    each(animated, function (el) { seen.observe(el); });
  }

  /* ---------- eased in-page jumps --------------------------------------------
     Slow off the mark, quick through the middle, settling under the sticky bar.
     Timer-driven rather than rAF: a backgrounded tab never services rAF, and the
     jump would then do nothing at all. */
  var glideTimer = null;

  function glideTo(id) {
    var el = id && document.getElementById(id);
    if (!el) { return false; }
    var nav = document.querySelector('[data-site-nav]');
    var pad = (nav ? nav.getBoundingClientRect().height : 0) + 12;
    var from = window.pageYOffset;
    var max = document.documentElement.scrollHeight - window.innerHeight;
    var to = Math.min(max, Math.max(0, el.getBoundingClientRect().top + from - pad));
    if (Math.abs(to - from) < 2) { return true; }
    if (reduced) { window.scrollTo(0, to); return true; }

    var dur = Math.min(1500, Math.max(650, Math.abs(to - from) * 0.55));
    var t0 = Date.now();
    clearInterval(glideTimer);
    glideTimer = setInterval(function () {
      var p = Math.min(1, (Date.now() - t0) / dur);
      var e = p < 0.5 ? 4 * p * p * p : 1 - Math.pow(-2 * p + 2, 3) / 2;
      window.scrollTo(0, from + (to - from) * e);
      if (p >= 1) { clearInterval(glideTimer); }
    }, 16);
    return true;
  }

  document.addEventListener('click', function (ev) {
    var link = ev.target.closest && ev.target.closest('a[href^="#"]');
    if (!link) { return; }
    var id = link.getAttribute('href').slice(1);
    if (!id) { return; }
    if (glideTo(id)) {
      ev.preventDefault();
      closeMenu();
      if (history.replaceState) { history.replaceState(null, '', '#' + id); }
    }
  });

  // Arriving on /page#section: let the browser paint, then ease to it.
  if (window.location.hash.length > 1) {
    var landing = window.location.hash.slice(1);
    window.addEventListener('load', function () { setTimeout(function () { glideTo(landing); }, 60); });
  }

  /* ---------- navigation -----------------------------------------------------
     Dropdowns open on hover and on focus via CSS. This adds what CSS cannot do:
     a burger for narrow screens, and tap-to-open on touch devices where there is
     no hover at all. */
  var nav = document.querySelector('[data-site-nav]');
  var menu = nav && nav.querySelector('[data-nav-menu]');
  var burger = nav && nav.querySelector('[data-burger]');

  function closeMenu() {
    if (!menu) { return; }
    menu.removeAttribute('data-open');
    if (burger) { burger.setAttribute('aria-expanded', 'false'); }
    each(nav.querySelectorAll('[data-sub-open]'), function (li) { li.removeAttribute('data-sub-open'); });
  }

  if (burger && menu) {
    burger.addEventListener('click', function () {
      var open = menu.hasAttribute('data-open');
      if (open) { closeMenu(); } else {
        menu.setAttribute('data-open', '');
        burger.setAttribute('aria-expanded', 'true');
      }
    });
  }

  if (nav) {
    // On a touch screen the first tap on a parent opens its submenu instead of
    // following the link, so the child pages are reachable at all.
    each(nav.querySelectorAll('[data-menu] > a'), function (link) {
      link.addEventListener('click', function (ev) {
        var hoverable = window.matchMedia && window.matchMedia('(hover: hover)').matches;
        var wide = window.innerWidth >= 1024;
        if (hoverable && wide) { return; }
        var li = link.parentNode;
        if (!li.hasAttribute('data-sub-open')) {
          ev.preventDefault();
          each(nav.querySelectorAll('[data-sub-open]'), function (o) {
            if (o !== li) { o.removeAttribute('data-sub-open'); }
          });
          li.setAttribute('data-sub-open', '');
        }
      });
    });

    document.addEventListener('click', function (ev) {
      if (!nav.contains(ev.target)) { closeMenu(); }
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') { closeMenu(); }
    });
  }

  /* ---------- gold reading rule under the sticky bar -------------------------
     Replaces the native scrollbar, which the design hides. */
  var progress = document.querySelector('[data-progress]');
  if (progress) {
    var tick = function () {
      var max = document.documentElement.scrollHeight - window.innerHeight;
      progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, window.pageYOffset / max) : 0) + ')';
    };
    window.addEventListener('scroll', tick, { passive: true });
    window.addEventListener('resize', tick);
    tick();
  }

  /* ---------- counters -------------------------------------------------------
     data-count-to rolls a number up once, when it first comes into view. */
  each(document.querySelectorAll('[data-count-to]'), function (el) {
    var target = parseFloat(el.getAttribute('data-count-to'));
    if (isNaN(target)) { return; }
    if (reduced || !('IntersectionObserver' in window)) { el.textContent = String(target); return; }

    var counter = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        counter.unobserve(entry.target);
        var t0 = Date.now();
        var run = setInterval(function () {
          var p = Math.min(1, (Date.now() - t0) / 1100);
          el.textContent = String(Math.round(target * (1 - Math.pow(1 - p, 3))));
          if (p >= 1) { clearInterval(run); }
        }, 32);
      });
    }, { threshold: 0.5 });
    counter.observe(el);
  });

  /* ---------- countdown ------------------------------------------------------
     data-countdown-to="YYYY-MM-DD" counts whole days remaining. */
  each(document.querySelectorAll('[data-countdown-to]'), function (el) {
    var target = new Date(el.getAttribute('data-countdown-to') + 'T00:00:00+03:00');
    var days = Math.max(0, Math.ceil((target - new Date()) / 86400000));
    el.textContent = String(days);
  });
})();

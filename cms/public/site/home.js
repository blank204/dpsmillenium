/* DPS Millennium — front page behaviour.
 *
 * Two things: the opening curtain only plays once per tab, and the architect's
 * study fills in with the finished building as the band crosses the viewport.
 *
 * The reveal is scrubbed by scroll position rather than played on a clock, so
 * every frame is a pure function of where the band sits — scrolling back up runs
 * it backwards exactly, draining the colour out again.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  root.classList.remove('no-js');

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- curtain: first visit in this tab only ---------- */
  try {
    if (sessionStorage.getItem('dpsm_visited') === '1') { root.setAttribute('data-returning', ''); }
    sessionStorage.setItem('dpsm_visited', '1');
  } catch (e) {
    // Private mode or blocked storage: play it. Better than a blank screen.
  }

  /* ---------- scrubbed campus reveal ---------- */
  var band = document.getElementById('render');
  var concept = band && band.querySelector('[data-chase]');
  var canvas = band && band.querySelector('[data-bloom]');
  if (!band || !concept) { return; }

  if (reduced) {
    concept.style.clipPath = 'none';
    if (canvas) { canvas.style.display = 'none'; }
    return;
  }

  var ctx = canvas ? canvas.getContext('2d') : null;

  // Fixed lobe field, so the bloom is the same shape every time rather than
  // shimmering differently on each scroll.
  var lobes = [
    { x: 0.50, y: 0.70, s: 1.00 }, { x: 0.31, y: 0.62, s: 0.72 },
    { x: 0.69, y: 0.64, s: 0.76 }, { x: 0.18, y: 0.78, s: 0.54 },
    { x: 0.82, y: 0.76, s: 0.58 }, { x: 0.42, y: 0.48, s: 0.48 },
    { x: 0.60, y: 0.46, s: 0.44 }
  ];

  function draw(p) {
    // p is 0 at the moment the band's top reaches the bottom of the viewport,
    // and 1 once it has travelled a screen height further up.
    concept.style.clipPath = 'circle(' + (p * 148).toFixed(2) + '% at 50% 70%)';

    if (!ctx) { return; }
    var w = canvas.width, h = canvas.height;
    ctx.clearRect(0, 0, w, h);
    if (p <= 0 || p >= 1) { return; }

    // Gold bloom leads the edge of the chase and fades out behind it.
    var lead = Math.min(1, p * 1.15);
    var fade = p < 0.12 ? p / 0.12 : (p > 0.82 ? Math.max(0, (1 - p) / 0.18) : 1);
    ctx.globalCompositeOperation = 'lighter';

    for (var i = 0; i < lobes.length; i++) {
      var l = lobes[i];
      var r = lead * l.s * w * 0.42;
      if (r <= 0) { continue; }
      // Soft all the way through: a hard ring reads as a disc sitting on top of
      // the picture rather than light spreading across it.
      var g = ctx.createRadialGradient(l.x * w, l.y * h, 0, l.x * w, l.y * h, r);
      g.addColorStop(0, 'rgba(216,168,0,0)');
      g.addColorStop(0.62, 'rgba(216,168,0,' + (0.03 * fade).toFixed(3) + ')');
      g.addColorStop(0.86, 'rgba(216,168,0,' + (0.09 * fade).toFixed(3) + ')');
      g.addColorStop(0.97, 'rgba(216,168,0,' + (0.04 * fade).toFixed(3) + ')');
      g.addColorStop(1, 'rgba(216,168,0,0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.arc(l.x * w, l.y * h, r, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalCompositeOperation = 'source-over';
  }

  var queued = false;

  function measure() {
    queued = false;
    var rect = band.getBoundingClientRect();
    var vh = window.innerHeight || 1;
    // Nothing until the band is properly on screen, complete by the time its top
    // edge reaches the top of the viewport — so the sketch is what you see first
    // and the finished building arrives as you scroll down through the band.
    var start = vh * 0.72;
    var travelled = (start - rect.top) / start;
    draw(Math.max(0, Math.min(1, travelled)));
  }

  function onScroll() {
    if (queued) { return; }
    queued = true;
    window.requestAnimationFrame(measure);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  window.addEventListener('load', measure);
  measure();
})();

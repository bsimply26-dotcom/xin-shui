(function () {
  'use strict';

  /* ---------- Sticky nav ---------- */
  var nav = document.getElementById('nav');
  function onScroll() {
    nav.classList.toggle('is-stuck', window.scrollY > 24);
  }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---------- Mobile menu ---------- */
  var toggle = document.querySelector('.nav-toggle');
  var mobile = document.getElementById('nav-mobile');
  if (toggle && mobile) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      toggle.setAttribute('aria-label', open ? 'Open menu' : 'Close menu');
      mobile.hidden = open;
    });
    mobile.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        toggle.setAttribute('aria-expanded', 'false');
        mobile.hidden = true;
      }
    });
  }

  /* ---------- FAQ accordion ---------- */
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var open = btn.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll('.faq-item.is-open').forEach(function (other) {
        if (other !== item) {
          other.classList.remove('is-open');
          other.querySelector('.faq-q').setAttribute('aria-expanded', 'false');
        }
      });
      btn.setAttribute('aria-expanded', String(!open));
      item.classList.toggle('is-open', !open);
    });
  });

  /* ---------- Hero carousel ----------
     Autoplay every 6 seconds. Pauses on hover, on keyboard focus, and when
     the tab is in the background. Stops permanently once the visitor uses a
     control, so it never fights them. Disabled entirely if the visitor has
     asked for reduced motion.

     TUNE: DELAY is the interval in milliseconds. */
  var hero = document.querySelector('.hero');
  if (hero) {
    var slides = Array.prototype.slice.call(hero.querySelectorAll('.slide'));
    var dots = Array.prototype.slice.call(hero.querySelectorAll('.car-dots button'));
    var prev = hero.querySelector('.car-prev');
    var next = hero.querySelector('.car-next');
    var index = 0;
    var timer = null;
    var stopped = false;
    var DELAY = 6000;
    var reduce = window.matchMedia &&
                 window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (s, n) {
        s.classList.toggle('is-active', n === index);
        s.setAttribute('aria-hidden', String(n !== index));
      });
      dots.forEach(function (d, n) {
        d.setAttribute('aria-selected', String(n === index));
      });
    }
    function start() {
      if (stopped || reduce || slides.length < 2) return;
      stop();
      timer = window.setInterval(function () { show(index + 1); }, DELAY);
    }
    function stop() {
      if (timer) { window.clearInterval(timer); timer = null; }
    }
    function halt() { stopped = true; stop(); }

    if (prev) prev.addEventListener('click', function () { halt(); show(index - 1); });
    if (next) next.addEventListener('click', function () { halt(); show(index + 1); });
    dots.forEach(function (d, n) {
      d.addEventListener('click', function () { halt(); show(n); });
    });
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);
    hero.addEventListener('focusin', stop);
    hero.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stop(); } else { start(); }
    });
    hero.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { halt(); show(index - 1); }
      if (e.key === 'ArrowRight') { halt(); show(index + 1); }
    });

    show(0);
    start();
  }

  /* ---------- Pillar strip auto scroll ----------
     Advances one item every 3.5 seconds and loops back to the start.
     Stops permanently the first time the visitor touches or scrolls it,
     so it never fights them. Runs at 700px and below only, and only when
     the row actually overflows. Disabled under reduced motion.

     This also serves as a diagnostic for the swipe fault. Programmatic
     scrolling is not blocked by a CSS mask, but touch scrolling is. If the
     strip auto scrolls and still will not swipe, a mask is still reaching
     the scroll container and override block v1 has not been removed.

     TUNE: STRIP_DELAY is the interval in milliseconds. */
  var strip = document.querySelector('.strip-in');
  if (strip) {
    var stripTimer = null;
    var stripStopped = false;
    var STRIP_DELAY = 3500;
    var stripReduce = window.matchMedia &&
                      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function stripCanRun() {
      return !stripStopped && !stripReduce &&
             window.matchMedia('(max-width:700px)').matches &&
             strip.scrollWidth > strip.clientWidth + 4;
    }
    function stripStep() {
      var item = strip.querySelector('.strip-item');
      if (!item) return;
      var step = item.getBoundingClientRect().width;
      var max = strip.scrollWidth - strip.clientWidth;
      var target = strip.scrollLeft + step;
      if (target > max - 4) target = 0;
      if (strip.scrollTo) {
        strip.scrollTo({ left: target, behavior: 'smooth' });
      } else {
        strip.scrollLeft = target;
      }
    }
    function stripStop() {
      if (stripTimer) { window.clearInterval(stripTimer); stripTimer = null; }
    }
    function stripStart() {
      stripStop();
      if (!stripCanRun()) return;
      stripTimer = window.setInterval(stripStep, STRIP_DELAY);
    }
    function stripHalt() { stripStopped = true; stripStop(); }

    strip.addEventListener('touchstart', stripHalt, { passive: true });
    strip.addEventListener('pointerdown', stripHalt);
    strip.addEventListener('wheel', stripHalt, { passive: true });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stripStop(); } else { stripStart(); }
    });
    window.addEventListener('resize', stripStart);
    stripStart();
  }

  /* ---------- Footer year ---------- */
  var year = document.getElementById('year');
  if (year) year.textContent = new Date().getFullYear();

})();

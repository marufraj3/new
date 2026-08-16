/* ============================================================
   SHOP GENIE — storefront interactions (vanilla, no framework)
   ============================================================ */
(function () {
  'use strict';

  /* ---------- helpers ---------- */
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  document.addEventListener('DOMContentLoaded', function () {
    initStickyHeader();
    initDrawer();
    initMobileSearch();
    initCatsPanel();
    initUserMenu();
    initSearchDropdown();
    initQty();
    initOptGroups();
    initHeroSlider();
    initFlashTimer();
    initGotop();
    initChat();
    initTabs();
    initGallery();
    initFilterSidebar();
    initRangeFilter();
  });

  /* ---------- sticky header ---------- */
  function initStickyHeader () {
    var hd = $('#sfHeader');
    if (!hd) return;
    var onScroll = function () {
      if (window.scrollY > 40) hd.classList.add('is-sticky');
      else hd.classList.remove('is-sticky');
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- mobile drawer ---------- */
  function initDrawer () {
    var ovl = $('#sfDrawerOvl'), drawer = $('#sfDrawer');
    if (!drawer) return;
    function open () { drawer.classList.add('show'); if (ovl) ovl.classList.add('show'); document.body.classList.add('sf-locked'); }
    function close () { drawer.classList.remove('show'); if (ovl) ovl.classList.remove('show'); document.body.classList.remove('sf-locked'); }
    $$('[data-drawer-open]').forEach(function (b) { b.addEventListener('click', open); });
    $$('[data-drawer-close]').forEach(function (b) { b.addEventListener('click', close); });
    if (ovl) ovl.addEventListener('click', close);
    /* category accordion */
    $$('.sf-dcat__top', drawer).forEach(function (t) {
      t.addEventListener('click', function () { t.parentElement.classList.toggle('open'); });
    });
  }

  /* ---------- mobile search panel ---------- */
  function initMobileSearch () {
    var btn = $('#sfMSearchBtn'), panel = $('#sfMSearch');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
      panel.classList.toggle('show');
      if (panel.classList.contains('show')) {
        var inp = $('input', panel);
        if (inp) setTimeout(function () { inp.focus(); }, 120);
      }
    });
  }

  /* ---------- all-categories panel ---------- */
  function initCatsPanel () {
    var trig = $('#sfCatsTrigger'), panel = $('#sfCatsPanel');
    if (!trig || !panel) return;
    var isHover = window.matchMedia('(min-width: 992px)').matches;
    if (isHover) {
      var wrap = trig.parentElement;
      wrap.addEventListener('mouseenter', function () { panel.classList.add('show'); });
      wrap.addEventListener('mouseleave', function () { panel.classList.remove('show'); });
    } else {
      trig.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        panel.classList.toggle('show');
      });
      document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && e.target !== trig) panel.classList.remove('show');
      });
    }
  }

  /* ---------- account dropdown ---------- */
  function initUserMenu () {
    var btn = $('#sfUserBtn'), menu = $('#sfUserMenu');
    if (!btn || !menu) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation(); menu.classList.toggle('show');
    });
    document.addEventListener('click', function (e) {
      if (!menu.contains(e.target)) menu.classList.remove('show');
    });
  }

  /* ---------- live search dropdown ---------- */
  function initSearchDropdown () {
    var inputs = $$('.sf-search__box input');
    if (!inputs.length) return;
    var url = (window.SF && SF.searchUrl) || null;
    inputs.forEach(function (inp) {
      var box = inp.closest('.sf-search__box');
      var wrap = inp.closest('.sf-search');
      var drop = wrap ? $('.sf-search-drop', wrap) : null;
      if (!drop || !url) return;
      var timer = null;
      inp.addEventListener('keyup', function () {
        var kw = inp.value.trim();
        clearTimeout(timer);
        if (!kw) { drop.classList.remove('show'); return; }
        timer = setTimeout(function () {
          fetch(url + '?keyword=' + encodeURIComponent(kw), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
              var body = $('.sf-search-drop__body', drop);
              if (body) body.innerHTML = html;
              drop.classList.add('show');
            })
            .catch(function () {});
        }, 260);
      });
      document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) drop.classList.remove('show');
      });
      inp.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') drop.classList.remove('show');
      });
      if (box) box.addEventListener('submit', function () { drop.classList.remove('show'); });
    });
  }

  /* ---------- qty steppers ---------- */
  function initQty () {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-qty]');
      if (!btn) return;
      var grp = btn.closest('.sf-qty');
      var inp = grp && $('input', grp);
      if (!inp) return;
      var min = parseInt(inp.getAttribute('min') || '1', 10);
      var max = parseInt(inp.getAttribute('max') || '9999', 10);
      var v = parseInt(inp.value || min, 10);
      v = btn.getAttribute('data-qty') === 'plus' ? v + 1 : v - 1;
      v = Math.max(min, Math.min(max, v));
      inp.value = v;
      inp.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  /* ---------- option groups (size/color) ---------- */
  function initOptGroups () {
    document.addEventListener('click', function (e) {
      var opt = e.target.closest('.sf-opt');
      if (!opt) return;
      var grp = opt.parentElement;
      $$('.sf-opt', grp).forEach(function (o) { o.classList.remove('active'); });
      opt.classList.add('active');
      var inp = $('input', opt);
      if (inp) { inp.checked = true; inp.dispatchEvent(new Event('change', { bubbles: true })); }
    });
  }

  /* ---------- hero slider ---------- */
  function initHeroSlider () {
    var hero = $('#sfHero');
    if (!hero) return;
    var slides = $$('.sf-hero-slide', hero);
    var dotsWrap = $('.sf-hero__dots', hero);
    if (!slides.length) return;
    var idx = 0, timer = null;
    function go (n) {
      slides[idx].classList.remove('active');
      idx = (n + slides.length) % slides.length;
      slides[idx].classList.add('active');
      if (dotsWrap) $$('button', dotsWrap).forEach(function (d, i) { d.classList.toggle('active', i === idx); });
    }
    function auto () { clearInterval(timer); timer = setInterval(function () { go(idx + 1); }, 4500); }
    if (dotsWrap && slides.length > 1) {
      slides.forEach(function (_, i) {
        var b = document.createElement('button');
        b.addEventListener('click', function () { go(i); auto(); });
        dotsWrap.appendChild(b);
      });
      dotsWrap.querySelector('button').classList.add('active');
    }
    var prev = $('.sf-hero__nav.prev', hero), next = $('.sf-hero__nav.next', hero);
    if (prev) prev.addEventListener('click', function () { go(idx - 1); auto(); });
    if (next) next.addEventListener('click', function () { go(idx + 1); auto(); });
    auto();
    hero.addEventListener('mouseenter', function () { clearInterval(timer); });
    hero.addEventListener('mouseleave', auto);
  }

  /* ---------- flash sale countdown ---------- */
  function initFlashTimer () {
    var boxes = $$('.sf-flash__timer');
    if (!boxes.length) return;
    boxes.forEach(function (box) {
      var end = parseInt(box.getAttribute('data-end') || '0', 10);
      if (!end) return;
      var dEl = $('[data-t-d]', box), hEl = $('[data-t-h]', box), mEl = $('[data-t-m]', box), sEl = $('[data-t-s]', box);
      function tick () {
        var left = Math.max(0, end - Date.now());
        var d = Math.floor(left / 864e5), h = Math.floor(left % 864e5 / 36e5), m = Math.floor(left % 36e5 / 6e4), s = Math.floor(left % 6e4 / 1e3);
        if (dEl) dEl.textContent = String(d).padStart(2, '0');
        if (hEl) hEl.textContent = String(h).padStart(2, '0');
        if (mEl) mEl.textContent = String(m).padStart(2, '0');
        if (sEl) sEl.textContent = String(s).padStart(2, '0');
        if (left <= 0) clearInterval(iv);
      }
      tick();
      var iv = setInterval(tick, 1000);
    });
  }

  /* ---------- back to top ---------- */
  function initGotop () {
    var btn = $('#sfGotop');
    if (!btn) return;
    window.addEventListener('scroll', function () {
      btn.classList.toggle('show', window.scrollY > 500);
    }, { passive: true });
    btn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  /* ---------- chat widget ---------- */
  function initChat () {
    var main = $('#sfChatMain'), opts = $('#sfChatOpts');
    if (!main || !opts) return;
    main.addEventListener('click', function (e) {
      e.stopPropagation(); opts.classList.toggle('show');
    });
    document.addEventListener('click', function () { opts.classList.remove('show'); });
  }

  /* ---------- tabs ---------- */
  function initTabs () {
    $$('.sf-tabs').forEach(function (tabs) {
      var nav = $$('.sf-tabs__nav button', tabs);
      nav.forEach(function (btn) {
        btn.addEventListener('click', function () {
          nav.forEach(function (b) { b.classList.remove('active'); });
          btn.classList.add('active');
          $$('.sf-tabs__pane', tabs).forEach(function (p) { p.classList.remove('active'); });
          var tgt = document.getElementById(btn.getAttribute('data-tab'));
          if (tgt) tgt.classList.add('active');
        });
      });
    });
  }

  /* ---------- product gallery ---------- */
  function initGallery () {
    $$('[data-thumb]').forEach(function (th) {
      th.addEventListener('click', function () {
        var main = $('#sfMainImg');
        if (!main) return;
        var full = th.getAttribute('data-full');
        if (full) main.src = full;
        $$('[data-thumb]').forEach(function (t) { t.classList.remove('active'); });
        th.classList.add('active');
      });
    });
  }

  /* ---------- filter sidebar (mobile) ---------- */
  function initFilterSidebar () {
    var btn = $('#sfFilterBtn'), side = $('#sfFilterSide'), ovl = $('#sfFilterOvl');
    if (!btn || !side) return;
    function open () { side.classList.add('show'); if (ovl) ovl.classList.add('show'); document.body.classList.add('sf-locked'); }
    function close () { side.classList.remove('show'); if (ovl) ovl.classList.remove('show'); document.body.classList.remove('sf-locked'); }
    btn.addEventListener('click', open);
    if (ovl) ovl.addEventListener('click', close);
    var cls = $('.cls', side);
    if (cls) cls.addEventListener('click', close);
  }

  /* ---------- price range ---------- */
  function initRangeFilter () {
    var ra = $('#sfRangeMin'), rb = $('#sfRangeMax');
    if (!ra || !rb) return;
    var bar = $('#sfRangeBar');
    var minGap = 5, minV = parseInt(ra.min, 10), maxV = parseInt(ra.max, 10);
    function paint () {
      var a = parseInt(ra.value, 10), b = parseInt(rb.value, 10);
      if (b - a < minGap) {
        if (ra === document.activeElement) ra.value = b - minGap;
        else rb.value = a + minGap;
        a = parseInt(ra.value, 10); b = parseInt(rb.value, 10);
      }
      var la = ((a - minV) / (maxV - minV)) * 100, lb = ((b - minV) / (maxV - minV)) * 100;
      if (bar) bar.style.left = la + '%';
      if (bar) bar.style.width = (lb - la) + '%';
      var o1 = $('#sfRangeMinVal'), o2 = $('#sfRangeMaxVal');
      if (o1) o1.textContent = '৳' + a.toLocaleString();
      if (o2) o2.textContent = '৳' + b.toLocaleString();
    }
    ra.addEventListener('input', paint); rb.addEventListener('input', paint);
    paint();
  }

  /* ---------- exposed API (used by Blade inline scripts) ---------- */
  window.SF = window.SF || {};
  window.SF.openDrawer = function () {
    var d = $('#sfDrawer'), o = $('#sfDrawerOvl');
    if (d) { d.classList.add('show'); if (o) o.classList.add('show'); document.body.classList.add('sf-locked'); }
  };
  window.SF.closeDrawer = function () {
    var d = $('#sfDrawer'), o = $('#sfDrawerOvl');
    if (d) { d.classList.remove('show'); if (o) o.classList.remove('show'); document.body.classList.remove('sf-locked'); }
  };
  window.SF.openCartDrawer = function () {
    var d = $('#sfCartDrawer'), o = $('#sfCartDrawerOvl');
    if (d) { d.classList.add('show'); if (o) o.classList.add('show'); document.body.classList.add('sf-locked'); }
  };
  window.SF.closeCartDrawer = function () {
    var d = $('#sfCartDrawer'), o = $('#sfCartDrawerOvl');
    if (d) { d.classList.remove('show'); if (o) o.classList.remove('show'); document.body.classList.remove('sf-locked'); }
  };
  window.SF.modal = function (id, show) {
    var m = document.getElementById(id);
    if (!m) return;
    if (show) { m.classList.add('show'); document.body.classList.add('sf-locked'); }
    else { m.classList.remove('show'); document.body.classList.remove('sf-locked'); }
  };
})();

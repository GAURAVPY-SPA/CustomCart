/* CustomCart - site script (all pages; Bootstrap 5.3.3 from Scripts/bootstrap.js).
   Header, drawer, sliders, product zoom,
   cart / wishlist (AJAX: window.KT from bottom_application.php), popups, share links,
   lightboxes, account forms and scroll reveal. */
(function () {
  'use strict';

  function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function modal(el) { return bootstrap.Modal.getOrCreateInstance(el); }
  var KT = window.KT || {};
  function post(url, data) {
    var body = new URLSearchParams();
    body.set(KT.csrfName, KT.csrfHash);
    Object.keys(data).forEach(function (k) { body.set(k, data[k]); });
    return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); });
  }
  function setCount(sel, n) {
    qsa(sel).forEach(function (el) {
      el.textContent = n;
      el.hidden = sel === '.js-wish-count' && !n;
      el.classList.add('is-bump');
      setTimeout(function () { el.classList.remove('is-bump'); }, 260);
    });
  }

  /* ---------- Toast ---------- */
  var toastEl = qs('#ktToast');
  function toast(msg) {
    if (!toastEl) return;
    qs('#ktToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(toastEl).show();
  }

  /* ---------- Header: scroll shadow, back to top, mega menu, search ---------- */
  var header = qs('#ktHeader');
  var toTop = qs('#ktToTop');
  var searchToggle = qs('#ktSearchToggle');
  var searchForm = qs('#ktSearch');

  function onScroll() {
    var y = window.pageYOffset || document.documentElement.scrollTop;
    header.classList.toggle('is-scrolled', y > 20);
    if (toTop) toTop.classList.toggle('is-show', y > 800);
  }
  if (header) {
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  }
  if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

  function closeSearch() {
    header.classList.remove('search-open');
    searchToggle.setAttribute('aria-expanded', 'false');
  }
  qsa('.kt-nav-link').forEach(function (link) {
    link.addEventListener('mouseenter', function () {
      header.classList.toggle('mega-open', link.classList.contains('has-mega'));
      closeSearch();
    });
  });
  if (header) header.addEventListener('mouseleave', function () { header.classList.remove('mega-open'); });

  if (searchToggle) searchToggle.addEventListener('click', function () {
    var open = header.classList.toggle('search-open');
    header.classList.remove('mega-open');
    searchToggle.setAttribute('aria-expanded', String(open));
    if (open) qs('input', searchForm).focus();
  });
  /* Header search: POST, a blank search is sent too (all products: Products::listing()). */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && header) { closeSearch(); header.classList.remove('mega-open'); }
  });

  /* ---------- Mobile drawer (below 1200px): raise the header over the backdrop; a used link closes it ---------- */
  var drawer = qs('#ktDrawer');
  if (drawer) drawer.addEventListener('show.bs.offcanvas', function () { header.classList.add('drawer-open'); });
  if (drawer) drawer.addEventListener('hidden.bs.offcanvas', function () { header.classList.remove('drawer-open'); });
  if (drawer) qsa('a', drawer).forEach(function (a) {
    a.addEventListener('click', function () {
      if (drawer.classList.contains('show')) bootstrap.Offcanvas.getOrCreateInstance(drawer).hide();
    });
  });

  /* ---------- Sliders: [data-kt-slide="prev|next"][data-kt-target="#track"] ----------
     Optional on the track: data-kt-loop (wrap at the ends), data-kt-auto="ms" (autoplay, pauses on hover/touch/focus) */
  function slideTrack(track, dir) {
    var max = track.scrollWidth - track.clientWidth;
    if (track.hasAttribute('data-kt-loop')) {
      if (dir > 0 && track.scrollLeft >= max - 2) { track.scrollTo({ left: 0, behavior: 'smooth' }); return; }
      if (dir < 0 && track.scrollLeft <= 2) { track.scrollTo({ left: max, behavior: 'smooth' }); return; }
    }
    track.scrollBy({ left: dir * track.clientWidth * 0.75, behavior: 'smooth' });
  }
  qsa('[data-kt-slide]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var track = qs(btn.getAttribute('data-kt-target'));
      if (track) slideTrack(track, btn.getAttribute('data-kt-slide') === 'prev' ? -1 : 1);
    });
  });
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  qsa('.kt-track[data-kt-auto]').forEach(function (track) {
    if (reduceMotion) return;
    var paused = false;
    function pause() { paused = true; }
    function resume() { paused = false; }
    track.addEventListener('mouseenter', pause);
    track.addEventListener('mouseleave', resume);
    track.addEventListener('focusin', pause);
    track.addEventListener('focusout', resume);
    track.addEventListener('touchstart', pause, { passive: true });
    track.addEventListener('touchend', function () { setTimeout(resume, 4000); }, { passive: true });
    setInterval(function () {
      if (!paused && !document.hidden && track.scrollWidth > track.clientWidth) slideTrack(track, 1);
    }, parseInt(track.getAttribute('data-kt-auto'), 10) || 4000);
  });

  /* ---------- Product detail gallery: keep the active thumbnail in view ---------- */
  var pdCar = qs('#ktPdCar');
  var pdThumbs = qs('#ktPdThumbs');
  if (pdCar && pdThumbs) {
    pdCar.addEventListener('slid.bs.carousel', function () {
      var on = qs('.active', pdThumbs);
      if (!on) return;
      var left = on.offsetLeft - pdThumbs.offsetLeft;
      if (left < pdThumbs.scrollLeft || left + on.offsetWidth > pdThumbs.scrollLeft + pdThumbs.clientWidth) {
        pdThumbs.scrollTo({ left: left, behavior: 'smooth' });
      }
    });
  }

  /* ---------- Product image inner zoom: <a href="big.jpg" data-kt-zoom><img></a> ----------
     Mouse: the big image (loaded on first hover, at least 2x) pans with the pointer
     through --mz-s / --mz-tx / --mz-ty. Click / tap opens the lightbox. */
  var zoomLinks = qsa('[data-kt-zoom]');
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  zoomLinks.forEach(function (link) {
    var big = null, last = null;
    function pan(x, y) {
      var r = link.getBoundingClientRect();
      var s = big && big.naturalWidth ? Math.max(2, big.naturalWidth / r.width) : 2;
      link.style.setProperty('--mz-s', s);
      link.style.setProperty('--mz-tx', (-(s - 1) * Math.min(Math.max(x - r.left, 0), r.width)) + 'px');
      link.style.setProperty('--mz-ty', (-(s - 1) * Math.min(Math.max(y - r.top, 0), r.height)) + 'px');
    }
    function track(e) { last = { x: e.clientX, y: e.clientY }; pan(last.x, last.y); }
    if (finePointer) {
      link.addEventListener('mouseenter', function (e) {
        if (!big) {
          big = new Image();
          big.className = 'kt-mz-big';
          big.alt = '';
          big.setAttribute('aria-hidden', 'true');
          big.addEventListener('load', function () {
            link.classList.add('is-mz-ready');
            if (last) pan(last.x, last.y);
          });
          big.src = link.getAttribute('href');
          link.appendChild(big);
        }
        track(e);
        link.classList.add('is-mz');
      });
      link.addEventListener('mousemove', track);
      link.addEventListener('mouseleave', function () { link.classList.remove('is-mz'); });
    }
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var lb = qs('#ktLightbox');
      if (lb) modal(lb).show(link);
    });
  });
  if (pdCar) {
    pdCar.addEventListener('slide.bs.carousel', function () {
      zoomLinks.forEach(function (l) { l.classList.remove('is-mz'); });
    });
  }

  /* ---------- Product cards: add to cart [data-kt-add] / wishlist [data-kt-wish] ----------
     The card carries data-product-id; data-kt-options="1" (colours / sizes to choose)
     opens the product page instead of adding. Product page forms: form[data-kt-cart]. */
  function showWish(btn, on) {
    btn.classList.toggle('is-active', on);
    btn.setAttribute('aria-pressed', String(on));
    var icon = qs('i', btn);
    if (icon) { icon.classList.toggle('bi-heart', !on); icon.classList.toggle('bi-heart-fill', on); }
  }
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-kt-add]');
    if (add) {
      e.preventDefault();
      var card = add.closest('[data-product-id]');
      if (!card) return;
      if (card.getAttribute('data-kt-options') === '1') { location.href = card.getAttribute('data-url'); return; }
      add.disabled = true;
      var size = qs('[data-kt-size]', card);
      post(KT.cartAdd, { product_id: card.getAttribute('data-product-id'), size_id: size ? size.value : 0, qty: 1 }).then(function (r) {
        add.disabled = false;
        if (r.ok) setCount('.js-cart-count', r.count);
        toast(r.ok ? (card.getAttribute('data-name') || 'Item') + ' added to cart' : r.message);
      }, function () { add.disabled = false; toast('The cart could not be updated. Please try again.'); });
      return;
    }
    var wish = e.target.closest('[data-kt-wish]');
    if (wish) {
      e.preventDefault();
      var holder = wish.closest('[data-product-id]');
      if (!holder) return;
      var adding = !wish.classList.contains('is-active');
      post(KT.wishlist, { action: adding ? 'add' : 'remove', product_id: holder.getAttribute('data-product-id'), ref: location.pathname.slice(new URL(KT.base, location.href).pathname.replace(/\/?$/, '/').length) }).then(function (r) {
        if (r.login) { location.href = r.redirect; return; }
        if (r.ok) {
          showWish(wish, adding);
          if (r.count !== null) setCount('.js-wish-count', r.count);
          if (!adding && wish.hasAttribute('data-kt-wish-remove')) { var row = wish.closest('[data-kt-wish-item]'); if (row) row.remove(); }
        }
        toast(r.message);
      }, function () { toast('Your wishlist could not be updated. Please try again.'); });
    }
  });
  qsa('form[data-kt-cart]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var btn = e.submitter;
      if (btn && btn.name === 'buy_now') return;
      e.preventDefault();
      var data = {};
      new FormData(form).forEach(function (v, k) { data[k] = v; });
      post(form.action, data).then(function (r) {
        if (r.ok) setCount('.js-cart-count', r.count);
        if (r.redirect) { location.href = r.redirect; return; }
        toast(r.message);
      }, function () { form.submit(); });
    });
  });

  /* ---------- Share icons a[data-kt-page-share] (detail, news-detail) ----------
     Share this page with the product name / news headline; static hrefs are the fallback. */
  var pageShare = qsa('a[data-kt-page-share]');
  if (pageShare.length && location.protocol.indexOf('http') === 0) {
    var titleEl = qs('.kt-pd-name, .kt-nd-title');
    var pu = encodeURIComponent(location.href.replace(/#.*$/, ''));
    var pt = encodeURIComponent((titleEl ? titleEl.textContent.trim() : document.title) + ' | CustomCart');
    var shareHref = {
      facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + pu,
      linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + pu,
      x: 'https://twitter.com/intent/tweet?text=' + pt + '&url=' + pu,
      whatsapp: 'https://wa.me/?text=' + pt + '%20' + pu
    };
    pageShare.forEach(function (a) { a.href = shareHref[a.getAttribute('data-kt-page-share')]; });
  }

  /* ---------- Iframe popups ----------
     #ktInquiry: send-enquiry.htm (?product= from the clicked card).
     #ktInvoiceModal: print-invoice.htm.
     Shared popup (built on first use): a[data-kt-refer] (footer, refer-a-friend.htm) and
     [data-kt-pop="page.htm"] (cancel-item, return-item, track-order); the button's
     data-kt-order / -info / -name / -var / -qty / -price / -img go in the query string.
     Pages inside answer with postMessage: height, close, sent (msg). */
  function frameUrl(page, data) {
    var p = new URLSearchParams();
    Object.keys(data).forEach(function (k) { if (data[k]) p.set(k, data[k]); });
    var q = p.toString();
    return page + (q ? (page.indexOf('?') < 0 ? '?' : '&') + q : '');
  }

  var inquiry = qs('#ktInquiry');
  var inqFrame = inquiry ? qs('iframe', inquiry) : null;
  if (inqFrame) {
    inquiry.addEventListener('show.bs.modal', function (e) {
      var card = e.relatedTarget ? e.relatedTarget.closest('[data-name]') : null;
      inqFrame.src = frameUrl(inqFrame.getAttribute('data-src'), { product: card ? card.getAttribute('data-product-id') : '' });
    });
  }

  var popModal = null, popFrame = null;
  var popKeys = ['order', 'info', 'name', 'var', 'qty', 'price', 'img'];
  qsa('a[data-kt-refer], [data-kt-pop]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.bootstrap) return;
      e.preventDefault();
      if (!popModal) {
        popModal = document.createElement('div');
        popModal.className = 'modal fade kt-modal kt-modal-pop';
        popModal.tabIndex = -1;
        popModal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content overflow-hidden">' +
          '<iframe class="kt-modal-frame kt-modal-frame-pop"></iframe></div></div>';
        document.body.appendChild(popModal);
        popFrame = qs('iframe', popModal);
        popModal.addEventListener('hidden.bs.modal', function () { popFrame.src = 'about:blank'; });
      }
      var data = {};
      popKeys.forEach(function (k) { data[k] = el.getAttribute('data-kt-' + k); });
      var title = el.textContent.trim();
      popModal.setAttribute('aria-label', title);
      popFrame.title = title;
      popFrame.src = frameUrl(el.getAttribute('data-kt-pop') || el.getAttribute('href'), data);
      modal(popModal).show();
    });
  });

  var invFrame = qs('#ktInvoiceModal iframe');
  window.addEventListener('message', function (e) {
    var d = e.data;
    if (!d || typeof d !== 'object' || !d.kt) return;
    var frame = [inqFrame, invFrame, popFrame].filter(function (f) { return f && f.contentWindow === e.source; })[0];
    if (!frame) return;
    if (d.kt === 'height') {
      /* the invoice popup keeps the design's fixed height (.kt-inv-frame: min(84vh, 980px)) and scrolls inside */
      if (frame !== invFrame) frame.style.height = Math.ceil(d.h) + 'px';
    } else if (d.kt === 'close' || d.kt === 'sent') {
      modal(frame.closest('.modal')).hide();
      if (d.kt === 'sent') toast(d.msg || 'Inquiry sent. Our team will contact you shortly.');
    }
  });

  /* ---------- Gallery lightbox (images-gallery, certificate, product detail) ---------- */
  var lightbox = qs('#ktLightbox');
  if (lightbox) {
    var lbImg = qs('#ktLbImg');
    var lbItems = [], lbIndex = 0;
    var lbShow = function (i) {
      if (!lbItems.length) return;
      lbIndex = (i + lbItems.length) % lbItems.length;
      var img = qs('img', lbItems[lbIndex]);
      var zl = qs('[data-kt-zoom]', lbItems[lbIndex]);
      lbImg.src = zl ? zl.getAttribute('href') : img.getAttribute('src');
      lbImg.alt = img.getAttribute('alt');
      var box = lbImg.parentNode;
      box.classList.remove('kt-lb-anim');
      void box.offsetWidth;
      box.classList.add('kt-lb-anim');
    };
    lightbox.addEventListener('show.bs.modal', function (e) {
      var t = e.relatedTarget;
      if (!t) return;
      /* product detail: page through the carousel images from the current one */
      if (pdCar && (t.hasAttribute('data-kt-pdzoom') || t.hasAttribute('data-kt-zoom'))) {
        lbItems = qsa('.carousel-item', pdCar);
        lbShow(lbItems.indexOf(qs('.carousel-item.active', pdCar)));
        return;
      }
      var item = t.closest('.kt-gal-item');
      if (!item) return;
      lbItems = qsa('.kt-gal-item', item.closest('.tab-pane') || document);
      lbShow(lbItems.indexOf(item));
    });
    qs('#ktLbPrev').addEventListener('click', function () { lbShow(lbIndex - 1); });
    qs('#ktLbNext').addEventListener('click', function () { lbShow(lbIndex + 1); });
    lightbox.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') lbShow(lbIndex - 1);
      if (e.key === 'ArrowRight') lbShow(lbIndex + 1);
    });
  }

  /* ---------- Video lightbox (video-gallery): data-video = YouTube link; stops on close ---------- */
  var vidModal = qs('#ktVideo');
  if (vidModal) {
    var vidFrame = qs('#ktVideoFrame');
    vidModal.addEventListener('show.bs.modal', function (e) {
      var m = /(?:youtu\.be\/|[?&]v=|embed\/|shorts\/)([\w-]{11})/.exec(e.relatedTarget ? e.relatedTarget.getAttribute('data-video') || '' : '');
      if (m) vidFrame.src = 'https://www.youtube.com/embed/' + m[1] + '?autoplay=1&rel=0';
    });
    vidModal.addEventListener('hidden.bs.modal', function () { vidFrame.src = 'about:blank'; });
  }


  /* ---------- Account: show / hide password ---------- */
  qsa('[data-kt-pass]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = qs('input', btn.closest('.kt-pass'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', String(show));
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      qs('i', btn).className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  /* ---------- Account forms: form.kt-validate ----------
     [data-kt-match="#id"] must equal that field; action="#" stays on the page,
     closes its popup and shows data-kt-done. */
  qsa('form.kt-validate').forEach(function (form) {
    var matches = qsa('[data-kt-match]', form);
    function checkMatch() {
      matches.forEach(function (f) {
        var other = qs(f.getAttribute('data-kt-match'));
        f.setCustomValidity(other && f.value && f.value !== other.value ? 'Passwords do not match' : '');
      });
    }
    form.addEventListener('input', checkMatch);
    form.addEventListener('submit', function (e) {
      checkMatch();
      if (!form.checkValidity()) {
        e.preventDefault();
        form.classList.add('was-validated');
        var bad = qs('input:invalid, select:invalid, textarea:invalid', form);
        if (bad) bad.focus();
        return;
      }
      if (form.getAttribute('action') !== '#') return;
      e.preventDefault();
      form.classList.remove('was-validated');
      var box = form.closest('.modal');
      if (box) modal(box).hide();
      toast(form.getAttribute('data-kt-done') || 'Saved successfully');
    });
  });

  /* ---------- Coupons: copy code [data-kt-copy] ---------- */
  qsa('[data-kt-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var code = btn.getAttribute('data-kt-copy');
      function done() {
        btn.classList.add('is-copied');
        toast('Coupon code ' + code + ' copied');
        setTimeout(function () { btn.classList.remove('is-copied'); }, 2000);
      }
      function fallback() {
        var t = document.createElement('textarea');
        t.value = code;
        t.setAttribute('readonly', '');
        t.className = 'kt-copy-tmp';
        document.body.appendChild(t);
        t.select();
        try { document.execCommand('copy'); } catch (err) { /* ignore */ }
        document.body.removeChild(t);
        done();
      }
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(code).then(done, fallback);
      else fallback();
    });
  });

  /* ---------- Order history: status chips name="ktOrderFilter" show matching .kt-ord[data-status] ---------- */
  var ordOrders = qsa('.kt-ord[data-status]');
  var ordEmpty = qs('#ktOrdEmpty');
  qsa('input[name="ktOrderFilter"]').forEach(function (r) {
    r.addEventListener('change', function () {
      var shown = 0;
      ordOrders.forEach(function (o) {
        var on = r.value === 'all' || o.getAttribute('data-status') === r.value;
        o.classList.toggle('d-none', !on);
        if (on) shown++;
      });
      if (ordEmpty) ordEmpty.classList.toggle('d-none', shown > 0);
    });
  });

  /* ---------- Order Summary: delivery option select[data-kt-shipping] ----------
     Posts the chosen method to cart/shipping and swaps in the refreshed summary card
     (no reload: the address typed on the page is kept); [data-kt-grand-total] gets the new total. */
  document.addEventListener('change', function (e) {
    var sel = e.target.closest ? e.target.closest('select[data-kt-shipping]') : null;
    if (!sel) return;
    var card = sel.closest('.kt-sum');
    sel.disabled = true;
    if (card) card.setAttribute('aria-busy', 'true');
    post(sel.getAttribute('data-kt-shipping'), { shipping_id: sel.value, items: sel.getAttribute('data-kt-items') || '0' })
      .then(function (res) {
        /* cart page (data-kt-items="0"): its card also holds the page's buttons, so reload it */
        if (res.ok && sel.getAttribute('data-kt-items') !== '1') { window.location.reload(); return; }
        if (res.html && card) {
          var box = document.createElement('div');
          box.innerHTML = res.html.trim();
          if (box.firstElementChild) card.replaceWith(box.firstElementChild);
        }
        if (res.grand_total) qsa('[data-kt-grand-total]').forEach(function (el) { el.innerHTML = res.grand_total; });
        if (!res.ok && res.message) toast(res.message);
      })
      .catch(function () { window.location.reload(); });
  });

  /* ---------- Address form: Country -> State -> City ----------
     select[data-kt-locations] (the Country) = {country: {state: [cities]}} (Sitepanel > Manage Location);
     [data-kt-state-for="<country id>"] holds the State select; [data-kt-city-for="<state id>"] holds a
     City select and a typed City input (for a state without cities): only the one in use is enabled and sent. */
  qsa('[data-kt-state-for]').forEach(function (stateBox) {
    var countrySel = document.getElementById(stateBox.getAttribute('data-kt-state-for'));
    var stateSel = qs('select[name="state"]', stateBox);
    var cityBox = stateSel && qs('[data-kt-city-for="' + stateSel.id + '"]');
    var citySel = cityBox && qs('select[name="city"]', cityBox);
    var cityText = cityBox && qs('input[name="city"]', cityBox);
    if (!countrySel || !stateSel || !citySel || !cityText) return;
    var tree = {};
    try { tree = JSON.parse(countrySel.getAttribute('data-kt-locations') || '{}'); } catch (e) { tree = {}; }
    var has = function (o, k) { return Object.prototype.hasOwnProperty.call(o, k); };
    function use(el, on) { el.hidden = !on; el.disabled = !on; }
    function fill(sel, first, names) {
      sel.length = 0;
      sel.add(new Option(first, ''));
      names.forEach(function (name) { sel.add(new Option(name, name)); });
    }
    function states() { return has(tree, countrySel.value) ? tree[countrySel.value] : {}; }
    function onState() {
      var all = states(), state = stateSel.value, known = state !== '' && has(all, state);
      var cities = known ? all[state] : [];
      var typed = known && !cities.length;
      fill(citySel, known ? 'Select city' : 'Select state first', cities);
      cityText.value = '';
      use(citySel, !typed);
      use(cityText, typed);
    }
    countrySel.addEventListener('change', function () {
      var names = Object.keys(states());
      fill(stateSel, names.length ? 'Select state / region' : 'Select country first', names);
      onState();
    });
    stateSel.addEventListener('change', onState);
  });

  /* ---------- Account sidebar: highlight the current page ---------- */
  var accPage = (location.pathname.split('/').pop() || '').toLowerCase();
  qsa('.kt-acc-menu .kt-acc-link').forEach(function (a) {
    if ((a.getAttribute('href') || '').split('?')[0].split('/').pop().toLowerCase() !== accPage) return;
    a.classList.add('active');
    a.setAttribute('aria-current', 'page');
  });

  /* ---------- A popup form returned with errors by the server: open it again ---------- */
  qsa('.modal[data-kt-open]').forEach(function (m) { modal(m).show(); });

  /* ---------- Scroll reveal [data-reveal], [data-circ] (anything below the fold) ---------- */
  if ('IntersectionObserver' in window) {
    setTimeout(function () {
      var vh = window.innerHeight;
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          io.unobserve(en.target);
          en.target.classList.remove('kt-pending');
          en.target.classList.add('kt-in');
        });
      }, { threshold: 0.08 });
      var els = qsa('[data-reveal],[data-circ]');
      els.forEach(function (el) {
        if (el.getBoundingClientRect().top < vh) return;
        el.classList.add('kt-pending');
        io.observe(el);
      });
      /* safety net: never leave content hidden */
      setTimeout(function () { els.forEach(function (el) { el.classList.remove('kt-pending'); }); }, 6000);
    }, 300);
  }
})();

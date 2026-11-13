/* CustomCart - popup pages (send-enquiry.htm, refer-a-friend.htm, cancel-item.htm,
   return-item.htm, track-order.htm, print-invoice.htm)
   send-enquiry.htm loads inside the Inquiry modal iframe on every page; the product
   name arrives in the query string (?product=). refer-a-friend.htm loads in a popup
   from the footer link; cancel-item / return-item / track-order load in a popup from
   the order buttons (details in the query string). print-invoice.htm loads inside the
   invoice popup on invoice.htm. Actions are sent to the parent page with
   postMessage: height, close, sent. Opened directly (not in an iframe) both pages
   still work on their own. */
(function () {
  'use strict';

  function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  var params = new URLSearchParams(location.search);
  function param(key) { return params.get(key) || ''; }

  var framed = window.parent !== window;
  function send(type, data) {
    if (!framed) return;
    var msg = { kt: type };
    Object.keys(data || {}).forEach(function (k) { msg[k] = data[k]; });
    window.parent.postMessage(msg, '*');
  }

  /* ---------- Close (button + Escape) ---------- */
  function close() {
    if (framed) send('close');
    else if (history.length > 1) history.back();
    else location.href = document.body.getAttribute('data-kt-home') || '/';
  }
  qsa('[data-kt-close]').forEach(function (b) { b.addEventListener('click', close); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

  /* ---------- Print (print-invoice.htm): prints this page only ----------
     The page uses inline CSS only, so [data-kt-noprint] (toolbar) is hidden here while printing. */
  qsa('[data-kt-print]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });
  /* While printing: hide [data-kt-noprint], and print [data-kt-print-plain] elements
     on plain white (their data-kt-print-plain value is the inline style used for print). */
  var noPrint = qsa('[data-kt-noprint]');
  var plain = qsa('[data-kt-print-plain]');
  if (noPrint.length || plain.length) {
    window.addEventListener('beforeprint', function () {
      noPrint.forEach(function (el) { el.setAttribute('hidden', ''); });
      plain.forEach(function (el) {
        el.setAttribute('data-kt-screen-style', el.getAttribute('style') || '');
        el.setAttribute('style', el.getAttribute('data-kt-print-plain'));
      });
    });
    window.addEventListener('afterprint', function () {
      noPrint.forEach(function (el) { el.removeAttribute('hidden'); });
      plain.forEach(function (el) {
        el.setAttribute('style', el.getAttribute('data-kt-screen-style'));
        el.removeAttribute('data-kt-screen-style');
      });
    });
  }

  /* ---------- Tell the parent how tall the content is ---------- */
  var frame = qs('.kt-frame');
  if (frame) {
    var postHeight = function () { send('height', { h: frame.offsetHeight }); };
    if (window.ResizeObserver) new ResizeObserver(postHeight).observe(frame);
    window.addEventListener('load', postHeight);
    postHeight();
  }

  /* ---------- Refer a Friend (refer-a-friend.htm): share links use the live website address ---------- */
  var shareLinks = qsa('[data-kt-share]');
  if (shareLinks.length && location.protocol.indexOf('http') === 0) {
    var siteUrl = location.href.replace(/[?#].*$/, '').replace(/[^\/]*$/, '');
    var u = encodeURIComponent(siteUrl);
    var m = encodeURIComponent('Check out CustomCart for premium everyday clothing:');
    var shareHref = {
      whatsapp: 'https://wa.me/?text=' + m + '%20' + u,
      email: 'mailto:?subject=' + encodeURIComponent('Have a look at CustomCart') + '&body=' + m + '%20' + u,
      facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + u,
      x: 'https://twitter.com/intent/tweet?text=' + m + '&url=' + u,
      linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + u
    };
    shareLinks.forEach(function (a) { a.href = shareHref[a.getAttribute('data-kt-share')]; });
  }

  /* ---------- Server result: <body data-kt-sent="message"> after a form was saved ---------- */
  var sent = document.body.getAttribute('data-kt-sent');
  if (sent !== null) {
    if (framed) send('sent', { msg: sent });
    else { var done = qs('[data-kt-done-msg]'); if (done) done.hidden = false; }
  }

  /* Forms: required fields are checked before they are sent */
  qsa('form.kt-validate').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) { e.preventDefault(); form.classList.add('was-validated'); }
    });
  });
})();

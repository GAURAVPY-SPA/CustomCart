/* CustomCart - product page (modules/products/Views/product_detail.php): colour / size choice
   updates the price, stock, quantity limit and buttons from #ktPdVariants ("color-size" => data). */
/* A product tab with something to show after a form was sent (thank-you message or errors): open it and scroll to it. */
(function () {
  'use strict';
  var openTab = document.querySelector('[data-kt-open-tab]');
  if (openTab && window.bootstrap) {
    bootstrap.Tab.getOrCreateInstance(openTab).show();
    openTab.scrollIntoView({ block: 'start' });
  }
})();

(function () {
  'use strict';
  var form = document.getElementById('ktPdForm');
  var data = document.getElementById('ktPdVariants');
  if (!form || !data) return;
  var variants = JSON.parse(data.textContent || '{}');
  var qty = document.getElementById('ktPdQty');
  var size = document.getElementById('ktPdSize');
  var colorName = document.getElementById('ktPdColorName');
  var price = document.getElementById('ktPdPrice');
  var old = document.getElementById('ktPdOld');
  var stock = document.getElementById('ktPdStock');
  var buttons = [document.getElementById('ktPdAdd'), document.getElementById('ktPdBuy')];

  function current() {
    var c = form.querySelector('input[name="color_id"]:checked');
    return (c ? c.value : '0') + '-' + (size ? size.value : '0');
  }

  function update() {
    var c = form.querySelector('input[name="color_id"]:checked');
    if (colorName && c) colorName.textContent = c.getAttribute('data-name');
    var v = variants[current()];
    var available = v ? v.available : 0;
    if (v) {
      price.textContent = v.price;
      old.textContent = v.regular;
      old.hidden = !(v.saving > 0);
      qty.max = v.max;
      if (parseInt(qty.value, 10) > v.max) qty.value = v.max;
    }
    stock.querySelector('span').textContent = v ? v.status : 'This option is not available';
    var low = available > 0 && v && v.low; /* low stock: amber with "!" (design: .is-low, cart.htm) */
    stock.classList.toggle('text-danger', available <= 0);
    stock.classList.toggle('is-low', !!low);
    stock.querySelector('i').className = 'bi bi-' + (available <= 0 ? 'x' : (low ? 'exclamation' : 'check')) + '-circle-fill';
    buttons.forEach(function (b) { if (b) b.disabled = available <= 0; });
  }

  form.addEventListener('change', function (e) {
    if (e.target.name === 'color_id' || e.target.name === 'size_id') update();
  });
})();

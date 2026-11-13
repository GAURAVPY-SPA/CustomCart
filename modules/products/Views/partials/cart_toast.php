<?php
/*
| "Added to cart" toast of the store pages (design: details.htm #cartToast),
| shown by shop-catalog.js after an Add to Cart succeeded in the background.
*/
?>
<!-- ============ Added-to-cart toast ============ -->
<div class="toast-container cart-toast-wrap">
<div class="toast cart-toast" id="cartToast" role="status" aria-live="polite" aria-atomic="true">
<div class="toast-body">
<i class="bi bi-bag-check-fill cart-toast-icon" aria-hidden="true"></i>
<span class="cart-toast-text"><strong id="cartToastName">Item</strong> <span id="cartToastMessage">added to your cart.</span></span>
<a href="<?php echo site_url('cart'); ?>" class="cart-toast-link">View Cart</a>
<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
</div>
</div>

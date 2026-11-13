<?php
/*
| Order confirmation (design: html/order-confirmation.htm), see Cart::confirmation().
| $order, $items, $customer (signed-in customer or NULL), $shop.
*/
$units  = array_sum(array_map('intval', array_column($items, 'quantity')));
$paid   = $order['payment_status'] === 'Paid';
$placed = strtotime((string) $order['order_received_date']);
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Order Confirmation', 'banner' => 'Online Store', 'crumbs' => array('Order Confirmation' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-shop">
<?php echo view('Modules\Cart\Views\partials\steps', array('step' => 4)); ?>
<div class="kt-shop-card kt-oc-hero bg-white border text-center position-relative overflow-hidden">
<div class="kt-ty-ring position-absolute rounded-circle pe-none"></div>
<div class="kt-ty-ring kt-ty-ring2 position-absolute rounded-circle pe-none"></div>
<div class="position-relative">
<div class="kt-ty-check mx-auto position-relative d-flex align-items-center justify-content-center">
<span class="kt-ty-pulse position-absolute rounded-circle pe-none"></span>
<span class="kt-ty-ico rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-check-lg"></i></span>
</div>
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3">ORDER <?php echo $paid ? 'CONFIRMED' : 'RECEIVED'; ?></div>
<h2 class="kt-ty-title fw-bold text-dark">Your Order Has Been Placed Successfully!</h2>
<p class="kt-ty-text kt-text-2 mx-auto">Thank you for shopping with CustomCart, <?php echo html_escape($order['first_name']); ?>. A confirmation with your order details has been sent to <b class="text-dark"><?php echo html_escape($order['email']); ?></b>.<?php echo $paid ? '' : ' We will contact you to complete the payment of this order.'; ?></p>
<div class="kt-oc-info row row-cols-2 row-cols-md-3 g-0 text-start">
<div class="col"><div class="kt-oc-cell"><span class="kt-oc-label d-block">Order ID</span><b class="kt-oc-val text-dark">#<?php echo html_escape($order['invoice_number']); ?></b></div></div>
<div class="col"><div class="kt-oc-cell"><span class="kt-oc-label d-block">Order Date</span><b class="kt-oc-val text-dark"><?php echo date('j M Y', $placed); ?></b></div></div>
<div class="col"><div class="kt-oc-cell"><span class="kt-oc-label d-block">Payment Method</span><b class="kt-oc-val text-dark"><?php echo html_escape(payment_method_label((string) $order['payment_method'])); ?></b></div></div>
<div class="col"><div class="kt-oc-cell"><span class="kt-oc-label d-block">Payment Status</span><span class="kt-status <?php echo $paid ? 'is-paid' : 'is-pending'; ?> rounded-pill fw-bold"><i class="bi bi-<?php echo $paid ? 'check-circle-fill' : 'hourglass-split'; ?>"></i> <?php echo $paid ? 'Paid' : 'Pending'; ?></span></div></div>
<div class="col"><div class="kt-oc-cell"><span class="kt-oc-label d-block">Order Status</span><span class="kt-status is-confirmed rounded-pill fw-bold"><i class="bi bi-box-seam"></i> <?php echo html_escape(\Modules\Members\Controllers\Members::STATUS_LABELS[$order['order_status']] ?? $order['order_status']); ?></span></div></div>
<div class="col"><div class="kt-oc-cell"><span class="kt-oc-label d-block">Estimated Delivery</span><b class="kt-oc-val text-dark"><?php echo html_escape(expected_delivery($order)); ?></b></div></div>
</div>
<?php /* VIEW INVOICE for everyone (design: order-confirmation.htm): signed-in customers get the invoice in My Account, guests the printable invoice of this order. */ ?>
<a href="<?php echo html_escape(order_url($customer ? 'acc-order-invoice' : 'order-invoice', $order)); ?>" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">VIEW INVOICE <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-receipt"></i></span></a>
<?php /* TRACK ORDER hidden:
<a href="<?php echo site_url('track-order'); ?>?order=<?php echo rawurlencode($order['invoice_number']); ?>" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">TRACK ORDER <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-truck"></i></span></a>
*/ ?>
</div>
</div>
<div class="row kt-g-shop">
<div class="col-12 col-xl-8">
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Ordered Products <span class="kt-shop-count"><?php echo $units; ?> item<?php echo $units === 1 ? '' : 's'; ?></span></h2>
</div>
<div class="kt-oc-table-wrap">
<table class="table kt-oc-table align-middle mb-0">
<thead>
<tr><th scope="col">Product</th><th scope="col" class="text-center">Qty</th><th scope="col" class="text-end">Price</th><th scope="col" class="text-end">Item Total</th></tr>
</thead>
<tbody>
<?php foreach ($items as $item): $variant = implode(' / ', array_filter(array($item['product_color'], $item['product_size']))); ?>
<tr>
<td data-label="Product"><div class="d-flex align-items-center kt-gap-12"><span class="kt-oc-img rounded-3 overflow-hidden bg-primary-subtle flex-shrink-0"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($item['product_image'], 160, 160)); ?>" alt="<?php echo html_escape($item['product_name']); ?>" class="w-100 h-100 object-fit-cover"></span><span class="kt-min-0"><b class="d-block text-dark"><?php echo html_escape($item['product_name']); ?></b><span class="kt-os-var text-body-secondary">SKU: <?php echo html_escape($item['product_code']); ?><?php echo $variant !== '' ? ' &middot; '.html_escape($variant) : ''; ?></span></span></div></td>
<td data-label="Qty" class="text-center"><?php echo (int) $item['quantity']; ?></td>
<td data-label="Price" class="text-end"><?php echo display_price($item['product_price']); ?></td>
<td data-label="Item Total" class="text-end fw-bold text-dark"><?php echo display_price((float) $item['product_price'] * (int) $item['quantity']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<div class="col-12 col-xl-4">
<aside class="kt-shop-side d-flex flex-column">
<div class="kt-shop-card bg-white border">
<div class="kt-sum-title fw-bold text-dark d-flex align-items-center gap-2"><i class="bi bi-geo-alt text-primary"></i> Delivery Address</div>
<b class="kt-addr-name d-block text-dark"><?php echo html_escape($order['shipping_name']); ?></b>
<div class="kt-addr-phone text-body-secondary"><i class="bi bi-telephone"></i> <?php echo html_escape($order['shipping_mobile']); ?></div>
<p class="kt-addr-text mb-0"><?php echo html_escape($order['shipping_address']); ?><?php echo (string) ($order['shipping_landmark'] ?? '') !== '' ? ', '.html_escape($order['shipping_landmark']) : ''; ?><br><?php echo html_escape(trim($order['shipping_city'].', '.$order['shipping_state'].' '.$order['shipping_zipcode'], ', ')); ?><br><?php echo html_escape($order['shipping_country']); ?></p>
</div>
<?php echo view('Modules\Cart\Views\partials\order_totals', array('order' => $order, 'items' => $items)); ?>
</aside>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>

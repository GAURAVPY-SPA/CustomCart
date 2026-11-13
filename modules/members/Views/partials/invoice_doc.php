<?php
/*
| Invoice document (design: the article.kt-inv of invoice.htm / print-invoice.htm).
| $order, $items, $settings (SiteSettings).
*/
$paid    = $order['payment_status'] === 'Paid';
$date    = date('j M Y', strtotime((string) $order['order_received_date']));
$percent = rtrim(rtrim(number_format((float) $order['vat_applied_cent'], 2), '0'), '.');
$address = trim((string) $settings->get('contact_address'));
$phone   = trim((string) $settings->get('contact_phone'));
$email   = trim((string) $settings->get('contact_email'));
$license = trim((string) $settings->get('company_license'));
$line    = implode(', ', array_filter(array($order['shipping_address'], $order['shipping_landmark'] ?? '', $order['shipping_city'], trim($order['shipping_state'].' '.$order['shipping_zipcode']), $order['shipping_country'])));
?>
<article class="kt-inv bg-white border" id="ktInvoice">
<header class="kt-inv-head d-flex flex-wrap align-items-start justify-content-between">
<div class="kt-inv-co d-flex align-items-start">
<img src="<?php echo theme_url(); ?>images/customcart-logo.png" alt="CustomCart logo" class="kt-inv-logo flex-shrink-0">
<div>
<div class="kt-inv-co-name fw-bold">CustomCart</div>
<div class="kt-inv-co-text"><?php echo nl2br(html_escape(str_replace('\n', "\n", $address))); ?><br><?php echo $phone !== '' ? 'Phone: '.html_escape($phone) : ''; ?><?php echo $phone !== '' && $email !== '' ? ' &nbsp;|&nbsp; ' : ''; ?><?php echo $email !== '' ? 'Email: '.html_escape($email) : ''; ?><?php echo $license !== '' ? '<br>'.html_escape($license) : ''; ?></div>
</div>
</div>
<div class="kt-inv-titlebox">
<h2 class="kt-inv-title fw-bold mb-1">INVOICE</h2>
<div class="kt-inv-no"><?php echo html_escape($order['invoice_number']); ?></div>
<span class="kt-status <?php echo $paid ? 'is-paid' : 'is-pending'; ?> rounded-pill fw-bold"><i class="bi bi-<?php echo $paid ? 'check-circle-fill' : 'hourglass-split'; ?>"></i> <?php echo $paid ? 'PAID' : 'UNPAID'; ?></span>
</div>
</header>
<div class="kt-inv-meta row row-cols-2 row-cols-md-3 g-0">
<div class="col"><div class="kt-inv-cell"><span class="kt-inv-label d-block">Invoice Number</span><b><?php echo html_escape($order['invoice_number']); ?></b></div></div>
<div class="col"><div class="kt-inv-cell"><span class="kt-inv-label d-block">Invoice Date</span><b><?php echo $date; ?></b></div></div>
<div class="col"><div class="kt-inv-cell"><span class="kt-inv-label d-block">Order Status</span><b><?php echo html_escape(\Modules\Members\Controllers\Members::STATUS_LABELS[$order['order_status']] ?? $order['order_status']); ?></b></div></div>
<div class="col"><div class="kt-inv-cell"><span class="kt-inv-label d-block">Order Date</span><b><?php echo $date; ?></b></div></div>
<div class="col"><div class="kt-inv-cell"><span class="kt-inv-label d-block">Payment Method</span><b><?php echo html_escape(payment_method_label((string) $order['payment_method'])); ?></b></div></div>
<div class="col"><div class="kt-inv-cell"><span class="kt-inv-label d-block">Payment Status</span><b class="<?php echo $paid ? 'kt-inv-paid' : ''; ?>"><?php echo $paid ? 'Paid' : 'Unpaid'; ?></b></div></div>
</div>
<div class="kt-inv-parties row g-0">
<div class="col-12">
<div class="kt-inv-party">
<div class="kt-inv-party-title fw-bold">DELIVERY ADDRESS</div>
<b class="d-block"><?php echo html_escape($order['shipping_name']); ?></b>
<span class="d-block">Mobile: <?php echo html_escape($order['shipping_mobile']); ?></span>
<span class="d-block">Email: <?php echo html_escape($order['email']); ?></span>
<span class="d-block"><?php echo html_escape($line); ?></span>
</div>
</div>
</div>
<div class="kt-inv-table-wrap">
<table class="table kt-inv-table mb-0">
<thead>
<tr><th scope="col" class="text-center">S.No.</th><th scope="col">Product</th><th scope="col">SKU</th><th scope="col" class="text-center">Qty</th><th scope="col" class="text-end">Unit Price</th><th scope="col" class="text-end">Amount</th></tr>
</thead>
<tbody>
<?php foreach ($items as $n => $item): $variant = implode(' / ', array_filter(array($item['product_color'], $item['product_size']))); ?>
<tr>
<td class="text-center"><?php echo $n + 1; ?></td>
<td><b><?php echo html_escape($item['product_name']); ?></b><?php if ($variant !== ''): ?><span class="kt-inv-var d-block"><?php echo html_escape($variant); ?></span><?php endif; ?></td>
<td><?php echo html_escape($item['product_code']); ?></td>
<td class="text-center"><?php echo (int) $item['quantity']; ?></td>
<td class="text-end"><?php echo display_price($item['product_price']); ?></td>
<td class="text-end"><?php echo display_price((float) $item['product_price'] * (int) $item['quantity']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="kt-inv-bottom row g-0">
<div class="col-12 col-md-6">
<div class="kt-inv-note">
<div class="kt-inv-party-title fw-bold">PAYMENT</div>
<span class="d-block"><?php echo $paid ? 'Paid via '.html_escape(payment_method_label((string) $order['payment_method'])).($order['payment_date'] ? ' on '.date('j M Y', strtotime((string) $order['payment_date'])) : '') : 'Payment pending'; ?></span>
<div class="kt-inv-party-title kt-inv-note-gap fw-bold">NOTE</div>
<span class="d-block">Prices are in <?php echo html_escape($order['currency_code'] ?: 'SAR'); ?>.<?php echo (float) $order['vat_amount'] > 0 ? ' VAT is charged at '.$percent.'% on the order value after discounts.' : ''; ?></span>
</div>
</div>
<div class="col-12 col-md-6">
<table class="kt-inv-sum w-100">
<tbody>
<tr><th scope="row">Subtotal</th><td><?php echo display_price($order['total_amount']); ?></td></tr>
<?php if ((float) $order['coupon_discount_amount'] > 0): ?><tr><th scope="row">Coupon Discount (<?php echo html_escape($order['coupon_code']); ?>)</th><td>- <?php echo display_price($order['coupon_discount_amount']); ?></td></tr><?php endif; ?>
<tr><th scope="row">Delivery Charges</th><td><?php echo display_price($order['shipping_amount']); ?></td></tr>
<?php if ((float) $order['vat_amount'] > 0): ?><tr><th scope="row">VAT (<?php echo $percent; ?>%)</th><td><?php echo display_price($order['vat_amount']); ?></td></tr><?php endif; ?>
<tr class="kt-inv-grand"><th scope="row">Grand Total</th><td><?php echo display_price(\Modules\Cart\Models\OrderModel::payable($order)); ?></td></tr>
</tbody>
</table>
</div>
</div>
<footer class="kt-inv-foot text-center">Thank you for shopping with CustomCart. This is a computer-generated invoice and does not require a signature.</footer>
</article>

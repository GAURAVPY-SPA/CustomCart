<?php
/*
| Printable invoice of an order ($res, $items, $shop = Config\Shop): a page
| of its own, without the sitepanel layout (opened in a new tab from the
| order list and page). Customer input is always escaped.
*/
use Modules\Cart\Models\OrderModel;

$payable  = OrderModel::payable($res);
$settings = service('siteSettings');
$address  = array_filter(array_map('trim', preg_split('/\R|\\\\n/', (string) $settings->get('contact_address'))));
// An uploaded logo (Admin Settings) is under uploads/; otherwise the theme's logo.
$logo     = str_starts_with((string) $site_logo, 'uploads/') ? base_url($site_logo) : theme_url().'images/customcart-logo.png';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Invoice <?php echo html_escape($res['invoice_number']); ?> - <?php echo html_escape($site_name); ?></title>
<style>
	*{box-sizing:border-box}body{margin:0;padding:24px;background:#f4f4f4;font:14px/1.5 Arial,Helvetica,sans-serif;color:#242424}
	.sheet{max-width:820px;margin:0 auto;background:#fff;padding:32px;border-radius:8px}
	.head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;border-bottom:2px solid #d71920;padding-bottom:16px;margin-bottom:20px}
	.head img{max-height:64px;max-width:220px}.head h1{margin:0 0 4px;font-size:24px;color:#d71920}.muted{color:#6b6b6b}
	.cols{display:flex;gap:24px;flex-wrap:wrap;margin-bottom:20px}.cols>div{flex:1 1 220px}.cols h2{font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin:0 0 6px;color:#6b6b6b}
	table{width:100%;border-collapse:collapse}th,td{padding:8px 10px;border-bottom:1px solid #e6e6e6;text-align:left;vertical-align:top}th{background:#faf6f5;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
	.num{text-align:right;white-space:nowrap}.totals td{border:0;padding:4px 10px}.totals .grand td{font-weight:bold;font-size:16px;border-top:2px solid #242424;padding-top:8px}
	.badge{display:inline-block;padding:2px 8px;border-radius:999px;background:#eee;font-size:12px}.badge.ok{background:#e3f4e8;color:#1b6b34}
	.actions{text-align:center;margin:0 auto 16px;max-width:820px}.actions button{padding:8px 18px;border:0;border-radius:999px;background:#d71920;color:#fff;font-weight:bold;cursor:pointer}
	@media print{body{background:#fff;padding:0}.sheet{padding:0;border-radius:0}.actions{display:none}}
</style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">Print invoice</button></div>
<main class="sheet">
	<div class="head">
		<div>
			<img src="<?php echo html_escape($logo); ?>" alt="<?php echo html_escape($site_name); ?>">
			<p class="muted" style="margin:8px 0 0"><?php echo html_escape($site_name); ?><?php foreach ($address as $line): ?><br><?php echo html_escape($line); ?><?php endforeach; ?>
			<?php if (trim((string) $settings->get('contact_email')) !== ''): ?><br><?php echo html_escape($settings->get('contact_email')); ?><?php endif; ?>
			<?php if (trim((string) $settings->get('contact_phone')) !== ''): ?><br><?php echo html_escape($settings->get('contact_phone')); ?><?php endif; ?></p>
		</div>
		<div style="text-align:right">
			<h1>Invoice</h1>
			<strong><?php echo html_escape($res['invoice_number']); ?></strong><br>
			<span class="muted"><?php echo html_escape(date('d M Y', strtotime($res['order_received_date']))); ?></span><br>
			<span class="badge<?php echo $res['payment_status'] === 'Paid' ? ' ok' : ''; ?>"><?php echo html_escape($res['payment_status']); ?></span>
			<span class="badge"><?php echo html_escape($res['order_status']); ?></span>
		</div>
	</div>

	<div class="cols">
		<div>
			<h2>Bill to</h2>
			<?php echo html_escape(trim($res['first_name'].' '.$res['last_name'])); ?><br>
			<?php echo html_escape($res['email']); ?><br>
			<?php echo html_escape($res['mobile_number']); ?>
		</div>
		<div>
			<h2>Deliver to</h2>
			<?php echo html_escape($res['shipping_name']); ?><br>
			<?php echo html_escape($res['shipping_address']); ?><br>
			<?php echo html_escape(trim(implode(', ', array_filter(array($res['shipping_city'], $res['shipping_state'], $res['shipping_zipcode']))))); ?><br>
			<?php echo html_escape($res['shipping_country']); ?> &middot; <?php echo html_escape($res['shipping_mobile']); ?>
		</div>
		<div>
			<h2>Payment &amp; delivery</h2>
			<?php echo html_escape(payment_method_label((string) $res['payment_method'])); ?><br>
			<?php echo html_escape($res['shipping_method']); ?>
			<?php if ((string) $res['bill_number'] !== ''): ?><br><?php echo html_escape((string) $res['courier_company_name']); ?> &middot; <?php echo html_escape($res['bill_number']); ?><?php endif; ?>
		</div>
	</div>

	<?php if (is_array($items)): ?>
	<table>
		<thead><tr><th>Product</th><th class="num">Price</th><th class="num">Qty</th><th class="num">Total</th></tr></thead>
		<tbody>
			<?php foreach ($items as $item): ?>
			<tr>
				<td>
					<?php echo html_escape($item['product_name']); ?>
					<?php if (in_array($item['product_order_status'], OrderModel::LINE_ENDED, TRUE)): ?> <em class="muted">(<?php echo html_escape($item['product_order_status']); ?>)</em><?php endif; ?>
					<br><span class="muted"><?php echo html_escape($item['product_code']); ?><?php
						$variant = array_filter(array((string) $item['product_color'] !== '' ? 'Colour: '.$item['product_color'] : '', (string) $item['product_size'] !== '' ? 'Size: '.$item['product_size'] : ''));
						echo $variant ? ' &middot; '.html_escape(implode(', ', $variant)) : ''; ?></span>
				</td>
				<td class="num"><?php echo display_price($item['product_price']); ?></td>
				<td class="num"><?php echo (int) $item['quantity']; ?></td>
				<td class="num"><?php echo display_price((float) $item['product_price'] * (int) $item['quantity']); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else: ?>
	<p>The items could not be loaded. Please reload the page.</p>
	<?php endif; ?>

	<table class="totals" style="max-width:360px;margin:12px 0 0 auto">
		<tr><td>Subtotal</td><td class="num"><?php echo display_price($res['total_amount']); ?></td></tr>
		<?php if ((float) $res['coupon_discount_amount'] > 0): ?>
		<tr><td>Coupon<?php echo (string) $res['coupon_code'] !== '' ? ' ('.html_escape($res['coupon_code']).')' : ''; ?></td><td class="num">&minus;<?php echo display_price($res['coupon_discount_amount']); ?></td></tr>
		<?php endif; ?>
		<tr><td>Delivery</td><td class="num"><?php echo (float) $res['shipping_amount'] > 0 ? display_price($res['shipping_amount']) : 'Free'; ?></td></tr>
		<tr class="grand"><td>Grand total</td><td class="num"><?php echo display_price($payable); ?></td></tr>
		<?php if ((float) $res['vat_amount'] > 0): ?>
		<tr><td class="muted">Includes GST (<?php echo rtrim(rtrim(number_format((float) $res['vat_applied_cent'], 2), '0'), '.'); ?>%)</td><td class="num muted"><?php echo display_price($res['vat_amount']); ?></td></tr>
		<?php endif; ?>
	</table>

	<?php if ($res['payment_status'] !== 'Paid' && $res['payment_method'] === 'paynow' && ($shop->paynowUen !== '' || $shop->bankDetails !== '')): ?>
	<p style="margin-top:20px"><strong>How to pay:</strong> PayNow or bank transfer, quoting <?php echo html_escape($res['invoice_number']); ?> as the reference.
		<?php if ($shop->paynowUen !== ''): ?><br>PayNow UEN: <?php echo html_escape($shop->paynowUen); ?><?php endif; ?>
		<?php if ($shop->bankDetails !== ''): ?><br><?php echo nl2br(html_escape($shop->bankDetails)); ?><?php endif; ?></p>
	<?php endif; ?>
	<p class="muted" style="margin-top:24px">Thank you for supporting <?php echo html_escape($site_name); ?>. Prices include GST.</p>
</main>
</body>
</html>

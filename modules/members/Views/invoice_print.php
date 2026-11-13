<?php
/*
| Printable invoice (design: html/print-invoice.htm): a page of its own with
| inline CSS only, opened in the PRINT INVOICE popup (#ktInvoiceModal) of the
| invoice pages, or on its own. Members::invoice_print() and Cart::invoice()
| (?print=1). $order, $items, $settings (SiteSettings).
| customcart-popup.js: PRINT / DOWNLOAD [data-kt-print], close [data-kt-close];
| while printing it hides [data-kt-noprint] and swaps in [data-kt-print-plain].
*/
$paid     = $order['payment_status'] === 'Paid';
$date     = date('j M Y', strtotime((string) $order['order_received_date']));
$percent  = rtrim(rtrim(number_format((float) $order['vat_applied_cent'], 2), '0'), '.');
$address  = trim((string) $settings->get('contact_address'));
$phone    = trim((string) $settings->get('contact_phone'));
$email    = trim((string) $settings->get('contact_email'));
$license  = trim((string) $settings->get('company_license'));
$line     = implode(', ', array_filter(array($order['shipping_address'], $order['shipping_landmark'] ?? '', $order['shipping_city'], trim($order['shipping_state'].' '.$order['shipping_zipcode']), $order['shipping_country'])));
$invoice  = html_escape($order['invoice_number']);
$status   = \Modules\Members\Controllers\Members::STATUS_LABELS[$order['order_status']] ?? $order['order_status'];
$cell     = 'padding:10px 14px; border:1px solid #e6ecf2;';
$label    = 'font-size:10px; letter-spacing:1px; text-transform:uppercase; color:#8a919c; margin:0px 0px 3px 0px;';
$value    = 'font-size:13px; color:#0a1f3d;';
$th       = 'padding:9px 8px; background:#0a1f3d; color:#ffffff; font-size:11px; letter-spacing:1px; text-transform:uppercase; white-space:nowrap;';
$heading  = 'font-size:11px; font-weight:bold; letter-spacing:2px; color:#1d5faf;';
$sum_l    = 'padding:6px 10px; color:#555555;';
$sum_r    = 'padding:6px 10px; font-weight:bold; color:#0a1f3d; white-space:nowrap;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<link rel="shortcut icon" href="<?php echo base_url('favicon.ico'); ?>">
<title>Invoice <?php echo $invoice; ?> | CustomCart</title>
</head>
<body style="font-size:14px; color:#333; margin:0px; padding:15px; font-family:Arial, Helvetica, sans-serif; min-width:660px; background:#f7f6f5; -webkit-print-color-adjust:exact; print-color-adjust:exact;" data-kt-print-plain="font-size:14px; color:#333; margin:0px; padding:0px; font-family:Arial, Helvetica, sans-serif; background:#ffffff; -webkit-print-color-adjust:exact; print-color-adjust:exact;" data-kt-home="<?php echo site_url(); ?>">

<!-- toolbar (not printed) -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" data-kt-noprint style="max-width:900px; margin:0px auto 15px auto; background:#ffffff; border:1px solid #e6ecf2; border-radius:8px;">
<tr>
<td style="padding:10px 15px; font-size:15px; font-weight:bold; color:#0a1f3d;">Invoice <span style="color:#1d5faf;"><?php echo $invoice; ?></span></td>
<td align="right" style="padding:10px 15px; white-space:nowrap;">
<button type="button" data-kt-print style="height:36px; padding:0px 18px; border:0px; border-radius:18px; background:#0a1f3d; color:#ffffff; font-size:12px; font-weight:bold; letter-spacing:1px; font-family:Arial, Helvetica, sans-serif; cursor:pointer;">PRINT / DOWNLOAD</button>
<button type="button" data-kt-close aria-label="Close" style="width:36px; height:36px; margin-left:6px; padding:0px; border:1px solid #e6ecf2; border-radius:18px; background:#ffffff; color:#0a1f3d; font-size:20px; line-height:20px; font-family:Arial, Helvetica, sans-serif; cursor:pointer;">&times;</button>
</td>
</tr>
</table>

<!-- INVOICE DOCUMENT -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:900px; margin:0px auto; background:#ffffff; border:1px solid #e6ecf2; border-radius:8px;" data-kt-print-plain="margin:0px; background:#ffffff; border:0px;">
<tr>
<td style="padding:30px;" data-kt-print-plain="padding:0px;">

<!-- header: company + title -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-bottom:3px solid #0a1f3d;">
<tr>
<td valign="top" style="width:120px; padding:0px 15px 20px 0px;"><img src="<?php echo theme_url(); ?>images/customcart-logo.png" alt="CustomCart logo" width="110" style="display:block; width:110px; height:auto; border:0px;"></td>
<td valign="top" style="padding:0px 0px 20px 0px;">
<div style="font-size:18px; font-weight:bold; color:#0a1f3d; margin:0px 0px 4px 0px;">CustomCart</div>
<div style="font-size:12px; line-height:19px; color:#555555;"><?php echo nl2br(html_escape(str_replace('\n', "\n", $address))); ?><?php if ($phone !== '' || $email !== ''): ?><br><?php echo $phone !== '' ? 'Phone: '.html_escape($phone) : ''; ?><?php echo $phone !== '' && $email !== '' ? ' &nbsp;|&nbsp; ' : ''; ?><?php echo $email !== '' ? 'Email: '.html_escape($email) : ''; ?><?php endif; ?><?php echo $license !== '' ? '<br>'.html_escape($license) : ''; ?></div>
</td>
<td valign="top" align="right" style="padding:0px 0px 20px 15px; white-space:nowrap;">
<div style="font-size:32px; line-height:36px; font-weight:bold; letter-spacing:5px; color:#0a1f3d; margin:0px;">INVOICE</div>
<div style="font-size:13px; font-weight:bold; color:#666666; margin:4px 0px 8px 0px;"><?php echo $invoice; ?></div>
<span style="display:inline-block; padding:4px 12px; border-radius:12px; background:<?php echo $paid ? '#e7f5ec' : '#fff4e0'; ?>; color:<?php echo $paid ? '#1e7e46' : '#b26a00'; ?>; font-size:11px; font-weight:bold; letter-spacing:1px;"><?php echo $paid ? 'PAID' : 'UNPAID'; ?></span>
</td>
</tr>
</table>

<!-- invoice details -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0px; border:1px solid #e6ecf2; border-collapse:collapse;">
<tr>
<td width="33%" style="<?php echo $cell; ?>"><div style="<?php echo $label; ?>">Invoice Number</div><b style="<?php echo $value; ?>"><?php echo $invoice; ?></b></td>
<td width="33%" style="<?php echo $cell; ?>"><div style="<?php echo $label; ?>">Invoice Date</div><b style="<?php echo $value; ?>"><?php echo $date; ?></b></td>
<td width="34%" style="<?php echo $cell; ?>"><div style="<?php echo $label; ?>">Order Status</div><b style="<?php echo $value; ?>"><?php echo html_escape($status); ?></b></td>
</tr>
<tr>
<td style="<?php echo $cell; ?>"><div style="<?php echo $label; ?>">Order Date</div><b style="<?php echo $value; ?>"><?php echo $date; ?></b></td>
<td style="<?php echo $cell; ?>"><div style="<?php echo $label; ?>">Payment Method</div><b style="<?php echo $value; ?>"><?php echo html_escape(payment_method_label((string) $order['payment_method'])); ?></b></td>
<td style="<?php echo $cell; ?>"><div style="<?php echo $label; ?>">Payment Status</div><b style="font-size:13px; color:<?php echo $paid ? '#1e7e46' : '#b26a00'; ?>;"><?php echo $paid ? 'Paid' : 'Unpaid'; ?></b></td>
</tr>
</table>

<!-- delivery address -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0px 0px 20px 0px; border:1px solid #e6ecf2; border-collapse:collapse;">
<tr>
<td valign="top" style="padding:14px 16px; border:1px solid #e6ecf2; font-size:13px; line-height:21px;">
<div style="<?php echo $heading; ?> margin:0px 0px 6px 0px;">DELIVERY ADDRESS</div>
<b style="color:#0a1f3d;"><?php echo html_escape($order['shipping_name']); ?></b><br>
Mobile: <?php echo html_escape($order['shipping_mobile']); ?><br>
Email: <?php echo html_escape($order['email']); ?><br>
<?php echo html_escape($line); ?>
</td>
</tr>
</table>

<!-- products -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-size:12px;">
<thead>
<tr>
<th align="center" style="<?php echo $th; ?>">S.No.</th>
<th align="left" style="<?php echo $th; ?>">Product</th>
<th align="left" style="<?php echo $th; ?>">SKU</th>
<th align="center" style="<?php echo $th; ?>">Qty</th>
<th align="right" style="<?php echo $th; ?>">Unit Price</th>
<th align="right" style="<?php echo $th; ?>">Amount</th>
</tr>
</thead>
<tbody>
<?php foreach (array_values($items) as $n => $item):
	$variant = implode(' / ', array_filter(array($item['product_color'], $item['product_size'])));
	$td      = 'padding:10px 8px; border-bottom:1px solid #e6ecf2;'.($n % 2 ? ' background:#f6f8fa;' : '');
?>
<tr>
<td align="center" valign="top" style="<?php echo $td; ?>"><?php echo $n + 1; ?></td>
<td valign="top" style="<?php echo $td; ?>"><b style="color:#0a1f3d;"><?php echo html_escape($item['product_name']); ?></b><?php if ($variant !== ''): ?><br><span style="font-size:11px; color:#8a919c;"><?php echo html_escape($variant); ?></span><?php endif; ?></td>
<td valign="top" style="<?php echo $td; ?>"><?php echo html_escape($item['product_code']); ?></td>
<td align="center" valign="top" style="<?php echo $td; ?>"><?php echo (int) $item['quantity']; ?></td>
<td align="right" valign="top" style="<?php echo $td; ?> white-space:nowrap;"><?php echo display_price($item['product_price']); ?></td>
<td align="right" valign="top" style="<?php echo $td; ?> white-space:nowrap; font-weight:bold; color:#0a1f3d;"><?php echo display_price((float) $item['product_price'] * (int) $item['quantity']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- payment note + totals -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0px 0px 0px;">
<tr>
<td width="50%" valign="top" style="padding:0px 20px 0px 0px; font-size:12px; line-height:20px; color:#555555;">
<div style="<?php echo $heading; ?> margin:0px 0px 4px 0px;">PAYMENT</div>
<?php echo $paid ? 'Paid via '.html_escape(payment_method_label((string) $order['payment_method'])).($order['payment_date'] ? ' on '.date('j M Y', strtotime((string) $order['payment_date'])) : '') : 'Payment pending'; ?>
<div style="<?php echo $heading; ?> margin:14px 0px 4px 0px;">NOTE</div>
Prices are in <?php echo html_escape($order['currency_code'] ?: 'SAR'); ?>.<?php echo (float) $order['vat_amount'] > 0 ? ' VAT is charged at '.$percent.'% on the order value after discounts.' : ''; ?>
</td>
<td width="50%" valign="top" style="padding:0px;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px; border-collapse:collapse;">
<tr><td style="<?php echo $sum_l; ?>">Subtotal</td><td align="right" style="<?php echo $sum_r; ?>"><?php echo display_price($order['total_amount']); ?></td></tr>
<?php if ((float) $order['coupon_discount_amount'] > 0): ?><tr><td style="<?php echo $sum_l; ?>">Coupon Discount (<?php echo html_escape($order['coupon_code']); ?>)</td><td align="right" style="<?php echo $sum_r; ?>">- <?php echo display_price($order['coupon_discount_amount']); ?></td></tr><?php endif; ?>
<tr><td style="<?php echo $sum_l; ?>">Delivery Charges</td><td align="right" style="<?php echo $sum_r; ?>"><?php echo display_price($order['shipping_amount']); ?></td></tr>
<?php if ((float) $order['vat_amount'] > 0): ?><tr><td style="<?php echo $sum_l; ?>">VAT (<?php echo $percent; ?>%)</td><td align="right" style="<?php echo $sum_r; ?>"><?php echo display_price($order['vat_amount']); ?></td></tr><?php endif; ?>
<tr><td style="padding:11px 10px; font-size:16px; font-weight:bold; color:#0a1f3d; background:#fff8e8; border-top:1px solid #f2d9a6; border-bottom:1px solid #f2d9a6; border-left:1px solid #f2d9a6;">Grand Total</td><td align="right" style="padding:11px 10px; font-size:16px; font-weight:bold; color:#0a1f3d; background:#fff8e8; border-top:1px solid #f2d9a6; border-bottom:1px solid #f2d9a6; border-right:1px solid #f2d9a6; white-space:nowrap;"><?php echo display_price(\Modules\Cart\Models\OrderModel::payable($order)); ?></td></tr>
</table>
</td>
</tr>
</table>

<!-- footer -->
<div style="margin:24px 0px 0px 0px; padding:14px 0px 0px 0px; border-top:1px dashed #d5dee8; text-align:center; font-size:11px; color:#8a919c;">Thank you for shopping with CustomCart. This is a computer-generated invoice and does not require a signature.</div>

</td>
</tr>
</table>

<script src="<?php echo asset_url('assets/designer/resources/js/customcart-popup.js'); ?>"></script>
</body>
</html>

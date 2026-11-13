<?php
/*
| Invoice of an order from the order confirmation (design: html/invoice.htm),
| see Cart::invoice(): BACK TO ORDER, PRINT INVOICE (the printable invoice in
| a popup, as the design's #ktInvoiceModal) and the invoice document.
| $order, $items, $settings (SiteSettings), $ref (order reference).
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Invoice', 'crumbs' => array('Order Confirmation' => site_url('order-confirmation').'?order='.$ref, 'Invoice' => NULL))));
?>
<!-- INVOICE (printable: site header, footer and buttons are hidden by the print CSS) -->
<section class="container-xxl kt-container kt-sec kt-inv-page">
<div class="kt-inv-bar kt-no-print d-flex flex-wrap align-items-center justify-content-between">
<a href="<?php echo site_url('order-confirmation'); ?>?order=<?php echo html_escape($ref); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> BACK TO ORDER</a>
<button type="button" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ktInvoiceModal"><i class="bi bi-printer"></i> PRINT INVOICE</button>
</div>
<?php echo view('Modules\Members\Views\partials\invoice_doc', array('order' => $order, 'items' => $items, 'settings' => $settings)); ?>
</section>

<!-- PRINT INVOICE popup: the printable invoice (print-invoice.htm) -->
<div class="modal fade kt-inv-modal" id="ktInvoiceModal" tabindex="-1" aria-label="Invoice" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered">
<div class="modal-content overflow-hidden border-0">
<iframe src="<?php echo site_url('order-invoice'); ?>?order=<?php echo html_escape($ref); ?>&amp;print=1" title="Invoice <?php echo html_escape($order['invoice_number']); ?>" class="kt-inv-frame d-block w-100 border-0" loading="lazy"></iframe>
</div>
</div>
</div>
<?php echo view('bottom_application'); ?>

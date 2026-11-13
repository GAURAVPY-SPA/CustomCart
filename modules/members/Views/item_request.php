<?php
/*
| Cancel Item / Return Item popup (designs: html/cancel-item.htm, return-item.htm),
| see Members::item_request(). $order, $item, $type 'cancel' | 'return', $allowed,
| $error, $reasons (Manage Reasons). After a saved request the page body carries
| data-kt-sent: the parent closes the popup and shows the message.
*/
$return  = $type === 'return';
$variant = implode(' / ', array_filter(array($item['product_color'], $item['product_size'])));
echo view('top_application', array('is_frame' => TRUE, 'body_attrs' => $body_attrs));
?>
<main class="kt-frame kt-pop-page">
<div class="kt-form-modal-head d-flex align-items-center justify-content-between">
<div class="d-flex align-items-center kt-gap-12">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-<?php echo $return ? 'arrow-return-left' : 'x-circle'; ?>"></i></span>
<h1 class="kt-shop-title fw-bold text-dark mb-0"><?php echo $return ? 'Return' : 'Cancel'; ?> Item</h1>
</div>
<button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-kt-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>
<?php echo form_open('item-request?order='.\Modules\Cart\Models\OrderModel::reference($order['invoice_number']), array('class' => 'd-flex flex-column kt-min-0 kt-validate', 'novalidate' => 'novalidate')); ?>
<input type="hidden" name="action" value="<?php echo $type; ?>">
<input type="hidden" name="item_id" value="<?php echo (int) $item['orders_products_id']; ?>">
<div class="kt-form-modal-body">
<p class="kt-shop-text text-body-secondary mb-3">Order <b class="text-dark">#<?php echo html_escape($order['invoice_number']); ?></b> &middot; Placed on <?php echo date('j M Y', strtotime((string) $order['order_received_date'])); ?></p>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo html_escape($error); ?></div><?php endif; ?>
<div class="row kt-g-ctf">
<div class="col-12">
<div class="kt-ret-item d-flex align-items-center">
<span class="kt-oc-img rounded-3 overflow-hidden bg-primary-subtle flex-shrink-0"><img src="<?php echo html_escape(product_image_url($item['product_image'], 160, 160)); ?>" alt="" class="w-100 h-100 object-fit-cover"></span>
<span class="flex-grow-1 kt-min-0"><b class="d-block text-dark"><?php echo html_escape($item['product_name']); ?></b><span class="kt-os-var text-body-secondary"><?php echo $variant !== '' ? html_escape($variant).' &middot; ' : ''; ?>Qty <?php echo (int) $item['quantity']; ?> &middot; <?php echo display_price((float) $item['product_price'] * (int) $item['quantity']); ?></span></span>
</div>
</div>
<?php if ( ! $allowed): ?>
<div class="col-12"><div class="alert alert-info mb-0" role="status">A <?php echo $return ? 'return' : 'cancellation'; ?> request is not available for this item (it may already have been requested).</div></div>
<?php else: ?>
<div class="col-12"><label for="ktReqReason" class="kt-ct-label fw-bold">Reason <span class="text-danger">*</span></label><select id="ktReqReason" name="reason" required class="form-select kt-input kt-select rounded-pill">
<option value="">Select a reason</option>
<?php foreach ($reasons as $reason): ?><option><?php echo html_escape($reason); ?></option><?php endforeach; ?>
</select><div class="invalid-feedback">Please select a reason.</div></div>
<?php if ($return): ?>
<div class="col-12">
<span class="kt-ct-label fw-bold d-block">Request Type <span class="text-danger">*</span></span>
<div class="d-flex flex-wrap kt-gap-14">
<div class="form-check kt-side-check mb-0"><input class="form-check-input" type="radio" name="request_type" id="ktRetRefund" value="Return & Refund" checked><label class="form-check-label" for="ktRetRefund">Return &amp; Refund</label></div>
<div class="form-check kt-side-check mb-0"><input class="form-check-input" type="radio" name="request_type" id="ktRetExchange" value="Exchange"><label class="form-check-label" for="ktRetExchange">Exchange (another size)</label></div>
</div>
</div>
<?php endif; ?>
<div class="col-12"><label for="ktReqNote" class="kt-ct-label fw-bold">Comments <span class="kt-ct-opt">(optional)</span></label><textarea id="ktReqNote" name="comment" rows="2" maxlength="900" placeholder="Tell us more" class="form-control kt-textarea"></textarea></div>
<?php endif; ?>
</div>
<?php if ($return && $allowed): ?>
<div class="kt-ret-note d-flex align-items-start kt-gap-12"><i class="bi bi-info-circle text-primary"></i><span>Our team reviews every request. Once the return is accepted, the refund is credited to your original payment method; you can follow its status in My Account.</span></div>
<?php endif; ?>
</div>
<div class="kt-form-modal-foot d-flex flex-wrap justify-content-end gap-2">
<button type="button" class="btn btn-kt-outline kt-shop-btn rounded-pill" data-kt-close>CLOSE</button>
<?php if ($allowed): ?><button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-check-lg"></i> SUBMIT REQUEST</button><?php endif; ?>
</div>
<?php echo form_close(); ?>
</main>
<?php echo view('bottom_application', array('is_frame' => TRUE)); ?>

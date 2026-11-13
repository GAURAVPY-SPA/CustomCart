<?php
/*
| Checkout address step for a signed-in customer (design: html/checkout.htm),
| see Cart::checkout(): the saved addresses (choose one: action=deliver) and
| Add New Address (popup; action=add, opened again when it has errors).
*/
$chosen_key = $chosen ? implode('|', array($chosen['full_name'], $chosen['address'], $chosen['zipcode'])) : NULL;
$selected   = 0;
foreach ($addresses as $row)
{
	$key = implode('|', array(trim($row['first_name'].' '.$row['last_name']), $row['address'], $row['zipcode']));
	if ($chosen_key === $key) { $selected = (int) $row['address_id']; }
}
$selected = $selected ?: ($addresses ? (int) $addresses[0]['address_id'] : 0);
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Checkout', 'banner' => 'Online Store', 'crumbs' => array('Shopping Cart' => site_url('cart'), 'Checkout' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-shop">
<?php echo view('Modules\Cart\Views\partials\steps', array('step' => 2)); ?>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => $open_form ? array() : $errors)); ?>
<div class="row kt-g-shop">
<div class="col-12 col-xl-8">
<?php echo form_open('checkout'); ?>
<input type="hidden" name="action" value="deliver">
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Delivery Address</h2>
<button type="button" class="btn btn-kt-outline kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ktAddrModal"><i class="bi bi-plus-lg"></i> ADD NEW ADDRESS</button>
</div>
<?php if ($addresses): ?>
<div class="row row-cols-1 kt-g-addr">
<?php foreach ($addresses as $row): $aid = (int) $row['address_id']; ?>
<div class="col">
<div class="kt-addr h-100 bg-white border d-flex flex-column">
<div class="d-flex align-items-start kt-gap-12">
<input type="radio" name="address_id" value="<?php echo $aid; ?>" id="ktAddr<?php echo $aid; ?>" class="form-check-input kt-addr-radio flex-shrink-0"<?php echo $aid === $selected ? ' checked' : ''; ?> aria-label="Deliver to this address">
<div class="flex-grow-1 kt-min-0">
<div class="kt-addr-top d-flex flex-wrap align-items-center gap-2"><b class="kt-addr-name text-dark"><?php echo html_escape(trim($row['first_name'].' '.$row['last_name'])); ?></b><?php if ($row['default_status'] === 'Y'): ?><span class="kt-addr-tag rounded-pill fw-bold">DEFAULT</span><?php endif; ?></div>
<div class="kt-addr-phone text-body-secondary"><i class="bi bi-telephone"></i> <?php echo html_escape($row['mobile']); ?></div>
<p class="kt-addr-text mb-0"><?php echo html_escape($row['address']); ?><?php echo (string) $row['landmark'] !== '' ? ', '.html_escape($row['landmark']) : ''; ?><br><?php echo html_escape(trim($row['city'].', '.$row['state'].' '.$row['zipcode'], ', ')); ?><br><?php echo html_escape($row['country']); ?></p>
</div>
</div>
<div class="kt-addr-foot d-flex flex-wrap align-items-center justify-content-between gap-2 mt-auto">
<div class="d-flex gap-1"><a href="<?php echo site_url('acc-address'); ?>?edit=<?php echo $aid; ?>" class="btn kt-addr-act d-inline-flex align-items-center gap-1"><i class="bi bi-pencil"></i> Edit</a></div>
<label for="ktAddr<?php echo $aid; ?>" class="kt-addr-deliver rounded-pill fw-bold"><span class="kt-addr-on"><i class="bi bi-check-circle-fill"></i> DELIVERING HERE</span><span class="kt-addr-off">DELIVER TO THIS ADDRESS</span></label>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-body-secondary mb-0">You have no saved addresses yet. Please add your delivery address.</p>
<?php endif; ?>
</div>
<div class="kt-shop-nav d-flex flex-wrap align-items-center justify-content-between">
<a href="<?php echo site_url('cart'); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> BACK TO CART</a>
<?php if ($addresses): ?>
<button type="submit" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">PROCEED TO PAYMENT <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></button>
<?php endif; ?>
</div>
<?php echo form_close(); ?>
</div>
<div class="col-12 col-xl-4">
<aside class="kt-shop-side d-flex flex-column">
<?php echo view('Modules\Cart\Views\partials\summary', array('summary' => $summary, 'items' => TRUE, 'choose_shipping' => TRUE)); ?>
</aside>
</div>
</div>
</section>

<div class="modal fade kt-form-modal" id="ktAddrModal" tabindex="-1" aria-labelledby="ktAddrModalTitle" aria-hidden="true"<?php echo $open_form ? ' data-kt-open' : ''; ?>>
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
<div class="modal-content border-0">
<div class="kt-form-modal-head d-flex align-items-center justify-content-between">
<div class="d-flex align-items-center kt-gap-12">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-geo-alt"></i></span>
<h2 class="kt-shop-title fw-bold text-dark mb-0" id="ktAddrModalTitle">Add New Address</h2>
</div>
<button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>
<?php echo form_open('checkout', array('class' => 'd-flex flex-column kt-min-0 kt-validate', 'novalidate' => 'novalidate')); ?>
<input type="hidden" name="action" value="add">
<div class="modal-body kt-form-modal-body">
<?php if ($open_form && $errors): ?><?php echo view('Modules\Products\Views\partials\form_errors', array('form_errors' => $errors)); ?><?php endif; ?>
<?php echo view('Modules\Cart\Views\partials\address_fields', array('values' => $values, 'locations' => $locations, 'shop' => $shop, 'id_prefix' => 'ktAf')); ?>
</div>
<div class="kt-form-modal-foot d-flex flex-wrap justify-content-end gap-2">
<button type="button" class="btn btn-kt-outline kt-shop-btn rounded-pill" data-bs-dismiss="modal">CANCEL</button>
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-check-lg"></i> SAVE &amp; DELIVER HERE</button>
</div>
<?php echo form_close(); ?>
</div>
</div>
</div>
<?php echo view('bottom_application'); ?>

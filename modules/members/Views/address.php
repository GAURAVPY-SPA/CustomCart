<?php
/*
| My Address (design: html/address.htm), see Members::address(): the add / edit
| form first (rules: Cart\Libraries\DeliveryAddress), then "My Address" with
| the saved addresses as in the design's .kt-addr-full block (Edit, Delete,
| Make Default under each). $addresses, $values, $edit, $errors, $locations, $shop.
*/
$labels = array('city' => 'City', 'state' => 'State / Region', 'zipcode' => 'Postal Code', 'country' => 'Country');
echo view('top_application');
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'start', 'customer' => $customer, 'nav_active' => 'address', 'title' => 'My Address'));
?>
<div class="kt-shop-card bg-white border" id="ktAddrForm">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-0"><?php echo $edit ? 'Edit Address' : 'Add New Address'; ?></h2>
<p class="kt-acc-sub text-body-secondary mb-0">Enter the delivery address for your orders.</p>
</div>
<?php if ($edit): ?><a href="<?php echo site_url('acc-address'); ?>" class="kt-shop-link fw-bold">CANCEL</a><?php endif; ?>
</div>
<?php if ($errors): ?><?php echo view('Modules\Products\Views\partials\form_errors', array('form_errors' => $errors)); ?><?php endif; ?>
<?php echo form_open('acc-address', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<input type="hidden" name="action" value="save">
<input type="hidden" name="address_id" value="<?php echo (int) $edit; ?>">
<?php echo view('Modules\Cart\Views\partials\address_fields', array('values' => $values, 'locations' => $locations, 'shop' => $shop, 'id_prefix' => 'ktAd')); ?>
<?php /* Not in the design (the first address becomes the default; "Make Default" is under each saved address):
<div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="make_default" value="1" id="ktAdDefault"><label class="form-check-label" for="ktAdDefault">Make this my default address</label></div>
*/ ?>
<div class="kt-shop-nav d-flex flex-wrap justify-content-end gap-2">
<button type="reset" class="btn btn-kt-outline kt-shop-btn rounded-pill">CLEAR</button>
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-check-lg"></i> SAVE ADDRESS</button>
</div>
<?php echo form_close(); ?>
</div>

<?php if ($addresses): ?>
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-0">My Address</h2>
<p class="kt-acc-sub text-body-secondary mb-0"><?php echo count($addresses) > 1 ? 'Your orders are delivered to your default address.' : 'Your orders are delivered to this address.'; ?></p>
</div>
</div>
<?php foreach (array_values($addresses) as $n => $row): $aid = (int) $row['address_id']; $default = $row['default_status'] === 'Y'; ?>
<div class="kt-addr-full<?php echo $n ? ' mt-3' : ''; ?>">
<div class="kt-addr<?php echo $default ? ' is-default' : ''; ?> bg-white border">
<div class="d-flex align-items-start kt-gap-12">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-geo-alt"></i></span>
<div class="flex-grow-1 kt-min-0">
<div class="kt-addr-top d-flex flex-wrap align-items-center gap-2"><b class="kt-addr-name text-dark"><?php echo html_escape(trim($row['first_name'].' '.$row['last_name'])); ?></b><?php if ($default && count($addresses) > 1): ?><span class="kt-addr-tag rounded-pill fw-bold">DEFAULT</span><?php endif; ?></div>
<div class="kt-addr-phone text-body-secondary"><i class="bi bi-telephone"></i> <?php echo html_escape($row['mobile']); ?></div>
<p class="kt-addr-text mb-0"><?php echo html_escape($row['address']); ?><?php echo (string) $row['landmark'] !== '' ? ', '.html_escape($row['landmark']) : ''; ?></p>
<dl class="kt-addr-dl mb-0">
<?php foreach ($labels as $field => $label): ?>
<div><dt><?php echo $label; ?></dt><dd><?php echo (string) $row[$field] !== '' ? html_escape($row[$field]) : '&ndash;'; ?></dd></div>
<?php endforeach; ?>
</dl>
<div class="kt-addr-foot d-flex flex-wrap align-items-center justify-content-between gap-2">
<div class="d-flex gap-1">
<a href="<?php echo site_url('acc-address'); ?>?edit=<?php echo $aid; ?>#ktAddrForm" class="btn kt-addr-act d-inline-flex align-items-center gap-1"><i class="bi bi-pencil"></i> Edit</a>
<?php echo form_open('acc-address', array('class' => 'd-inline')); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="address_id" value="<?php echo $aid; ?>"><button type="submit" class="btn kt-addr-act is-del d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this address?')"><i class="bi bi-trash3"></i> Delete</button><?php echo form_close(); ?>
</div>
<?php if ( ! $default): ?>
<?php echo form_open('acc-address', array('class' => 'd-inline')); ?><input type="hidden" name="action" value="default"><input type="hidden" name="address_id" value="<?php echo $aid; ?>"><button type="submit" class="btn kt-addr-act d-inline-flex align-items-center gap-1"><i class="bi bi-check2-circle"></i> Make Default</button><?php echo form_close(); ?>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'end'));
echo view('bottom_application');

<?php if (empty($is_frame)): ?>
<?php echo view('project_footer'); ?>
</div>

<div class="modal fade kt-modal kt-modal-inq" id="ktInquiry" tabindex="-1" aria-label="Send inquiry" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content overflow-hidden">
<iframe data-src="<?php echo site_url('send-inquiry'); ?>" title="Send inquiry" class="kt-modal-frame kt-modal-frame-inq" loading="lazy"></iframe>
</div>
</div>
</div>

<div class="toast kt-toast position-fixed text-white bg-dark border-0 rounded-pill align-items-center kt-gap-12" id="ktToast" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="2400">
  <span class="kt-toast-ico rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-check-lg"></i></span><span id="ktToastMsg">Added to cart</span>
</div>
<?php endif; ?>

<?php if ( ! empty($is_frame)): ?>
<script src="<?php echo asset_url('assets/designer/resources/js/customcart-popup.js'); ?>"></script>
<?php else: ?>
<script src="<?php echo asset_url('assets/designer/resources/Scripts/bootstrap.js'); ?>"></script>
<script>window.KT = <?php echo json_encode(array(
	'base'       => site_url(),
	'csrfName'   => csrf_token(),
	'csrfHash'   => csrf_hash(),
	'cartAdd'    => site_url('cart/add'),
	'wishlist'   => site_url('acc-wishlist'),
	'login'      => site_url('login'),
	'inquiry'    => site_url('send-inquiry'),
), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;</script>
<script src="<?php echo asset_url('assets/designer/resources/js/customcart.js'); ?>"></script>
<?php endif; ?>
<?php if ( ! empty($page_scripts)): ?>
<?php foreach ($page_scripts as $page_script): ?>
<script src="<?php echo html_escape($page_script['src']); ?>"<?php echo ! empty($page_script['defer']) ? ' defer' : ''; ?>></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>

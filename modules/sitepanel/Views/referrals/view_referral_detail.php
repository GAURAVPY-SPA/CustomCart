<?php
/* One Refer a Friend message (Referrals::view()). */
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="ref-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="ref-title">Referral</h2>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/referrals'); ?>">Back to list</a>
	</header>
	<dl class="row mb-0">
		<dt class="col-sm-3">From</dt><dd class="col-sm-9"><?php echo html_escape($row['sender_name']); ?> &lt;<?php echo html_escape($row['sender_email']); ?>&gt;<?php echo (int) $row['customers_id'] ? ' <span class="sp-badge">Member #'.(int) $row['customers_id'].'</span>' : ''; ?></dd>
		<dt class="col-sm-3">To friend</dt><dd class="col-sm-9"><?php echo html_escape($row['friend_name']); ?> &lt;<?php echo html_escape($row['friend_email']); ?>&gt;</dd>
		<dt class="col-sm-3">Product</dt><dd class="col-sm-9"><?php echo $row['product_name'] !== NULL ? html_escape($row['product_name']) : '—'; ?></dd>
		<dt class="col-sm-3">Page</dt><dd class="col-sm-9"><?php echo (string) $row['page_url'] !== '' ? html_escape($row['page_url']) : '—'; ?></dd>
		<dt class="col-sm-3">Message</dt><dd class="col-sm-9"><?php echo nl2br(html_escape((string) $row['message'] !== '' ? $row['message'] : '—')); ?></dd>
		<dt class="col-sm-3">Sent on</dt><dd class="col-sm-9"><?php echo html_escape(sp_datetime($row['referral_date'])); ?></dd>
		<dt class="col-sm-3">IP address</dt><dd class="col-sm-9"><?php echo html_escape((string) $row['ip_address']); ?></dd>
	</dl>
	<?php if ($deletePrvg): ?>
	<?php echo form_open('sitepanel/referrals/delete/'.(int) $row['referral_id'], array('class' => 'mt-3', 'onsubmit' => "return confirm('Delete this referral?');")); ?>
		<button class="btn sp-btn-outline text-danger" type="submit">Delete</button>
	<?php echo form_close(); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>

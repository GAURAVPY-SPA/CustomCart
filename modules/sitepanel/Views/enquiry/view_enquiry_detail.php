<?php
/* One enquiry ($res). Every value is visitor input: always escaped. */
echo sp_view('includes/header');
$replied = ($res['reply_status'] === 'Y');
$fields = array(
	'Type'            => isset($types[$res['type']]) ? $types[$res['type']] : $res['type'],
	'Name'            => trim($res['first_name'].' '.$res['last_name']),
	'Email'           => $res['email'],
	'Phone'           => $res['phone_number'],
	'Mobile'          => $res['mobile_number'],
	'Company'         => $res['company_name'],
	'Website'         => $res['website'],
	'Product/service' => $res['product_service'],
	'Manual work / month' => $res['manual_hours'],
	'First-project budget' => $res['budget'],
	'Address'         => $res['address'],
	'City'            => $res['city'],
	'Country'         => $res['country'],
	'Received'        => sp_datetime($res['receive_date']),
);
?>
<section class="sp-panel mb-4" aria-labelledby="enquiry-info">
	<header class="sp-panel-head">
		<h2 class="sp-panel-title" id="enquiry-info">Enquiry #<?php echo (int) $res['id']; ?></h2>
		<?php echo $replied ? '<span class="sp-badge sp-badge--ok">Replied</span>' : '<span class="sp-badge sp-badge--muted">Not replied</span>'; ?>
	</header>
	<dl class="sp-details">
		<?php foreach ($fields as $label => $text): ?>
		<?php if ((string) $text === '') { continue; } ?>
		<div><dt><?php echo $label; ?></dt><dd>
			<?php if ($label === 'Email' && filter_var($text, FILTER_VALIDATE_EMAIL)): ?>
			<a class="sp-link" href="mailto:<?php echo html_escape($text); ?>"><?php echo html_escape($text); ?></a>
			<?php else: ?>
			<?php echo html_escape($text); ?>
			<?php endif; ?>
		</dd></div>
		<?php endforeach; ?>
	</dl>
</section>

<section class="sp-panel mb-4" aria-labelledby="enquiry-message">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="enquiry-message">Message</h2></header>
	<p class="sp-text mb-0"><?php echo html_escape((string) $res['message']); ?></p>
</section>

<div class="sp-form-actions mb-4">
	<?php if ($editPrvg): ?>
	<?php echo form_open($base.'/reply_status/'.(int) $res['id']); ?>
		<input type="hidden" name="replied" value="<?php echo $replied ? 'N' : 'Y'; ?>">
		<input type="hidden" name="from" value="view">
		<button class="btn sp-btn-primary" type="submit"><?php echo $replied ? 'Mark as not replied' : 'Mark as replied'; ?></button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<?php if ($deletePrvg): ?>
	<?php echo form_open($base.'/delete/'.(int) $res['id'], array('data-sp-confirm' => 'Delete this enquiry? This cannot be undone.')); /* form_open() escapes attribute values */ ?>
		<button class="btn sp-btn-outline sp-btn-danger" type="submit">Delete enquiry</button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url($base); ?>">Back to enquiries</a>
</div>
<?php echo sp_view('includes/footer'); ?>

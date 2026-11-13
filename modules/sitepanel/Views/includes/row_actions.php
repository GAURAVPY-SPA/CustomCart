<?php
/*
| Buttons for one list row. Pass everything in $a (CI keeps view variables
| between partial loads, so separate variables could leak to the next row):
|   base, id, page, label (row name for screen readers / confirm),
|   view (bool), edit (bool), delete (bool, default TRUE), active (TRUE/FALSE = status buttons, NULL = none),
|   status_words ([activate, deactivate] button texts),
|   send_mail (array of hidden fields: POST <base>/send_mail/<id> "Send Mail"
|   button, Edit privilege; NULL = none)
| Buttons follow the controller's privileges ($editPrvg, activatePrvg,
| deactivatePrvg, deletePrvg). Status and delete are POST forms
| (CSRF-protected); delete asks to confirm.
*/
$a += array('view' => FALSE, 'edit' => TRUE, 'delete' => TRUE, 'active' => NULL, 'status_words' => array('Activate', 'Deactivate'), 'send_mail' => NULL);
$label = html_escape($a['label']);
?>
<div class="sp-row-actions">
	<?php if ($a['view']): ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url($a['base'].'/view/'.(int) $a['id']); ?>">View<span class="visually-hidden"> <?php echo $label; ?></span></a>
	<?php endif; ?>
	<?php if ($a['edit'] && $editPrvg): ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url($a['base'].'/edit/'.(int) $a['id']); ?>">Edit<span class="visually-hidden"> <?php echo $label; ?></span></a>
	<?php endif; ?>
	<?php if ($a['send_mail'] !== NULL && $editPrvg): ?>
	<?php echo form_open($a['base'].'/send_mail/'.(int) $a['id']); ?>
		<input type="hidden" name="page" value="<?php echo (int) $a['page']; ?>">
		<?php foreach ($a['send_mail'] as $name => $value): ?>
		<input type="hidden" name="<?php echo html_escape($name); ?>" value="<?php echo html_escape($value); ?>">
		<?php endforeach; ?>
		<button class="btn sp-btn-outline" type="submit">Send Mail<span class="visually-hidden"> to <?php echo $label; ?></span></button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<?php if ($a['active'] !== NULL && ($a['active'] ? $deactivatePrvg : $activatePrvg)): ?>
	<?php echo form_open($a['base'].'/status/'.(int) $a['id']); ?>
		<input type="hidden" name="status" value="<?php echo $a['active'] ? '0' : '1'; ?>">
		<input type="hidden" name="page" value="<?php echo (int) $a['page']; ?>">
		<button class="btn sp-btn-outline" type="submit"><?php echo html_escape($a['status_words'][$a['active'] ? 1 : 0]); ?><span class="visually-hidden"> <?php echo $label; ?></span></button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<?php if ($a['delete'] && $deletePrvg): ?>
	<?php echo form_open($a['base'].'/delete/'.(int) $a['id'], array('data-sp-confirm' => 'Delete "'.$a['label'].'"? This cannot be undone.')); /* form_open() escapes attribute values */ ?>
		<input type="hidden" name="page" value="<?php echo (int) $a['page']; ?>">
		<button class="btn sp-btn-outline sp-btn-danger" type="submit">Delete<span class="visually-hidden"> <?php echo $label; ?></span></button>
	<?php echo form_close(); ?>
	<?php endif; ?>
</div>

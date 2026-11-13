<?php
/*
| Bulk action toolbar of a list. Top (default): the bulk form itself, with the
| page and filters; row checkboxes join it with form="sp-bulk-<key>".
| 'position' => 'bottom' (under the table, same $bulk): the same buttons
| without a form of their own, sent with that form (form="..."), so either
| toolbar posts the one form once, with the same selected ids and filters.
*/
$bulk += array('filters' => array(), 'can_activate' => FALSE, 'can_deactivate' => FALSE, 'can_delete' => FALSE, 'can_send_email' => FALSE, 'can_set_recently_added' => FALSE, 'can_unset_recently_added' => FALSE);
$has_actions = $bulk['can_activate'] || $bulk['can_deactivate'] || $bulk['can_delete'] || $bulk['can_send_email'];
$has_actions = $has_actions || $bulk['can_set_recently_added'] || $bulk['can_unset_recently_added'];
$form_id = isset($bulk['form_id']) ? $bulk['form_id'] : 'sp-bulk-'.$bulk['key'];
$bottom = isset($bulk['position']) && $bulk['position'] === 'bottom';
$controls_only = !empty($bulk['controls_only']) || $bottom;
$control_form = isset($bulk['control_form']) ? $bulk['control_form'] : $form_id;
$control_suffix = isset($bulk['control_suffix']) ? $bulk['control_suffix'] : ($bottom ? '_bottom' : '');
$set_as_options = isset($bulk['set_as_options']) && is_array($bulk['set_as_options']) ? $bulk['set_as_options'] : ($bulk['can_set_recently_added'] ? array('is_recently_added' => 'Recently Added') : array());
$unset_as_options = isset($bulk['unset_as_options']) && is_array($bulk['unset_as_options']) ? $bulk['unset_as_options'] : ($bulk['can_unset_recently_added'] ? array('is_recently_added' => 'Recently Added') : array());
$can_set_as = !empty($set_as_options);
$can_unset_as = !empty($unset_as_options);
$has_actions = $has_actions || $can_set_as || $can_unset_as;
?>
<?php if ($has_actions): ?>
<div class="sp-bulk-toolbar mt-3<?php echo $bottom ? ' sp-bulk-toolbar--bottom' : ''; ?>">
	<?php if (!$controls_only): ?><?php echo form_open($bulk['base'].'/bulk', array('id' => $form_id, 'class' => 'sp-row-actions', 'data-sp-bulk-form' => $bulk['key'], 'data-sp-bulk-noun' => $bulk['noun'])); ?><?php else: ?><div class="sp-row-actions"><?php endif; ?>
		<?php $control_attributes = $controls_only ? ' form="'.html_escape($control_form).'"' : ''; ?>
		<?php if (!$controls_only): /* page and filters: in the form only (a bottom toolbar sends that form) */ ?>
		<input type="hidden" name="page" value="<?php echo (int) $bulk['page']; ?>">
		<?php foreach ($bulk['filters'] as $name => $value): ?>
		<input type="hidden" name="<?php echo html_escape($name); ?>" value="<?php echo html_escape($value); ?>">
		<?php endforeach; ?>
		<?php endif; ?>
		<?php if ($bulk['can_activate']): ?><button<?php echo $control_attributes; ?> class="btn sp-btn-outline" type="submit" name="bulk_action" value="activate">Active</button><?php endif; ?>
		<?php if ($bulk['can_deactivate']): ?><button<?php echo $control_attributes; ?> class="btn sp-btn-outline" type="submit" name="bulk_action" value="deactivate">Deactive</button><?php endif; ?>
		<?php if ($bulk['can_send_email']): ?><button<?php echo $control_attributes; ?> class="btn sp-btn-outline" type="submit" name="bulk_action" value="send_email">Send Email</button><?php endif; ?>
		<?php if ($bulk['can_delete']): ?><button<?php echo $control_attributes; ?> class="btn sp-btn-outline sp-btn-danger" type="submit" name="bulk_action" value="delete" data-sp-confirm="Delete the selected <?php echo html_escape($bulk['noun']); ?>? This cannot be undone.">Delete</button><?php endif; ?>
		<?php if ($can_set_as): ?><select<?php echo $control_attributes; ?> class="form-select form-select-sm sp-bulk-select" name="set_as<?php echo $control_suffix; ?>" aria-label="Set selected products as"><option value="">Product Set As</option><?php foreach ($set_as_options as $value => $label): ?><option value="<?php echo html_escape($value); ?>"><?php echo html_escape($label); ?></option><?php endforeach; ?></select><?php endif; ?>
		<?php if ($can_unset_as): ?><select<?php echo $control_attributes; ?> class="form-select form-select-sm sp-bulk-select" name="unset_as<?php echo $control_suffix; ?>" aria-label="Unset selected products as"><option value="">Product Unset As</option><?php foreach ($unset_as_options as $value => $label): ?><option value="<?php echo html_escape($value); ?>"><?php echo html_escape($label); ?></option><?php endforeach; ?></select><?php endif; ?>
		<?php if ($can_set_as || $can_unset_as): ?><button<?php echo $control_attributes; ?> class="btn sp-btn-outline" type="submit" name="bulk_action" value="flag">Apply</button><?php endif; ?>
	<?php if (!$controls_only): ?><?php echo form_close(); ?><?php else: ?></div><?php endif; ?>
</div>
<?php endif; ?>

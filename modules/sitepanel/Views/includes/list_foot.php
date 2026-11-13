<?php
/* Under a list: record count and page links. $foot = [total, noun (plural), pagination html]. */
?>
<div class="sp-list-foot">
	<p class="sp-muted mb-0"><?php echo (int) $foot['total'].' '.html_escape($foot['noun']); ?></p>
	<?php echo $foot['pagination']; ?>
</div>

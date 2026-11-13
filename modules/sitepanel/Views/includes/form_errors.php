<?php
/* Validation errors of a sitepanel form ($errors = list of messages). Same markup as Admin Settings. */
?>
<?php if ( ! empty($errors)): ?>
<div class="alert alert-danger" role="alert">
	<p class="mb-1 fw-semibold">The changes were not saved:</p>
	<ul class="mb-0">
		<?php foreach ($errors as $error): ?>
		<li><?php echo html_escape($error); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

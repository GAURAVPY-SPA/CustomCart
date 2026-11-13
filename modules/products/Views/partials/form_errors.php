<?php
/*
| Validation errors of a store form ($form_errors = list of messages).
*/
?>
<?php if ( ! empty($form_errors)): ?>
<div class="alert alert-danger" role="alert">
<p class="mb-1 fw-semibold">Please check the following:</p>
<ul class="mb-0">
<?php foreach ($form_errors as $error): ?>
<li><?php echo html_escape($error); ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

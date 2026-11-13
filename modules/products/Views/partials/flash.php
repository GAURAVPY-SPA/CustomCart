<?php
/*
| One-time messages of the store and account pages (session flashdata
| 'shop_success', 'shop_error', 'shop_notice'), plus $errors (list of
| validation messages) when given. Reads the session only when the visitor
| has one, so a first visit doesn't start one.
*/
$flash = array();
if (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[config('Session')->cookieName]))
{
	foreach (array('shop_success' => 'success', 'shop_error' => 'danger', 'shop_notice' => 'info') as $key => $type)
	{
		$message = session()->getFlashdata($key);
		if (is_string($message) && $message !== '')
		{
			$flash[] = array($type, $message);
		}
	}
}
$errors = isset($errors) && is_array($errors) ? $errors : array();
?>
<?php foreach ($flash as list($type, $message)): ?>
<div class="alert alert-<?php echo $type; ?> d-flex align-items-start gap-2" role="<?php echo $type === 'danger' ? 'alert' : 'status'; ?>">
<i class="bi bi-<?php echo $type === 'success' ? 'check-circle' : ($type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> flex-shrink-0 mt-1" aria-hidden="true"></i><span><?php echo html_escape($message); ?></span>
</div>
<?php endforeach; ?>
<?php if ($errors): ?>
<div class="alert alert-danger" role="alert">
<p class="mb-1 fw-semibold">Please check the following:</p>
<ul class="mb-0">
<?php foreach ($errors as $error): ?>
<li><?php echo html_escape($error); ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

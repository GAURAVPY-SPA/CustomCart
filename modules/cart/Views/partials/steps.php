<?php
/*
| Checkout steps (design: the .kt-steps of cart.htm / checkout.htm / payment.htm
| / order-confirmation.htm). $step = 1 Cart, 2 Address, 3 Payment, 4 Confirmation;
| finished steps show a tick.
*/
$names = array(1 => 'Cart', 2 => 'Address', 3 => 'Payment', 4 => 'Confirmation');
?>
<nav class="kt-steps" aria-label="Checkout steps">
<ol class="list-unstyled d-flex justify-content-center mb-0">
<?php foreach ($names as $n => $name): $done = $n < $step || $step === 4; ?>
<li class="kt-step<?php echo ($done ? ' is-done' : '').($n === $step ? ' is-active' : ''); ?>"<?php echo $n === $step ? ' aria-current="step"' : ''; ?>><span class="kt-step-in d-flex flex-column align-items-center"><span class="kt-step-dot rounded-circle d-flex align-items-center justify-content-center fw-bold"><?php echo $done ? '<i class="bi bi-check-lg"></i>' : $n; ?></span><span class="kt-step-name fw-bold"><?php echo $name; ?></span></span></li>
<?php endforeach; ?>
</ol>
</nav>

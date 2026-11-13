/* Verification code fields (app/Views/includes/captcha.php, App\Libraries\Captcha).
 * "Show a new code" (a type="button", so the form is never submitted) loads a new image from
 * the field's data-captcha-src (GET captcha/<form>, which replaces the code in the session, so
 * the previous code stops working) with a cache-busting query on the image only: the page is
 * not reloaded, its address does not change and the other fields keep what was typed. The
 * code input (data-captcha-input = its id) is emptied and focused.
 */
(function () {
	'use strict';

	Array.prototype.forEach.call(document.querySelectorAll('[data-captcha]'), function (field) {
		var image  = field.querySelector('[data-captcha-image]');
		var button = field.querySelector('[data-captcha-refresh]');
		var status = field.parentNode.querySelector('[data-captcha-status]');
		var input  = document.getElementById(field.getAttribute('data-captcha-input'));
		var src    = field.getAttribute('data-captcha-src');

		if (!image || !button || !src) { return; }

		button.addEventListener('click', function (event) {
			event.preventDefault();
			image.src = src + (src.indexOf('?') === -1 ? '?' : '&') + 'r=' + Date.now();
			if (input) { input.value = ''; input.focus(); }
			if (status) { status.textContent = 'A new code is shown.'; }
		});
	});
})();

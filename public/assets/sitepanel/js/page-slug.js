/*
 * Read-only Page URL / URL slug preview (category and product forms).
 * <input readonly data-slug-source="<name field id>" data-slug-max="100"
 *        data-saved-name="..." data-saved-url="..."> shows the slug the name
 * would get, with the same rules as sp_page_slug() (sitepanel_helper.php).
 * Preview only: the server makes the real slug (and adds -2, -3... when it is taken).
 */
(function () {
	'use strict';
	function slug(text, max) {
		return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
			.replace(/['‘’`]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
			.slice(0, max).replace(/-+$/, '');
	}
	Array.prototype.forEach.call(document.querySelectorAll('input[data-slug-source]'), function (url) {
		var name = document.getElementById(url.getAttribute('data-slug-source'));
		if (!name) { return; }
		var max = parseInt(url.getAttribute('data-slug-max'), 10) || 100;
		var savedName = url.getAttribute('data-saved-name') || '', savedUrl = url.getAttribute('data-saved-url') || '';
		name.addEventListener('input', function () {
			url.value = (savedName !== '' && name.value.trim() === savedName) ? savedUrl : slug(name.value, max);
		});
	});
}());

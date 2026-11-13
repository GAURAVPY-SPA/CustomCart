/* CustomCart sitepanel — sidebar toggle and groups, delete confirmations, account menu (no dependencies) */
(function () {
	'use strict';

	var shell = document.getElementById('sp-shell');
	var mobile = window.matchMedia('(max-width: 991.98px)');

	/* ---------- Sidebar ---------- */
	if (shell) {
		var toggle = document.querySelector('[data-sp-toggle-sidebar]');
		var overlay = document.querySelector('[data-sp-close-sidebar]');
		var STORAGE_KEY = 'sp-sidebar-collapsed';

		var isOpen = function () {
			return mobile.matches ? shell.classList.contains('is-open') : !shell.classList.contains('is-collapsed');
		};

		var sync = function () {
			toggle.setAttribute('aria-expanded', isOpen() ? 'true' : 'false');
			overlay.hidden = !(mobile.matches && isOpen());
		};

		var setOpen = function (open) {
			if (mobile.matches) {
				shell.classList.toggle('is-open', open);
			} else {
				shell.classList.toggle('is-collapsed', !open);
				try { localStorage.setItem(STORAGE_KEY, open ? '0' : '1'); } catch (e) { /* storage unavailable */ }
			}
			sync();
		};

		// Desktop preference survives page loads; mobile always starts closed.
		try {
			if (!mobile.matches && localStorage.getItem(STORAGE_KEY) === '1') {
				shell.classList.add('is-collapsed');
			}
		} catch (e) { /* storage unavailable */ }

		toggle.addEventListener('click', function () { setOpen(!isOpen()); });
		overlay.addEventListener('click', function () { setOpen(false); });

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && mobile.matches && isOpen()) {
				setOpen(false);
				toggle.focus();
			}
		});

		mobile.addEventListener('change', function () {
			shell.classList.remove('is-open');
			var collapsed = false;
			try { collapsed = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { /* storage unavailable */ }
			shell.classList.toggle('is-collapsed', !mobile.matches && collapsed);
			sync();
		});

		sync();
	}

	/* ---------- Sidebar groups (expand / collapse, sliding) ---------- */
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	Array.prototype.forEach.call(document.querySelectorAll('[data-sp-nav-group]'), function (btn) {
		var list = document.getElementById(btn.getAttribute('aria-controls'));
		var done = null;  // transitionend handler of the running animation
		var timer = null; // fallback when no transitionend comes (no height change, background tab)

		function finish() {
			list.classList.remove('is-sliding');
			list.style.height = '';
			list.style.opacity = '';
			if (btn.getAttribute('aria-expanded') !== 'true') { list.hidden = true; }
			list.removeEventListener('transitionend', done);
			clearTimeout(timer);
			done = null;
		}

		btn.addEventListener('click', function () {
			var open = btn.getAttribute('aria-expanded') !== 'true';
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');

			if (reduceMotion) {
				list.hidden = !open;
				return;
			}

			// Start from the current height, so a click during the animation reverses it smoothly.
			var from = list.hidden ? 0 : list.getBoundingClientRect().height;
			if (done) { list.removeEventListener('transitionend', done); }
			clearTimeout(timer);
			list.hidden = false;
			list.classList.add('is-sliding');
			list.style.height = from + 'px';
			list.style.opacity = from ? '1' : '0';
			void list.offsetHeight; // apply the start values before the transition

			list.style.height = (open ? list.scrollHeight : 0) + 'px';
			list.style.opacity = open ? '1' : '0';

			done = function (e) { if (e.target === list && e.propertyName === 'height') { finish(); } };
			list.addEventListener('transitionend', done);
			timer = setTimeout(finish, 500); // the transition takes 350ms
		});
	});

	/* ---------- Confirm destructive actions (forms with data-sp-confirm) ---------- */
	document.addEventListener('submit', function (e) {
		var message = e.target.getAttribute('data-sp-confirm');
		if (message && !window.confirm(message)) { e.preventDefault(); }

		if (e.target.hasAttribute('data-sp-bulk-form')) {
			var key = e.target.getAttribute('data-sp-bulk-form');
			var selected = document.querySelectorAll('[data-sp-select="' + key + '"]:checked').length;
			if (!selected) {
				e.preventDefault();
				window.alert('Select one or more records first.');
				return;
			}

			var submitter = e.submitter || document.activeElement;
			if (submitter && submitter.hasAttribute('data-sp-confirm')
				&& !window.confirm(submitter.getAttribute('data-sp-confirm'))) {
				e.preventDefault();
			}
		}
	});

	/* ---------- Bulk list selection ---------- */
	Array.prototype.forEach.call(document.querySelectorAll('[data-sp-select-all]'), function (selectAll) {
		var key = selectAll.getAttribute('data-sp-select-all');
		var selector = '[data-sp-select="' + key + '"]';
		var sync = function () {
			var rows = document.querySelectorAll(selector);
			var checked = document.querySelectorAll(selector + ':checked').length;
			selectAll.checked = rows.length > 0 && checked === rows.length;
			selectAll.indeterminate = checked > 0 && checked < rows.length;
		};

		selectAll.addEventListener('change', function () {
			Array.prototype.forEach.call(document.querySelectorAll(selector), function (checkbox) {
				checkbox.checked = selectAll.checked;
			});
			sync();
		});

		Array.prototype.forEach.call(document.querySelectorAll(selector), function (checkbox) {
			checkbox.addEventListener('change', sync);
		});
	});

	/* ---------- Account menu ---------- */
	var accountBtn = document.querySelector('[data-sp-toggle-account]');
	if (accountBtn) {
		var menu = document.getElementById(accountBtn.getAttribute('aria-controls'));

		var setMenu = function (open) {
			menu.hidden = !open;
			accountBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
		};

		accountBtn.addEventListener('click', function (e) {
			e.stopPropagation();
			setMenu(menu.hidden);
		});

		document.addEventListener('click', function (e) {
			if (!menu.hidden && !menu.contains(e.target)) { setMenu(false); }
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !menu.hidden) {
				setMenu(false);
				accountBtn.focus();
			}
		});
	}

	/* ---------- Colour picker + hex field ---------- */
	Array.prototype.forEach.call(document.querySelectorAll('[data-sp-color-for]'), function (picker) {
		var field = document.getElementById(picker.getAttribute('data-sp-color-for'));
		if (!field) { return; }

		picker.addEventListener('input', function () { field.value = picker.value; });
		field.addEventListener('input', function () {
			var v = field.value.trim();
			if (/^#[0-9a-f]{3}$/i.test(v)) { v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]; }
			if (/^#[0-9a-f]{6}([0-9a-f]{2})?$/i.test(v)) { picker.value = v.slice(0, 7).toLowerCase(); }
		});
	});

	/* ---------- Dashboard card gradients: live preview (Theme Management) ----------
	   Same text-colour choice as SitepanelTheme::text_for(): white or dark,
	   whichever has the better contrast on both ends. */
	function longHex(v) {
		v = (v || '').trim().toLowerCase();
		if (/^#[0-9a-f]{3}$/.test(v)) { v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]; }
		return /^#[0-9a-f]{6}$/.test(v) ? v : null;
	}
	function luminance(hex) {
		var c = [1, 3, 5].map(function (i) {
			var x = parseInt(hex.substr(i, 2), 16) / 255;
			return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4);
		});
		return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
	}
	function contrast(a, b) {
		var la = luminance(a), lb = luminance(b);
		return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
	}

	Array.prototype.forEach.call(document.querySelectorAll('[data-sp-grad-card]'), function (card) {
		var preview = card.querySelector('[data-sp-grad-preview]');
		var note = card.querySelector('[data-sp-grad-note]');
		var field = function (name) { return card.querySelector('[data-sp-grad="' + name + '"]'); };
		if (!preview || !note) { return; }

		function update() {
			var start = longHex(field('start').value), end = longHex(field('end').value);
			var angle = parseInt(field('angle').value, 10);
			if (!start || !end) {
				note.textContent = 'Enter both colors as hex, e.g. #1a73c9, to see the preview.';
				return;
			}
			var best = ['#ffffff', 0];
			['#ffffff', '#1f2933'].forEach(function (text) {
				var r = Math.min(contrast(text, start), contrast(text, end));
				if (r > best[1]) { best = [text, r]; }
			});
			preview.style.setProperty('--sp-g-start', start);
			preview.style.setProperty('--sp-g-end', end);
			if (!isNaN(angle)) { preview.style.setProperty('--sp-g-angle', angle + 'deg'); }
			preview.style.setProperty('--sp-g-text', best[0]);
			note.textContent = 'Text: ' + (best[0] === '#ffffff' ? 'white' : 'dark') + ' · contrast ' + best[1].toFixed(2) + ':1'
				+ (best[1] < 4.5 ? ' — low; choose darker or lighter colors so the text stays readable' : '');
			note.classList.toggle('text-danger', best[1] < 4.5);
		}

		card.addEventListener('input', update);
		card.addEventListener('change', update);
	});

	// Dashboard orders graph: the Daily / Monthly / Yearly links (?period=) show that
	// series in place (all three are in the page, so nothing is reloaded; the line
	// draws in again, see sitepanel.css), and every point shows a tooltip with a
	// guide line on hover or keyboard focus.
	Array.prototype.forEach.call(document.querySelectorAll('[data-sp-chart]'), function (chart) {
		var links = chart.querySelectorAll('[data-sp-chart-period]');

		Array.prototype.forEach.call(links, function (link) {
			link.addEventListener('click', function (e) {
				var period = link.getAttribute('data-sp-chart-period');
				var panel = chart.querySelector('[data-sp-chart-panel="' + period + '"]');
				if (!panel) { return; }
				e.preventDefault();

				Array.prototype.forEach.call(chart.querySelectorAll('[data-sp-chart-panel]'), function (p) { p.hidden = (p !== panel); });
				Array.prototype.forEach.call(links, function (l) {
					var on = (l === link);
					l.classList.toggle('is-active', on);
					if (on) { l.setAttribute('aria-current', 'true'); } else { l.removeAttribute('aria-current'); }
				});
				if (window.history && history.replaceState) { history.replaceState(null, '', link.href); }
			});
		});

		// The plot is drawn in a 0-100 coordinate space stretched to the plot box (dashboard/orders_chart).
		Array.prototype.forEach.call(chart.querySelectorAll('[data-sp-chart-plot]'), function (frame) {
			var tip = frame.querySelector('[data-sp-chart-tip]');
			var guide = frame.querySelector('[data-sp-chart-guide]');
			var active = null;
			if (!tip || !guide) { return; }

			function show(hit) {
				var i = hit.getAttribute('data-sp-chart-point');
				var x = parseFloat(hit.getAttribute('data-x')), y = parseFloat(hit.getAttribute('data-y'));
				var px = x / 100 * frame.clientWidth, py = y / 100 * frame.clientHeight;

				// The native <title> tooltip would show as well: keep it only for no-JS.
				if (hit.firstElementChild && hit.firstElementChild.tagName.toLowerCase() === 'title') {
					hit.setAttribute('data-tip-title', hit.firstElementChild.textContent);
					hit.removeChild(hit.firstElementChild);
				}
				if (active) { active.classList.remove('is-active'); }
				active = frame.querySelector('[data-sp-chart-dot="' + i + '"]');
				if (active) { active.classList.add('is-active'); }
				guide.setAttribute('x1', x); guide.setAttribute('x2', x);
				guide.classList.add('is-shown');

				tip.querySelector('.sp-chart-tip-title').textContent = hit.getAttribute('data-title');
				tip.querySelector('.sp-chart-tip-value').textContent = hit.getAttribute('data-count');
				tip.hidden = false;
				// Keep the tooltip inside the frame (the scroll box clips it); the arrow stays on the point.
				var half = tip.offsetWidth / 2, left = Math.min(Math.max(px, half + 4), frame.clientWidth - half - 4);
				tip.style.left = left + 'px';
				tip.style.top = py + 'px';
				tip.style.setProperty('--sp-tip-arrow', Math.min(Math.max(half + px - left, 12), tip.offsetWidth - 12) + 'px');
				tip.classList.toggle('is-below', py < tip.offsetHeight + 16);
			}
			function hide() {
				tip.hidden = true;
				guide.classList.remove('is-shown');
				if (active) { active.classList.remove('is-active'); active = null; }
			}

			Array.prototype.forEach.call(frame.querySelectorAll('[data-sp-chart-point]'), function (hit) {
				hit.addEventListener('mouseenter', function () { show(hit); });
				hit.addEventListener('focus', function () { show(hit); });
				hit.addEventListener('blur', hide);
			});
			frame.addEventListener('mouseleave', hide);
		});
	});
})();

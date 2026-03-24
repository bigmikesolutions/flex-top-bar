/**
 * Top Bar – message rotation effects (slider, fadein, blink).
 */
(function () {
	'use strict';

	function parseMessages(el) {
		var raw = el.getAttribute('data-top-bar-effect-messages');
		if (!raw) return [];
		try {
			var parsed = JSON.parse(raw);
			if (!Array.isArray(parsed)) return [];
			return parsed.filter(function (item) {
				return typeof item === 'string' && item.trim() !== '';
			});
		} catch (e) {
			return [];
		}
	}

	function startEffect(el) {
		var effect = (el.getAttribute('data-top-bar-effect') || 'none').toLowerCase();
		if (['slider', 'fadein', 'blink'].indexOf(effect) === -1) return;

		var inner = el.querySelector('.top-bar__inner');
		if (!inner) return;

		var messages = parseMessages(el);
		if (messages.length < 2) return;

		var index = 0;
		inner.innerHTML = messages[index];

		setInterval(function () {
			index = (index + 1) % messages.length;
			if (effect === 'slider') {
				inner.style.transition = 'transform 220ms ease, opacity 220ms ease';
				inner.style.transform = 'translateY(-10px)';
				inner.style.opacity = '0';
				setTimeout(function () {
					inner.innerHTML = messages[index];
					inner.style.transform = 'translateY(10px)';
					requestAnimationFrame(function () {
						inner.style.transform = 'translateY(0)';
						inner.style.opacity = '1';
					});
				}, 220);
				return;
			}

			if (effect === 'fadein') {
				inner.style.transition = 'opacity 280ms ease';
				inner.style.opacity = '0';
				setTimeout(function () {
					inner.innerHTML = messages[index];
					inner.style.opacity = '1';
				}, 280);
				return;
			}

			// blink
			inner.style.transition = 'opacity 120ms ease';
			inner.style.opacity = '0';
			setTimeout(function () {
				inner.innerHTML = messages[index];
				inner.style.opacity = '1';
			}, 120);
		}, 2000);
	}

	function run() {
		var bars = document.querySelectorAll('.top-bar[data-top-bar-effect]');
		for (var i = 0; i < bars.length; i++) {
			startEffect(bars[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();

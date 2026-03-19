/**
 * Top Bar – hide specific bars after scrolling (only elements with data-top-bar-scroll-hide).
 */
(function () {
	'use strict';

	function scrollY() {
		return window.pageYOffset || document.documentElement.scrollTop || 0;
	}

	function bars() {
		return document.querySelectorAll('[data-top-bar-scroll-hide="1"]');
	}

	function thresholdFor(el) {
		var t = parseInt(el.getAttribute('data-top-bar-hide-threshold'), 10);
		return isNaN(t) ? 30 : t;
	}

	function update() {
		var list = bars();
		for (var i = 0; i < list.length; i++) {
			var el = list[i];
			var t = thresholdFor(el);
			el.style.display = scrollY() > t ? 'none' : '';
		}
	}

	function run() {
		if (bars().length === 0) {
			return;
		}
		update();
		window.addEventListener('scroll', update, { passive: true });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();

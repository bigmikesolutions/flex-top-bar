/**
 * Top Bar – visible only at the top of the page; hide when user scrolls down.
 */
(function () {
	'use strict';

	var scrollThreshold = 20;

	function run() {
		var bar = document.getElementById('top-bar');
		if (!bar || !bar.classList.contains('top-bar--hide-on-scroll')) return;

		function update() {
			var scrollY = window.scrollY || document.documentElement.scrollTop;
			if (scrollY <= scrollThreshold) {
				bar.classList.remove('is-hidden');
			} else {
				bar.classList.add('is-hidden');
			}
		}

		function onScroll() {
			window.requestAnimationFrame(update);
		}

		window.addEventListener('scroll', onScroll, { passive: true });
		update();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();

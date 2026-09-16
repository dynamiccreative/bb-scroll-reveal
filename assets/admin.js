/**
 * BB Scroll Reveal 1.8.0 — page Réglages.
 *
 * Trois comportements, en ES5 comme le reste du plugin (aucun build) :
 *   1. onglets côté client — tous les champs restent dans un seul formulaire,
 *      donc tous sont postés, y compris ceux des onglets non affichés ;
 *   2. barre d'enregistrement — état « modifications non enregistrées » et
 *      garde-fou avant de quitter la page ;
 *   3. retour sur le même onglet après enregistrement, en écrivant l'ancre
 *      dans le champ `_wp_http_referer` que options.php utilise pour rediriger.
 *
 * Sans JavaScript, la page reste utilisable : le <noscript> de la page déplie
 * tous les panneaux et le bouton d'enregistrement est un submit natif.
 */
(function () {
	'use strict';

	var root = document.querySelector('.bbsr-ui');
	if (!root) {
		return;
	}

	var form    = root.querySelector('form[data-bbsr-form]');
	var tabs    = root.querySelectorAll('.bbsr-tab');
	var panels  = root.querySelectorAll('.bbsr-panel');
	var helps   = root.querySelectorAll('.bbsr-help');
	var savebar = root.querySelector('[data-bbsr-savebar]');
	var referer = form ? form.querySelector('input[name="_wp_http_referer"]') : null;
	var refBase = referer ? referer.value.split('#')[0] : '';

	function each(list, fn) {
		Array.prototype.forEach.call(list, fn);
	}

	// =====================================================================
	// 1. ONGLETS
	// =====================================================================
	function activate(slug, push) {
		var known = false;

		each(tabs, function (tab) {
			var on = tab.getAttribute('data-bbsr-tab') === slug;
			if (on) {
				known = true;
			}
			tab.className = on ? 'bbsr-tab active' : 'bbsr-tab';
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
		});

		if (!known) {
			return false;
		}

		each(panels, function (panel) {
			var on = panel.getAttribute('data-bbsr-panel') === slug;
			panel.className = on ? 'bbsr-panel active' : 'bbsr-panel';
			panel.hidden = !on;
		});

		each(helps, function (help) {
			help.className = help.getAttribute('data-bbsr-help') === slug ? 'bbsr-help active' : 'bbsr-help';
		});

		// L'ancre sert au rechargement de la page ET au retour après
		// enregistrement : options.php redirige sur `_wp_http_referer`, fragment
		// compris.
		if (referer) {
			referer.value = refBase + '#' + slug;
		}
		if (push && window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + slug);
		}

		return true;
	}

	each(tabs, function (tab) {
		tab.addEventListener('click', function (event) {
			event.preventDefault();
			activate(tab.getAttribute('data-bbsr-tab'), true);
		});
	});

	if (window.location.hash) {
		activate(window.location.hash.replace('#', ''), false);
	}

	// =====================================================================
	// 2. BARRE D'ENREGISTREMENT
	// =====================================================================
	var dirty = false;

	function setDirty(state) {
		dirty = state;
		if (savebar) {
			savebar.setAttribute('data-dirty', state ? 'true' : 'false');
		}
	}

	if (form) {
		form.addEventListener('input', function () { setDirty(true); });
		form.addEventListener('change', function () { setDirty(true); });
		form.addEventListener('submit', function () { dirty = false; });

		if (savebar) {
			var cancel = savebar.querySelector('[data-bbsr-cancel]');
			if (cancel) {
				cancel.addEventListener('click', function () {
					dirty = false; // évite le beforeunload avant le rechargement
					window.location.reload();
				});
			}
		}

		window.addEventListener('beforeunload', function (event) {
			if (!dirty) {
				return;
			}
			event.preventDefault();
			event.returnValue = '';
		});
	}

	// =====================================================================
	// 3. RÉINITIALISATION
	// =====================================================================
	var reset = root.querySelector('form[data-bbsr-reset]');
	if (reset) {
		reset.addEventListener('submit', function (event) {
			if (!window.confirm(reset.getAttribute('data-bbsr-confirm'))) {
				event.preventDefault();
				return;
			}
			dirty = false;
		});
	}
})();

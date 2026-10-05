/*!
 * BB Scroll Reveal 1.9.1 — widgets Elementor « BB Liste dépliante » / « BB Image liée »
 *
 * Deux widgets appariés par leur identifiant de liaison (data-bb-acc) :
 *   .bb-acc            la liste dépliante ; elle rend aussi la pile d'images (.bb-acc-media)
 *   .bb-acc-media-box  le cadre image, où la pile est déplacée au chargement
 *
 * Indépendant de GSAP et de bb-reveal.js : ce fichier est aussi chargé dans
 * l'aperçu de l'éditeur Elementor, où le reste du plugin est volontairement à
 * l'arrêt. Le dépliement est une transition CSS (grid-template-rows).
 */
(function () {
	'use strict';

	function qs(sel) {
		return document.querySelector(sel);
	}

	function all(root, sel) {
		return [].slice.call(root.querySelectorAll(sel));
	}

	function setClass(el, name, on) {
		if (on) {
			el.classList.add(name);
		} else {
			el.classList.remove(name);
		}
	}

	/* L'identifiant est un slug (sanitize_key), utilisable tel quel en sélecteur. */
	function boxFor(group) {
		return qs('.bb-acc-media-box[data-bb-acc="' + group + '"]');
	}

	/* Pile d'images encore dans la liste, c'est-à-dire pas encore déplacée. */
	function ownStack(acc) {
		var kids = acc.children;
		var i;

		for (i = 0; i < kids.length; i++) {
			if (kids[i].classList && kids[i].classList.contains('bb-acc-media')) {
				return kids[i];
			}
		}

		return null;
	}

	/**
	 * Déplace la pile d'images rendue par la liste dans le cadre image de même
	 * identifiant. Idempotent : rejoué à chaque re-rendu de l'un ou l'autre
	 * widget dans l'éditeur Elementor. Un re-rendu de la liste produit une pile
	 * neuve alors que la précédente est déjà dans le cadre : elle est retirée,
	 * sans quoi les anciennes images resteraient affichées.
	 */
	function pair(acc, group) {
		var box = boxFor(group);

		if (!box) {
			return null;
		}

		/* Après un re-rendu du cadre image dans l'éditeur, la pile déplacée a été
		   emportée avec l'ancien cadre : on la retrouve par la référence gardée
		   sur la liste, le nœud existe encore, simplement détaché. */
		var stack = ownStack(acc) || acc.bbAccStack;

		if (!stack) {
			return box;
		}

		acc.bbAccStack = stack;

		all(box, '.bb-acc-media').forEach(function (stale) {
			if (stale !== stack && stale.parentNode) {
				stale.parentNode.removeChild(stale);
			}
		});

		if (stack.parentNode !== box) {
			box.appendChild(stack);
		}

		return box;
	}

	function showImage(group, index) {
		var box = boxFor(group);

		if (!box) {
			return;
		}

		var items = all(box, '.bb-acc-media-item');
		var matched = false;
		var i;

		for (i = 0; i < items.length; i++) {
			if (items[i].classList.contains('bb-acc-media-fallback')) {
				continue;
			}
			var on = items[i].getAttribute('data-bb-index') === String(index);
			matched = matched || on;
			setClass(items[i], 'is-active', on);
		}

		var fallback = box.querySelector('.bb-acc-media-fallback');

		if (fallback) {
			setClass(fallback, 'is-active', !matched);
		}
	}

	/* Une ligne qui se déplie change la hauteur du document : les épinglages
	   ScrollTrigger de bb-reveal.js doivent être recalculés. */
	function refreshScrollTrigger() {
		if (window.ScrollTrigger && typeof window.ScrollTrigger.refresh === 'function') {
			window.ScrollTrigger.refresh();
		}
	}

	function indexOf(item) {
		return parseInt(item.getAttribute('data-bb-index'), 10) || 0;
	}

	function setOpen(item, open) {
		var head = item.querySelector('.bb-acc-head');

		setClass(item, 'is-open', open);

		if (head) {
			head.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	function openItem(acc) {
		var items = all(acc, '.bb-acc-item');
		var i;

		for (i = 0; i < items.length; i++) {
			if (items[i].classList.contains('is-open')) {
				return items[i];
			}
		}

		return null;
	}

	/**
	 * Déplie et replie, façon slideDown : la hauteur du panneau est écrite en
	 * pixels, du point de départ mesuré vers la cible.
	 *
	 * Tout le lot passe par ici en une fois — la ligne qui se ferme et celle qui
	 * s'ouvre — avec un seul recalcul de style entre les hauteurs de départ et
	 * les hauteurs d'arrivée. Les deux transitions démarrent donc dans la même
	 * frame et se terminent ensemble : c'est ce qui manquait quand chaque ligne
	 * était laissée à sa propre bascule de classe.
	 */
	function slide(changes) {
		var forced = null;

		changes.forEach(function (c) {
			c.panel = c.item.querySelector('.bb-acc-panel');
			c.inner = c.panel ? c.panel.querySelector('.bb-acc-panel-inner') : null;

			if (!c.panel) {
				return;
			}

			/* Mesuré avant la bascule de classe : le panneau est encore dans son
			   état de départ. Le contenu, lui, garde sa hauteur naturelle même
			   quand le panneau le rogne. */
			c.from = c.panel.getBoundingClientRect().height;
			c.to = c.open && c.inner ? c.inner.getBoundingClientRect().height : 0;
			c.panel.style.height = c.from + 'px';
			forced = c.panel;
		});

		changes.forEach(function (c) {
			setOpen(c.item, c.open);
		});

		if (forced) {
			// Un seul reflow pour le lot : sans lui, la hauteur de départ et la
			// hauteur d'arrivée seraient fusionnées et rien ne s'animerait.
			void forced.offsetHeight;
		}

		changes.forEach(function (c) {
			if (c.panel) {
				c.panel.style.height = c.to + 'px';
			}
		});
	}

	/* Remet le cadre image en accord avec l'élément ouvert (init, re-rendu du
	   widget image, sortie du survol). */
	function sync(acc) {
		var group = acc.getAttribute('data-bb-acc') || 'default';
		var open = openItem(acc);

		pair(acc, group);
		showImage(group, open ? indexOf(open) : -1);
	}

	function init(acc) {
		if (acc.getAttribute('data-bb-ready') === '1') {
			return;
		}
		acc.setAttribute('data-bb-ready', '1');

		var group = acc.getAttribute('data-bb-acc') || 'default';
		var single = acc.getAttribute('data-bb-toggle') !== 'multiple';
		var keepOpen = acc.getAttribute('data-bb-keep-open') === '1';
		var hover = acc.getAttribute('data-bb-hover') === '1';
		var items = all(acc, '.bb-acc-item');
		var heads = all(acc, '.bb-acc-head');

		sync(acc);

		function toggle(item) {
			var isOpen = item.classList.contains('is-open');
			var changes = [];

			if (isOpen && single && keepOpen) {
				return;
			}

			if (!isOpen && single) {
				items.forEach(function (other) {
					if (other !== item && other.classList.contains('is-open')) {
						changes.push({ item: other, open: false });
					}
				});
			}

			changes.push({ item: item, open: !isOpen });
			slide(changes);

			if (!isOpen) {
				showImage(group, indexOf(item));
			} else if (single) {
				showImage(group, -1);
			}
		}

		function move(from, delta) {
			var next = heads.indexOf(from) + delta;

			if (next < 0) {
				next = heads.length - 1;
			} else if (next >= heads.length) {
				next = 0;
			}

			heads[next].focus();
		}

		items.forEach(function (item) {
			var head = item.querySelector('.bb-acc-head');

			if (!head) {
				return;
			}

			head.addEventListener('click', function () {
				toggle(item);
			});

			head.addEventListener('keydown', function (e) {
				if (e.key === 'ArrowDown') {
					e.preventDefault();
					move(head, 1);
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					move(head, -1);
				} else if (e.key === 'Home') {
					e.preventDefault();
					heads[0].focus();
				} else if (e.key === 'End') {
					e.preventDefault();
					heads[heads.length - 1].focus();
				}
			});

			if (hover) {
				head.addEventListener('mouseenter', function () {
					showImage(group, indexOf(item));
				});
			}
		});

		if (hover) {
			acc.addEventListener('mouseleave', function () {
				sync(acc);
			});
		}

		/* Fin du dépliement : la hauteur en pixels est rendue à « auto », pour que
		   le panneau suive ensuite le contenu (images chargées après coup,
		   changement de largeur). Et les épinglages ScrollTrigger sont recalés. */
		acc.addEventListener('transitionend', function (e) {
			if (e.propertyName !== 'height' || !e.target.classList.contains('bb-acc-panel')) {
				return;
			}

			var panelItem = e.target.parentNode;

			if (panelItem && panelItem.classList.contains('is-open')) {
				e.target.style.height = 'auto';
			}

			refreshScrollTrigger();
		});
	}

	function initAll() {
		all(document, '.bb-acc').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	/* Éditeur Elementor : chaque widget re-rendu doit être ré-initialisé, et le
	   cadre image ré-apparié (son contenu a été recréé vide). */
	if (window.jQuery) {
		jQuery(window).on('elementor/frontend/init', function () {
			if (!window.elementorFrontend || !elementorFrontend.hooks) {
				return;
			}

			elementorFrontend.hooks.addAction('frontend/element_ready/bb_accordion.default', function ($scope) {
				var acc = $scope && $scope[0] ? $scope[0].querySelector('.bb-acc') : null;

				if (acc) {
					init(acc);
				}
			});

			elementorFrontend.hooks.addAction('frontend/element_ready/bb_accordion_media.default', function () {
				all(document, '.bb-acc').forEach(sync);
			});
		});
	}
})();

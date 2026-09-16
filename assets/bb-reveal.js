/*!
 * BB Scroll Reveal 1.8.0 — bleuebuzz
 * Classes à poser dans Elementor > Avancé > Classes CSS :
 *   bb-reveal           élément révélé (fade + translation) une fois
 *   bb-reveal-children  enfants directs d'un conteneur révélés en cascade
 *   bb-split            titre / texte révélé ligne par ligne sous masque
 *   bb-scrub            opacité + échelle liées à la position de scroll
 *   bb-parallax         parallaxe verticale liée au scroll
 *   bb-steps + bb-step  section épinglée, étapes révélées une à une au fil du scroll (desktop),
 *                       simple révélation sous le breakpoint mobile
 *   bb-hscroll + bb-hpanel  section épinglée dont les panneaux défilent horizontalement (desktop),
 *                       empilement vertical normal sous le breakpoint mobile
 *   bb-ink              conteneur de deux images : la seconde est révélée par une tache d'encre
 *                       qui grandit au scroll (masque SVG + turbulence, façon Heron)
 * Réglages par élément (Elementor Pro > Avancé > Attributs) :
 *   data-bb-y, data-bb-duration, data-bb-delay, data-bb-stagger, data-bb-start, data-bb-parallax,
 *   data-bb-distance (bb-steps : % de hauteur d'écran scrollé par étape ;
 *                    bb-hscroll : longueur de scroll en % du déplacement horizontal, défaut 100)
 *   data-bb-mode     (bb-steps : "slide" pour un glissement des étapes par le bas, défaut = fondu)
 *   data-bb-offset   (bb-steps, bb-hscroll : hauteur en px du header fixe sous lequel épingler ; auto-détecté sinon)
 *   data-bb-replay   (bb-steps ou bb-step : les animations internes aux étapes se rembobinent au retour ; défaut = acquises)
 *   data-bb-pin      (bb-steps slide : "top" = plein écran figé, "bottom" = section collée en bas ; auto = selon sa hauteur)
 *   data-bb-direction (bb-hscroll : "rtl" pour un défilement de droite à gauche ; défaut = ltr)
 *   data-bb-width    (bb-hscroll : largeur des panneaux, "60vw", "600px" ou un nombre lu en vw ;
 *                    par défaut la largeur posée dans Elementor est conservée)
 *   data-bb-ink-start, data-bb-ink-end (bb-ink : % de progression entre lesquels la tache grandit ;
 *                    progression de la section bb-steps parente, sinon du passage dans l'écran)
 *   data-bb-ink-origin ("x y" en %, centre de la tache, défaut "50 50"), data-bb-ink-scale (force du
 *                    déchiquetage, défaut 100), data-bb-ink-freq (grain, défaut 0.05), data-bb-ink-seed
 */
(function () {
	'use strict';

	var root = document.documentElement;

	function release() {
		root.classList.remove('bb-js');
		window.bbRevealReady = true;
	}

	function init() {
		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var inEditor = document.body.classList.contains('elementor-editor-active');

		if (reduceMotion || inEditor || !window.gsap || !window.ScrollTrigger) {
			release();
			return;
		}

		var gsap = window.gsap;
		var ScrollTrigger = window.ScrollTrigger;
		var SplitText = window.SplitText || null;
		var cfg = Object.assign(
			{ y: 40, duration: 0.9, stagger: 0.12, ease: 'power3.out', start: 'top 85%', smooth: true, stepsBreakpoint: 768, stepsOffset: 'auto', hscrollBreakpoint: 768 },
			window.BB_REVEAL || {}
		);

		gsap.registerPlugin.apply(gsap, SplitText ? [ScrollTrigger, SplitText] : [ScrollTrigger]);

		function num(el, key, fallback) {
			var v = parseFloat(el.dataset[key]);
			return isFinite(v) ? v : fallback;
		}

		// Piste bb-hscroll en cours de câblage : voir hscrollPinned(). Les éléments qu'elle contient ne
		// traversent jamais l'écran verticalement, leurs déclencheurs sont exprimés dans l'axe de la
		// piste (containerAnimation) et lus sur les mots-clés horizontaux de ScrollTrigger.
		var hostTl = null;
		var H_START = 'left 85%'; // équivalent horizontal de cfg.start.

		function trigger(el, host) {
			var vars = { trigger: el, start: el.dataset.bbStart || (host ? H_START : cfg.start), once: true };
			if (host) { vars.containerAnimation = host; }
			return vars;
		}

		// Animations contenues dans une étape bb-step : créées en pause, sans ScrollTrigger (pendant
		// l'épinglage la position dans l'écran ne bouge plus, un déclencheur de scroll serait arbitraire).
		// C'est l'étape qui les joue une fois en place, et les rembobine quand elle repart.
		var gates = [];
		function gateOf(el) {
			var step = el.closest('.bb-step');
			if (!step || !step.closest('.bb-steps') || step === el) { return null; }
			for (var i = 0; i < gates.length; i++) { if (gates[i].step === step) { return gates[i]; } }
			var gate = { step: step, anims: [], armed: false };
			gates.push(gate);
			return gate;
		}
		function gateAdd(gate, anim) {
			gate.anims.push(anim);
			if (gate.armed) { anim.play(); }
		}
		function gatePlay(step) {
			gates.forEach(function (g) { if (g.step === step && !g.armed) { g.armed = true; g.anims.forEach(function (a) { a.play(); }); } });
		}
		function gateReverse(step) {
			gates.forEach(function (g) { if (g.step === step && g.armed) { g.armed = false; g.anims.forEach(function (a) { a.reverse(); }); } });
		}
		// Vars communes : déclencheur de scroll, ou pause si l'élément est dans une étape.
		// `host` est passé explicitement plutôt que lu dans hostTl : splitReveal crée son animation
		// depuis onSplit, qui peut s'exécuter bien après le câblage de la piste (chargement des polices).
		function drive(el, gate, host) {
			return gate ? { paused: true } : { scrollTrigger: trigger(el, host) };
		}

		// Enfants directs selon la structure Elementor (conteneurs flex/grid, sections/colonnes legacy, widgets).
		function childrenOf(el) {
			var selectors = [
				':scope > .e-con-inner > .elementor-element',
				':scope > .elementor-element',
				':scope > .elementor-container > .elementor-column',
				':scope > .elementor-widget-wrap > .elementor-element'
			];
			for (var i = 0; i < selectors.length; i++) {
				var found = el.querySelectorAll(selectors[i]);
				if (found.length) {
					return Array.prototype.slice.call(found);
				}
			}
			var host = el.querySelector(':scope > .elementor-widget-container') || el;
			return Array.prototype.slice.call(host.children);
		}

		function reveal(el, extra) {
			var gate = gateOf(el);
			var vars = Object.assign({
				autoAlpha: 1,
				y: 0,
				duration: num(el, 'bbDuration', cfg.duration),
				delay: num(el, 'bbDelay', 0),
				ease: cfg.ease
			}, gate ? {} : { clearProps: 'transform' }, drive(el, gate, hostTl), extra || {});
			var tween = gsap.fromTo(el, { autoAlpha: 0, y: num(el, 'bbY', cfg.y) }, vars);
			if (gate) { gateAdd(gate, tween); }
			return tween;
		}

		function revealChildren(el) {
			var kids = childrenOf(el);
			gsap.set(el, { autoAlpha: 1 });
			if (!kids.length) {
				return;
			}
			var gate = gateOf(el);
			var tween = gsap.fromTo(
				kids,
				{ autoAlpha: 0, y: num(el, 'bbY', cfg.y) },
				Object.assign({
					autoAlpha: 1,
					y: 0,
					duration: num(el, 'bbDuration', cfg.duration),
					delay: num(el, 'bbDelay', 0),
					stagger: num(el, 'bbStagger', cfg.stagger),
					ease: cfg.ease
				}, gate ? {} : { clearProps: 'transform' }, drive(el, gate, hostTl))
			);
			if (gate) { gateAdd(gate, tween); }
		}

		function splitReveal(el) {
			if (!SplitText) {
				reveal(el);
				return;
			}
			var target = el.querySelector('.elementor-heading-title, .elementor-widget-container') || el;
			var gate = gateOf(el);
			var host = hostTl; // capturé maintenant : onSplit peut attendre le chargement des polices.
			// Reste masqué (inline) jusqu'au premier split, qui peut attendre le chargement des polices.
			gsap.set(el, { autoAlpha: 0 });

			// Dans une étape : SplitText recrée l'animation à chaque re-split (polices, resize) ; le
			// contrôleur enregistré auprès de l'étape pointe toujours sur la dernière.
			var current = null;
			if (gate) {
				gateAdd(gate, {
					play: function () { if (current) { current.play(); } },
					reverse: function () { if (current) { current.reverse(); } }
				});
			}

			SplitText.create(target, {
				type: 'lines',
				mask: 'lines',
				linesClass: 'bb-line',
				autoSplit: true, // re-split au chargement des polices et au resize
				onSplit: function (self) {
					self.masks.forEach(function (m) { m.classList.add('bb-line-mask'); });
					gsap.set(el, { autoAlpha: 1 });
					// Retourner l'animation permet à SplitText de conserver sa progression lors d'un re-split.
					current = gsap.from(self.lines, Object.assign({
						yPercent: 110,
						duration: num(el, 'bbDuration', cfg.duration),
						delay: num(el, 'bbDelay', 0),
						stagger: num(el, 'bbStagger', 0.08),
						ease: cfg.ease
					}, drive(el, gate, host)));
					if (gate && gate.armed) { current.play(); }
					return current;
				}
			});
		}

		// Déclencheur scrubé : dans une piste horizontale, les mots-clés d'axe changent
		// (top/bottom → left/right) et la progression est celle de la piste, pas celle de la page.
		function scrubTrigger(el, host, start, end, hStart, hEnd) {
			var st = { trigger: el, start: host ? hStart : start, end: host ? hEnd : end, scrub: true };
			if (host) { st.containerAnimation = host; }
			return st;
		}

		function scrub(el) {
			gsap.fromTo(
				el,
				{ autoAlpha: 0.15, scale: 0.94 },
				{
					autoAlpha: 1,
					scale: 1,
					ease: 'none',
					scrollTrigger: scrubTrigger(el, hostTl, 'top bottom', 'center 60%', 'left right', 'center 60%')
				}
			);
		}

		function parallax(el) {
			var amount = num(el, 'bbParallax', 10);
			gsap.fromTo(
				el,
				{ yPercent: -amount },
				{
					yPercent: amount,
					ease: 'none',
					scrollTrigger: scrubTrigger(el, hostTl, 'top bottom', 'bottom top', 'left right', 'right left')
				}
			);
		}

		// ---- bb-ink : révélation « tache d'encre » entre deux images (masque SVG + turbulence) -------
		var inkCount = 0;
		var SVG_NS = 'http://www.w3.org/2000/svg';
		var XLINK_NS = 'http://www.w3.org/1999/xlink';

		function svgEl(name, attrs, parent) {
			var node = document.createElementNS(SVG_NS, name);
			Object.keys(attrs || {}).forEach(function (k) { node.setAttribute(k, attrs[k]); });
			if (parent) { parent.appendChild(node); }
			return node;
		}

		// Construit la couche SVG au-dessus de la première image, avec la seconde masquée par un cercle
		// dont le bord est déchiqueté par un filtre de turbulence. Retourne { circle, maxR } ou null.
		function inkBuild(el) {
			var imgs = el.querySelectorAll('img');
			if (imgs.length < 2 || el.querySelector(':scope .bb-ink-layer')) { return null; }
			var base = imgs[0];
			var top = imgs[1];
			var id = 'bb-ink-' + (++inkCount);
			var origin = (el.dataset.bbInkOrigin || '50 50').split(/[\s,]+/);
			var scale = num(el, 'bbInkScale', 100);
			var freq = num(el, 'bbInkFreq', 0.05);
			var seed = num(el, 'bbInkSeed', 5);

			// Le cadre de l'animation est l'image de base elle-même (souvent centrée, plus étroite que
			// son widget). La couche SVG est posée dans le parent direct de l'image et calée en pixels
			// sur son rectangle, recalée à chaque refresh (resize, chargement) : aucun cadre ajouté,
			// donc aucune interaction avec les largeurs en % réglées dans Elementor.
			el.classList.add('bb-ink-ready');
			var host = base.parentElement;
			host.classList.add('bb-ink-host');
			var topWidget = top.closest('.elementor-widget-image') || top;
			if (!topWidget.contains(base)) { topWidget.classList.add('bb-ink-source'); }

			// ViewBox 1000 × ratio de l'image : le filtre travaille en unités stables quelle que soit
			// la largeur rendue (baseFrequency et scale gardent le même rendu sur mobile et desktop).
			var ratio = (base.naturalHeight && base.naturalWidth) ? base.naturalHeight / base.naturalWidth
				: (base.offsetHeight && base.offsetWidth) ? base.offsetHeight / base.offsetWidth : 0.6;
			var W = 1000;
			var H = Math.round(W * ratio * 1000) / 1000;

			var svg = svgEl('svg', { 'class': 'bb-ink-layer', viewBox: '0 0 ' + W + ' ' + H, preserveAspectRatio: 'none', 'aria-hidden': 'true' });
			var defs = svgEl('defs', {}, svg);
			var filter = svgEl('filter', { id: id + '-f', x: '-20%', y: '-20%', width: '140%', height: '140%', 'color-interpolation-filters': 'sRGB' }, defs);
			svgEl('feTurbulence', { type: 'fractalNoise', baseFrequency: freq + ' ' + (freq * 1.2), numOctaves: 4, seed: seed, result: 'noise' }, filter);
			svgEl('feDisplacementMap', { 'in': 'SourceGraphic', in2: 'noise', scale: scale, xChannelSelector: 'R', yChannelSelector: 'G', result: 'disp' }, filter);
			svgEl('feGaussianBlur', { 'in': 'disp', stdDeviation: 1.8, result: 'blurred' }, filter);
			var ct = svgEl('feComponentTransfer', { 'in': 'blurred', result: 'contrast' }, filter);
			svgEl('feFuncA', { type: 'linear', slope: 2.2, intercept: -0.6 }, ct);
			var mask = svgEl('mask', { id: id + '-m', maskContentUnits: 'userSpaceOnUse' }, defs);
			var circle = svgEl('circle', { cx: (parseFloat(origin[0]) || 50) + '%', cy: (parseFloat(origin[1]) || 50) + '%', r: 0, fill: 'white', filter: 'url(#' + id + '-f)' }, mask);
			var image = svgEl('image', { x: 0, y: 0, width: '100%', height: '100%', preserveAspectRatio: 'none', mask: 'url(#' + id + '-m)' }, svg);
			image.setAttribute('href', top.currentSrc || top.src);
			image.setAttributeNS(XLINK_NS, 'xlink:href', top.currentSrc || top.src);
			host.appendChild(svg);

			function fit() {
				svg.style.left = base.offsetLeft + 'px';
				svg.style.top = base.offsetTop + 'px';
				svg.style.width = base.offsetWidth + 'px';
				svg.style.height = base.offsetHeight + 'px';
			}
			fit();
			ScrollTrigger.addEventListener('refresh', fit);
			base.addEventListener('load', function () { fit(); ScrollTrigger.refresh(); });

			// Rayon couvrant tout le cadre depuis l'origine, marge pour le déchiquetage.
			var ox = W * (parseFloat(origin[0]) || 50) / 100;
			var oy = H * (parseFloat(origin[1]) || 50) / 100;
			var maxR = Math.ceil(Math.sqrt(Math.pow(Math.max(ox, W - ox), 2) + Math.pow(Math.max(oy, H - oy), 2)) + scale);
			return { circle: circle, maxR: maxR };
		}

		// Tache pilotée par le timeline d'une section bb-steps : entre start % et end % de sa progression.
		function inkInSteps(el, tl, total) {
			var built = inkBuild(el);
			if (!built) { return; }
			var start = Math.min(100, Math.max(0, num(el, 'bbInkStart', 50))) / 100;
			var end = Math.min(100, Math.max(start, num(el, 'bbInkEnd', 70) / 100));
			tl.fromTo(built.circle, { attr: { r: 0 } }, { attr: { r: built.maxR }, duration: Math.max(0.01, (end - start) * total), ease: 'power1.inOut' }, start * total);
		}

		// Tache autonome : liée au passage de l'élément dans l'écran (scrub), entre start % et end %.
		function ink(el) {
			if (el.closest('.bb-steps')) { return; } // pilotée par la section, voir stepsPinned
			var built = inkBuild(el);
			if (!built) { return; }
			var start = Math.min(100, Math.max(0, num(el, 'bbInkStart', 30)));
			var end = Math.min(100, Math.max(start, num(el, 'bbInkEnd', 70)));
			// Dans une piste horizontale, la progression se mesure sur la largeur de l'élément.
			var host = hostTl;
			var edge = host ? 'left+=' : 'top+=';
			var size = function () { return host ? el.offsetWidth : el.offsetHeight; };
			var st = {
				trigger: el,
				start: function () { return edge + Math.round(size() * start / 100) + (host ? ' right' : ' bottom'); },
				end: function () { return edge + Math.round(size() * end / 100) + ' center'; },
				scrub: 0.4,
				invalidateOnRefresh: true
			};
			if (host) { st.containerAnimation = host; }
			gsap.fromTo(
				built.circle,
				{ attr: { r: 0 } },
				{
					attr: { r: built.maxR },
					ease: 'power1.inOut',
					scrollTrigger: st
				}
			);
		}

		// Le masquage porte sur la section épinglée elle-même, pas sur la ligne des étapes :
		// c'est la section qui occupe l'écran, donc son bord bas est le « bas » d'où les étapes montent.
		// Masquer la ligne des étapes ferait démarrer le glissement sous cette ligne, souvent en haut
		// de l'écran, au lieu du bas de la section.
		function clipSteps(el) {
			el.classList.add('bb-steps-clip');
			return function () {
				el.classList.remove('bb-steps-clip');
			};
		}

		// yPercent: 100 descend l'étape de sa propre hauteur ; il faut y ajouter la distance
		// qui la sépare encore du bord bas de la section pour qu'elle en sorte entièrement.
		// Mesuré avant toute transformation, donc les rects sont ceux du repos.
		function slideSlack(el, step) {
			return Math.max(0, Math.ceil(el.getBoundingClientRect().bottom - step.getBoundingClientRect().bottom)) + 1;
		}

		// Hauteur d'un header fixe ou sticky collé en haut de la fenêtre (header Elementor ou autre) :
		// la section épinglée se cale sous lui, sinon il masque le haut des étapes.
		// data-bb-offset (px) sur la section force la valeur ; cfg.stepsOffset : 'auto' ou un nombre.
		function topBarOffset(el) {
			var forced = parseFloat(el.dataset.bbOffset);
			if (isFinite(forced)) { return Math.max(0, forced); }
			if (cfg.stepsOffset !== 'auto') { return Math.max(0, parseFloat(cfg.stepsOffset) || 0); }

			var candidates = document.querySelectorAll(
				'header, header *, .elementor-location-header, .elementor-location-header *, ' +
				'[data-elementor-type="header"], [data-elementor-type="header"] *'
			);
			var best = 0;
			Array.prototype.forEach.call(candidates, function (node) {
				if (!node.offsetWidth || el.contains(node)) { return; }
				if (node.offsetWidth < window.innerWidth * 0.8 || node.offsetHeight > window.innerHeight * 0.4) { return; }
				var cs = getComputedStyle(node);
				var fixedNow = (cs.position === 'fixed' || cs.position === 'sticky') && parseFloat(cs.top) <= 0;
				// Effet « Sticky » d'Elementor Pro : l'élément ne devient fixe qu'une fois scrollé, donc au
				// chargement il est encore en position relative. On se fie à son réglage plutôt qu'à son état.
				// (la classe elementor-sticky n'est posée qu'après notre init : on lit le réglage brut)
				var stickyLater = /"sticky"\s*:\s*"top"/.test(node.getAttribute('data-settings') || '');
				if (!fixedNow && !stickyLater) { return; }
				best = Math.max(best, node.offsetHeight);
			});
			return Math.round(best);
		}

		// Façon Heron : la ligne des étapes est calée en bas de la section et chaque étape garde sa
		// hauteur naturelle. Une étape ne parcourt ainsi que sa propre hauteur en montant depuis le bord
		// bas, au lieu de traverser tout l'écran (ce que produit l'étirement par défaut d'Elementor).
		// La ligne peut être imbriquée (conteneur boxed > .e-con-inner > ligne) : on pousse en bas chaque
		// maillon de la chaîne, sans toucher au contenu placé au-dessus (titre, visuel…).
		// Retourne une fonction qui restaure les styles inline posés.
		function anchorStepsBottom(el, steps) {
			var saved = [];
			function set(node, prop, value) {
				saved.push([node, prop, node.style.getPropertyValue(prop), node.style.getPropertyPriority(prop)]);
				node.style.setProperty(prop, value, 'important');
			}
			function isRow(node) {
				var cs = getComputedStyle(node);
				return cs.display.indexOf('flex') !== -1 && cs.flexDirection.indexOf('row') === 0;
			}
			// Ligne(s) contenant les étapes : les étapes s'alignent en bas de leur ligne.
			var rows = [];
			steps.forEach(function (step) {
				var row = step.parentElement;
				if (row && rows.indexOf(row) === -1) { rows.push(row); }
			});
			rows.forEach(function (row) {
				// Seul l'étirement par défaut (stretch) est retiré : les étapes gardent leur hauteur
				// naturelle, alignées en haut de la ligne. Un alignement choisi dans Elementor est conservé.
				if (isRow(row)) {
					var ai = getComputedStyle(row).alignItems;
					if (ai === 'normal' || ai === 'stretch') {
						set(row, 'align-items', 'flex-start');
						set(row, '--align-items', 'flex-start');
					}
				}
				// Chaque maillon entre la ligne et la section est poussé en bas de son parent.
				var node = row;
				while (node && node !== el) {
					var parent = node.parentElement;
					if (!parent) { break; }
					if (isRow(parent)) {
						set(node, 'align-self', 'flex-end');
					} else {
						set(node, 'margin-top', 'auto');
					}
					node = parent;
				}
			});
			return function () {
				saved.forEach(function (s) {
					if (s[2]) { s[0].style.setProperty(s[1], s[2], s[3]); } else { s[0].style.removeProperty(s[1]); }
				});
			};
		}

		// Sous un header fixe, la section doit occuper l'écran restant : son bord bas reste le bas de la
		// fenêtre, d'où partent les étapes. Retourne une fonction qui restaure la hauteur.
		function fitViewport(el, offset) {
			var prev = [el.style.getPropertyValue('min-height'), el.style.getPropertyPriority('min-height')];
			el.style.setProperty('min-height', 'calc(100vh - ' + offset + 'px)', 'important');
			el.style.setProperty('min-height', 'calc(100svh - ' + offset + 'px)', 'important'); // ignoré si svh non supporté
			return function () {
				if (prev[0]) { el.style.setProperty('min-height', prev[0], prev[1]); } else { el.style.removeProperty('min-height'); }
			};
		}

		// Section épinglée : chaque étape apparaît quand on scrolle, en avant comme en arrière.
		// Retourne une fonction de nettoyage pour gsap.matchMedia.
		function stepsPinned(el, steps) {
			var distance = num(el, 'bbDistance', 60);
			var slide = el.dataset.bbMode === 'slide';
			var y = num(el, 'bbY', 60);
			var offset = topBarOffset(el);
			var cleanups = [];
			// Section plus courte que l'écran (cas courant : elle ne contient que les étapes) : elle est
			// épinglée par son bord bas au bas de la fenêtre, à sa hauteur naturelle. Les étapes montent
			// depuis ce bord. Une section plus haute que l'écran est épinglée par le haut, sous le header,
			// et ramenée à la hauteur de l'écran pour que son bord bas reste le bas de la fenêtre.
			// data-bb-pin : "top" force l'épinglage plein écran (la section est portée à la hauteur de la
			// fenêtre : tout ce qu'elle contient se fige, comme chez Heron), "bottom" force le pin par le bas.
			var pinMode = el.dataset.bbPin || 'auto';
			var tall = pinMode === 'top' || (pinMode !== 'bottom' && el.offsetHeight > window.innerHeight - offset);
			if (slide) {
				cleanups.push(clipSteps(el));
				cleanups.push(anchorStepsBottom(el, steps));
				if (tall) { cleanups.push(fitViewport(el, offset)); }
			} else if (el.offsetHeight < (window.innerHeight - offset) * 0.9 && window.console) {
				// En fondu, une section plus courte que la fenêtre laisse une bande vide sous elle pendant l'épinglage.
				console.warn(
					'[bb-reveal] .bb-steps mesure ' + el.offsetHeight + 'px pour une fenêtre de ' + (window.innerHeight - offset) +
					'px : une bande vide restera sous la section pendant l\'épinglage. Lui donner une hauteur minimale de 100vh.'
				);
			}
			function pinStart() {
				if (el.dataset.bbStart) { return el.dataset.bbStart; }
				if (slide && !tall) { return 'bottom bottom'; }
				return offset > 0 ? 'top ' + offset + 'px' : 'top top';
			}
			var tl = gsap.timeline({
				defaults: { ease: 'none' },
				scrollTrigger: {
					trigger: el,
					start: pinStart,
					end: function () {
						return '+=' + Math.round(window.innerHeight * (distance / 100) * (steps.length + 0.5));
					},
					pin: true,
					scrub: 0.6,
					anticipatePin: 1,
					invalidateOnRefresh: true
				}
			});

			steps.forEach(function (step, i) {
				// Les animations contenues dans l'étape partent avec elle (dès le début de sa montée) et
				// restent acquises, comme chez Heron. data-bb-replay (sur la section ou l'étape) les
				// rembobine dès que l'étape repart en arrière.
				var replay = 'bbReplay' in el.dataset || 'bbReplay' in step.dataset;
				var lastProgress = 0;
				var state = {
					onStart: function () { step.classList.add('is-active'); },
					// Sous scrub, le sens se lit sur la progression (reversed() ne bouge pas). Le lancement
					// attend un vrai début de montée (2 %) : le lissage du scrub peut frémir à 0,0001.
					onUpdate: function () {
						var pr = this.progress();
						if (pr >= 0.02 && pr >= lastProgress) { gatePlay(step); }
						if (replay && pr < lastProgress) { gateReverse(step); }
						lastProgress = pr;
					},
					onReverseComplete: function () { step.classList.remove('is-active'); if (replay) { gateReverse(step); } }
				};

				if (slide) {
					// L'étape monte depuis sous sa propre ligne, débord masqué par .bb-steps-clip.
					// Le slack est une valeur fonctionnelle : ScrollTrigger (invalidateOnRefresh) la ré-évalue
					// à chaque refresh, une fois l'animation remise au repos, donc sur des mesures justes.
					tl.fromTo(
						step,
						{ autoAlpha: 1, yPercent: 100, y: function () { return slideSlack(el, step); } },
						{
							yPercent: 0,
							y: 0,
							duration: 1,
							ease: 'power2.out',
							onStart: state.onStart,
							onUpdate: state.onUpdate,
							onReverseComplete: state.onReverseComplete
						},
						i
					);
					return; // le glissement est la révélation : pas de cascade interne en plus
				}

				tl.fromTo(
					step,
					{ autoAlpha: 0, y: y },
					{
						autoAlpha: 1,
						y: 0,
						duration: 1,
						ease: 'power2.out',
						onStart: state.onStart,
						onUpdate: state.onUpdate,
						onReverseComplete: state.onReverseComplete
					},
					i
				);

				// Contenu interne de l'étape (numéro, icône, titre, texte) en cascade légère.
				var inner = childrenOf(step);
				if (inner.length > 1) {
					tl.fromTo(
						inner,
						{ autoAlpha: 0, y: 20 },
						{ autoAlpha: 1, y: 0, duration: 0.5, stagger: 0.15, ease: 'power2.out' },
						i + 0.3
					);
				}
			});

			// Maintien de la dernière étape avant de libérer l'épinglage.
			tl.to({}, { duration: 0.5 });

			// Taches d'encre de la section : calées sur la progression du même timeline.
			var total = tl.duration();
			Array.prototype.forEach.call(el.querySelectorAll('.bb-ink'), function (inkEl) {
				safe(function (node) { inkInSteps(node, tl, total); })(inkEl);
			});

			return function () {
				cleanups.forEach(function (fn) { if (typeof fn === 'function') { fn(); } });
			};
		}

		function stepsSection(el) {
			var steps = Array.prototype.slice.call(el.querySelectorAll('.bb-step'));
			if (!steps.length) {
				steps = childrenOf(el);
			}
			gsap.set(el, { autoAlpha: 1 });
			if (!steps.length) {
				return;
			}

			var bp = parseInt(cfg.stepsBreakpoint, 10) || 768;
			var mm = gsap.matchMedia();
			mm.add(
				{ desktop: '(min-width: ' + bp + 'px)', mobile: '(max-width: ' + (bp - 1) + 'px)' },
				function (ctx) {
					if (ctx.conditions.desktop) {
						// Le retour est appelé par matchMedia au passage sous le breakpoint (retire le clip).
						return stepsPinned(el, steps);
					} else {
						steps.forEach(function (step) {
							step.classList.add('is-active');
							reveal(step, { onStart: function () { gatePlay(step); } });
						});
						// Sous le breakpoint, pas d'épinglage : la tache suit le passage dans l'écran.
						Array.prototype.forEach.call(el.querySelectorAll('.bb-ink'), function (inkEl) {
							var built = inkBuild(inkEl);
							if (!built) { return; }
							gsap.fromTo(built.circle, { attr: { r: 0 } }, {
								attr: { r: built.maxR }, ease: 'power1.inOut',
								scrollTrigger: { trigger: inkEl, start: 'top 80%', end: 'center 40%', scrub: 0.4, invalidateOnRefresh: true }
							});
						});
					}
				}
			);
		}

		// ---- bb-hscroll : section épinglée dont la piste de panneaux défile horizontalement --------
		// Principe : la section est épinglée, et la piste (le parent commun des panneaux) est
		// translatée en x au fil du scroll vertical. Les animations contenues dans les panneaux sont
		// câblées sur ce timeline via containerAnimation, sans quoi leur position dans l'écran ne
		// changerait jamais dans l'axe vertical et elles se déclencheraient toutes en même temps.

		// Panneaux explicites, sinon les enfants directs de la section selon la structure Elementor.
		function hpanelsOf(el) {
			var panels = Array.prototype.slice.call(el.querySelectorAll('.bb-hpanel'));
			return panels.length ? panels : childrenOf(el);
		}

		// Une piste imbriquée dans une section épinglée (bb-steps, ou une autre piste) donnerait deux
		// épinglages concurrents, et containerAnimation interdit d'épingler dans une piste.
		function hscrollNested(el) {
			var outer = el.parentElement && el.parentElement.closest('.bb-hscroll');
			if (outer || el.closest('.bb-steps')) {
				if (window.console) {
					console.warn('[bb-reveal] .bb-hscroll ignorée : une piste horizontale ne peut pas être imbriquée dans une section épinglée. Retirer la classe de', el);
				}
				// La classe est retirée pour que ce qu'elle contient reste visible : les passages
				// globaux ignorent les descendants d'une .bb-hscroll, qu'elle soit active ou non.
				el.classList.remove('bb-hscroll');
				return true;
			}
			return false;
		}

		// Câble les animations d'une section bb-hscroll. hostTl est posé par l'appelant en mode
		// horizontal, laissé à null sous le breakpoint : les mêmes fonctions servent dans les deux cas.
		function hscrollInner(el) {
			// Une section bb-steps dans un panneau ne peut pas s'épingler : sa classe est retirée pour
			// que ses étapes redeviennent de simples révélations (sinon gateOf les mettrait en pause
			// en attendant un épinglage qui n'aura pas lieu, et elles resteraient masquées).
			Array.prototype.forEach.call(el.querySelectorAll('.bb-steps'), function (nested) {
				nested.classList.remove('bb-steps');
				if (window.console) {
					console.warn('[bb-reveal] .bb-steps dans une piste bb-hscroll : épinglage impossible, les étapes sont simplement révélées.', nested);
				}
			});

			var pass = [
				['.bb-reveal', reveal],
				['.bb-reveal-children', revealChildren],
				['.bb-split', splitReveal],
				['.bb-step:not(.bb-reveal)', reveal],
				['.bb-scrub', scrub],
				['.bb-parallax', parallax],
				['.bb-ink', ink]
			];
			pass.forEach(function (entry) {
				Array.prototype.forEach.call(el.querySelectorAll(entry[0]), safe(entry[1]));
			});
		}

		function hscrollPinned(el, panels) {
			var track = panels[0].parentElement;

			// La piste doit pouvoir être mise en ligne et translatée sans emporter la section épinglée.
			if (!track || track === el) {
				if (window.console) {
					console.warn('[bb-reveal] .bb-hscroll : les panneaux doivent être dans un conteneur interne. Placer les panneaux dans un conteneur Elementor à l\'intérieur de la section.', el);
				}
				hscrollInner(el); // hostTl est null : les animations des panneaux restent verticales.
				return null;
			}

			var rtl = el.dataset.bbDirection === 'rtl';
			var distance = num(el, 'bbDistance', 100);
			var offset = topBarOffset(el);
			var width = ( el.dataset.bbWidth || '' ).trim();
			var saved = [];

			function set(node, prop, value) {
				saved.push({ node: node, prop: prop, value: node.style[prop] });
				node.style[prop] = value;
			}

			// La section est le cadre visible : elle masque la partie de la piste qui la déborde.
			el.classList.add('bb-hscroll-clip');
			set(track, 'display', 'flex');
			set(track, 'flexDirection', rtl ? 'row-reverse' : 'row');
			set(track, 'flexWrap', 'nowrap');
			set(track, 'alignItems', 'stretch');
			set(track, 'width', 'max-content');
			set(track, 'maxWidth', 'none');
			// Un conteneur Elementor « boxed » centre sa piste interne (align-items / margin auto) :
			// une fois élargie en max-content, elle déborderait des deux côtés et le premier panneau
			// serait tronqué autant que le dernier. Les marges auto priment sur l'alignement du parent.
			set(track, 'marginLeft', '0');
			set(track, 'marginRight', 'auto');
			panels.forEach(function (panel) {
				set(panel, 'flex', '0 0 auto');
				if (width) {
					set(panel, 'width', /^[\d.]+$/.test(width) ? width + 'vw' : width);
				}
			});

			// Cadre visible pour la piste. overflow:hidden coupe au padding box (largeur clientWidth),
			// et la piste y démarre décalée du padding gauche : c'est ce décalage, et lui seul, qui
			// raccourcit la course. Le padding droit n'est pas une limite visuelle — du contenu s'y
			// affiche — le déduire laisserait un vide après le dernier panneau.
			function frame() {
				var cs = window.getComputedStyle(el);
				return Math.max(1, el.clientWidth - (parseFloat(cs.paddingLeft) || 0));
			}

			// Course horizontale : ce qui dépasse du cadre. Recalculée à chaque refresh (images, polices).
			function travel() {
				return Math.max(0, track.scrollWidth - frame());
			}

			// Piège classique : les conteneurs Elementor ont un padding par défaut. Sur la piste, il
			// s'ajoute à sa largeur et laisse un vide après le dernier panneau — la piste est bien
			// défilée en entier, mais son bord n'est pas celui du dernier panneau.
			( function () {
				var cs = window.getComputedStyle(track);
				var pad = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
				if (pad > 0 && window.console) {
					console.warn(
						'[bb-reveal] .bb-hscroll : le conteneur piste a ' + pad + 'px de padding horizontal, qui apparaîtra comme un vide au début et à la fin du défilement. Mettre son padding gauche/droite à 0 et le porter sur les panneaux.', track
					);
				}
			}() );

			if (travel() < 1 && window.console) {
				console.warn(
					'[bb-reveal] .bb-hscroll : la piste (' + track.scrollWidth + 'px) ne dépasse pas le cadre visible (' + frame() +
					'px), il n\'y a rien à faire défiler. Donner une largeur aux panneaux (data-bb-width, ou une largeur en vw dans Elementor).', el
				);
			}

			var tl = gsap.timeline({
				defaults: { ease: 'none' },
				scrollTrigger: {
					trigger: el,
					start: el.dataset.bbStart || ( offset > 0 ? 'top ' + offset + 'px' : 'top top' ),
					end: function () { return '+=' + Math.round(travel() * (distance / 100)); },
					pin: true,
					scrub: 0.6,
					anticipatePin: 1,
					invalidateOnRefresh: true
				}
			});

			// En rtl la piste est en row-reverse : le premier panneau est à son bord droit, donc elle
			// démarre décalée vers la gauche et revient à 0.
			tl.fromTo(
				track,
				{ x: function () { return rtl ? -travel() : 0; } },
				{ x: function () { return rtl ? 0 : -travel(); } }
			);

			hostTl = tl;
			try {
				hscrollInner(el);
			} finally {
				hostTl = null;
			}

			// Appelé par matchMedia au passage sous le breakpoint : les tweens et ScrollTrigger créés
			// dans le contexte sont rembobinés par GSAP, restent les styles de mise en ligne.
			return function () {
				el.classList.remove('bb-hscroll-clip');
				saved.forEach(function (s) {
					s.node.style[s.prop] = s.value;
				});
				saved = [];
			};
		}

		function hscrollSection(el) {
			var panels = hpanelsOf(el);
			gsap.set(el, { autoAlpha: 1 });
			if (!panels.length) {
				return;
			}

			var bp = parseInt(cfg.hscrollBreakpoint, 10) || 768;
			var mm = gsap.matchMedia();
			mm.add(
				{ desktop: '(min-width: ' + bp + 'px)', mobile: '(max-width: ' + (bp - 1) + 'px)' },
				function (ctx) {
					if (ctx.conditions.desktop) {
						return hscrollPinned(el, panels);
					}
					// Sous le breakpoint : pas d'épinglage ni de mise en ligne, les panneaux gardent la
					// mise en page Elementor et leur contenu se révèle à son entrée à l'écran.
					hscrollInner(el);
				}
			);
		}

		function each(selector, fn) {
			Array.prototype.forEach.call(document.querySelectorAll(selector), fn);
		}

		// Un élément situé dans une piste horizontale est câblé par sa section (déclencheurs exprimés
		// dans l'axe de la piste, et recréés à chaque passage du breakpoint) : le passage global l'ignore.
		function outsideTracks(fn) {
			return function (el) {
				if (!el.closest('.bb-hscroll')) { fn(el); }
			};
		}

		// Firefox défile de façon asynchrone : un élément épinglé (position:fixed) est recalé une frame
		// après le défilement, d'où un saut visible sous le header. normalizeScroll fait piloter le
		// défilement par ScrollTrigger (synchrone avec le rendu). Inutile avec Lenis, qui a le même effet.
		if (cfg.normalizeScroll && !cfg.smooth && ScrollTrigger.normalizeScroll) {
			ScrollTrigger.normalizeScroll(true);
		}

		if (cfg.smooth && window.Lenis) {
			// lerp : part du chemin parcourue à chaque frame (0,1 = réactif, 0,05 = très doux).
			// Réglable via $cfg['lenis'] = ['lerp' => 0.07, 'wheelMultiplier' => 1].
			var lenis = new window.Lenis(Object.assign({ lerp: 0.07, wheelMultiplier: 1, anchors: true }, cfg.lenis || {}));
			window.bbLenis = lenis;
			lenis.on('scroll', ScrollTrigger.update);
			gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
			gsap.ticker.lagSmoothing(0);
		}

		// Chaque élément est isolé : une erreur sur un bloc ne doit jamais laisser la page masquée.
		function safe(fn) {
			return function (el) {
				try {
					fn(el);
				} catch (e) {
					gsap.set(el, { autoAlpha: 1, clearProps: 'transform' });
					if (window.console) { console.warn('[bb-reveal]', e); }
				}
			};
		}

		// Les sections épinglées d'abord : leur espacement doit exister avant le calcul des autres
		// déclencheurs. Les pistes horizontales avant les bb-steps : elles câblent elles-mêmes tout ce
		// qu'elles contiennent, y compris les sections bb-steps qu'elles déclassent.
		each('.bb-hscroll', safe(function (el) {
			// Piste ignorée : le CSS de garde la masque, il faut la rendre visible nous-mêmes.
			if (hscrollNested(el)) {
				gsap.set(el, { autoAlpha: 1 });
				return;
			}
			hscrollSection(el);
		}));
		each('.bb-steps', outsideTracks(safe(function (el) {
			// Une section imbriquée dans une autre section bb-steps est ignorée : deux épinglages l'un
			// dans l'autre se disputeraient les mêmes étapes (cas typique : classe oubliée sur la ligne
			// après l'avoir posée sur le conteneur parent).
			var outer = el.parentElement && el.parentElement.closest('.bb-steps');
			if (outer) {
				if (window.console) { console.warn('[bb-reveal] .bb-steps imbriquée ignorée : retirer la classe bb-steps et data-bb-mode de', el); }
				return;
			}
			stepsSection(el);
		})));
		Array.prototype.forEach.call(document.querySelectorAll('.bb-step'), outsideTracks(function (step) {
			if (!step.closest('.bb-steps')) { safe(reveal)(step); } // étape orpheline : simple révélation
		}));
		each('.bb-reveal', outsideTracks(safe(reveal)));
		each('.bb-reveal-children', outsideTracks(safe(revealChildren)));
		each('.bb-split', outsideTracks(safe(splitReveal)));
		each('.bb-scrub', outsideTracks(safe(scrub)));
		each('.bb-parallax', outsideTracks(safe(parallax)));
		each('.bb-ink', outsideTracks(safe(ink)));

		release();

		// Hauteurs recalculées après images lazy-load, widgets Elementor et polices.
		window.addEventListener('load', function () { ScrollTrigger.refresh(); });
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

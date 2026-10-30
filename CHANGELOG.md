# Changelog

Toutes les modifications notables de **BB Scroll Reveal** sont documentées ici.

Le format suit Keep a Changelog et le projet adhère au Semantic Versioning. Les versions antérieures à la 1.7.3 précèdent le dépôt Git et ne sont pas datées.

## [1.11.0] — 2026-10-30

### Ajouté
- **Mises à jour automatiques** depuis le dépôt GitHub public (`lib/GitHubUpdater.php`, branche `main`, sans jeton), avec ce changelog dans la fenêtre de détails de la mise à jour. En-têtes `Plugin URI`, `Update URI`, `Author: Team bleuebuzz`, `Author URI` et `Primary Branch: main`.
- **Icône du plugin** (`assets/img/icon.svg`, cartouche « BB ») après « Afficher les détails » dans la liste des extensions, et dans l'écran des mises à jour.

### Modifié
- **PHP 8.0 requis** (au lieu de 7.4), imposé par la lib de mise à jour.
- Page de réglages : logo de la gamme BB (cartouche « BB » devant « Scroll Reveal », en Sora hébergée dans `assets/fonts/`). `window.BB_REVEAL` inchangé.
- L'historique des versions quitte le README pour ce fichier.

## [1.10.1] — 2026-10-26

### Corrigé
- Lenis : `allowNestedScroll` actif par défaut. Lenis captait la molette partout, et une zone défilante de la page (panneau latéral, menu, liste) ne défilait plus : c'était la page qui bougeait. Se coupe via `$cfg['lenis']['allowNestedScroll'] = false;`. `window.BB_REVEAL` inchangé (le défaut est dans `bb-reveal.js`).

## [1.10.0] — 2026-10-05

### Ajouté
- **Vidéo lue au scroll** : classe `bb-video-scroll` et widget Elementor **BB Vidéo au scroll**. La vidéo (MP4/WebM hébergé) avance et recule avec le scroll, épinglée par défaut, avec arrêts sur image (`data-bb-holds`), textes superposés calés sur la vidéo (`bb-video-text` + `data-bb-at`) et boutons lecture/pause en option. Chargement complet en mémoire par défaut pour un scrub fluide, repli en flux si le serveur le refuse. Voir « Vidéo lue au scroll » dans le README, dont la consigne d'encodage ffmpeg.
- Nouveau fichier `assets/bb-video.css`, chargé par le seul widget. L'animation est dans `bb-reveal.js` : le widget ne produit que le balisage et ses `data-bb-*`. Aucune nouvelle clé de configuration, `window.BB_REVEAL` inchangé.
- Page de réglages, onglet **Classes et attributs** : entrées de la vidéo au scroll (nouvelle famille, en rose).

## [1.9.1] — 2026-10-05

### Corrigé
- Widgets : le dépliement n'anime plus `grid-template-rows` (0fr → 1fr) mais la hauteur en pixels, posée par le JS. L'interpolation des pistes flexibles n'est pas fiable partout ; là où elle ne s'applique pas, la hauteur sautait pendant que le fondu du contenu continuait — le mouvement se lisait en deux temps. Le fondu et la translation du contenu sont supprimés : comme chez Heron, une seule propriété est animée.
- Ouverture et fermeture d'un même clic sont traitées en un seul lot (hauteurs de départ, classes, un recalcul de style, hauteurs d'arrivée) : les deux transitions démarrent dans la même frame.
- Couleur des titres : la propriété `color` est écrite en dur par le contrôle Elementor, et plus seulement via une variable CSS. La règle qui la consommait n'avait qu'un niveau de classe, et le `button { color }` de la plupart des thèmes passait devant — le réglage restait sans effet.

## [1.9.0] — 2026-10-05

### Ajouté
- **Module de widgets Elementor**, chargé uniquement quand Elementor est actif : **BB Liste dépliante** et **BB Image liée**, appariés par un identifiant de liaison. La liste porte les images de ses éléments ; le cadre image affiche celle de l'élément ouvert. Deux widgets et non un seul pour laisser la mise en page (colonnes, contenu libre au-dessus de la liste) entièrement à Elementor. Voir « Widgets Elementor » dans le README.
- Nouveaux fichiers `assets/bb-accordion.css` et `assets/bb-accordion.js`, chargés par les seuls widgets qui les demandent (`get_style_depends()` / `get_script_depends()`), éditeur compris. Aucun changement sur le front « classes CSS » : `window.BB_REVEAL`, les réglages et `bb-reveal.js` sont inchangés.

## [1.8.1] — 2026-10-05

### Corrigé
- Sections épinglées (`bb-steps`, `bb-hscroll`) : plus de saut à l'épinglage avec Lenis. `anticipatePin` (pré-épinglage selon la vitesse de scroll) figeait la section plusieurs dizaines de px avant son point de départ ; il n'est conservé qu'en défilement natif (`smooth` désactivé).

## [1.8.0] — 2026-09-16

### Modifié
- **Refonte de la page de réglages** sur le design system des plugins maison (celui de DC Support Technique) : header de marque, onglets en colonne, cards, interrupteurs, barre d'enregistrement collante avec état « modifications non enregistrées », colonne d'aide contextuelle et état du front.
- Onglet **Classes et attributs** : le rappel des classes et des `data-bb-*` a sa propre page au lieu d'être empilé sous le formulaire. Chaque entrée porte un pictogramme (SVG inline, sprite unique) dont la teinte dit la famille — révélations, effets liés au scroll, sections épinglées, défilement horizontal, tache d'encre. Réinitialisation déplacée dans la colonne de droite.
- Deux fichiers d'assets admin (`assets/admin.css`, `assets/admin.js`, chargés uniquement sur la page de réglages). Aucun changement de comportement sur le front : `window.BB_REVEAL`, la sanitisation et l'ordre de résolution des réglages sont inchangés.

## [1.7.3] — 2026-09-16

### Ajouté
- `bb-hscroll` : avertissement console quand le conteneur piste a un padding horizontal (valeur par défaut des conteneurs Elementor), qui se lit comme un vide au début et à la fin du défilement.

## [1.7.2]

### Corrigé
- `bb-hscroll` : seul le padding **gauche** de la section est déduit de la course. La 1.7.1 déduisait aussi le padding droit, qui n'est pas une limite visuelle (`overflow:hidden` coupe au padding box) : un vide de cette largeur apparaissait après le dernier panneau.

## [1.7.1]

### Corrigé
- `bb-hscroll` : course de défilement calculée sur le cadre visible de la section et non sur sa largeur totale. Le dernier panneau n'est plus tronqué de la valeur du padding gauche.
- `bb-hscroll` : centrage de la piste neutralisé (`margin-left:0`), un conteneur Elementor « boxed » la faisait déborder des deux côtés une fois élargie.

## [1.7.0]

### Ajouté
- **Défilement horizontal** `bb-hscroll` + `bb-hpanel` : section épinglée dont la piste de panneaux se translate au fil du scroll. Sens (`data-bb-direction`), longueur de course (`data-bb-distance`), largeur des panneaux (`data-bb-width`), breakpoint dans l'admin.
- Les animations placées dans les panneaux (`bb-reveal`, `bb-split`, `bb-scrub`, `bb-parallax`, `bb-ink`…) sont câblées sur la piste via `containerAnimation` : elles partent à l'arrivée du panneau. `data-bb-start` s'y écrit en horizontal (`left 85%`).
- Sous le breakpoint (768 px par défaut) le défilement horizontal est entièrement désactivé : les panneaux reprennent la mise en page Elementor et leur contenu se révèle normalement.

## [1.6.0]

### Ajouté
- **Page de réglages Réglages > BB Scroll Reveal** : tous les réglages globaux depuis l'admin, sans `functions.php`. Un champ vide garde le défaut du plugin ; le filtre `bb_reveal_config` reste prioritaire sur l'admin.
- Deux réglages d'exploitation sans équivalent dans `bb_reveal_config` : activation globale (coupe le plugin sur tout le site sans le désactiver) et liste de pages exclues par identifiant ou slug.
- Comportement inchangé tant qu'aucun réglage n'est enregistré : `window.BB_REVEAL` est identique à celui de la 1.5.1.

## [1.5.1]

### Ajouté
- Réglages Lenis exposés (`$cfg['lenis']`).

### Modifié
- `lerp` par défaut passé de 0.1 à 0.07 pour une inertie plus proche de Heron.

## [1.5.0]

### Modifié
- Animations internes aux étapes : lancement à 2 % de progression de l'étape (et non à la première frame), le lissage du scrub pouvant frémir avant l'épinglage.
- **Lenis (smooth scroll) actif par défaut** : supprime le saut d'épinglage sous Firefox et donne l'inertie de Heron. `$cfg['smooth'] = false;` pour revenir au défilement natif.

## [1.4.2]

### Ajouté
- Option `normalizeScroll` (défaut false) pour supprimer le saut de la section épinglée sous Firefox. Voir la section Firefox du README.

## [1.4.1]

### Modifié
- Animations internes aux étapes : lancées dès le début de la montée de l'étape (plus à la fin) et acquises par défaut. `data-bb-replay` pour le va-et-vient.

## [1.4.0]

### Modifié
- Les animations `bb-reveal`, `bb-reveal-children` et `bb-split` placées dans une étape `bb-step` n'ont plus de déclencheur de scroll (arbitraire pendant l'épinglage) : elles sont créées en pause et jouées par l'étape quand elle atteint sa position, rembobinées dès qu'elle la quitte. Sous le breakpoint mobile, jouées à la fin de la révélation de l'étape.

## [1.3.2]

### Modifié
- Mode slide : les étapes ne sont plus forcées en bas de leur ligne. Seul l'étirement par défaut d'Elementor est retiré (hauteur naturelle, alignement haut) ; un alignement choisi dans Elementor sur la ligne est conservé. Pour des étapes de même hauteur, leur donner une hauteur minimale dans Elementor.

## [1.3.1]

### Corrigé
- `bb-ink` : la couche SVG est calée en pixels sur le rectangle de l'image 1 (recalée au resize) et non plus sur son widget ; l'image 2 n'est plus étirée quand l'image 1 est centrée ou plus étroite que le conteneur.

## [1.3.0]

### Ajouté
- **Nouveau `bb-ink`** : révélation d'une seconde image par une tache d'encre (masque SVG + feTurbulence/feDisplacementMap), pilotée par la progression d'une section `bb-steps` ou par le passage dans l'écran.

## [1.2.9]

### Corrigé
- Une section `bb-steps` imbriquée dans une autre est ignorée (avertissement console) : évite deux épinglages concurrents quand la classe est restée sur la ligne après avoir été posée sur le conteneur parent.

## [1.2.8]

### Modifié
- Calage de la ligne des étapes en bas : gère l'imbrication (conteneur boxed > `.e-con-inner` > ligne) en poussant chaque maillon en bas de son parent (`margin-top:auto`), sans toucher au contenu placé au-dessus. Les règles CSS de calage de la 1.2.4 sont retirées au profit du JS.

## [1.2.7]

### Ajouté
- `data-bb-pin|top` / `bottom` sur la section slide pour forcer le mode d'épinglage. `top` porte la section à la hauteur de l'écran (sous le header) : tout son contenu se fige.

## [1.2.6]

### Corrigé
- Neutralisation de la transition Elementor sur `transform` : la variable lue par Elementor est `--e-con-transform-transition-duration` (pas `--transform-transition`). Appliquée aussi à la section `bb-steps` épinglée : sans cela, le `translateY` posé par ScrollTrigger au dépin était animé sur 0,4 s (la section remontait puis redescendait) et chaque frame de scrub était lissée (saccades).

## [1.2.5]

### Modifié
- Mode slide : plus de `min-height:100vh` imposée. Une section plus courte que l'écran est épinglée par son bord bas au bas de la fenêtre, à sa hauteur naturelle ; une section plus haute est épinglée par le haut sous le header.
- Détection du header sticky Elementor par son réglage (`data-settings`) et non plus par la classe `elementor-sticky`, posée après notre initialisation.

## [1.2.4]

### Ajouté
- Header fixe ou sticky détecté automatiquement (y compris l'effet « Sticky » d'Elementor Pro, encore en position relative au chargement) : la section est épinglée juste dessous et réduite d'autant. Réglages `data-bb-offset` et `$cfg['stepsOffset']`.

### Modifié
- `bb-steps` en mode slide : la ligne des étapes est calée en bas de la section et chaque étape garde sa hauteur naturelle (plus d'étirement Elementor), donc chaque étape ne parcourt que sa propre hauteur, comme chez Heron. Posé en CSS (anti-flash) et en inline par le JS (prioritaire sur le CSS Elementor), retiré sous le breakpoint.
- Le débord mesuré pour le glissement (`slack`) est une valeur fonctionnelle ré-évaluée par ScrollTrigger à chaque refresh (resize, polices, images), sur l'étape au repos.

### Corrigé
- Neutralisation de `transition: transform .4s` posée par Elementor sur les conteneurs, qui retardait chaque frame des animations.

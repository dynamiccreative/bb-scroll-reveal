# BB Scroll Reveal — bleuebuzz

Révélations au scroll façon GSAP/Webflow dans Elementor, pilotées par classes CSS. GSAP 3.15 (ScrollTrigger + SplitText) et Lenis 1.3 sont auto-hébergés dans `assets/vendor/` : aucun appel CDN tiers.

## Installation

Dézipper dans `wp-content/plugins/`, activer. Rien d'autre à configurer : les valeurs par défaut conviennent à la plupart des sites. Pour les ajuster sans toucher au code, voir « Réglages dans l'admin » plus bas.

## Utilisation dans Elementor

Onglet **Avancé > Classes CSS** de l'élément :

| Classe | Effet | Où la poser |
|---|---|---|
| `bb-reveal` | fade + montée, une seule fois | n'importe quel widget ou conteneur |
| `bb-reveal-children` | enfants directs révélés en cascade | conteneur (flex/grid), section, colonne |
| `bb-split` | texte révélé ligne par ligne sous masque | widget Titre ou Éditeur de texte |
| `bb-scrub` | opacité + échelle liées au scroll | bloc visuel, image, carte |
| `bb-parallax` | parallaxe verticale liée au scroll | image, fond décoratif |
| `bb-steps` | section épinglée pendant le scroll, étapes révélées une à une | conteneur de la section entière |
| `bb-step` | une étape pilotée par la section `bb-steps` parente | chaque bloc étape |
| `bb-hscroll` | section épinglée dont les panneaux défilent horizontalement | conteneur de la section entière |
| `bb-hpanel` | un panneau de la piste `bb-hscroll` parente | chaque conteneur panneau |
| `bb-ink` | seconde image révélée par une tache d'encre au scroll | conteneur de deux widgets Image |

Réglages fins par élément (Elementor Pro, **Avancé > Attributs**, une ligne par attribut) :

```
data-bb-y|60
data-bb-duration|1.2
data-bb-delay|0.2
data-bb-stagger|0.15
data-bb-start|top 70%
data-bb-parallax|15
data-bb-distance|60
data-bb-mode|slide
data-bb-direction|rtl
data-bb-width|60vw
```

**Important :** laisser l'« Animation d'entrée » native d'Elementor sur *Aucune* pour les éléments qui portent ces classes.

## Section « étapes » épinglée (bb-steps)

Structure Elementor type :

```
Conteneur section        → classe bb-steps   (c'est lui qui est épinglé)
├── Titre, sous-titre
└── Conteneur ligne
    ├── Conteneur étape 1 → classe bb-step
    ├── Conteneur étape 2 → classe bb-step
    └── Conteneur étape 3 → classe bb-step
```

Au-dessus du breakpoint (768 px par défaut), la section se fige quand son haut touche le haut de l'écran. Chaque étape apparaît au fil du scroll, puis son contenu interne (numéro, titre, texte) en cascade. Le mouvement suit le scroll dans les deux sens. La section se libère après la dernière étape.

Sous le breakpoint, pas d'épinglage : chaque étape se révèle simplement à son entrée à l'écran.

Chaque étape reçoit la classe `is-active` quand elle apparaît, pour styler l'état actif en CSS (couleur du numéro, bordure, icône) :

```css
.bb-step.is-active .step-number { color: var(--e-global-color-accent); }
```

Réglages sur le conteneur `bb-steps` :

- `data-bb-distance` : hauteur d'écran scrollée par étape, en %. 60 par défaut, soit environ deux écrans pour trois étapes.
- `data-bb-start` : point d'épinglage. Le header fixe est détecté automatiquement (voir prérequis) ; ne s'en servir que pour un cas particulier.
- `data-bb-offset` : hauteur (px) du header fixe sous lequel épingler, si la détection automatique se trompe.
- `data-bb-pin` (mode slide) : `top` = épinglage plein écran, la section est portée à la hauteur de la fenêtre et **tout ce qu'elle contient se fige** pendant que les étapes montent ; `bottom` = section collée en bas de l'écran à sa hauteur naturelle, le contenu au-dessus continue de défiler. Sans l'attribut : `bottom` si la section tient dans l'écran, `top` sinon.
- `data-bb-y` : décalage d'entrée des étapes, en px (mode fondu uniquement).
- `data-bb-mode|slide` : voir ci-dessous.

Si aucun enfant ne porte `bb-step`, les enfants directs du conteneur `bb-steps` sont utilisés comme étapes.

### Mode « slide » (`data-bb-mode|slide`)

Sur le conteneur `bb-steps`, l'attribut `data-bb-mode|slide` remplace le fondu par un glissement : chaque étape part du bord bas de la section et remonte à sa place, l'une après l'autre, façon Webflow (`heronaiapp.com`).

Le masquage est posé sur **la section épinglée elle-même** — le plugin lui ajoute `overflow:hidden` via la classe `bb-steps-clip`, et la retire sous le breakpoint :

```
Conteneur section        → bb-steps + data-bb-mode|slide
                           ← reçoit overflow:hidden, c'est le cadre de l'animation
├── Titre, sous-titre
└── Conteneur ligne
    ├── Conteneur étape 1 → bb-step
    ├── Conteneur étape 2 → bb-step
    └── Conteneur étape 3 → bb-step
```

C'est bien la section qui sert de cadre, pas la ligne des étapes : comme la section occupe l'écran pendant l'épinglage, son bord bas est le « bas » d'où les blocs montent. Masquer la ligne des étapes ferait démarrer le glissement juste sous cette ligne — donc souvent en haut de l'écran, sur une course de quelques dizaines de pixels au lieu d'une remontée depuis le bas.

C'est aussi pour cette raison que le plugin force la section à `100svh` en mode slide, voir les prérequis ci-dessous.

Le glissement fait office de révélation : la cascade du contenu interne de l'étape n'est pas rejouée en mode slide (elle l'est en mode fondu). Pour animer aussi l'intérieur, styler l'état actif en CSS via `.bb-step.is-active`, comme le fait Heron pour ses icônes :

```css
.bb-step .step-ic-done { opacity: 0; transition: opacity .4s; }
.bb-step.is-active .step-ic-done { opacity: 1; }
```

Le conteneur ligne ne doit pas avoir besoin de laisser déborder quoi que ce soit (ombres portées, badges hors cadre — ils seraient coupés).

### Prérequis de mise en page pour une section épinglée

**1. Section à hauteur naturelle, épinglée par le bas — automatique en mode slide (1.2.5).** Au-dessus du breakpoint, le plugin :

- épingle la section **par son bord bas au bas de la fenêtre** (`start: bottom bottom`) dès qu'elle est plus courte que l'écran : elle garde sa hauteur naturelle, arrive en scrollant, se fige en bas de l'écran le temps que les étapes montent, puis repart. Aucune hauteur minimale imposée ;
- cale la ligne des étapes en bas de la section (`margin-top:auto` sur chaque maillon) et rend à chaque `bb-step` sa hauteur naturelle (l'étirement `stretch` de la ligne est remplacé par `flex-start` ; un alignement réglé dans Elementor est conservé). Sans cela, Elementor étire les étapes sur toute la hauteur de la section et chaque étape traverse la section entière au lieu de ne parcourir que sa propre hauteur ;
- si la section est plus haute que l'écran (titre + visuel + étapes, comme chez Heron), elle est épinglée par le haut sous le header et ramenée à la hauteur de l'écran, pour que son bord bas reste le bas de la fenêtre.

Ces styles inline sont retirés sous le breakpoint. Les étapes partent du bord bas de la section : prévoir un **padding bas** sur la section pour que le dernier élément d'une étape (bouton) ne colle pas au bord de l'écran pendant l'épinglage.

En mode fondu (sans `data-bb-mode`), rien n'est imposé, mais une section plus courte que la fenêtre laisse une bande vide sous elle pendant l'épinglage. Le plugin émet alors un avertissement console.

**2. Header fixe ou sticky : détecté automatiquement.** Le plugin lit le réglage « Sticky : haut » d'Elementor Pro (`data-settings`) ou une barre déjà `fixed`/`sticky` en haut de la fenêtre. Il ne sert qu'aux sections plus hautes que l'écran (épinglage par le haut). Pour forcer la valeur : `data-bb-offset|96` sur la section, ou `$cfg['stepsOffset'] = 96;` globalement (`0` pour désactiver). `data-bb-start` reste prioritaire.

**Figer tout l'écran (comme Heron).** Avec le pin par le bas, le contenu situé au-dessus de la section continue de défiler pendant l'épinglage — c'est mécanique, ScrollTrigger ne fige que la section. Pour que rien ne bouge, ce contenu doit être **dans** l'élément épinglé : un conteneur parent avec `bb-steps` + `data-bb-mode|slide` + `data-bb-pin|top`, qui contient d'une part le bloc du dessus (titre, visuel…), d'autre part la ligne des étapes (avec le fond de couleur). Contrainte : l'ensemble doit tenir dans un écran (hauteur de fenêtre moins header, ~740 px sur un portable 16:9), sinon les étapes montent sous la ligne de flottaison. Prévoir donc un bloc du dessus compact.

**Conseil :** pour reproduire Heron à l'identique (titre + visuel qui restent affichés pendant que les étapes montent), mettre ces éléments dans la section hors des `bb-step` : le plugin n'y touche pas. La section dépasse alors l'écran et bascule automatiquement sur l'épinglage par le haut.

**3. Ajuster `data-bb-distance` au nombre d'étapes.** La longueur de l'épinglage vaut `hauteur d'écran × distance% × (nombre d'étapes + 0,5)`. Avec la valeur par défaut de 60 et deux étapes, cela fait déjà 1350 px de scroll sur un écran de 900 px. Pour deux étapes, `data-bb-distance|35` donne un rythme plus proche de celui de Heron (environ un écran de scroll pour dérouler la séquence).

## Défilement horizontal (bb-hscroll)

La section est épinglée, et sa piste de panneaux est translatée horizontalement au fil du scroll vertical. Sous le breakpoint, rien n'est épinglé ni mis en ligne : les panneaux gardent la mise en page Elementor et s'empilent normalement.

### Structure

Deux niveaux de conteneur, l'imbrication est obligatoire :

```
Conteneur  ← classe bb-hscroll        (la section épinglée, c'est le cadre visible)
└─ Conteneur  ← la piste              (pas de classe : c'est le parent des panneaux)
   ├─ Conteneur  ← classe bb-hpanel   (un panneau, avec une largeur)
   ├─ Conteneur  ← classe bb-hpanel
   └─ …
```

Le plugin met la piste en ligne (`display:flex`, `width:max-content`) et la translate. Si les panneaux sont des enfants directs de la section, il n'y a pas de piste à déplacer sans emporter la section épinglée : le plugin s'arrête avec un avertissement console. Sans classe `bb-hpanel`, les enfants directs de la piste sont pris comme panneaux.

**Donner une largeur aux panneaux.** Un panneau à `100 %` dans une piste en `max-content` se réduit à la largeur de son contenu. Soit une largeur en `vw` ou en `px` sur chaque panneau dans Elementor, soit `data-bb-width|60vw` sur la section, qui l'applique à tous. Si la piste ne dépasse pas la section, il n'y a rien à faire défiler : avertissement console.

**Panneaux en `100vw` : mettre le padding horizontal de la section à zéro.** `100vw` vaut la largeur de la fenêtre *scrollbar comprise*, un peu plus que la largeur réellement visible : les panneaux ne seront jamais parfaitement calés sur l'écran tant que la section porte un padding. Pour du plein écran : padding gauche/droite à `0` sur la section `bb-hscroll` **et** sur la piste, et le padding décoratif porté par les panneaux eux-mêmes.

**Le padding du conteneur piste est le piège le plus courant.** Les conteneurs Elementor en ont un par défaut ; sur la piste, il s'ajoute à sa largeur et se lit comme un vide au début et à la fin du défilement. Le mettre à `0` et porter le padding décoratif sur les panneaux. Le plugin l'avertit en console depuis la 1.7.3.

La course, elle, tient compte du padding gauche de la **section** (depuis la 1.7.2) : la piste s'arrête quand son dernier panneau affleure le bord du cadre, sans vide à la fin.

### Réglages de la section

| Attribut | Défaut | Effet |
|---|---|---|
| `data-bb-direction\|rtl` | ltr | défilement de droite à gauche : le premier panneau est à droite |
| `data-bb-distance\|150` | 100 | longueur de scroll en % du déplacement horizontal. 100 = 1 px de scroll pour 1 px de translation ; au-dessus la piste avance plus lentement |
| `data-bb-width\|60vw` | — | largeur appliquée à tous les panneaux (`60vw`, `600px`, ou un nombre lu en `vw`) |
| `data-bb-offset\|96` | auto | hauteur du header fixe sous lequel épingler |
| `data-bb-start\|top top` | auto | déclenchement de l'épinglage (syntaxe ScrollTrigger) |

Le breakpoint est global : **Réglages > BB Scroll Reveal > Défilement horizontal**, ou `$cfg['hscrollBreakpoint']`.

### Animations dans les panneaux

`bb-reveal`, `bb-reveal-children`, `bb-split`, `bb-scrub`, `bb-parallax` et `bb-ink` fonctionnent à l'intérieur des panneaux : leurs déclencheurs sont automatiquement rebasculés sur l'axe de la piste (`containerAnimation`), et se déclenchent donc à l'arrivée du panneau dans l'écran, pas au chargement. Les blocs sont recréés à chaque passage du breakpoint.

Conséquence sur `data-bb-start` : **dans une piste, il s'écrit en horizontal** — `left 85%` (défaut), `left center`, `center right`… La syntaxe verticale ne déclenchera jamais.

### Limites

- **Pas d'épinglage dans un épinglage.** Une `bb-hscroll` dans une `bb-steps` (ou l'inverse, ou deux pistes imbriquées) est ignorée avec un avertissement console. Une `bb-steps` placée dans un panneau est déclassée : ses `bb-step` redeviennent de simples révélations.
- Mêmes prérequis que `bb-steps` : aucun parent de la section ne doit porter de `transform`, de `filter` ni d'effet de mouvement Elementor.

## Révélation « tache d'encre » entre deux images (bb-ink)

Reproduit l'effet `product-how-img` de Heron : une seconde image est révélée à travers une tache d'encre qui grandit au scroll (masque SVG circulaire dont le bord est déchiqueté par un filtre de turbulence). Pas de widget : un conteneur Elementor avec la classe `bb-ink` et **deux widgets Image** de mêmes dimensions, dans cet ordre :

```
Conteneur                 → classe bb-ink
├── Image 1               → l'état de départ
└── Image 2               → l'état révélé (masquée au chargement, puis dessinée dans une couche SVG)
```

Le plugin construit la couche SVG par-dessus l'image 1 et retire l'image 2 du flux ; le conteneur garde donc la hauteur de l'image 1.

- **Dans une section `bb-steps`** : la tache est calée sur la progression de l'épinglage, entre `data-bb-ink-start` et `data-bb-ink-end` (% de la progression, défaut 50 → 70, comme Heron). Elle se joue donc entre deux étapes, en avant comme en arrière.
- **Hors section** : la tache suit le passage du conteneur dans l'écran (scrub), défaut 30 → 70.

Réglages (attributs sur le conteneur `bb-ink`) :

- `data-bb-ink-start|50`, `data-bb-ink-end|70` : fenêtre de progression.
- `data-bb-ink-origin|50 50` : centre de la tache en % (x y). `30 60` pour partir en bas à gauche.
- `data-bb-ink-scale|100` : force du déchiquetage (0 = cercle net).
- `data-bb-ink-freq|0.05` : grain de la turbulence (plus petit = taches plus larges).
- `data-bb-ink-seed|5` : variante du motif.

Les paramètres du filtre sont exprimés dans un viewBox de largeur 1000, donc le rendu est identique quelle que soit la largeur affichée. Le filtre SVG coûte du GPU : une tache par écran, pas dix.

## Réglages dans l'admin

**Réglages > BB Scroll Reveal** (capacité `manage_options`). Tous les réglages globaux y sont accessibles sans passer par `functions.php`.

La page est organisée en onglets (colonne de gauche), un par famille de réglages, plus un onglet **Classes et attributs** qui rappelle en lecture seule ce qui se règle dans Elementor. Une barre d'enregistrement reste collée en bas de l'écran et signale les modifications non enregistrées ; la colonne de droite donne l'aide de l'onglet en cours, l'état du front (animations, Lenis, GSAP, pages exclues) et le bouton de réinitialisation. Les onglets ne sont qu'un affichage : tous les champs appartiennent au même formulaire, donc un enregistrement porte sur la page entière.

Le principe : **un champ laissé vide garde la valeur par défaut du plugin**, affichée en filigrane. L'option ne stocke que ce qui est effectivement renseigné, donc les défauts du code restent la référence et évoluent avec les mises à jour.

L'ordre de résolution est le suivant, du plus faible au plus fort :

```
défauts (bb_reveal_defaults())
  → réglages de l'admin (option bb_reveal_settings)
    → filtre bb_reveal_config
```

Un `add_filter( 'bb_reveal_config', … )` dans le thème **écrase donc toujours** la page de réglages. C'est voulu : un site livré avec sa configuration dans le code ne peut pas être déréglé depuis l'admin.

| Section | Réglages |
|---|---|
| Général | activation globale, pages exclues, chargement de GSAP |
| Révélations | `y`, `duration`, `stagger`, `ease`, `start` |
| Défilement | Lenis (`smooth`, `lerp`, sensibilité molette), `normalizeScroll` |
| Sections épinglées | `stepsBreakpoint`, `stepsOffset` |
| Défilement horizontal | `hscrollBreakpoint` |

Trois réglages n'ont pas d'équivalent dans `bb_reveal_config` :

- **Activer les animations** — décoché, le plugin ne charge plus rien sur le front (ni scripts, ni CSS de garde) sans avoir à le désactiver. Pratique en recette. Équivaut à `bb_reveal_enabled`, mais évalué avant lui.
- **Pages exclues** — une entrée par ligne, identifiant numérique ou slug. La comparaison porte sur l'ID et sur le slug, jamais sur le titre.
- **Charger GSAP** — équivalent du filtre `bb_reveal_load_gsap`.

Une valeur hors bornes ou invalide est refusée à l'enregistrement, avec un message nommant le réglage : elle n'est pas stockée, donc le défaut s'applique. Le bouton **Réinitialiser** supprime l'option et rend la main aux défauts du code.

Les réglages **par élément** (classes et attributs `data-bb-*`) ne passent pas par cette page : ils se posent dans Elementor. L'onglet **Classes et attributs** les rappelle, en lecture seule.

## Configuration globale

Pour figer la configuration dans le thème, ou la faire dépendre du contexte (type de page, langue, rôle), le filtre reste disponible et prioritaire sur l'admin :

```php
add_filter( 'bb_reveal_config', function ( $cfg ) {
	$cfg['smooth']   = false;  // Coupe Lenis (smooth scroll), actif par défaut
	$cfg['lenis']    = array( 'lerp' => 0.05 ); // Inertie du défilement : 0.1 réactif, 0.07 défaut, 0.05 très doux
	$cfg['duration'] = 1.1;
	$cfg['stepsBreakpoint'] = 1025; // épinglage bb-steps uniquement sur desktop
	return $cfg;
} );

add_filter( 'bb_reveal_enabled', fn() => ! is_page( 'mentions-legales' ) );
add_filter( 'bb_reveal_load_gsap', '__return_false' ); // si GSAP est déjà chargé ailleurs
```

## Firefox : saut de la section épinglée

Firefox défile de façon asynchrone (APZ) : un élément épinglé en `position:fixed` est recalé une frame après le défilement. À l'entrée dans l'épinglage, la section passe brièvement sous le header puis se recale. Chrome/Safari ne le montrent pas. `anticipatePin` atténue sans supprimer. Deux remèdes, à activer via `bb_reveal_config` :

- **Lenis, actif par défaut depuis 1.5.0** — pilote le défilement en JS, synchronisé avec le rendu : plus de saut, et le rendu « Heron » (inertie). Se coupe avec `$cfg['smooth'] = false;`.
- `$cfg['normalizeScroll'] = true;` — si Lenis est coupé : ScrollTrigger intercepte la molette et le tactile et défile lui-même, synchronisé avec le rendu. Plus léger que Lenis, mais désactive le défilement lisse natif de Firefox.

Un seul des deux suffit ; `normalizeScroll` est ignoré si `smooth` est actif.

## Points de vigilance

- **Plugins de cache** (WP Rocket « Retarder l'exécution JS », LiteSpeed, Perfmatters) : exclure `bb-reveal`, `gsap`, `ScrollTrigger`, `SplitText`, `lenis` ainsi que le script inline `bb-reveal-guard`. Sinon les blocs restent masqués ~3,5 s (filet de sécurité) jusqu'à interaction.
- **Lenis + ancres Elementor** : l'option `anchors` de Lenis gère les liens `#ancre`, mais tester les menus one-page et le header sticky avant mise en prod.
- **Accessibilité** : `prefers-reduced-motion` désactive toutes les animations, contenu affiché directement.
- **Éditeur Elementor** : aucune animation dans l'aperçu, les blocs restent éditables.
- **Épinglage (bb-steps)** : aucun conteneur parent de la section ne doit avoir de `transform`, de `filter` ni d'effet de mouvement Elementor, sinon l'épinglage décroche. Ne pas mettre `bb-reveal` ou `bb-split` sur les étapes elles-mêmes : c'est la section qui les pilote. **À l'intérieur** d'une étape en revanche, `bb-reveal`, `bb-reveal-children` et `bb-split` sont pilotés par l'étape (1.4.x) : lancés dès le début de sa montée et acquis ensuite, comme chez Heron. `data-bb-replay` sur la section (ou une étape) les rembobine dès que l'étape repart en arrière. `data-bb-delay` et `data-bb-stagger` s'appliquent normalement — un délai de 0,3–0,4 s laisse la carte arriver avant le texte.
- **Performance** : seules `transform` et `opacity` sont animées. Éviter `bb-scrub` / `bb-parallax` sur des dizaines d'éléments d'une même page.

## Historique

**1.8.0**
- Refonte de la page de réglages sur le design system des plugins maison (celui de DC Support Technique) : header de marque, onglets en colonne, cards, interrupteurs, barre d'enregistrement collante avec état « modifications non enregistrées », colonne d'aide contextuelle et état du front.
- Onglet **Classes et attributs** : le rappel des classes et des `data-bb-*` a sa propre page au lieu d'être empilé sous le formulaire. Chaque entrée porte un pictogramme (SVG inline, sprite unique) dont la teinte dit la famille — révélations, effets liés au scroll, sections épinglées, défilement horizontal, tache d'encre. Réinitialisation déplacée dans la colonne de droite.
- Deux fichiers d'assets admin (`assets/admin.css`, `assets/admin.js`, chargés uniquement sur la page de réglages). Aucun changement de comportement sur le front : `window.BB_REVEAL`, la sanitisation et l'ordre de résolution des réglages sont inchangés.

**1.7.3**
- `bb-hscroll` : avertissement console quand le conteneur piste a un padding horizontal (valeur par défaut des conteneurs Elementor), qui se lit comme un vide au début et à la fin du défilement.

**1.7.2**
- `bb-hscroll` : seul le padding **gauche** de la section est déduit de la course. La 1.7.1 déduisait aussi le padding droit, qui n'est pas une limite visuelle (`overflow:hidden` coupe au padding box) : un vide de cette largeur apparaissait après le dernier panneau.

**1.7.1**
- `bb-hscroll` : course de défilement calculée sur le cadre visible de la section et non sur sa largeur totale. Le dernier panneau n'est plus tronqué de la valeur du padding gauche.
- `bb-hscroll` : centrage de la piste neutralisé (`margin-left:0`), un conteneur Elementor « boxed » la faisait déborder des deux côtés une fois élargie.

**1.7.0**
- Défilement horizontal `bb-hscroll` + `bb-hpanel` : section épinglée dont la piste de panneaux se translate au fil du scroll. Sens (`data-bb-direction`), longueur de course (`data-bb-distance`), largeur des panneaux (`data-bb-width`), breakpoint dans l'admin.
- Les animations placées dans les panneaux (`bb-reveal`, `bb-split`, `bb-scrub`, `bb-parallax`, `bb-ink`…) sont câblées sur la piste via `containerAnimation` : elles partent à l'arrivée du panneau. `data-bb-start` s'y écrit en horizontal (`left 85%`).
- Sous le breakpoint (768 px par défaut) le défilement horizontal est entièrement désactivé : les panneaux reprennent la mise en page Elementor et leur contenu se révèle normalement.

**1.6.0**
- Page de réglages **Réglages > BB Scroll Reveal** : tous les réglages globaux depuis l'admin, sans `functions.php`. Un champ vide garde le défaut du plugin ; le filtre `bb_reveal_config` reste prioritaire sur l'admin.
- Deux réglages d'exploitation sans équivalent dans `bb_reveal_config` : activation globale (coupe le plugin sur tout le site sans le désactiver) et liste de pages exclues par identifiant ou slug.
- Comportement inchangé tant qu'aucun réglage n'est enregistré : `window.BB_REVEAL` est identique à celui de la 1.5.1.

**1.5.1**
- Réglages Lenis exposés (`$cfg['lenis']`), `lerp` par défaut passé de 0.1 à 0.07 pour une inertie plus proche de Heron.

**1.5.0**
- Animations internes aux étapes : lancement à 2 % de progression de l'étape (et non à la première frame), le lissage du scrub pouvant frémir avant l'épinglage.
- Lenis (smooth scroll) actif par défaut : supprime le saut d'épinglage sous Firefox et donne l'inertie de Heron. `$cfg['smooth'] = false;` pour revenir au défilement natif.

**1.4.2**
- Option `normalizeScroll` (défaut false) pour supprimer le saut de la section épinglée sous Firefox. Voir la section Firefox du README.

**1.4.1**
- Animations internes aux étapes : lancées dès le début de la montée de l'étape (plus à la fin) et acquises par défaut. `data-bb-replay` pour le va-et-vient.

**1.4.0**
- Les animations `bb-reveal`, `bb-reveal-children` et `bb-split` placées dans une étape `bb-step` n'ont plus de déclencheur de scroll (arbitraire pendant l'épinglage) : elles sont créées en pause et jouées par l'étape quand elle atteint sa position, rembobinées dès qu'elle la quitte. Sous le breakpoint mobile, jouées à la fin de la révélation de l'étape.

**1.3.2**
- Mode slide : les étapes ne sont plus forcées en bas de leur ligne. Seul l'étirement par défaut d'Elementor est retiré (hauteur naturelle, alignement haut) ; un alignement choisi dans Elementor sur la ligne est conservé. Pour des étapes de même hauteur, leur donner une hauteur minimale dans Elementor.

**1.3.1**
- `bb-ink` : la couche SVG est calée en pixels sur le rectangle de l'image 1 (recalée au resize) et non plus sur son widget ; l'image 2 n'est plus étirée quand l'image 1 est centrée ou plus étroite que le conteneur.

**1.3.0**
- Nouveau `bb-ink` : révélation d'une seconde image par une tache d'encre (masque SVG + feTurbulence/feDisplacementMap), pilotée par la progression d'une section `bb-steps` ou par le passage dans l'écran.

**1.2.9**
- Une section `bb-steps` imbriquée dans une autre est ignorée (avertissement console) : évite deux épinglages concurrents quand la classe est restée sur la ligne après avoir été posée sur le conteneur parent.

**1.2.8**
- Calage de la ligne des étapes en bas : gère l'imbrication (conteneur boxed > `.e-con-inner` > ligne) en poussant chaque maillon en bas de son parent (`margin-top:auto`), sans toucher au contenu placé au-dessus. Les règles CSS de calage de la 1.2.4 sont retirées au profit du JS.

**1.2.7**
- `data-bb-pin|top` / `bottom` sur la section slide pour forcer le mode d'épinglage. `top` porte la section à la hauteur de l'écran (sous le header) : tout son contenu se fige.

**1.2.6**
- Correction de la neutralisation de la transition Elementor sur `transform` : la variable lue par Elementor est `--e-con-transform-transition-duration` (pas `--transform-transition`). Appliquée aussi à la section `bb-steps` épinglée : sans cela, le `translateY` posé par ScrollTrigger au dépin était animé sur 0,4 s (la section remontait puis redescendait) et chaque frame de scrub était lissée (saccades).

**1.2.5**
- Mode slide : plus de `min-height:100vh` imposée. Une section plus courte que l'écran est épinglée par son bord bas au bas de la fenêtre, à sa hauteur naturelle ; une section plus haute est épinglée par le haut sous le header.
- Détection du header sticky Elementor par son réglage (`data-settings`) et non plus par la classe `elementor-sticky`, posée après notre initialisation.

**1.2.4**
- `bb-steps` en mode slide : la ligne des étapes est calée en bas de la section et chaque étape garde sa hauteur naturelle (plus d'étirement Elementor), donc chaque étape ne parcourt que sa propre hauteur, comme chez Heron. Posé en CSS (anti-flash) et en inline par le JS (prioritaire sur le CSS Elementor), retiré sous le breakpoint.
- Header fixe ou sticky détecté automatiquement (y compris l'effet « Sticky » d'Elementor Pro, encore en position relative au chargement) : la section est épinglée juste dessous et réduite d'autant. Réglages `data-bb-offset` et `$cfg['stepsOffset']`.
- Le débord mesuré pour le glissement (`slack`) est une valeur fonctionnelle ré-évaluée par ScrollTrigger à chaque refresh (resize, polices, images), sur l'étape au repos.
- Neutralisation de `transition: transform .4s` posée par Elementor sur les conteneurs, qui retardait chaque frame des animations.

# BB Scroll Reveal — bleuebuzz

Révélations au scroll façon GSAP/Webflow dans Elementor, pilotées par classes CSS. GSAP 3.15 (ScrollTrigger + SplitText) et Lenis 1.3 sont auto-hébergés dans `assets/vendor/` : aucun appel CDN tiers.

## Installation

Dézipper dans `wp-content/plugins/`, activer. Rien d'autre à configurer : les valeurs par défaut conviennent à la plupart des sites. Pour les ajuster sans toucher au code, voir « Réglages dans l'admin » plus bas.

### Mises à jour

Le plugin se met à jour depuis le dépôt GitHub public [dynamiccreative/bb-scroll-reveal](https://github.com/dynamiccreative/bb-scroll-reveal), branche `main`, comme une extension du répertoire WordPress : aucun jeton à saisir. La version proposée est celle de l'en-tête `Version` du fichier principal sur `main` ; le dossier du plugin doit rester `bb-scroll-reveal`. La mise à jour automatique demande PHP 8.0 ou plus (lib `lib/GitHubUpdater.php`) ; sous PHP 7.4 le plugin fonctionne, mais se met à jour à la main.

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
| `bb-video-scroll` | vidéo hébergée lue au fil du scroll, épinglée | conteneur d'un widget Vidéo auto-hébergée (ou widget BB Vidéo au scroll) |
| `bb-video-text` | texte superposé à la vidéo, sur une plage donnée | widget texte dans le conteneur `bb-video-scroll` |

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

## Vidéo lue au scroll (bb-video-scroll)

Une vidéo hébergée sur le site avance quand on scrolle vers le bas et recule quand on remonte, au lieu d'être lue par un bouton. Par défaut, la vidéo reste épinglée à l'écran le temps de sa lecture.

Deux façons de la mettre en place, qui produisent exactement le même effet :

- **Widget « BB Vidéo au scroll »** (catégorie bleuebuzz, si Elementor est actif) : choix de la vidéo dans la médiathèque ou par URL, épinglage, longueur de scroll, plein écran, arrêts sur image, boutons lecture/pause.
- **Classe `bb-video-scroll`** sur un conteneur qui contient un widget Vidéo d'Elementor en mode « Auto-hébergée ».

**Uniquement des fichiers MP4 ou WebM.** YouTube et Vimeo ne permettent pas d'afficher la vidéo image par image : le résultat saccade, donc ils ne sont pas pris en charge.

### Encodage de la vidéo : indispensable

Un MP4 classique ne contient une image complète (image clé) que toutes les quelques secondes. Entre deux, le navigateur doit recalculer chaque image depuis la précédente image clé : en lecture normale ça ne se voit pas, mais quand on scrolle, la vidéo saccade, surtout sous Safari. Il faut ré-encoder avec une image clé sur chaque image :

```
ffmpeg -i source.mp4 -an -g 1 -crf 23 -movflags +faststart video.mp4
```

- `-g 1` : une image clé par image (le réglage qui compte).
- `-an` : retire le son (la vidéo est de toute façon muette).
- `-crf 23` : qualité ; monter à 26-28 si le fichier est trop lourd.
- Viser 1280 ou 1920 px de large, 24 à 30 images par seconde, et une vidéo courte (5 à 15 s). Ajouter `-vf scale=1280:-2` pour redimensionner.

Le fichier sera plus lourd qu'un MP4 classique, c'est normal.

### Réglages (attributs sur le conteneur `bb-video-scroll`)

- `data-bb-distance|300` : scroll nécessaire pour lire toute la vidéo, en % de hauteur d'écran (300 = trois écrans), ou en pixels avec `1500px`.
- `data-bb-pin|off` : pas d'épinglage. La vidéo se lit pendant qu'elle traverse l'écran, du moment où son haut entre en bas de l'écran à celui où son bas sort en haut.
- `data-bb-holds|30, 70` : arrêts sur image à 30 % et 70 % de la vidéo. La vidéo reste figée pendant qu'on continue de scroller, le temps de lire un texte par exemple.
- `data-bb-hold|15` : durée de chaque arrêt, en % du scroll de la vidéo entière (défaut 15).
- `data-bb-controls` : affiche les boutons lecture/pause. L'internaute peut lire la vidéo normalement ; le scroll reprend la main au mouvement suivant.
- `data-bb-preload|stream` : voir « Chargement » ci-dessous.
- `data-bb-offset|96` : hauteur du header fixe, comme pour les sections épinglées (détectée automatiquement sinon).
- `data-bb-start|top 100px` : point de départ de l'épinglage, si le calcul automatique ne convient pas.

### Textes superposés (bb-video-text)

Dans un conteneur `bb-video-scroll`, un élément avec la classe `bb-video-text` apparaît et disparaît à des moments précis de la vidéo :

- `data-bb-at|20-45` : apparaît à 20 % de la vidéo, disparaît à 45 %.
- `data-bb-at|60` : apparaît à 60 % et reste affiché jusqu'à la fin.

Un texte calé sur un arrêt sur image (`data-bb-holds|30` et `data-bb-at|30-40`) apparaît au début de l'arrêt et reste affiché pendant toute sa durée.

Le positionnement par-dessus la vidéo se règle dans Elementor : Avancé > Position « Absolue » sur le widget texte. Exemple de structure :

```
Conteneur (hauteur 100vh, padding 0)   → classe bb-video-scroll
├── Vidéo (auto-hébergée) ou BB Vidéo au scroll
├── Titre (position absolue)           → classe bb-video-text, data-bb-at|10-35
└── Texte (position absolue)           → classe bb-video-text, data-bb-at|55
```

Quand le widget « BB Vidéo au scroll » est placé dans un conteneur `bb-video-scroll`, c'est le conteneur qui est épinglé (avec les textes), et les réglages du widget s'appliquent sauf si le conteneur porte le même attribut.

### Chargement

Par défaut, la vidéo est téléchargée entière en mémoire avant d'être pilotée. Le navigateur n'a ainsi plus à interroger le serveur à chaque saut dans la vidéo : c'est ce qui rend le scrub fluide. Il faut pour cela que la vidéo soit sur le même domaine que le site, ou servie par un CDN avec les en-têtes CORS ; sinon, elle est lue en flux, sans erreur. Pour une vidéo très lourde (plus de 30 à 40 Mo), `data-bb-preload|stream` (ou « En flux » dans le widget) évite de la garder en mémoire.

Sur iPhone, la vidéo est forcément muette et lue en ligne (le plugin pose `muted` et `playsinline`). En mode économie d'énergie, iOS bloque le chargement jusqu'au premier toucher de l'écran : la vidéo s'affiche alors au premier geste.

### Limites

- Dans une section `bb-steps` ou une piste `bb-hscroll`, l'épinglage est déjà pris : la vidéo suit simplement son passage dans l'écran (`data-bb-pin|off` implicite).
- Un `bb-video-scroll` imbriqué dans un autre est ignoré (avertissement dans la console).
- Avec « Réduire les animations » activé dans le système, ou dans l'éditeur Elementor, la vidéo n'est pas pilotée : elle reste sur sa première image (ou affiche ses boutons si l'option est cochée).
- Une vidéo par écran : chaque vidéo pilotée sollicite le décodeur en continu pendant le scroll.

## Widgets Elementor : liste dépliante + image liée

Un module de widgets, **chargé uniquement si Elementor est actif**. Rien d'autre dans le plugin n'en dépend : sans Elementor, le comportement est inchangé.

Deux widgets, catégorie **bleuebuzz** du panneau Elementor :

| Widget | Rôle |
|---|---|
| **BB Liste dépliante** (`bb_accordion`) | la liste d'items dépliants : titre, pictogramme, contenu, **et l'image liée de chaque item** |
| **BB Image liée** (`bb_accordion_media`) | le cadre où s'affiche l'image de l'item ouvert |

### Pourquoi deux widgets

Le modèle (Heron, blocs `home-usecase-faq-list` / `home-usecase-img`) place la liste et l'image dans deux colonnes distinctes, avec du contenu libre au-dessus de la liste (sur-titre, titre, paragraphes, bouton). Un widget unique imposerait sa propre mise en page à deux colonnes ; en deux widgets, la mise en page reste entièrement celle d'Elementor : conteneur, colonnes, ordre, responsive, contenu libre au-dessus ou en dessous de chacun.

### Les raccorder

Les deux widgets portent un champ **Identifiant de liaison**, `default` par défaut : poser un widget de chaque suffit, ils se trouvent. Pour plusieurs paires sur une même page, donner un identifiant distinct à chaque paire (`metiers`, `services`…). L'identifiant est réduit à un slug (minuscules, chiffres, tirets).

**Les images ne se saisissent qu'une fois, dans la liste** : chaque item du répéteur porte son image. La liste rend la pile d'images (masquée), que le JS déplace dans le cadre de même identifiant au chargement. Aucun risque de décalage entre l'ordre des items et l'ordre des images, et un item ajouté ou déplacé n'oblige pas à retoucher le cadre image.

### Mise en page type

```
Conteneur (flex, 2 colonnes)
├── Colonne 1
│   └── BB Image liée            ← « Occuper toute la hauteur du conteneur »
└── Colonne 2
    ├── Titre                    (widgets Elementor libres)
    ├── Éditeur de texte
    ├── Bouton
    └── BB Liste dépliante
```

### Réglages de la liste

- **Éléments** : répéteur (titre, contenu WYSIWYG, pictogramme, image liée) et taille des images.
- **Comportement** : identifiant de liaison, ouverture (un seul élément / plusieurs), « toujours un élément ouvert » (un clic sur l'élément ouvert ne le referme pas, l'image reste cohérente), élément ouvert au chargement (`0` = aucun), aperçu au survol (le survol change l'image sans déplier), balise des titres, indicateur (plus/moins, chevron, aucun).
- **Style** : marges des lignes, séparateurs (style, couleur, épaisseur, séparateur haut), titres (typographie, couleur du texte, fond, contour — type, épaisseur, couleur — et rayon des angles ; texte, fond et couleur du contour déclinés en normal / survol / ouvert), contenu déplié (typographie, couleur du texte, fond, marge, durée **et courbe** du dépliement), pictogrammes et indicateur.

### Réglages du cadre image

Identifiant de liaison, image de repli (affichée tant qu'aucun élément n'est ouvert), transition (fondu + zoom façon Heron par défaut, fondu, fondu + montée, aucune) et sa durée (0,4 s par défaut, la valeur de Heron), proportions, hauteur minimale, pleine hauteur, cadrage `cover`/`contain`, fond, angles, bordure, ombre.

### Points à connaître

- **Animation, calée sur le modèle Heron** : chez eux le panneau fait un `slideDown` — une seule propriété animée, la hauteur, sans fondu ni translation du contenu — et l'image passe en `scale(1.04) → 1` + opacité sur 0,4 s. Ici aussi : la hauteur du panneau est écrite en pixels par le JS, de la valeur mesurée vers la cible, en 0,4 s sur `cubic-bezier(0.33, 1, 0.68, 1)` (l'équivalent CSS de `power2.out`). Rien d'autre n'est animé.
- **Ouverture et fermeture sont simultanées par construction** : un clic traite d'un bloc la ligne qui se ferme et celle qui s'ouvre — hauteurs de départ, bascule des classes, un seul recalcul de style, puis les hauteurs d'arrivée. Les deux transitions démarrent donc dans la même frame.
- Éviter les courbes très amorties (`expo.out`, `quint.out`) proposées dans **Courbe du dépliement** : elles placent 80 % de la course dans les premières dizaines de millisecondes puis traînent, et l'ouverture/fermeture simultanée se lit alors en deux temps. Réglables : **Durée du dépliement** et **Courbe du dépliement** (Style > Contenu déplié), **Transition** et sa durée pour l'image.
- Le rognage (`overflow:hidden`) est porté par `.bb-acc-panel` : c'est lui qui produit le glissé. À la fin du dépliement, la hauteur en pixels est rendue à `auto`, pour que le panneau suive ensuite son contenu (image chargée après coup, changement de largeur).
- À la fin de chaque dépliement, `ScrollTrigger.refresh()` est appelé s'il est présent, pour que les sections épinglées de la page restent calées.
- Les widgets fonctionnent dans l'éditeur Elementor : leurs CSS et JS sont chargés par `get_style_depends()` / `get_script_depends()`, y compris dans l'aperçu où le reste du plugin est volontairement à l'arrêt.
- Accessibilité : chaque titre est un `<button>` avec `aria-expanded` / `aria-controls`, navigation au clavier par flèches haut/bas, `Début`/`Fin`, contenu replié retiré du parcours (`visibility:hidden`). `prefers-reduced-motion` coupe les transitions.
- Les classes `bb-reveal`, `bb-split`… se posent normalement sur ces widgets ou sur leurs conteneurs, comme sur n'importe quel élément Elementor.

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
- **Lenis + zones défilantes** (1.10.1) : `allowNestedScroll` est actif, une zone qui défile elle-même (panneau latéral, menu, liste déroulante) reçoit la molette au lieu de la page. Pour une zone qui ne doit jamais céder la molette à la page, poser `data-lenis-prevent` dessus. Se coupe avec `$cfg['lenis']['allowNestedScroll'] = false;` (Lenis teste alors moins d'éléments à chaque cran de molette).
- **Accessibilité** : `prefers-reduced-motion` désactive toutes les animations, contenu affiché directement.
- **Éditeur Elementor** : aucune animation dans l'aperçu, les blocs restent éditables.
- **Épinglage (bb-steps)** : aucun conteneur parent de la section ne doit avoir de `transform`, de `filter` ni d'effet de mouvement Elementor, sinon l'épinglage décroche. Ne pas mettre `bb-reveal` ou `bb-split` sur les étapes elles-mêmes : c'est la section qui les pilote. **À l'intérieur** d'une étape en revanche, `bb-reveal`, `bb-reveal-children` et `bb-split` sont pilotés par l'étape (1.4.x) : lancés dès le début de sa montée et acquis ensuite, comme chez Heron. `data-bb-replay` sur la section (ou une étape) les rembobine dès que l'étape repart en arrière. `data-bb-delay` et `data-bb-stagger` s'appliquent normalement — un délai de 0,3–0,4 s laisse la carte arriver avant le texte.
- **Performance** : seules `transform` et `opacity` sont animées. Éviter `bb-scrub` / `bb-parallax` sur des dizaines d'éléments d'une même page.

## Historique

Voir [CHANGELOG.md](CHANGELOG.md), également affiché dans la fenêtre de détails de la mise à jour.

# BB Scroll Reveal — contexte pour Claude Code

Plugin WordPress bleuebuzz : révélations au scroll (GSAP 3.15 + ScrollTrigger + SplitText, Lenis) pilotées par des classes CSS posées dans Elementor. Aucun widget, aucun build : PHP + un fichier JS vanilla (ES5, pas de transpilation).

## Fichiers

| Fichier | Rôle |
|---|---|
| `bb-scroll-reveal.php` | Bootstrap : `bb_reveal_config()` (défauts + filtre), script/CSS de garde anti-flash dans `wp_head`, enqueue des vendors et de `bb-reveal.js`, injection de `window.BB_REVEAL` (config JSON). |
| `assets/bb-reveal.js` | Toute la logique front. Lit `window.BB_REVEAL` fusionné avec ses propres défauts (`cfg`). Ne pas y ajouter de dépendance à l'admin. |
| `includes/class-bb-reveal-settings.php` | Page **Réglages > BB Scroll Reveal** (Settings API + rendu maison : onglets, cards, barre d'enregistrement). |
| `assets/admin.css`, `assets/admin.js` | Habillage de la page de réglages (préfixe `bbsr-`, design system des plugins maison). Chargés uniquement sur cette page. |
| `assets/vendor/` | gsap, ScrollTrigger, SplitText, lenis (+ lenis.css). Ne pas modifier. |
| `README.md` | Documentation utilisateur (classes, attributs, prérequis, historique). À tenir à jour à chaque changement de comportement. |

## Conventions

- PHP ≥ 7.4, WordPress ≥ 6.3. Fonctions préfixées `bb_reveal_`, constantes `BB_REVEAL_*`, text domain `bb-scroll-reveal`, `defined( 'ABSPATH' ) || exit`.
- Style WordPress (tabulations, espaces dans les parenthèses, `esc_*` en sortie, nonce + capability en entrée). `php -l` doit passer.
- JS : ES5 (`var`, `function`), pas de build. Tout réglage global transite par `window.BB_REVEAL` → `cfg`. Les réglages par élément passent par `data-bb-*` dans Elementor, jamais par l'admin.
- Le numéro de version est à incrémenter à trois endroits : en-tête du plugin, `BB_REVEAL_VERSION`, en-tête de `bb-reveal.js`. Ajouter une entrée dans « Historique » du README.
- Ne jamais casser le filtre `bb_reveal_config` : du code dans un thème peut s'en servir.

## Clés de configuration (`bb_reveal_config()`)

| Clé | Défaut | Rôle |
|---|---|---|
| `y` | 40 | Décalage vertical (px) des révélations |
| `duration` | 0.9 | Durée (s) |
| `stagger` | 0.12 | Décalage entre enfants (s) |
| `ease` | `power3.out` | Easing GSAP |
| `start` | `top 85%` | Déclenchement ScrollTrigger des révélations |
| `smooth` | true | Lenis (smooth scroll) |
| `lenis` | `['lerp' => 0.07, 'wheelMultiplier' => 1]` | Réglages Lenis |
| `normalizeScroll` | false | `ScrollTrigger.normalizeScroll` (ignoré si `smooth`) |
| `stepsBreakpoint` | 768 | Largeur mini (px) pour l'épinglage `bb-steps` |
| `stepsOffset` | `'auto'` | Hauteur du header fixe sous lequel épingler (`'auto'`, ou px) |
| `hscrollBreakpoint` | 768 | Largeur mini (px) pour le défilement horizontal `bb-hscroll` |

Filtres : `bb_reveal_config` (config), `bb_reveal_enabled` (couper le plugin sur certaines pages), `bb_reveal_load_gsap` (ne pas charger GSAP si déjà présent).

---

# Tâche : page de réglages dans l'admin

Objectif : régler les clés ci-dessus depuis **Réglages > BB Scroll Reveal** sans passer par `functions.php`. Le filtre `bb_reveal_config` reste prioritaire (le code gagne sur l'admin), et les défauts actuels restent inchangés.

## Résolution de la configuration

```
défauts (dans le code)
  → écrasés par l'option `bb_reveal_settings` (seules les clés renseignées)
    → puis filtre `bb_reveal_config`
```

`bb_reveal_config()` devient : `apply_filters( 'bb_reveal_config', array_replace_recursive( $defaults, bb_reveal_settings_clean() ) )`. Une clé absente ou vide dans l'option ne doit pas écraser le défaut.

## Fichiers à créer / modifier

- `includes/class-bb-reveal-settings.php` : classe `BB_Reveal_Settings` (Settings API). Chargée depuis `bb-scroll-reveal.php` uniquement si `is_admin()`.
- `bb-scroll-reveal.php` : `require` de la classe, `bb_reveal_config()` lit l'option, lien « Réglages » dans la ligne du plugin (`plugin_action_links_*`).
- `README.md` : section « Réglages dans l'admin » + entrée d'historique.

## Page

- Menu : `add_options_page()`, capability `manage_options`, slug `bb-scroll-reveal`.
- Une seule option sérialisée : `bb_reveal_settings` (tableau), `register_setting()` avec callback de sanitisation.
- Sections et champs :

**Général**
- `enabled_globally` (case) — coupe le plugin sur tout le site sans le désactiver (utile en recette). Implémenté dans `bb_reveal_is_active()`, avant le filtre `bb_reveal_enabled`.
- `disabled_on` (textarea) — une entrée par ligne : ID de page ou slug. Le plugin est inactif sur ces pages (`is_page()` / `is_single()`, comparaison sur l'ID et sur `post_name`).
- `load_gsap` (case, défaut coché) — décoché = GSAP/ScrollTrigger/SplitText ne sont pas chargés par le plugin (équivalent du filtre `bb_reveal_load_gsap`).

**Révélations**
- `y` (nombre, px, min 0, max 400)
- `duration` (nombre, s, pas 0.05, 0.1 → 5)
- `stagger` (nombre, s, pas 0.01, 0 → 2)
- `ease` (select) : `power1.out`, `power2.out`, `power3.out`, `power4.out`, `expo.out`, `circ.out`, `back.out(1.4)`, `none`
- `start` (texte, placeholder `top 85%`) — validé par regex `^(top|center|bottom)\s+(top|center|bottom|\d{1,3}%|\d+px)$`

**Défilement**
- `smooth` (case) — Lenis
- `lenis_lerp` (nombre, pas 0.01, 0.02 → 0.3) avec aide : « 0.1 réactif · 0.07 défaut · 0.05 très doux »
- `lenis_wheel` (nombre, pas 0.1, 0.5 → 2)
- `normalizeScroll` (case) — aide : « Corrige le saut d'épinglage sous Firefox quand Lenis est coupé. Ignoré si Lenis est actif. »

**Sections épinglées (bb-steps)**
- `stepsBreakpoint` (nombre, px, 320 → 2000)
- `stepsOffset` (texte) : vide = `auto`, sinon entier px. Aide : « Hauteur du header fixe. Vide = détection automatique (header Elementor sticky compris). »

- En bas de page : un bloc « Réglages par élément » en lecture seule qui rappelle les classes et attributs `data-bb-*` (copié du README, sans le dupliquer : générer depuis un tableau PHP unique utilisé aussi pour la doc si simple, sinon texte statique).
- Bouton « Réinitialiser » : supprime l'option (`delete_option`) après confirmation JS, nonce dédié.

## Sanitisation

- Nombres : `floatval` / `intval` puis bornés (`min`/`max` ci-dessus) ; hors bornes → défaut.
- `ease` : whitelist, sinon défaut.
- `start` : regex, sinon défaut.
- `disabled_on` : `sanitize_text_field` ligne par ligne, lignes vides retirées, dédoublonnage.
- Cases : `! empty()`. Attention : une case décochée n'est pas envoyée par le formulaire — pour `smooth` et `load_gsap` (défaut true), stocker explicitement `0`/`1` et ne considérer la clé comme « renseignée » que si elle est présente dans l'option.
- `stepsOffset` : vide → ne pas stocker (défaut `'auto'`) ; sinon `absint`.

`bb_reveal_settings_clean()` retourne uniquement les clés renseignées, dans la forme attendue par le JS : `lenis_lerp` et `lenis_wheel` sont regroupés en `'lenis' => ['lerp' => …, 'wheelMultiplier' => …]`.

## Contraintes

- Aucune modification de `assets/bb-reveal.js` : la page ne fait que produire le même `window.BB_REVEAL` qu'aujourd'hui.
- Pas de dépendance externe, pas de framework admin, pas de React. Un fichier CSS admin minimal inline (`wp_add_inline_style` sur `wp-admin`) suffit pour les largeurs de champs.
- Chaînes traduisibles avec `__( '…', 'bb-scroll-reveal' )`, en français.
- Compatible multisite (options par site, pas de `network_admin`).

## Critères d'acceptation

1. Plugin fraîchement activé, option absente : comportement front strictement identique à la version précédente (comparer `window.BB_REVEAL` avant/après).
2. Changer `lerp` à 0.05 dans l'admin → `window.BB_REVEAL.lenis.lerp === 0.05` sur le front, sans purge de cache d'objet nécessaire (l'option est lue à chaque requête).
3. Un `add_filter( 'bb_reveal_config', … )` dans le thème écrase la valeur de l'admin.
4. `enabled_globally` décoché : aucun script du plugin ni CSS de garde dans le `<head>`.
5. Page ou slug listé dans `disabled_on` : idem, uniquement sur cette page.
6. Valeur hors bornes ou invalide saisie → remplacée par le défaut, avec un message `add_settings_error`.
7. `php -l` sans erreur ; aucune notice PHP avec `WP_DEBUG`.
8. Version incrémentée (3 endroits) et README mis à jour.

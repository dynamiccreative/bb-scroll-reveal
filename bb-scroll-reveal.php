<?php
/**
 * Plugin Name:       BB Scroll Reveal
 * Description:       Révélations au scroll (GSAP + ScrollTrigger + SplitText, Lenis en option) pilotées par classes CSS dans Elementor.
 * Version:           1.7.3
 * Author:            bleuebuzz
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       bb-scroll-reveal
 */

defined( 'ABSPATH' ) || exit;

define( 'BB_REVEAL_VERSION', '1.7.3' );
define( 'BB_REVEAL_GSAP_VERSION', '3.15.0' );
define( 'BB_REVEAL_LENIS_VERSION', '1.3.26' );
define( 'BB_REVEAL_URL', plugin_dir_url( __FILE__ ) );
define( 'BB_REVEAL_PATH', plugin_dir_path( __FILE__ ) );

if ( is_admin() ) {
	require_once BB_REVEAL_PATH . 'includes/class-bb-reveal-settings.php';
	BB_Reveal_Settings::init();

	add_filter(
		'plugin_action_links_' . plugin_basename( __FILE__ ),
		static function ( array $links ): array {
			array_unshift(
				$links,
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-general.php?page=bb-scroll-reveal' ) ),
					esc_html__( 'Réglages', 'bb-scroll-reveal' )
				)
			);

			return $links;
		}
	);
}

/**
 * Valeurs par défaut, dans le code. Elles ne sont jamais écrites en base :
 * l'option ne contient que les réglages effectivement renseignés.
 */
function bb_reveal_defaults(): array {
	return array(
		'y'        => 40,           // Décalage vertical (px) des révélations.
		'duration' => 0.9,          // Durée (s).
		'stagger'  => 0.12,         // Décalage entre enfants (s).
		'ease'     => 'power3.out',
		'start'    => 'top 85%',    // Déclenchement ScrollTrigger.
		'smooth'   => true,         // Lenis (smooth scroll) : actif par défaut depuis 1.5.0 (corrige aussi le saut d'épinglage Firefox).
		'lenis'    => array( 'lerp' => 0.07, 'wheelMultiplier' => 1 ), // Réglages Lenis : lerp 0.1 = réactif, 0.05 = très doux.
		'normalizeScroll' => false, // ScrollTrigger.normalizeScroll : corrige le décalage des épinglages sous Firefox (scroll asynchrone).
		'stepsBreakpoint' => 768,   // Largeur (px) à partir de laquelle bb-steps épingle la section.
		'stepsOffset'     => 'auto', // Hauteur (px) du header fixe sous lequel épingler bb-steps ; 'auto' = détection.
		'hscrollBreakpoint' => 768, // Largeur (px) à partir de laquelle bb-hscroll défile horizontalement.
	);
}

/**
 * Option brute (Réglages > BB Scroll Reveal). Mémoïsée : bb_reveal_config() est
 * appelée plusieurs fois par requête (wp_head, enqueue).
 */
function bb_reveal_settings(): array {
	static $settings = null;

	if ( null === $settings ) {
		$settings = get_option( 'bb_reveal_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
	}

	return $settings;
}

/**
 * Case à cocher : une case décochée est stockée à 0, donc sa présence se teste
 * avec array_key_exists() et non avec empty() — sans quoi un réglage dont le
 * défaut est true ne pourrait jamais être désactivé.
 */
function bb_reveal_setting_bool( string $key, bool $default ): bool {
	$settings = bb_reveal_settings();

	return array_key_exists( $key, $settings ) ? (bool) $settings[ $key ] : $default;
}

/**
 * Réglages de l'admin réduits aux clés de configuration du front, dans la forme
 * attendue par le JS. Les clés d'exploitation (enabled_globally, disabled_on,
 * load_gsap) sont volontairement exclues : elles n'ont rien à faire dans
 * window.BB_REVEAL.
 */
function bb_reveal_settings_clean(): array {
	$settings = bb_reveal_settings();
	$clean    = array();

	foreach ( array( 'y', 'duration', 'stagger', 'ease', 'start', 'stepsBreakpoint', 'stepsOffset', 'hscrollBreakpoint' ) as $key ) {
		if ( array_key_exists( $key, $settings ) ) {
			$clean[ $key ] = $settings[ $key ];
		}
	}

	foreach ( array( 'smooth', 'normalizeScroll' ) as $key ) {
		if ( array_key_exists( $key, $settings ) ) {
			$clean[ $key ] = (bool) $settings[ $key ];
		}
	}

	$lenis = array();
	if ( array_key_exists( 'lenis_lerp', $settings ) ) {
		$lenis['lerp'] = (float) $settings['lenis_lerp'];
	}
	if ( array_key_exists( 'lenis_wheel', $settings ) ) {
		$lenis['wheelMultiplier'] = (float) $settings['lenis_wheel'];
	}
	if ( $lenis ) {
		$clean['lenis'] = $lenis;
	}

	return $clean;
}

/**
 * Pas d'animation dans l'aperçu de l'éditeur Elementor : les éléments doivent rester visibles et éditables.
 */
function bb_reveal_is_elementor_preview(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['elementor-preview'] ) ) {
		return true;
	}

	return class_exists( '\Elementor\Plugin' )
		&& isset( \Elementor\Plugin::$instance->preview )
		&& \Elementor\Plugin::$instance->preview->is_preview_mode();
}

/**
 * Page listée dans « Pages exclues » ? Comparaison sur l'identifiant et sur le
 * slug uniquement : is_page( $liste ) comparerait aussi le titre, et sur-ciblerait
 * silencieusement.
 */
function bb_reveal_is_excluded(): bool {
	$settings = bb_reveal_settings();
	$excluded = isset( $settings['disabled_on'] ) && is_array( $settings['disabled_on'] ) ? $settings['disabled_on'] : array();

	// did_action( 'wp' ) : les tags conditionnels ne répondent pas avant la requête
	// principale, et WP émet un _doing_it_wrong si on les interroge trop tôt.
	if ( ! $excluded || ! did_action( 'wp' ) || ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return in_array( (string) $post->ID, $excluded, true ) || in_array( $post->post_name, $excluded, true );
}

function bb_reveal_is_active(): bool {
	if ( is_admin() || wp_doing_ajax() || is_feed() || bb_reveal_is_elementor_preview() ) {
		return false;
	}

	if ( ! bb_reveal_setting_bool( 'enabled_globally', true ) || bb_reveal_is_excluded() ) {
		return false;
	}

	/** Permet de couper le plugin sur certaines pages : add_filter( 'bb_reveal_enabled', fn() => ! is_checkout() ); */
	return (bool) apply_filters( 'bb_reveal_enabled', true );
}

/**
 * Configuration exposée au JS : défauts du code, écrasés par les réglages
 * renseignés dans l'admin, puis par le filtre bb_reveal_config (le code gagne).
 * array_replace_recursive garde les sous-clés de 'lenis' non renseignées.
 */
function bb_reveal_config(): array {
	return apply_filters(
		'bb_reveal_config',
		array_replace_recursive( bb_reveal_defaults(), bb_reveal_settings_clean() )
	);
}

/**
 * Anti-flash : masque les éléments ciblés avant le rendu, avec filet de sécurité
 * si le JS ne s'exécute pas (bloqué, différé par un plugin de cache, erreur).
 */
add_action(
	'wp_head',
	static function () {
		if ( ! bb_reveal_is_active() ) {
			return;
		}
		$steps_breakpoint = (int) ( bb_reveal_config()['stepsBreakpoint'] ?? 768 );
		?>
<script id="bb-reveal-guard">
(function(d){try{if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;}catch(e){}
d.documentElement.classList.add('bb-js');
setTimeout(function(){if(!window.bbRevealReady){d.documentElement.classList.remove('bb-js');}},3500);})(document);
</script>
<style id="bb-reveal-guard-css">
html.bb-js .bb-reveal,html.bb-js .bb-reveal-children,html.bb-js .bb-split,html.bb-js .bb-step,html.bb-js .bb-hscroll{visibility:hidden}
/* Elementor pose transition:transform var(--e-con-transform-transition-duration,.4s) sur tout
   conteneur (.e-con) : chaque frame GSAP serait lissée sur 0,4 s (saccades), et le translateY posé
   par ScrollTrigger au dépin serait animé (la section « remonte puis redescend »). Neutralisé sur
   les éléments animés et sur la section épinglée via la variable qu'Elementor lit. */
.bb-steps,.bb-steps .e-con,.bb-step,.bb-hscroll,.bb-hscroll .e-con,.bb-hpanel,.bb-reveal,.bb-reveal-children>*,.bb-reveal-children>.e-con-inner>*,.bb-split,.bb-scrub,.bb-parallax{--e-con-transform-transition-duration:0s!important;--transform-transition:0s!important}
.bb-steps-clip{overflow:hidden}
/* bb-hscroll : la section est le cadre visible, la piste la déborde à droite (ou à gauche en rtl). */
.bb-hscroll-clip{overflow:hidden}
/* bb-ink : la couche SVG épouse la première image ; la seconde (source) est retirée du flux. */
.bb-ink .bb-ink-host{position:relative}
.bb-ink .bb-ink-layer{position:absolute;left:0;top:0;pointer-events:none;overflow:visible}
.bb-ink .bb-ink-source,html.bb-js .bb-ink .elementor-widget-image+.elementor-widget-image{display:none}
.bb-split .bb-line-mask{padding-bottom:.08em;margin-bottom:-.08em}
/* Mode slide : la section garde sa hauteur naturelle, le JS l'épingle par son bord bas au bas de
   l'écran, d'où montent les étapes. Les étapes sont calées en bas avec leur hauteur naturelle (façon
   Heron) : elles ne parcourent que leur propre hauteur. Le calage de la ligne en bas est fait par le
   JS (il dépend de l'imbrication réelle) ; les étapes sont masquées d'ici là (html.bb-js). */
@media (min-width:<?php echo esc_html( (string) $steps_breakpoint ); ?>px){
.bb-steps[data-bb-mode="slide"] .bb-step{align-self:flex-start}
}
</style>
		<?php
	},
	1
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! bb_reveal_is_active() ) {
			return;
		}

		$config   = bb_reveal_config();
		$vendor   = BB_REVEAL_URL . 'assets/vendor/';
		$footer   = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);
		$deps     = array( 'bb-gsap', 'bb-gsap-scrolltrigger', 'bb-gsap-splittext' );

		/**
		 * Si le thème ou un addon charge déjà GSAP, décocher « Charger GSAP » dans
		 * les réglages ou passer ce filtre à false, pour éviter une double instance
		 * (source classique de bugs ScrollTrigger).
		 */
		if ( apply_filters( 'bb_reveal_load_gsap', bb_reveal_setting_bool( 'load_gsap', true ) ) ) {
			wp_enqueue_script( 'bb-gsap', $vendor . 'gsap.min.js', array(), BB_REVEAL_GSAP_VERSION, $footer );
			wp_enqueue_script( 'bb-gsap-scrolltrigger', $vendor . 'ScrollTrigger.min.js', array( 'bb-gsap' ), BB_REVEAL_GSAP_VERSION, $footer );
			wp_enqueue_script( 'bb-gsap-splittext', $vendor . 'SplitText.min.js', array( 'bb-gsap' ), BB_REVEAL_GSAP_VERSION, $footer );
		} else {
			$deps = array();
		}

		if ( ! empty( $config['smooth'] ) ) {
			wp_enqueue_style( 'bb-lenis', $vendor . 'lenis.css', array(), BB_REVEAL_LENIS_VERSION );
			wp_enqueue_script( 'bb-lenis', $vendor . 'lenis.min.js', array(), BB_REVEAL_LENIS_VERSION, $footer );
			$deps[] = 'bb-lenis';
		}

		wp_enqueue_script( 'bb-reveal', BB_REVEAL_URL . 'assets/bb-reveal.js', $deps, BB_REVEAL_VERSION, $footer );
		wp_add_inline_script( 'bb-reveal', 'window.BB_REVEAL=' . wp_json_encode( $config ) . ';', 'before' );
	}
);

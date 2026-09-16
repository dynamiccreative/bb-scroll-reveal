<?php
/**
 * Page de réglages : Réglages > BB Scroll Reveal.
 *
 * Ce fichier ne contient que l'interface d'administration. La lecture de l'option
 * et sa fusion avec les défauts vivent dans bb-scroll-reveal.php : le front en a
 * besoin, et cette classe n'est chargée que dans l'admin.
 *
 * Habillage : design system des plugins maison (cf. DC Support Technique),
 * préfixe `bbsr-`, feuille assets/admin.css. Les champs sont rendus à la main
 * depuis le schéma fields() plutôt que par do_settings_sections() : la
 * form-table native de WordPress ne sait pas produire les cards, les
 * interrupteurs ni la barre d'enregistrement collante. L'enregistrement, lui,
 * reste celui de la Settings API (options.php + sanitize_callback).
 *
 * @package bb-scroll-reveal
 */

defined( 'ABSPATH' ) || exit;

final class BB_Reveal_Settings {

	const OPTION = 'bb_reveal_settings';
	const PAGE   = 'bb-scroll-reveal';
	const GROUP  = 'bb_reveal_settings_group';

	public static function init(): void {
		$self = new self();

		add_action( 'admin_menu', array( $self, 'register_menu' ) );
		add_action( 'admin_init', array( $self, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue' ) );
		add_action( 'admin_post_bb_reveal_reset', array( $self, 'handle_reset' ) );
	}

	/**
	 * Schéma unique des champs : pilote le rendu, la sanitisation et l'aide.
	 *
	 * 'default' n'est jamais écrit en base : il sert de repère à l'écran
	 * (filigrane) et de valeur de repli quand une saisie est refusée.
	 * 'col' regroupe des champs CONSÉCUTIFS d'une même section dans une grille.
	 */
	public static function fields(): array {
		$defaults = bb_reveal_defaults();

		return array(
			'enabled_globally' => array(
				'section' => 'general',
				'type'    => 'checkbox',
				'label'   => __( 'Activer les animations', 'bb-scroll-reveal' ),
				'cb'      => __( 'Animer le site', 'bb-scroll-reveal' ),
				'help'    => __( 'Décoché, le plugin ne charge plus rien sur le front, sans avoir à le désactiver. Pratique en recette.', 'bb-scroll-reveal' ),
				'default' => true,
			),
			'disabled_on'      => array(
				'section' => 'general',
				'type'    => 'textarea',
				'label'   => __( 'Pages exclues', 'bb-scroll-reveal' ),
				'help'    => __( 'Une entrée par ligne : identifiant numérique ou slug de la page ou de l\'article. Le plugin y reste inactif.', 'bb-scroll-reveal' ),
				'ph'      => "contact\n42",
				'default' => '',
			),
			'load_gsap'        => array(
				'section' => 'general',
				'type'    => 'checkbox',
				'label'   => __( 'Charger GSAP', 'bb-scroll-reveal' ),
				'cb'      => __( 'Charger GSAP, ScrollTrigger et SplitText', 'bb-scroll-reveal' ),
				'help'    => __( 'À décocher seulement si le thème ou un autre plugin charge déjà GSAP : deux instances cassent ScrollTrigger.', 'bb-scroll-reveal' ),
				'default' => true,
			),
			'y'                => array(
				'section' => 'reveal',
				'type'    => 'number',
				'col'     => true,
				'int'     => true,
				'min'     => 0,
				'max'     => 400,
				'step'    => 1,
				'unit'    => __( 'px', 'bb-scroll-reveal' ),
				'label'   => __( 'Décalage vertical', 'bb-scroll-reveal' ),
				'help'    => __( 'Distance parcourue par un élément pendant sa révélation.', 'bb-scroll-reveal' ),
				'default' => $defaults['y'],
			),
			'duration'         => array(
				'section' => 'reveal',
				'type'    => 'number',
				'col'     => true,
				'min'     => 0.1,
				'max'     => 5,
				'step'    => 0.05,
				'unit'    => __( 's', 'bb-scroll-reveal' ),
				'label'   => __( 'Durée', 'bb-scroll-reveal' ),
				'help'    => __( 'Temps que met une révélation à s\'achever.', 'bb-scroll-reveal' ),
				'default' => $defaults['duration'],
			),
			'stagger'          => array(
				'section' => 'reveal',
				'type'    => 'number',
				'col'     => true,
				'min'     => 0,
				'max'     => 2,
				'step'    => 0.01,
				'unit'    => __( 's', 'bb-scroll-reveal' ),
				'label'   => __( 'Décalage entre enfants', 'bb-scroll-reveal' ),
				'help'    => __( 'Retard d\'un enfant sur le précédent dans une cascade (bb-reveal-children, bb-split).', 'bb-scroll-reveal' ),
				'default' => $defaults['stagger'],
			),
			'ease'             => array(
				'section' => 'reveal',
				'type'    => 'select',
				'label'   => __( 'Courbe d\'accélération', 'bb-scroll-reveal' ),
				'help'    => __( 'Le « .out » freine à l\'arrivée : le mouvement part vite et se pose. « none » est linéaire.', 'bb-scroll-reveal' ),
				'choices' => array( 'power1.out', 'power2.out', 'power3.out', 'power4.out', 'expo.out', 'circ.out', 'back.out(1.4)', 'none' ),
				'default' => $defaults['ease'],
			),
			'start'            => array(
				'section' => 'reveal',
				'type'    => 'text',
				'label'   => __( 'Point de déclenchement', 'bb-scroll-reveal' ),
				'help'    => __( 'Position de l\'élément, puis position dans la fenêtre : « top 85% » déclenche quand le haut de l\'élément atteint 85 % de la hauteur d\'écran. Pour une syntaxe plus fine (« top top+=100 »), passer par data-bb-start sur l\'élément.', 'bb-scroll-reveal' ),
				'default' => $defaults['start'],
			),
			'smooth'           => array(
				'section' => 'scroll',
				'type'    => 'checkbox',
				'label'   => __( 'Défilement lissé', 'bb-scroll-reveal' ),
				'cb'      => __( 'Activer Lenis', 'bb-scroll-reveal' ),
				'help'    => __( 'Donne l\'inertie au défilement et supprime le saut d\'épinglage sous Firefox.', 'bb-scroll-reveal' ),
				'default' => true,
			),
			'lenis_lerp'       => array(
				'section' => 'scroll',
				'type'    => 'number',
				'col'     => true,
				'min'     => 0.02,
				'max'     => 0.3,
				'step'    => 0.01,
				'label'   => __( 'Inertie (lerp)', 'bb-scroll-reveal' ),
				'help'    => __( '0.1 réactif · 0.07 défaut · 0.05 très doux', 'bb-scroll-reveal' ),
				'default' => $defaults['lenis']['lerp'],
			),
			'lenis_wheel'      => array(
				'section' => 'scroll',
				'type'    => 'number',
				'col'     => true,
				'min'     => 0.5,
				'max'     => 2,
				'step'    => 0.1,
				'label'   => __( 'Sensibilité de la molette', 'bb-scroll-reveal' ),
				'help'    => __( 'Multiplie la distance parcourue à chaque cran de molette.', 'bb-scroll-reveal' ),
				'default' => $defaults['lenis']['wheelMultiplier'],
			),
			'normalizeScroll'  => array(
				'section' => 'scroll',
				'type'    => 'checkbox',
				'label'   => __( 'Normaliser le défilement', 'bb-scroll-reveal' ),
				'cb'      => __( 'Laisser ScrollTrigger piloter le défilement', 'bb-scroll-reveal' ),
				'help'    => __( 'Corrige le saut d\'épinglage sous Firefox quand Lenis est coupé. Ignoré si Lenis est actif.', 'bb-scroll-reveal' ),
				'default' => false,
			),
			'stepsBreakpoint'  => array(
				'section' => 'steps',
				'type'    => 'number',
				'col'     => true,
				'int'     => true,
				'min'     => 320,
				'max'     => 2000,
				'step'    => 1,
				'unit'    => __( 'px', 'bb-scroll-reveal' ),
				'label'   => __( 'Largeur mini d\'épinglage', 'bb-scroll-reveal' ),
				'help'    => __( 'Sous cette largeur, les sections bb-steps ne sont plus épinglées : chaque étape se révèle à son entrée à l\'écran.', 'bb-scroll-reveal' ),
				'default' => $defaults['stepsBreakpoint'],
			),
			'stepsOffset'      => array(
				'section' => 'steps',
				'type'    => 'text',
				'col'     => true,
				'unit'    => __( 'px', 'bb-scroll-reveal' ),
				'label'   => __( 'Décalage du header', 'bb-scroll-reveal' ),
				'help'    => __( 'Hauteur du header fixe, en pixels. Vide = détection automatique (header Elementor sticky compris), 0 = aucun décalage.', 'bb-scroll-reveal' ),
				'default' => '',
			),
			'hscrollBreakpoint' => array(
				'section' => 'hscroll',
				'type'    => 'number',
				'int'     => true,
				'min'     => 320,
				'max'     => 2000,
				'step'    => 1,
				'unit'    => __( 'px', 'bb-scroll-reveal' ),
				'label'   => __( 'Largeur mini de défilement horizontal', 'bb-scroll-reveal' ),
				'help'    => __( 'Sous cette largeur, les sections bb-hscroll ne défilent plus horizontalement : les panneaux reprennent la mise en page Elementor et s\'empilent normalement.', 'bb-scroll-reveal' ),
				'default' => $defaults['hscrollBreakpoint'],
			),
		);
	}

	/**
	 * Un onglet par section : libellé de navigation, en-tête de page, en-tête de
	 * card et aide de la colonne de droite.
	 */
	private function sections(): array {
		return array(
			'general' => array(
				'group' => __( 'Réglages', 'bb-scroll-reveal' ),
				'nav'   => __( 'Général', 'bb-scroll-reveal' ),
				'icon'  => 'admin-generic',
				'intro' => __( 'Portée du plugin sur le site et chargement des bibliothèques.', 'bb-scroll-reveal' ),
				'card'  => array(
					'icon'  => 'admin-settings',
					'title' => __( 'Activation et chargement', 'bb-scroll-reveal' ),
					'desc'  => __( 'Trois réglages d\'exploitation, sans équivalent dans window.BB_REVEAL.', 'bb-scroll-reveal' ),
				),
				'help'  => __( 'Couper les animations ici n\'enlève rien du site : les classes restent posées dans Elementor, le plugin cesse simplement de charger ses scripts et son CSS de garde. Le contenu s\'affiche alors sans animation, jamais masqué.', 'bb-scroll-reveal' ),
			),
			'reveal'  => array(
				'group' => __( 'Réglages', 'bb-scroll-reveal' ),
				'nav'   => __( 'Révélations', 'bb-scroll-reveal' ),
				'icon'  => 'visibility',
				'intro' => __( 'Valeurs par défaut des classes bb-reveal, bb-reveal-children et bb-split.', 'bb-scroll-reveal' ),
				'card'  => array(
					'icon'  => 'visibility',
					'title' => __( 'Mouvement par défaut', 'bb-scroll-reveal' ),
					'desc'  => __( 'Chaque élément peut les surcharger avec ses attributs data-bb-*.', 'bb-scroll-reveal' ),
				),
				'help'  => __( 'Une durée courte (0,6 – 0,9 s) et un décalage modéré (30 – 60 px) passent mieux sur une page dense : l\'animation accompagne la lecture au lieu de la retenir. Le décalage entre enfants ne joue que sur les cascades.', 'bb-scroll-reveal' ),
			),
			'scroll'  => array(
				'group' => __( 'Réglages', 'bb-scroll-reveal' ),
				'nav'   => __( 'Défilement', 'bb-scroll-reveal' ),
				'icon'  => 'image-flip-vertical',
				'intro' => __( 'Inertie du défilement (Lenis) et correctif d\'épinglage.', 'bb-scroll-reveal' ),
				'card'  => array(
					'icon'  => 'image-flip-vertical',
					'title' => __( 'Défilement lissé', 'bb-scroll-reveal' ),
					'desc'  => __( 'Lenis pilote le défilement en JavaScript, synchronisé avec le rendu.', 'bb-scroll-reveal' ),
				),
				'help'  => __( 'Sous Firefox, un élément épinglé est recalé une frame après le défilement : la section saute en entrant dans l\'épinglage. Lenis supprime ce saut. S\'il est coupé, « Normaliser le défilement » joue le même rôle, en plus léger.', 'bb-scroll-reveal' ),
			),
			'steps'   => array(
				'group' => __( 'Sections', 'bb-scroll-reveal' ),
				'nav'   => __( 'Sections épinglées', 'bb-scroll-reveal' ),
				'icon'  => 'editor-insertmore',
				'intro' => __( 'Comportement des sections bb-steps, dont les étapes se révèlent une à une.', 'bb-scroll-reveal' ),
				'card'  => array(
					'icon'  => 'editor-insertmore',
					'title' => __( 'Épinglage (bb-steps)', 'bb-scroll-reveal' ),
					'desc'  => __( 'La longueur de course, le mode et le rembobinage se règlent section par section.', 'bb-scroll-reveal' ),
				),
				'help'  => __( 'Le décalage du header évite que la section épinglée passe sous un header fixe. Laissé vide, le plugin mesure le header lui-même, y compris un header Elementor sticky. Une section peut le surcharger avec data-bb-offset.', 'bb-scroll-reveal' ),
			),
			'hscroll' => array(
				'group' => __( 'Sections', 'bb-scroll-reveal' ),
				'nav'   => __( 'Défilement horizontal', 'bb-scroll-reveal' ),
				'icon'  => 'leftright',
				'intro' => __( 'Comportement des sections bb-hscroll, dont les panneaux défilent horizontalement.', 'bb-scroll-reveal' ),
				'card'  => array(
					'icon'  => 'leftright',
					'title' => __( 'Défilement horizontal (bb-hscroll)', 'bb-scroll-reveal' ),
					'desc'  => __( 'Le sens, la course et la largeur des panneaux se règlent section par section.', 'bb-scroll-reveal' ),
				),
				'help'  => __( 'Le padding horizontal du conteneur piste se lit comme un vide au début et à la fin du défilement : le mettre à 0 et porter le padding décoratif sur les panneaux. Le plugin l\'avertit dans la console du navigateur.', 'bb-scroll-reveal' ),
			),
		);
	}

	public function register_menu(): void {
		add_options_page(
			__( 'BB Scroll Reveal', 'bb-scroll-reveal' ),
			__( 'BB Scroll Reveal', 'bb-scroll-reveal' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Seul register_setting() est nécessaire : les champs sont rendus par cette
	 * classe, pas par do_settings_sections(). L'enregistrement passe toujours par
	 * options.php, qui appelle sanitize() puis redirige.
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
	}

	public function enqueue( string $hook ): void {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_style( 'bb-reveal-admin', BB_REVEAL_URL . 'assets/admin.css', array( 'dashicons' ), BB_REVEAL_VERSION );
		wp_enqueue_script( 'bb-reveal-admin', BB_REVEAL_URL . 'assets/admin.js', array(), BB_REVEAL_VERSION, true );
	}

	// =====================================================================
	// RENDU
	// =====================================================================

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$sections = $this->sections();
		$active   = key( $sections );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$reset = isset( $_GET['bb-reveal-reset'] );
		?>
		<div class="bbsr-ui">

			<div class="bbsr-header">
				<div class="bbsr-header-left">
					<div class="bbsr-logo"><span class="dashicons dashicons-visibility"></span></div>
					<div>
						<h1><?php esc_html_e( 'BB Scroll Reveal', 'bb-scroll-reveal' ); ?></h1>
						<p><?php esc_html_e( 'Révélations au scroll pilotées par classes CSS dans Elementor', 'bb-scroll-reveal' ); ?></p>
					</div>
				</div>
				<div class="bbsr-header-right">
					<?php $this->render_header_pill(); ?>
					<span class="bbsr-version">v<?php echo esc_html( BB_REVEAL_VERSION ); ?></span>
				</div>
			</div>

			<?php if ( $reset ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Réglages réinitialisés : les valeurs par défaut du plugin sont de nouveau appliquées.', 'bb-scroll-reveal' ); ?></p>
				</div>
			<?php endif; ?>

			<noscript>
				<style>.bbsr-panel{display:flex!important}.bbsr-tabs,.bbsr-savebar{display:none}.bbsr-layout{padding-bottom:0}.bbsr-help{display:block}</style>
			</noscript>

			<div class="bbsr-layout">

				<nav class="bbsr-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Sections des réglages', 'bb-scroll-reveal' ); ?>">
					<?php $this->render_nav( $sections, $active ); ?>
				</nav>

				<div class="bbsr-main">
					<form action="options.php" method="post" data-bbsr-form>
						<?php settings_fields( self::GROUP ); ?>

						<?php foreach ( $sections as $id => $section ) : ?>
							<section class="bbsr-panel<?php echo $id === $active ? ' active' : ''; ?>"
								data-bbsr-panel="<?php echo esc_attr( $id ); ?>"
								role="tabpanel" aria-labelledby="bbsr-tab-<?php echo esc_attr( $id ); ?>"
								<?php echo $id === $active ? '' : 'hidden'; ?>>

								<div class="bbsr-pagehead">
									<h2><?php echo esc_html( $section['nav'] ); ?></h2>
									<?php if ( ! empty( $section['intro'] ) ) : ?>
										<p><?php echo esc_html( $section['intro'] ); ?></p>
									<?php endif; ?>
								</div>

								<div class="bbsr-card">
									<div class="bbsr-card-header">
										<div class="bbsr-card-icon"><span class="dashicons dashicons-<?php echo esc_attr( $section['card']['icon'] ); ?>"></span></div>
										<div>
											<h3><?php echo esc_html( $section['card']['title'] ); ?></h3>
											<p><?php echo esc_html( $section['card']['desc'] ); ?></p>
										</div>
									</div>
									<div class="bbsr-card-body">
										<?php $this->render_section_fields( $id ); ?>
									</div>
								</div>
							</section>
						<?php endforeach; ?>

						<?php $this->render_savebar(); ?>
					</form>

					<?php $this->render_reference(); ?>
				</div>

				<?php $this->render_aside( $sections, $active ); ?>
			</div>
		</div>
		<?php
	}

	/** Voyant d'état global, à droite du header. */
	private function render_header_pill(): void {
		$on = bb_reveal_setting_bool( 'enabled_globally', true );

		printf(
			'<span class="bbsr-pill %s"><span class="dashicons dashicons-%s"></span> %s</span>',
			$on ? 'ok' : 'warn',
			$on ? 'yes' : 'warning',
			$on ? esc_html__( 'Animations actives', 'bb-scroll-reveal' ) : esc_html__( 'Animations coupées', 'bb-scroll-reveal' )
		);
	}

	/**
	 * Colonne d'onglets, groupée. Le compteur dit combien de réglages de
	 * l'onglet sont réellement enregistrés : le reste suit les défauts du code.
	 */
	private function render_nav( array $sections, string $active ): void {
		$counts = $this->stored_counts();
		$groups = array();

		foreach ( $sections as $id => $section ) {
			$groups[ $section['group'] ][ $id ] = $section;
		}

		foreach ( $groups as $label => $group ) {
			echo '<div class="bbsr-nav-group"><span class="bbsr-nav-label">' . esc_html( $label ) . '</span>';

			foreach ( $group as $id => $section ) {
				printf(
					'<a href="#%1$s" id="bbsr-tab-%1$s" class="bbsr-tab%2$s" data-bbsr-tab="%1$s" role="tab" aria-selected="%3$s" aria-controls="%1$s">'
					. '<span class="dashicons dashicons-%4$s"></span><span class="bbsr-tab-label">%5$s</span>%6$s</a>',
					esc_attr( $id ),
					$id === $active ? ' active' : '',
					$id === $active ? 'true' : 'false',
					esc_attr( $section['icon'] ),
					esc_html( $section['nav'] ),
					empty( $counts[ $id ] ) ? '' : sprintf(
						'<span class="bbsr-tab-count" title="%s">%d</span>',
						esc_attr__( 'Réglages enregistrés dans cet onglet', 'bb-scroll-reveal' ),
						(int) $counts[ $id ]
					)
				);
			}

			echo '</div>';
		}

		// Onglet de documentation : pas de champ, donc pas de compteur.
		echo '<div class="bbsr-nav-group"><span class="bbsr-nav-label">' . esc_html__( 'Documentation', 'bb-scroll-reveal' ) . '</span>';
		printf(
			'<a href="#reference" id="bbsr-tab-reference" class="bbsr-tab" data-bbsr-tab="reference" role="tab" aria-selected="false" aria-controls="reference">'
			. '<span class="dashicons dashicons-editor-code"></span><span class="bbsr-tab-label">%s</span></a>',
			esc_html__( 'Classes et attributs', 'bb-scroll-reveal' )
		);
		echo '</div>';
	}

	/**
	 * Champs d'une section. Des champs CONSÉCUTIFS portant 'col' sont regroupés
	 * dans une grille : trois champs nombre côte à côte se lisent mieux
	 * qu'empilés sur toute la largeur de la card.
	 */
	private function render_section_fields( string $section ): void {
		$fields = array_filter(
			self::fields(),
			static function ( $field ) use ( $section ) {
				return $field['section'] === $section;
			}
		);

		$run = array();

		$flush = function () use ( &$run ) {
			if ( ! $run ) {
				return;
			}
			if ( count( $run ) > 1 ) {
				echo '<div class="bbsr-cols">';
			}
			foreach ( $run as $key => $field ) {
				$this->render_field( $key, $field );
			}
			if ( count( $run ) > 1 ) {
				echo '</div>';
			}
			$run = array();
		};

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['col'] ) ) {
				$run[ $key ] = $field;
				continue;
			}
			$flush();
			$this->render_field( $key, $field );
		}

		$flush();
	}

	private function render_field( string $key, array $field ): void {
		$value = $this->stored( $key );
		$id    = 'bbsr-' . $key;
		$name  = self::OPTION . '[' . $key . ']';

		echo '<div class="bbsr-field">';

		// L'interrupteur porte son libellé dans sa propre ligne ; les autres
		// types gardent le libellé posé au-dessus du contrôle.
		if ( 'checkbox' !== $field['type'] ) {
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
		}

		switch ( $field['type'] ) {
			case 'checkbox':
				$checked = null === $value ? ! empty( $field['default'] ) : (bool) $value;
				echo '<div class="bbsr-switch-row"><div class="bbsr-switch-tt">';
				printf( '<b>%s</b>', esc_html( $field['label'] ) );
				printf( '<span>%s</span>', esc_html( $field['help'] ) );
				echo '</div>';
				printf(
					'<label class="bbsr-switch" for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s aria-label="%4$s">'
					. '<span class="bbsr-switch-track"></span></label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( $checked, true, false ),
					esc_attr( $field['cb'] )
				);
				echo '</div></div>';
				return; // L'aide est déjà dans la ligne d'interrupteur.

			case 'number':
				echo '<div class="bbsr-inline">';
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" min="%5$s" max="%6$s" step="%7$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( null === $value ? '' : (string) $value ),
					esc_attr( (string) $field['default'] ),
					esc_attr( (string) $field['min'] ),
					esc_attr( (string) $field['max'] ),
					esc_attr( (string) $field['step'] )
				);
				if ( ! empty( $field['unit'] ) ) {
					echo '<span class="bbsr-unit">' . esc_html( $field['unit'] ) . '</span>';
				}
				echo '<span class="bbsr-default">' . esc_html__( 'défaut', 'bb-scroll-reveal' ) . ' <b>' . esc_html( (string) $field['default'] ) . '</b></span>';
				echo '</div>';
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['choices'] as $choice ) {
					printf(
						'<option value="%1$s"%2$s>%1$s%3$s</option>',
						esc_attr( $choice ),
						selected( null === $value ? $field['default'] : $value, $choice, false ),
						$choice === $field['default'] ? esc_html__( ' — défaut', 'bb-scroll-reveal' ) : ''
					);
				}
				echo '</select>';
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" placeholder="%3$s">%4$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) ( $field['ph'] ?? '' ) ),
					esc_textarea( is_array( $value ) ? implode( "\n", $value ) : '' )
				);
				break;

			default:
				echo '<div class="bbsr-inline">';
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( null === $value ? '' : (string) $value ),
					esc_attr( '' === (string) $field['default'] ? __( 'auto', 'bb-scroll-reveal' ) : (string) $field['default'] )
				);
				if ( ! empty( $field['unit'] ) ) {
					echo '<span class="bbsr-unit">' . esc_html( $field['unit'] ) . '</span>';
				}
				echo '</div>';
		}

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="bbsr-desc">' . esc_html( $field['help'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Barre d'enregistrement collante, rendue À L'INTÉRIEUR du formulaire : le
	 * bouton reste un submit natif (fonctionne sans JS), et position:fixed la
	 * sort du flux. admin.js bascule data-dirty dès qu'un champ change.
	 */
	private function render_savebar(): void {
		?>
		<div class="bbsr-savebar" data-dirty="false" data-bbsr-savebar>
			<div class="bbsr-savebar-state">
				<span class="bbsr-savebar-ic">
					<span class="dashicons dashicons-yes bbsr-savebar-clean"></span>
					<span class="dashicons dashicons-warning bbsr-savebar-dirty"></span>
				</span>
				<span class="bbsr-savebar-clean"><?php esc_html_e( 'Tous les réglages sont enregistrés', 'bb-scroll-reveal' ); ?></span>
				<span class="bbsr-savebar-dirty"><?php esc_html_e( 'Modifications non enregistrées', 'bb-scroll-reveal' ); ?></span>
			</div>
			<div class="bbsr-savebar-acts">
				<button type="button" class="bbsr-btn bbsr-btn-ghost" data-bbsr-cancel><?php esc_html_e( 'Annuler', 'bb-scroll-reveal' ); ?></button>
				<button type="submit" class="bbsr-btn">
					<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Enregistrer', 'bb-scroll-reveal' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/** Colonne de droite : aide de l'onglet actif, état du front, remise à zéro. */
	private function render_aside( array $sections, string $active ): void {
		$settings = bb_reveal_settings();
		$excluded = isset( $settings['disabled_on'] ) && is_array( $settings['disabled_on'] ) ? count( $settings['disabled_on'] ) : 0;
		?>
		<aside class="bbsr-aside">

			<div class="bbsr-card bbsr-card-sm">
				<h3 class="bbsr-aside-title"><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'À savoir', 'bb-scroll-reveal' ); ?></h3>
				<?php foreach ( $sections as $id => $section ) : ?>
					<div class="bbsr-help<?php echo $id === $active ? ' active' : ''; ?>" data-bbsr-help="<?php echo esc_attr( $id ); ?>">
						<p><?php echo esc_html( $section['help'] ); ?></p>
					</div>
				<?php endforeach; ?>
				<div class="bbsr-help" data-bbsr-help="reference">
					<p><?php esc_html_e( 'Ces réglages se posent dans Elementor : les classes dans Avancé > Classes CSS, les attributs dans Avancé > Attributs, une ligne par attribut.', 'bb-scroll-reveal' ); ?></p>
				</div>
				<p class="bbsr-aside-note"><?php esc_html_e( 'Un champ laissé vide garde la valeur par défaut du plugin, affichée en filigrane. Un filtre bb_reveal_config placé dans le thème reste prioritaire sur cette page.', 'bb-scroll-reveal' ); ?></p>
			</div>

			<div class="bbsr-card bbsr-card-sm">
				<h3 class="bbsr-aside-title"><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'État du front', 'bb-scroll-reveal' ); ?></h3>
				<?php
				$this->render_status(
					bb_reveal_setting_bool( 'enabled_globally', true ),
					__( 'Animations actives', 'bb-scroll-reveal' ),
					__( 'Animations coupées sur tout le site', 'bb-scroll-reveal' )
				);
				$this->render_status(
					! empty( bb_reveal_config()['smooth'] ),
					__( 'Défilement lissé (Lenis)', 'bb-scroll-reveal' ),
					__( 'Lenis désactivé', 'bb-scroll-reveal' )
				);
				$this->render_status(
					bb_reveal_setting_bool( 'load_gsap', true ),
					__( 'GSAP chargé par le plugin', 'bb-scroll-reveal' ),
					__( 'GSAP attendu depuis le thème', 'bb-scroll-reveal' )
				);

				if ( $excluded ) {
					printf(
						'<div class="bbsr-status idle"><span class="dashicons dashicons-hidden"></span><span>%s</span></div>',
						esc_html(
							sprintf(
								/* translators: %d: nombre de pages exclues */
								_n( '%d page exclue', '%d pages exclues', $excluded, 'bb-scroll-reveal' ),
								$excluded
							)
						)
					);
				}
				?>
			</div>

			<div class="bbsr-card bbsr-card-sm">
				<h3 class="bbsr-aside-title"><span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Réinitialiser', 'bb-scroll-reveal' ); ?></h3>
				<p class="bbsr-aside-note" style="margin:0 0 12px">
					<?php esc_html_e( 'Supprime l\'option : tous les réglages reviennent aux valeurs par défaut du plugin.', 'bb-scroll-reveal' ); ?>
				</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
					data-bbsr-reset
					data-bbsr-confirm="<?php esc_attr_e( 'Supprimer tous les réglages et revenir aux valeurs par défaut ?', 'bb-scroll-reveal' ); ?>">
					<input type="hidden" name="action" value="bb_reveal_reset">
					<?php wp_nonce_field( 'bb_reveal_reset' ); ?>
					<button type="submit" class="bbsr-btn bbsr-btn-danger">
						<span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Tout réinitialiser', 'bb-scroll-reveal' ); ?>
					</button>
				</form>
			</div>

		</aside>
		<?php
	}

	private function render_status( bool $on, string $yes, string $no ): void {
		printf(
			'<div class="bbsr-status %s"><span class="dashicons dashicons-%s"></span><span>%s</span></div>',
			$on ? 'ok' : 'idle',
			$on ? 'yes-alt' : 'marker',
			esc_html( $on ? $yes : $no )
		);
	}

	/**
	 * Rappel en lecture seule des réglages qui ne passent pas par cette page.
	 * Le README reste la documentation de référence.
	 *
	 * Chaque ligne porte un pictogramme : dans une liste de vingt-trois entrées
	 * qui se ressemblent toutes (« bb-… », « data-bb-… »), la vignette est ce qui
	 * se retrouve à l'œil, et sa teinte dit à quelle famille l'entrée appartient.
	 */
	private function render_reference(): void {
		$classes = array(
			'bb-reveal'          => array( 'reveal', 'indigo', __( 'fade + montée, une seule fois', 'bb-scroll-reveal' ) ),
			'bb-reveal-children' => array( 'cascade', 'indigo', __( 'enfants directs révélés en cascade', 'bb-scroll-reveal' ) ),
			'bb-split'           => array( 'split', 'indigo', __( 'texte révélé ligne par ligne sous masque', 'bb-scroll-reveal' ) ),
			'bb-scrub'           => array( 'scrub', 'cyan', __( 'opacité et échelle liées au scroll', 'bb-scroll-reveal' ) ),
			'bb-parallax'        => array( 'layers', 'cyan', __( 'parallaxe verticale liée au scroll', 'bb-scroll-reveal' ) ),
			'bb-steps'           => array( 'steps', 'amber', __( 'section épinglée, étapes révélées une à une', 'bb-scroll-reveal' ) ),
			'bb-step'            => array( 'step', 'amber', __( 'une étape pilotée par la section bb-steps parente', 'bb-scroll-reveal' ) ),
			'bb-hscroll'         => array( 'hscroll', 'green', __( 'section épinglée dont les panneaux défilent horizontalement', 'bb-scroll-reveal' ) ),
			'bb-hpanel'          => array( 'hpanel', 'green', __( 'un panneau de la piste bb-hscroll parente', 'bb-scroll-reveal' ) ),
			'bb-ink'             => array( 'ink', 'violet', __( 'seconde image révélée par une tache d\'encre', 'bb-scroll-reveal' ) ),
		);

		$attributes = array(
			'data-bb-y|60'          => array( 'arrows-y', 'indigo', __( 'décalage vertical de cet élément', 'bb-scroll-reveal' ) ),
			'data-bb-duration|1.2'  => array( 'clock', 'indigo', __( 'durée', 'bb-scroll-reveal' ) ),
			'data-bb-delay|0.2'     => array( 'hourglass', 'indigo', __( 'retard avant le départ', 'bb-scroll-reveal' ) ),
			'data-bb-stagger|0.15'  => array( 'cascade', 'indigo', __( 'décalage entre enfants', 'bb-scroll-reveal' ) ),
			'data-bb-start|top 70%' => array( 'trigger', 'indigo', __( 'point de déclenchement', 'bb-scroll-reveal' ) ),
			'data-bb-parallax|15'   => array( 'layers', 'cyan', __( 'amplitude de la parallaxe', 'bb-scroll-reveal' ) ),
			'data-bb-distance|60'   => array( 'ruler', 'amber', __( 'bb-steps : hauteur d\'écran scrollée par étape, en % · bb-hscroll : longueur de scroll en % du déplacement horizontal', 'bb-scroll-reveal' ) ),
			'data-bb-mode|slide'    => array( 'slide', 'amber', __( 'bb-steps : les étapes montent du bas au lieu d\'un fondu', 'bb-scroll-reveal' ) ),
			'data-bb-pin|top'       => array( 'pin', 'amber', __( 'bb-steps slide : épinglage plein écran', 'bb-scroll-reveal' ) ),
			'data-bb-offset|96'     => array( 'offset', 'amber', __( 'bb-steps, bb-hscroll : hauteur du header fixe pour cette section', 'bb-scroll-reveal' ) ),
			'data-bb-replay'        => array( 'replay', 'amber', __( 'bb-steps : rembobine les animations internes au retour', 'bb-scroll-reveal' ) ),
			'data-bb-direction|rtl' => array( 'direction', 'green', __( 'bb-hscroll : défilement de droite à gauche', 'bb-scroll-reveal' ) ),
			'data-bb-width|60vw'    => array( 'width', 'green', __( 'bb-hscroll : largeur des panneaux (vw, px, ou un nombre lu en vw)', 'bb-scroll-reveal' ) ),
		);
		?>
		<section class="bbsr-panel" data-bbsr-panel="reference" role="tabpanel" aria-labelledby="bbsr-tab-reference" hidden>
			<?php $this->render_icon_sprite(); ?>

			<div class="bbsr-pagehead">
				<h2><?php esc_html_e( 'Classes et attributs', 'bb-scroll-reveal' ); ?></h2>
				<p><?php esc_html_e( 'Les réglages par élément ne passent pas par cette page : ils se posent dans Elementor, onglet Avancé. Documentation complète dans le README du plugin.', 'bb-scroll-reveal' ); ?></p>
			</div>

			<div class="bbsr-card">
				<div class="bbsr-card-header">
					<div class="bbsr-card-icon"><span class="dashicons dashicons-tag"></span></div>
					<div>
						<h3><?php esc_html_e( 'Classes CSS', 'bb-scroll-reveal' ); ?></h3>
						<p><?php esc_html_e( 'À poser dans Avancé > Classes CSS de l\'élément.', 'bb-scroll-reveal' ); ?></p>
					</div>
				</div>
				<?php $this->render_reference_rows( $classes ); ?>
			</div>

			<div class="bbsr-card">
				<div class="bbsr-card-header">
					<div class="bbsr-card-icon green"><span class="dashicons dashicons-editor-code"></span></div>
					<div>
						<h3><?php esc_html_e( 'Attributs', 'bb-scroll-reveal' ); ?></h3>
						<p><?php esc_html_e( 'À poser dans Avancé > Attributs, une ligne par attribut (clé|valeur).', 'bb-scroll-reveal' ); ?></p>
					</div>
				</div>
				<?php $this->render_reference_rows( $attributes ); ?>
			</div>

			<p class="bbsr-legend">
				<span data-tone="indigo"><i></i><?php esc_html_e( 'Révélations', 'bb-scroll-reveal' ); ?></span>
				<span data-tone="cyan"><i></i><?php esc_html_e( 'Liés au scroll', 'bb-scroll-reveal' ); ?></span>
				<span data-tone="amber"><i></i><?php esc_html_e( 'Sections épinglées', 'bb-scroll-reveal' ); ?></span>
				<span data-tone="green"><i></i><?php esc_html_e( 'Défilement horizontal', 'bb-scroll-reveal' ); ?></span>
				<span data-tone="violet"><i></i><?php esc_html_e( 'Tache d\'encre', 'bb-scroll-reveal' ); ?></span>
			</p>
		</section>
		<?php
	}

	/**
	 * Une table de référence : vignette + nom en code, puis rôle.
	 *
	 * @param array $rows nom => array( picto, teinte, rôle ).
	 */
	private function render_reference_rows( array $rows ): void {
		echo '<table class="bbsr-table"><tbody>';

		foreach ( $rows as $name => $row ) {
			list( $icon, $tone, $role ) = $row;

			printf(
				'<tr><td class="bbsr-name"><span class="bbsr-ico" data-tone="%1$s"><svg aria-hidden="true" focusable="false"><use href="#bbsr-i-%2$s"></use></svg></span>'
				. '<code>%3$s</code></td><td>%4$s</td></tr>',
				esc_attr( $tone ),
				esc_attr( $icon ),
				esc_html( $name ),
				esc_html( $role )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Sprite des pictogrammes, posé une fois en tête de l'onglet.
	 *
	 * Dessinés ici plutôt qu'empruntés à une police d'icônes : dashicons n'a rien
	 * pour « parallaxe », « piste horizontale » ou « tache d'encre », et un SVG
	 * inline suit la couleur et la taille de son conteneur sans charger de
	 * fichier. Les symboles se partagent — data-bb-stagger reprend celui de
	 * bb-reveal-children, data-bb-parallax celui de bb-parallax.
	 */
	private function render_icon_sprite(): void {
		?>
		<svg class="bbsr-sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
			<symbol id="bbsr-i-reveal" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M12 8V2.5"/><path d="M8.7 5.8 12 2.5l3.3 3.3"/></symbol>
			<symbol id="bbsr-i-cascade" viewBox="0 0 24 24"><path d="M4 6.5h9"/><path d="M7.5 12h9"/><path d="M11 17.5h9"/></symbol>
			<symbol id="bbsr-i-split" viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 11h12"/><path d="M4 16h8"/><path d="M3 20h18" stroke-dasharray="3 2.6" opacity=".55"/></symbol>
			<symbol id="bbsr-i-scrub" viewBox="0 0 24 24"><rect x="3" y="4.5" width="11" height="15" rx="2" opacity=".5"/><path d="M19 4.5v15"/><path d="M17 7 19 4.5 21 7"/><path d="M17 17 19 19.5 21 17"/></symbol>
			<symbol id="bbsr-i-layers" viewBox="0 0 24 24"><rect x="3" y="8.5" width="9.5" height="11" rx="2"/><rect x="11.5" y="4.5" width="9.5" height="11" rx="2" opacity=".55"/></symbol>
			<symbol id="bbsr-i-steps" viewBox="0 0 24 24"><path d="M3 20h4.6v-4.6h4.6v-4.6h4.6V6.2H21"/></symbol>
			<symbol id="bbsr-i-step" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4.2" rx="1.4" opacity=".35"/><rect x="3" y="9.9" width="18" height="4.2" rx="1.4"/><rect x="3" y="15.8" width="18" height="4.2" rx="1.4" opacity=".35"/></symbol>
			<symbol id="bbsr-i-hscroll" viewBox="0 0 24 24"><rect x="2.5" y="6" width="19" height="12" rx="2"/><path d="M7 12h8.6"/><path d="M12.8 9.2 15.6 12l-2.8 2.8"/></symbol>
			<symbol id="bbsr-i-hpanel" viewBox="0 0 24 24"><rect x="3" y="5" width="7.5" height="14" rx="1.8"/><rect x="13" y="5" width="7.5" height="14" rx="1.8" opacity=".35"/></symbol>
			<symbol id="bbsr-i-ink" viewBox="0 0 24 24"><path d="M12 3.2c3.4 2.6 6.6 5 6.6 8.7a6.6 6.6 0 0 1-13.2 0c0-3.7 3.2-6.1 6.6-8.7Z"/><circle cx="18.2" cy="5.2" r="1.3" opacity=".6"/></symbol>
			<symbol id="bbsr-i-arrows-y" viewBox="0 0 24 24"><path d="M12 3.5v17"/><path d="M8.6 6.9 12 3.5l3.4 3.4"/><path d="M8.6 17.1 12 20.5l3.4-3.4"/></symbol>
			<symbol id="bbsr-i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.2"/><path d="M12 7.2V12l3.2 1.9"/></symbol>
			<symbol id="bbsr-i-hourglass" viewBox="0 0 24 24"><path d="M6.8 3.2h10.4"/><path d="M6.8 20.8h10.4"/><path d="M8.4 3.2v3.3c0 1.9 3.6 3.6 3.6 5.5s-3.6 3.6-3.6 5.5v3.3"/><path d="M15.6 3.2v3.3c0 1.9-3.6 3.6-3.6 5.5s3.6 3.6 3.6 5.5v3.3"/></symbol>
			<symbol id="bbsr-i-trigger" viewBox="0 0 24 24"><rect x="3" y="3.5" width="18" height="17" rx="2"/><path d="M3 14.2h18" stroke-dasharray="3 2.6"/><circle cx="12" cy="14.2" r="1.7" fill="currentColor" stroke="none"/></symbol>
			<symbol id="bbsr-i-ruler" viewBox="0 0 24 24"><rect x="2.5" y="8" width="19" height="8" rx="1.8"/><path d="M7.3 8v3.2"/><path d="M12 8v4.2"/><path d="M16.7 8v3.2"/></symbol>
			<symbol id="bbsr-i-slide" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M12 17.5V10"/><path d="M8.8 13.2 12 10l3.2 3.2"/></symbol>
			<symbol id="bbsr-i-pin" viewBox="0 0 24 24"><path d="M9.2 3h5.6"/><path d="M10.4 3v5.6L8 13.4h8l-2.4-4.8V3"/><path d="M12 13.4V21"/></symbol>
			<symbol id="bbsr-i-offset" viewBox="0 0 24 24"><rect x="3" y="3.2" width="18" height="3.8" rx="1.4" fill="currentColor" stroke="none"/><path d="M12 11.6V8"/><path d="M10.3 9.7 12 8l1.7 1.7"/><rect x="3" y="13" width="18" height="7.8" rx="2"/></symbol>
			<symbol id="bbsr-i-replay" viewBox="0 0 24 24"><path d="M3.8 12a8.2 8.2 0 1 0 2.5-5.9"/><path d="M3.6 3.9v5h5"/></symbol>
			<symbol id="bbsr-i-direction" viewBox="0 0 24 24"><path d="M3 9h18"/><path d="M6.6 5.4 3 9l3.6 3.6"/><path d="M21 15H3"/><path d="M17.4 11.4 21 15l-3.6 3.6"/></symbol>
			<symbol id="bbsr-i-width" viewBox="0 0 24 24"><rect x="7" y="5.5" width="10" height="13" rx="1.8"/><path d="M3.6 12h2.4"/><path d="M18 12h2.4"/><path d="M5.4 10 3.4 12l2 2"/><path d="M18.6 10l2 2-2 2"/></symbol>
		</svg>
		<?php
	}

	// =====================================================================
	// OPTION
	// =====================================================================

	/**
	 * Valeur réellement enregistrée, ou null si la clé n'a jamais été renseignée.
	 *
	 * @return mixed
	 */
	private function stored( string $key ) {
		$settings = get_option( self::OPTION, array() );

		return is_array( $settings ) && array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
	}

	/** Nombre de réglages enregistrés par section, pour les compteurs des onglets. */
	private function stored_counts(): array {
		$settings = get_option( self::OPTION, array() );
		$settings = is_array( $settings ) ? $settings : array();
		$counts   = array();

		foreach ( self::fields() as $key => $field ) {
			if ( array_key_exists( $key, $settings ) ) {
				$counts[ $field['section'] ] = ( $counts[ $field['section'] ] ?? 0 ) + 1;
			}
		}

		return $counts;
	}

	/**
	 * Une clé absente du tableau retourné = valeur par défaut du code. On ne
	 * stocke donc que ce qui est renseigné et valide.
	 *
	 * Exception : les cases à cocher sont toujours écrites (0 ou 1). Une case
	 * décochée n'est pas envoyée par le navigateur, et pour smooth / load_gsap
	 * dont le défaut est true, l'absence de clé signifierait « coché ».
	 *
	 * @param mixed $input Données postées.
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		foreach ( self::fields() as $key => $field ) {
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : null;

			switch ( $field['type'] ) {
				case 'checkbox':
					$out[ $key ] = empty( $raw ) ? 0 : 1;
					break;

				case 'number':
					$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';
					if ( '' === $raw ) {
						break;
					}
					if ( ! is_numeric( $raw ) ) {
						$this->reject( $field, $raw );
						break;
					}
					$number = empty( $field['int'] ) ? round( (float) $raw, 4 ) : (int) $raw;
					if ( $number < $field['min'] || $number > $field['max'] ) {
						$this->reject( $field, $raw );
						break;
					}
					$out[ $key ] = $number;
					break;

				case 'select':
					$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';
					if ( '' === $raw ) {
						break;
					}
					if ( ! in_array( $raw, $field['choices'], true ) ) {
						$this->reject( $field, $raw );
						break;
					}
					$out[ $key ] = $raw;
					break;

				case 'textarea':
					$lines = preg_split( '/\R/', is_scalar( $raw ) ? (string) $raw : '' );
					$lines = array_map( 'sanitize_text_field', is_array( $lines ) ? $lines : array() );
					$lines = array_map( 'trim', $lines );
					$lines = array_values( array_unique( array_filter( $lines, 'strlen' ) ) );
					if ( $lines ) {
						$out[ $key ] = $lines;
					}
					break;

				default:
					$raw = trim( sanitize_text_field( is_scalar( $raw ) ? (string) $raw : '' ) );
					if ( '' === $raw ) {
						// Champ vide : détection automatique pour stepsOffset, défaut pour start.
						break;
					}
					if ( 'start' === $key && ! preg_match( '#^(top|center|bottom)\s+(top|center|bottom|\d{1,3}%|\d+px)$#', $raw ) ) {
						$this->reject( $field, $raw );
						break;
					}
					if ( 'stepsOffset' === $key ) {
						// '' est déjà écarté plus haut : 0 est une valeur légitime (aucun décalage).
						if ( ! ctype_digit( $raw ) ) {
							$this->reject( $field, $raw );
							break;
						}
						$out[ $key ] = absint( $raw );
						break;
					}
					$out[ $key ] = $raw;
			}
		}

		return $out;
	}

	private function reject( array $field, string $raw ): void {
		$default = (string) $field['default'];

		add_settings_error(
			self::OPTION,
			'bb_reveal_invalid',
			sprintf(
				/* translators: 1: nom du réglage, 2: valeur saisie, 3: valeur par défaut */
				__( '« %1$s » : la valeur « %2$s » n\'est pas acceptée, la valeur par défaut (%3$s) est conservée.', 'bb-scroll-reveal' ),
				$field['label'],
				$raw,
				'' === $default ? __( 'automatique', 'bb-scroll-reveal' ) : $default
			),
			'error'
		);
	}

	public function handle_reset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Droits insuffisants.', 'bb-scroll-reveal' ) );
		}

		check_admin_referer( 'bb_reveal_reset' );
		delete_option( self::OPTION );

		wp_safe_redirect( add_query_arg( 'bb-reveal-reset', '1', admin_url( 'options-general.php?page=' . self::PAGE ) ) );
		exit;
	}
}

<?php
/**
 * Page de réglages : Réglages > BB Scroll Reveal.
 *
 * Ce fichier ne contient que l'interface d'administration. La lecture de l'option
 * et sa fusion avec les défauts vivent dans bb-scroll-reveal.php : le front en a
 * besoin, et cette classe n'est chargée que dans l'admin.
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
				'min'     => 0.1,
				'max'     => 5,
				'step'    => 0.05,
				'unit'    => __( 's', 'bb-scroll-reveal' ),
				'label'   => __( 'Durée', 'bb-scroll-reveal' ),
				'default' => $defaults['duration'],
			),
			'stagger'          => array(
				'section' => 'reveal',
				'type'    => 'number',
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
				'min'     => 0.5,
				'max'     => 2,
				'step'    => 0.1,
				'label'   => __( 'Sensibilité de la molette', 'bb-scroll-reveal' ),
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

	private function sections(): array {
		return array(
			'general' => array(
				'title' => __( 'Général', 'bb-scroll-reveal' ),
				'intro' => '',
			),
			'reveal'  => array(
				'title' => __( 'Révélations', 'bb-scroll-reveal' ),
				'intro' => __( 'Valeurs par défaut des classes bb-reveal, bb-reveal-children et bb-split. Chaque élément peut les surcharger avec ses attributs data-bb-*.', 'bb-scroll-reveal' ),
			),
			'scroll'  => array(
				'title' => __( 'Défilement', 'bb-scroll-reveal' ),
				'intro' => '',
			),
			'steps'   => array(
				'title' => __( 'Sections épinglées (bb-steps)', 'bb-scroll-reveal' ),
				'intro' => '',
			),
			'hscroll' => array(
				'title' => __( 'Défilement horizontal (bb-hscroll)', 'bb-scroll-reveal' ),
				'intro' => __( 'Le sens, la longueur de course et la largeur des panneaux se règlent section par section, avec les attributs data-bb-direction, data-bb-distance et data-bb-width.', 'bb-scroll-reveal' ),
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

		foreach ( $this->sections() as $id => $section ) {
			$intro = $section['intro'];

			add_settings_section(
				'bb_reveal_' . $id,
				$section['title'],
				static function () use ( $intro ) {
					if ( '' !== $intro ) {
						echo '<p class="description">' . esc_html( $intro ) . '</p>';
					}
				},
				self::PAGE
			);
		}

		foreach ( self::fields() as $key => $field ) {
			add_settings_field(
				'bb_reveal_' . $key,
				esc_html( $field['label'] ),
				array( $this, 'render_field' ),
				self::PAGE,
				'bb_reveal_' . $field['section'],
				array(
					'key'       => $key,
					'field'     => $field,
					'label_for' => 'checkbox' === $field['type'] ? '' : 'bb-reveal-' . $key,
					'class'     => 'bb-reveal-row',
				)
			);
		}
	}

	public function render_field( array $args ): void {
		$key   = $args['key'];
		$field = $args['field'];
		$value = $this->stored( $key );
		$id    = 'bb-reveal-' . $key;
		$name  = self::OPTION . '[' . $key . ']';

		switch ( $field['type'] ) {
			case 'checkbox':
				$checked = null === $value ? ! empty( $field['default'] ) : (bool) $value;
				printf(
					'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( $checked, true, false ),
					esc_html( $field['cb'] )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" min="%5$s" max="%6$s" step="%7$s" class="small-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( null === $value ? '' : (string) $value ),
					esc_attr( (string) $field['default'] ),
					esc_attr( (string) $field['min'] ),
					esc_attr( (string) $field['max'] ),
					esc_attr( (string) $field['step'] )
				);
				if ( ! empty( $field['unit'] ) ) {
					echo ' <span class="bb-reveal-unit">' . esc_html( $field['unit'] ) . '</span>';
				}
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['choices'] as $choice ) {
					printf(
						'<option value="%1$s"%2$s>%1$s</option>',
						esc_attr( $choice ),
						selected( null === $value ? $field['default'] : $value, $choice, false )
					);
				}
				echo '</select>';
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" class="large-text code">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( is_array( $value ) ? implode( "\n", $value ) : '' )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( null === $value ? '' : (string) $value ),
					esc_attr( '' === (string) $field['default'] ? __( 'auto', 'bb-scroll-reveal' ) : (string) $field['default'] )
				);
		}

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}
	}

	/**
	 * Valeur réellement enregistrée, ou null si la clé n'a jamais été renseignée.
	 *
	 * @return mixed
	 */
	private function stored( string $key ) {
		$settings = get_option( self::OPTION, array() );

		return is_array( $settings ) && array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
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

	public function enqueue( string $hook ): void {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_add_inline_style(
			'wp-admin',
			'.bb-reveal-row .small-text{width:6em}'
			. '.bb-reveal-unit{color:#646970}'
			. '.bb-reveal-row .description{max-width:44em}'
			. '.bb-reveal-ref{margin-top:2em;max-width:60em}'
			. '.bb-reveal-ref td:first-child{width:16em}'
			. '.bb-reveal-ref code{white-space:nowrap}'
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$reset = isset( $_GET['bb-reveal-reset'] );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $reset ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Réglages réinitialisés : les valeurs par défaut du plugin sont de nouveau appliquées.', 'bb-scroll-reveal' ); ?></p>
				</div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'Un réglage laissé vide garde la valeur par défaut du plugin, affichée en filigrane. Un filtre bb_reveal_config placé dans le thème reste prioritaire sur cette page.', 'bb-scroll-reveal' ); ?>
			</p>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<?php $this->render_reference(); ?>

			<hr>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
				onsubmit="return confirm('<?php echo esc_js( __( 'Supprimer tous les réglages et revenir aux valeurs par défaut ?', 'bb-scroll-reveal' ) ); ?>');">
				<input type="hidden" name="action" value="bb_reveal_reset">
				<?php
				wp_nonce_field( 'bb_reveal_reset' );
				submit_button( __( 'Réinitialiser', 'bb-scroll-reveal' ), 'delete', 'submit', false );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Rappel en lecture seule des réglages qui ne passent pas par cette page.
	 * Le README reste la documentation de référence.
	 */
	private function render_reference(): void {
		$classes = array(
			'bb-reveal'             => __( 'fade + montée, une seule fois', 'bb-scroll-reveal' ),
			'bb-reveal-children'    => __( 'enfants directs révélés en cascade', 'bb-scroll-reveal' ),
			'bb-split'              => __( 'texte révélé ligne par ligne sous masque', 'bb-scroll-reveal' ),
			'bb-scrub'              => __( 'opacité et échelle liées au scroll', 'bb-scroll-reveal' ),
			'bb-parallax'           => __( 'parallaxe verticale liée au scroll', 'bb-scroll-reveal' ),
			'bb-steps'              => __( 'section épinglée, étapes révélées une à une', 'bb-scroll-reveal' ),
			'bb-step'               => __( 'une étape pilotée par la section bb-steps parente', 'bb-scroll-reveal' ),
			'bb-hscroll'            => __( 'section épinglée dont les panneaux défilent horizontalement', 'bb-scroll-reveal' ),
			'bb-hpanel'             => __( 'un panneau de la piste bb-hscroll parente', 'bb-scroll-reveal' ),
			'bb-ink'                => __( 'seconde image révélée par une tache d\'encre', 'bb-scroll-reveal' ),
		);

		$attributes = array(
			'data-bb-y|60'          => __( 'décalage vertical de cet élément', 'bb-scroll-reveal' ),
			'data-bb-duration|1.2'  => __( 'durée', 'bb-scroll-reveal' ),
			'data-bb-delay|0.2'     => __( 'retard avant le départ', 'bb-scroll-reveal' ),
			'data-bb-stagger|0.15'  => __( 'décalage entre enfants', 'bb-scroll-reveal' ),
			'data-bb-start|top 70%' => __( 'point de déclenchement', 'bb-scroll-reveal' ),
			'data-bb-parallax|15'   => __( 'amplitude de la parallaxe', 'bb-scroll-reveal' ),
			'data-bb-distance|60'   => __( 'bb-steps : hauteur d\'écran scrollée par étape, en % · bb-hscroll : longueur de scroll en % du déplacement horizontal', 'bb-scroll-reveal' ),
			'data-bb-mode|slide'    => __( 'bb-steps : les étapes montent du bas au lieu d\'un fondu', 'bb-scroll-reveal' ),
			'data-bb-pin|top'       => __( 'bb-steps slide : épinglage plein écran', 'bb-scroll-reveal' ),
			'data-bb-offset|96'     => __( 'bb-steps, bb-hscroll : hauteur du header fixe pour cette section', 'bb-scroll-reveal' ),
			'data-bb-replay'        => __( 'bb-steps : rembobine les animations internes au retour', 'bb-scroll-reveal' ),
			'data-bb-direction|rtl' => __( 'bb-hscroll : défilement de droite à gauche', 'bb-scroll-reveal' ),
			'data-bb-width|60vw'    => __( 'bb-hscroll : largeur des panneaux (vw, px, ou un nombre lu en vw)', 'bb-scroll-reveal' ),
		);
		?>
		<div class="bb-reveal-ref">
			<h2><?php esc_html_e( 'Réglages par élément', 'bb-scroll-reveal' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Ces réglages se posent dans Elementor, pas ici : les classes dans Avancé > Classes CSS, les attributs dans Avancé > Attributs, une ligne par attribut. Documentation complète dans le README du plugin.', 'bb-scroll-reveal' ); ?>
			</p>

			<h3><?php esc_html_e( 'Classes', 'bb-scroll-reveal' ); ?></h3>
			<table class="widefat striped">
				<tbody>
				<?php foreach ( $classes as $name => $role ) : ?>
					<tr>
						<td><code><?php echo esc_html( $name ); ?></code></td>
						<td><?php echo esc_html( $role ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Attributs', 'bb-scroll-reveal' ); ?></h3>
			<table class="widefat striped">
				<tbody>
				<?php foreach ( $attributes as $name => $role ) : ?>
					<tr>
						<td><code><?php echo esc_html( $name ); ?></code></td>
						<td><?php echo esc_html( $role ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
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

<?php
/**
 * Widget « BB Vidéo au scroll » : vidéo hébergée lue au fil du scroll.
 *
 * Le widget ne fait que produire le balisage (.bb-video-box + <video>) et ses
 * réglages en data-bb-* : l'animation est celle de la classe bb-video-scroll,
 * dans bb-reveal.js. Les deux usages restent donc strictement équivalents, et
 * le widget n'a pas de script propre.
 *
 * @package BB_Scroll_Reveal
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class BB_Reveal_Widget_Video_Scroll extends Widget_Base {

	public function get_name() {
		return 'bb_video_scroll';
	}

	public function get_title() {
		return esc_html__( 'BB Vidéo au scroll', 'bb-scroll-reveal' );
	}

	public function get_icon() {
		return 'eicon-video-camera';
	}

	public function get_categories() {
		return array( BB_Reveal_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'vidéo', 'video', 'scroll', 'scrub', 'défilement', 'bb', 'bleuebuzz' );
	}

	public function get_style_depends() {
		return array( 'bb-video' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_video',
			array(
				'label' => esc_html__( 'Vidéo', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'bb-scroll-reveal' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'media',
				'options' => array(
					'media' => esc_html__( 'Médiathèque', 'bb-scroll-reveal' ),
					'url'   => esc_html__( 'URL d’un fichier MP4', 'bb-scroll-reveal' ),
				),
			)
		);

		$this->add_control(
			'video',
			array(
				'label'       => esc_html__( 'Fichier vidéo', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'video' ),
				'condition'   => array( 'source' => 'media' ),
			)
		);

		$this->add_control(
			'url',
			array(
				'label'       => esc_html__( 'URL de la vidéo', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::TEXT,
				'input_type'  => 'url',
				'placeholder' => 'https://…/video.mp4',
				'dynamic'     => array( 'active' => true ),
				'description' => esc_html__( 'Fichier MP4 ou WebM. Les liens YouTube et Vimeo ne permettent pas une lecture image par image.', 'bb-scroll-reveal' ),
				'condition'   => array( 'source' => 'url' ),
			)
		);

		$this->add_control(
			'poster',
			array(
				'label'       => esc_html__( 'Image d’attente', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Affichée le temps que la vidéo se charge. Idéalement, la première image de la vidéo.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'encoding_hint',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Pour une lecture fluide, la vidéo doit avoir une image clé par image. Ré-encodage : ffmpeg -i source.mp4 -an -g 1 -crf 23 -movflags +faststart video.mp4', 'bb-scroll-reveal' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_scroll',
			array(
				'label' => esc_html__( 'Défilement', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'pin',
			array(
				'label'        => esc_html__( 'Épingler la vidéo', 'bb-scroll-reveal' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'La vidéo reste fixe pendant sa lecture. Désactivé : elle se lit pendant qu’elle traverse l’écran.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'distance',
			array(
				'label'       => esc_html__( 'Longueur de scroll', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'vh', 'px' ),
				'range'       => array(
					'vh' => array(
						'min'  => 50,
						'max'  => 1500,
						'step' => 10,
					),
					'px' => array(
						'min'  => 300,
						'max'  => 10000,
						'step' => 50,
					),
				),
				'default'     => array(
					'unit' => 'vh',
					'size' => 300,
				),
				'description' => esc_html__( 'Scroll nécessaire pour lire toute la vidéo. 300 vh = trois hauteurs d’écran.', 'bb-scroll-reveal' ),
				'condition'   => array( 'pin' => 'yes' ),
			)
		);

		$this->add_control(
			'fit',
			array(
				'label'        => esc_html__( 'Plein écran', 'bb-scroll-reveal' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Le cadre occupe la hauteur de l’écran, sous le header fixe s’il y en a un.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'holds',
			array(
				'label'       => esc_html__( 'Arrêts sur image', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '30, 70',
				'description' => esc_html__( 'Positions en % de la vidéo où elle se fige pendant qu’on continue de scroller. Vide = aucun.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'hold',
			array(
				'label'       => esc_html__( 'Durée d’un arrêt', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 15,
				),
				'description' => esc_html__( 'Scroll consommé par chaque arrêt, en % de celui de la vidéo entière.', 'bb-scroll-reveal' ),
				'condition'   => array( 'holds!' => '' ),
			)
		);

		$this->add_control(
			'controls',
			array(
				'label'        => esc_html__( 'Boutons lecture / pause', 'bb-scroll-reveal' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'L’internaute peut aussi lire la vidéo normalement ; le scroll reprend la main au mouvement suivant.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'preload',
			array(
				'label'       => esc_html__( 'Chargement', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'blob',
				'options'     => array(
					'blob'   => esc_html__( 'Complet, en mémoire (plus fluide)', 'bb-scroll-reveal' ),
					'stream' => esc_html__( 'En flux (vidéo très lourde)', 'bb-scroll-reveal' ),
				),
				'description' => esc_html__( 'Le chargement complet suppose une vidéo sur le même domaine que le site, ou servie avec les en-têtes CORS.', 'bb-scroll-reveal' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_box',
			array(
				'label' => esc_html__( 'Cadre', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ratio',
			array(
				'label'     => esc_html__( 'Proportions', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '16/9',
				'options'   => array(
					'16/9' => '16:9',
					'21/9' => '21:9',
					'4/3'  => '4:3',
					'1/1'  => '1:1',
					'4/5'  => '4:5',
					'9/16' => '9:16',
				),
				'selectors' => array( '{{WRAPPER}} .bb-video-box' => '--bb-video-ratio:{{VALUE}};' ),
				'condition' => array( 'fit!' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => esc_html__( 'Hauteur minimale', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 1200,
					),
					'vh' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .bb-video-box' => 'min-height:{{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'fit!' => 'yes' ),
			)
		);

		$this->add_control(
			'object_fit',
			array(
				'label'     => esc_html__( 'Cadrage', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => esc_html__( 'Remplir (cover)', 'bb-scroll-reveal' ),
					'contain' => esc_html__( 'Entière (contain)', 'bb-scroll-reveal' ),
				),
				'selectors' => array( '{{WRAPPER}} .bb-video-box' => '--bb-video-fit:{{VALUE}};' ),
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => esc_html__( 'Fond du cadre', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-video-box' => 'background-color:{{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Rayon des angles', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .bb-video-box' => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$url      = $this->video_url( $settings );

		if ( '' === $url ) {
			if ( $this->is_editor_preview() ) {
				echo '<div class="bb-video-hint">' . esc_html__( 'Choisir une vidéo hébergée (MP4) dans l’onglet Contenu.', 'bb-scroll-reveal' ) . '</div>';
			}
			return;
		}

		$attrs = array( 'class' => 'bb-video-box' );

		if ( 'yes' === ( $settings['pin'] ?? '' ) ) {
			$size = isset( $settings['distance']['size'] ) ? (float) $settings['distance']['size'] : 0;
			if ( $size > 0 ) {
				// En px : valeur suffixée ; en vh : nombre nu, lu par le JS en % de hauteur d'écran.
				$attrs['data-bb-distance'] = 'px' === ( $settings['distance']['unit'] ?? 'vh' ) ? (int) $size . 'px' : (string) (int) $size;
			}
		} else {
			$attrs['data-bb-pin'] = 'off';
		}

		if ( 'yes' === ( $settings['fit'] ?? '' ) ) {
			$attrs['data-bb-fit'] = '';
		}

		$holds = trim( preg_replace( '/[^0-9.,\s]/', '', (string) ( $settings['holds'] ?? '' ) ) );
		if ( '' !== $holds ) {
			$attrs['data-bb-holds'] = $holds;
			$hold                   = isset( $settings['hold']['size'] ) ? (float) $settings['hold']['size'] : 15;
			$attrs['data-bb-hold']  = (string) max( 0, min( 100, $hold ) );
		}

		if ( 'yes' === ( $settings['controls'] ?? '' ) ) {
			$attrs['data-bb-controls'] = '';
		}

		if ( 'stream' === ( $settings['preload'] ?? '' ) ) {
			$attrs['data-bb-preload'] = 'stream';
		}

		$poster = isset( $settings['poster']['url'] ) ? (string) $settings['poster']['url'] : '';

		$html = '';
		foreach ( $attrs as $name => $value ) {
			$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}
		?>
		<div<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributs échappés ci-dessus. ?>>
			<video
				src="<?php echo esc_url( '' === $poster ? $url . '#t=0.001' : $url ); ?>"
				<?php if ( '' !== $poster ) : ?>poster="<?php echo esc_url( $poster ); ?>"<?php endif; ?>
				<?php echo 'yes' === ( $settings['controls'] ?? '' ) ? 'controls' : ''; ?>
				muted playsinline webkit-playsinline preload="metadata"
			></video>
		</div>
		<?php
	}

	/** URL du fichier selon la source choisie ; vide si rien n'est renseigné. */
	private function video_url( array $settings ): string {
		$url = 'url' === ( $settings['source'] ?? 'media' )
			? (string) ( $settings['url'] ?? '' )
			: (string) ( $settings['video']['url'] ?? '' );

		return trim( $url );
	}

	private function is_editor_preview(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->preview )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}
}

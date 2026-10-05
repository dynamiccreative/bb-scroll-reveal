<?php
/**
 * Widget « BB Image liée » : cadre image piloté par le widget « BB Liste
 * dépliante » de même identifiant. Il ne contient aucune image en propre
 * (hors image de repli) : la pile est produite par la liste et déplacée ici
 * par le JS, pour qu'un média ne soit jamais saisi deux fois.
 *
 * @package BB_Scroll_Reveal
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Widget_Base;

class BB_Reveal_Widget_Accordion_Media extends Widget_Base {

	public function get_name() {
		return 'bb_accordion_media';
	}

	public function get_title() {
		return esc_html__( 'BB Image liée', 'bb-scroll-reveal' );
	}

	public function get_icon() {
		return 'eicon-image-rollover';
	}

	public function get_categories() {
		return array( BB_Reveal_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'image', 'accordéon', 'accordion', 'faq', 'bb', 'bleuebuzz' );
	}

	public function get_style_depends() {
		return array( 'bb-accordion' );
	}

	public function get_script_depends() {
		return array( 'bb-accordion' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_media',
			array(
				'label' => esc_html__( 'Liaison', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'group',
			array(
				'label'       => esc_html__( 'Identifiant de liaison', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'default',
				'placeholder' => 'default',
				'description' => esc_html__( 'Le même que sur le widget « BB Liste dépliante » de la page. Les images, elles, se saisissent dans la liste.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'fallback',
			array(
				'label'       => esc_html__( 'Image de repli', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Affichée tant qu’aucun élément n’est ouvert, et si la liste ne fournit aucune image.', 'bb-scroll-reveal' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'thumb',
				'label'     => esc_html__( 'Taille de l’image de repli', 'bb-scroll-reveal' ),
				'default'   => 'large',
				'condition' => array( 'fallback[url]!' => '' ),
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => esc_html__( 'Transition', 'bb-scroll-reveal' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'zoom',
				'options' => array(
					'zoom' => esc_html__( 'Fondu + zoom (Heron)', 'bb-scroll-reveal' ),
					'fade' => esc_html__( 'Fondu', 'bb-scroll-reveal' ),
					'up'   => esc_html__( 'Fondu + montée', 'bb-scroll-reveal' ),
					'none' => esc_html__( 'Aucune', 'bb-scroll-reveal' ),
				),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'      => esc_html__( 'Durée de la transition', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 2000,
						'step' => 20,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 400,
				),
				'selectors'  => array( '{{WRAPPER}} .bb-acc-media-box' => '--bb-acc-fade:{{SIZE}}ms;' ),
				'condition'  => array( 'effect!' => 'none' ),
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
				'default'   => '4/5',
				'options'   => array(
					'1/1'  => '1:1',
					'4/5'  => '4:5',
					'3/4'  => '3:4',
					'2/3'  => '2:3',
					'4/3'  => '4:3',
					'3/2'  => '3:2',
					'16/9' => '16:9',
					'auto' => esc_html__( 'Libres', 'bb-scroll-reveal' ),
				),
				'selectors' => array( '{{WRAPPER}} .bb-acc-media-box' => '--bb-acc-ratio:{{VALUE}};' ),
			)
		);

		$this->add_control(
			'fill_height',
			array(
				'label'       => esc_html__( 'Occuper toute la hauteur du conteneur', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => esc_html__( 'À activer pour une colonne image pleine hauteur (les proportions sont alors ignorées si le conteneur impose une hauteur).', 'bb-scroll-reveal' ),
				'selectors'   => array(
					'{{WRAPPER}}, {{WRAPPER}} > .elementor-widget-container' => 'height:100%;',
					'{{WRAPPER}} .bb-acc-media-box'                          => 'height:100%;',
				),
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
				'selectors'  => array( '{{WRAPPER}} .bb-acc-media-box' => 'min-height:{{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'fit',
			array(
				'label'     => esc_html__( 'Cadrage', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => esc_html__( 'Remplir (cover)', 'bb-scroll-reveal' ),
					'contain' => esc_html__( 'Entière (contain)', 'bb-scroll-reveal' ),
				),
				'selectors' => array( '{{WRAPPER}} .bb-acc-media-box' => '--bb-acc-fit:{{VALUE}};' ),
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => esc_html__( 'Fond du cadre', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-media-box' => 'background-color:{{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Rayon des angles', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .bb-acc-media-box' => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'border',
				'selector' => '{{WRAPPER}} .bb-acc-media-box',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'shadow',
				'selector' => '{{WRAPPER}} .bb-acc-media-box',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$group    = BB_Reveal_Elementor::group_id( $settings['group'] ?? '' );
		$effect   = in_array( $settings['effect'] ?? 'zoom', array( 'fade', 'up', 'zoom', 'none' ), true ) ? $settings['effect'] : 'zoom';
		$fallback = BB_Reveal_Elementor::image_html( $settings['fallback'] ?? array(), 'thumb', $settings );
		?>
		<div class="bb-acc-media-box" data-bb-acc="<?php echo esc_attr( $group ); ?>" data-bb-effect="<?php echo esc_attr( $effect ); ?>">
			<?php if ( '' !== $fallback ) : ?>
				<figure class="bb-acc-media-item bb-acc-media-fallback is-active"><?php echo $fallback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- balise construite et échappée dans image_html(). ?></figure>
			<?php endif; ?>
			<?php if ( '' === $fallback && $this->is_editor_preview() ) : ?>
				<div class="bb-acc-media-hint">
					<?php
					printf(
						/* translators: %s : identifiant de liaison. */
						esc_html__( 'Cadre image lié à « %s ». Les images se saisissent dans le widget « BB Liste dépliante » qui porte le même identifiant.', 'bb-scroll-reveal' ),
						esc_html( $group )
					);
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function is_editor_preview(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->preview )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}
}

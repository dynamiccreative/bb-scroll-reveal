<?php
/**
 * Widget « BB Liste dépliante » : accordéon dont l'élément ouvert pilote l'image
 * affichée par le widget « BB Image liée » portant le même identifiant.
 *
 * @package BB_Scroll_Reveal
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

class BB_Reveal_Widget_Accordion extends Widget_Base {

	public function get_name() {
		return 'bb_accordion';
	}

	public function get_title() {
		return esc_html__( 'BB Liste dépliante', 'bb-scroll-reveal' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_categories() {
		return array( BB_Reveal_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'accordéon', 'accordion', 'faq', 'toggle', 'bb', 'bleuebuzz' );
	}

	public function get_style_depends() {
		return array( 'bb-accordion' );
	}

	public function get_script_depends() {
		return array( 'bb-accordion' );
	}

	protected function register_controls() {
		$this->controls_items();
		$this->controls_behaviour();
		$this->controls_style_list();
		$this->controls_style_title();
		$this->controls_style_text();
		$this->controls_style_icon();
		$this->controls_style_marker();
	}

	private function controls_items(): void {
		$this->start_controls_section(
			'section_items',
			array(
				'label' => esc_html__( 'Éléments', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Titre', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Titre de l’élément', 'bb-scroll-reveal' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Contenu', 'bb-scroll-reveal' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => esc_html__( 'Texte affiché quand l’élément est ouvert.', 'bb-scroll-reveal' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'icon',
			array(
				'label'       => esc_html__( 'Pictogramme', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Image liée', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Affichée dans le widget « BB Image liée » qui porte le même identifiant.', 'bb-scroll-reveal' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array( 'title' => esc_html__( 'Une ingénierie approfondie en amont', 'bb-scroll-reveal' ) ),
					array( 'title' => esc_html__( 'Une préfabrication précise des éléments', 'bb-scroll-reveal' ) ),
					array( 'title' => esc_html__( 'Une mise en œuvre maîtrisée', 'bb-scroll-reveal' ) ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'    => 'thumb',
				'label'   => esc_html__( 'Taille des images liées', 'bb-scroll-reveal' ),
				'default' => 'large',
			)
		);

		$this->end_controls_section();
	}

	private function controls_behaviour(): void {
		$this->start_controls_section(
			'section_behaviour',
			array(
				'label' => esc_html__( 'Comportement', 'bb-scroll-reveal' ),
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
				'description' => esc_html__( 'À reporter à l’identique sur le widget « BB Image liée ». Un identifiant différent par paire si la page en contient plusieurs.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'toggle',
			array(
				'label'   => esc_html__( 'Ouverture', 'bb-scroll-reveal' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'single',
				'options' => array(
					'single'   => esc_html__( 'Un seul élément à la fois', 'bb-scroll-reveal' ),
					'multiple' => esc_html__( 'Plusieurs éléments', 'bb-scroll-reveal' ),
				),
			)
		);

		$this->add_control(
			'keep_open',
			array(
				'label'       => esc_html__( 'Toujours un élément ouvert', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => esc_html__( 'Un clic sur l’élément ouvert ne le referme pas : l’image liée reste cohérente.', 'bb-scroll-reveal' ),
				'condition'   => array( 'toggle' => 'single' ),
			)
		);

		$this->add_control(
			'default_active',
			array(
				'label'       => esc_html__( 'Élément ouvert au chargement', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'step'        => 1,
				'default'     => 1,
				'description' => esc_html__( '0 pour n’en ouvrir aucun.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'hover_preview',
			array(
				'label'       => esc_html__( 'Aperçu au survol', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => esc_html__( 'Le survol d’un titre change l’image sans ouvrir l’élément (souris uniquement).', 'bb-scroll-reveal' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Balise des titres', 'bb-scroll-reveal' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'marker',
			array(
				'label'   => esc_html__( 'Indicateur', 'bb-scroll-reveal' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'plus',
				'options' => array(
					'plus'    => esc_html__( 'Plus / moins', 'bb-scroll-reveal' ),
					'chevron' => esc_html__( 'Chevron', 'bb-scroll-reveal' ),
					'none'    => esc_html__( 'Aucun', 'bb-scroll-reveal' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_list(): void {
		$this->start_controls_section(
			'section_style_list',
			array(
				'label' => esc_html__( 'Liste', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => esc_html__( 'Marge intérieure des lignes', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => 24,
					'right'    => 0,
					'bottom'   => 24,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-acc-head' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'divider_heading',
			array(
				'label'     => esc_html__( 'Séparateurs', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'divider_style',
			array(
				'label'     => esc_html__( 'Style', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'dotted',
				'options'   => array(
					'none'   => esc_html__( 'Aucun', 'bb-scroll-reveal' ),
					'solid'  => esc_html__( 'Trait plein', 'bb-scroll-reveal' ),
					'dashed' => esc_html__( 'Tirets', 'bb-scroll-reveal' ),
					'dotted' => esc_html__( 'Pointillés', 'bb-scroll-reveal' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .bb-acc' => '--bb-acc-divider-style:{{VALUE}};',
				),
			)
		);

		$this->add_control(
			'divider_color',
			array(
				'label'     => esc_html__( 'Couleur', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#D8D6D0',
				'selectors' => array(
					'{{WRAPPER}} .bb-acc' => '--bb-acc-divider-color:{{VALUE}};',
				),
				'condition' => array( 'divider_style!' => 'none' ),
			)
		);

		$this->add_control(
			'divider_width',
			array(
				'label'      => esc_html__( 'Épaisseur', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 8,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-acc' => '--bb-acc-divider-width:{{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'divider_style!' => 'none' ),
			)
		);

		$this->add_control(
			'divider_first',
			array(
				'label'     => esc_html__( 'Séparateur avant le premier élément', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'selectors' => array(
					'{{WRAPPER}} .bb-acc-item:first-child' => 'border-top:var(--bb-acc-divider-width) var(--bb-acc-divider-style) var(--bb-acc-divider-color);',
				),
				'condition' => array( 'divider_style!' => 'none' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_title(): void {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => esc_html__( 'Titres', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .bb-acc-title',
			)
		);

		$this->start_controls_tabs( 'tabs_title' );

		$this->start_controls_tab( 'tab_title_normal', array( 'label' => esc_html__( 'Normal', 'bb-scroll-reveal' ) ) );
		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Couleur du texte', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					/* La propriété est posée en dur, et pas seulement via la variable :
					   la règle de bb-accordion.css qui la consomme n'a qu'une classe de
					   spécificité, donc le moindre « button { color } » du thème passait
					   devant et le réglage restait sans effet. */
					'{{WRAPPER}} .bb-acc-head'                => '--bb-acc-title-color:{{VALUE}};color:{{VALUE}};',
					'{{WRAPPER}} .bb-acc-head .bb-acc-title'  => 'color:{{VALUE}};',
				),
			)
		);
		$this->add_control(
			'title_bg',
			array(
				'label'     => esc_html__( 'Fond', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-head' => 'background-color:{{VALUE}};' ),
			)
		);
		/* « Aucune » dans le champ Type = pas de contour : l'interrupteur est le
		   champ lui-même, comme partout ailleurs dans Elementor. */
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'title_border',
				'label'    => esc_html__( 'Contour', 'bb-scroll-reveal' ),
				'selector' => '{{WRAPPER}} .bb-acc-head',
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_title_hover', array( 'label' => esc_html__( 'Survol', 'bb-scroll-reveal' ) ) );
		$this->add_control(
			'title_color_hover',
			array(
				'label'     => esc_html__( 'Couleur du texte', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-acc-head:hover'               => '--bb-acc-title-color:{{VALUE}};color:{{VALUE}};',
					'{{WRAPPER}} .bb-acc-head:hover .bb-acc-title' => 'color:{{VALUE}};',
				),
			)
		);
		$this->add_control(
			'title_bg_hover',
			array(
				'label'     => esc_html__( 'Fond', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-head:hover' => 'background-color:{{VALUE}};' ),
			)
		);
		$this->add_control(
			'title_border_color_hover',
			array(
				'label'     => esc_html__( 'Couleur du contour', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-head:hover' => 'border-color:{{VALUE}};' ),
				'condition' => array( 'title_border_border!' => '' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_title_active', array( 'label' => esc_html__( 'Ouvert', 'bb-scroll-reveal' ) ) );
		$this->add_control(
			'title_color_active',
			array(
				'label'     => esc_html__( 'Couleur du texte', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-acc-item.is-open .bb-acc-head, {{WRAPPER}} .bb-acc-item.is-open .bb-acc-head:hover'                         => '--bb-acc-title-color:{{VALUE}};color:{{VALUE}};',
					'{{WRAPPER}} .bb-acc-item.is-open .bb-acc-head .bb-acc-title, {{WRAPPER}} .bb-acc-item.is-open .bb-acc-head:hover .bb-acc-title' => 'color:{{VALUE}};',
				),
			)
		);
		$this->add_control(
			'title_bg_active',
			array(
				'label'       => esc_html__( 'Fond', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::COLOR,
				/* Plus spécifique que le survol : sans cela, survoler une ligne
				   ouverte lui rendrait le fond de survol. */
				'selectors'   => array( '{{WRAPPER}} .bb-acc-item.is-open .bb-acc-head, {{WRAPPER}} .bb-acc-item.is-open .bb-acc-head:hover' => 'background-color:{{VALUE}};' ),
			)
		);
		$this->add_control(
			'title_border_color_active',
			array(
				'label'     => esc_html__( 'Couleur du contour', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-item.is-open .bb-acc-head, {{WRAPPER}} .bb-acc-item.is-open .bb-acc-head:hover' => 'border-color:{{VALUE}};' ),
				'condition' => array( 'title_border_border!' => '' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_radius',
			array(
				'label'      => esc_html__( 'Rayon des angles', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .bb-acc-head' => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_gap',
			array(
				'label'      => esc_html__( 'Écart entre pictogramme, titre et indicateur', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .bb-acc-head' => 'gap:{{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_text(): void {
		$this->start_controls_section(
			'section_style_text',
			array(
				'label' => esc_html__( 'Contenu déplié', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .bb-acc-panel-inner',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Couleur du texte', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-panel-inner' => 'color:{{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_bg',
			array(
				'label'       => esc_html__( 'Fond', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::COLOR,
				/* Posé sur le panneau et non sur son contenu : le fond couvre alors
				   toute la ligne dès le début du dépliement, sans liseré. */
				'selectors'   => array( '{{WRAPPER}} .bb-acc-panel' => 'background-color:{{VALUE}};' ),
				'description' => esc_html__( 'Visible uniquement quand l’élément est ouvert.', 'bb-scroll-reveal' ),
			)
		);

		$this->add_responsive_control(
			'panel_padding',
			array(
				'label'      => esc_html__( 'Marge intérieure', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => 0,
					'right'    => 0,
					'bottom'   => 24,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-acc-panel-inner' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'panel_speed',
			array(
				'label'      => esc_html__( 'Durée du dépliement', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 1200,
						'step' => 20,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 400,
				),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .bb-acc' => '--bb-acc-speed:{{SIZE}}ms;' ),
				'description' => esc_html__( 'Vaut aussi pour le fondu du contenu et pour l’indicateur : tout part et s’arrête ensemble, sans décalage.', 'bb-scroll-reveal' ),
			)
		);

		/* Équivalents CSS des easings GSAP. Les courbes très amorties (expo, quint)
		   concentrent la course au tout début : quand une ligne s'ouvre pendant
		   qu'une autre se ferme, le mouvement se lit alors en deux temps. */
		$this->add_control(
			'panel_ease',
			array(
				'label'       => esc_html__( 'Courbe du dépliement', 'bb-scroll-reveal' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'cubic-bezier(0.33, 1, 0.68, 1)',
				'options'     => array(
					'cubic-bezier(0.33, 1, 0.68, 1)' => esc_html__( 'Naturelle (power2.out) — Heron', 'bb-scroll-reveal' ),
					'cubic-bezier(0.65, 0, 0.35, 1)' => esc_html__( 'Symétrique (in-out)', 'bb-scroll-reveal' ),
					'ease'                           => esc_html__( 'Standard navigateur', 'bb-scroll-reveal' ),
					'cubic-bezier(0.22, 1, 0.36, 1)' => esc_html__( 'Amortie (quint.out)', 'bb-scroll-reveal' ),
					'cubic-bezier(0.16, 1, 0.3, 1)'  => esc_html__( 'Très amortie (expo.out)', 'bb-scroll-reveal' ),
					'linear'                         => esc_html__( 'Linéaire', 'bb-scroll-reveal' ),
				),
				'selectors'   => array( '{{WRAPPER}} .bb-acc' => '--bb-acc-ease:{{VALUE}};' ),
				'description' => esc_html__( 'Les deux dernières concentrent le mouvement au démarrage : l’ouverture et la fermeture simultanées s’y lisent en deux temps.', 'bb-scroll-reveal' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_icon(): void {
		$this->start_controls_section(
			'section_style_icon',
			array(
				'label' => esc_html__( 'Pictogrammes', 'bb-scroll-reveal' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Taille', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 8,
						'max' => 120,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 32,
				),
				'selectors'  => array( '{{WRAPPER}} .bb-acc-icon' => 'font-size:{{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => esc_html__( 'Couleur', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-icon' => 'color:{{VALUE}};' ),
			)
		);

		$this->add_control(
			'icon_color_active',
			array(
				'label'     => esc_html__( 'Couleur (ouvert)', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-item.is-open .bb-acc-icon' => 'color:{{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_marker(): void {
		$this->start_controls_section(
			'section_style_marker',
			array(
				'label'     => esc_html__( 'Indicateur', 'bb-scroll-reveal' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'marker!' => 'none' ),
			)
		);

		$this->add_responsive_control(
			'marker_size',
			array(
				'label'      => esc_html__( 'Taille', 'bb-scroll-reveal' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 8,
						'max' => 64,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'selectors'  => array( '{{WRAPPER}} .bb-acc-mark' => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'marker_color',
			array(
				'label'     => esc_html__( 'Couleur', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-mark' => 'color:{{VALUE}};' ),
			)
		);

		$this->add_control(
			'marker_color_active',
			array(
				'label'     => esc_html__( 'Couleur (ouvert)', 'bb-scroll-reveal' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .bb-acc-item.is-open .bb-acc-mark' => 'color:{{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/** SVG de l'indicateur. Le trait vertical se rétracte à l'ouverture (CSS). */
	private function marker_svg( string $type ): string {
		if ( 'chevron' === $type ) {
			return '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false"><path d="M4 7.5 10 13.5 16 7.5" stroke="currentColor" stroke-width="2" /></svg>';
		}

		return '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">'
			. '<path d="M17 10H3" stroke="currentColor" stroke-width="2" class="bb-acc-mark-h" />'
			. '<path d="M10 3v14" stroke="currentColor" stroke-width="2" class="bb-acc-mark-v" />'
			. '</svg>';
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = ( isset( $settings['items'] ) && is_array( $settings['items'] ) ) ? $settings['items'] : array();

		if ( ! $items ) {
			return;
		}

		$group    = BB_Reveal_Elementor::group_id( $settings['group'] ?? '' );
		$uid      = 'bb-acc-' . $this->get_id();
		$single   = 'multiple' !== ( $settings['toggle'] ?? 'single' );
		$open_idx = isset( $settings['default_active'] ) ? (int) $settings['default_active'] : 1;
		$marker   = in_array( $settings['marker'] ?? 'plus', array( 'plus', 'chevron', 'none' ), true ) ? $settings['marker'] : 'plus';
		$tag      = in_array( $settings['title_tag'] ?? 'h3', array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ? $settings['title_tag'] : 'h3';
		?>
		<div class="bb-acc"
			data-bb-acc="<?php echo esc_attr( $group ); ?>"
			data-bb-toggle="<?php echo $single ? 'single' : 'multiple'; ?>"
			<?php echo 'yes' === ( $settings['hover_preview'] ?? '' ) ? ' data-bb-hover="1"' : ''; ?>
			<?php echo ( $single && 'yes' === ( $settings['keep_open'] ?? '' ) ) ? ' data-bb-keep-open="1"' : ''; ?>>

			<?php $this->render_media_stack( $items, $settings, $group ); ?>

			<?php foreach ( $items as $i => $item ) : ?>
				<?php
				$is_open  = ( (int) $i + 1 ) === $open_idx;
				$panel_id = $uid . '-p-' . (int) $i;
				$head_id  = $uid . '-h-' . (int) $i;
				$text     = (string) ( $item['text'] ?? '' );
				?>
				<div class="bb-acc-item<?php echo $is_open ? ' is-open' : ''; ?>" data-bb-index="<?php echo esc_attr( (string) $i ); ?>">
					<<?php echo esc_html( $tag ); ?> class="bb-acc-title-wrap">
						<button type="button" class="bb-acc-head" id="<?php echo esc_attr( $head_id ); ?>"
							aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>">
							<?php if ( ! empty( $item['icon']['value'] ) ) : ?>
								<span class="bb-acc-icon"><?php Icons_Manager::render_icon( $item['icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
							<?php endif; ?>
							<span class="bb-acc-title"><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></span>
							<?php if ( 'none' !== $marker ) : ?>
								<span class="bb-acc-mark bb-acc-mark--<?php echo esc_attr( $marker ); ?>" aria-hidden="true">
									<?php echo $this->marker_svg( $marker ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG littéral. ?>
								</span>
							<?php endif; ?>
						</button>
					</<?php echo esc_html( $tag ); ?>>
					<div class="bb-acc-panel" id="<?php echo esc_attr( $panel_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $head_id ); ?>">
						<div class="bb-acc-panel-inner"><?php echo '' !== $text ? $this->parse_text_editor( $text ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parse_text_editor filtre déjà le contenu. ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Pile d'images des éléments. Rendue ici — les médias sont saisis une seule
	 * fois, dans le repeater — puis déplacée par le JS dans le widget
	 * « BB Image liée » de même identifiant. Masquée tant qu'elle est ici.
	 */
	private function render_media_stack( array $items, array $settings, string $group ): void {
		$html = '';

		foreach ( $items as $i => $item ) {
			$img = BB_Reveal_Elementor::image_html( $item['image'] ?? array(), 'thumb', $settings );

			if ( '' === $img ) {
				continue;
			}

			$html .= sprintf(
				'<figure class="bb-acc-media-item" data-bb-index="%s">%s</figure>',
				esc_attr( (string) $i ),
				$img
			);
		}

		if ( '' === $html ) {
			return;
		}

		printf(
			'<div class="bb-acc-media" data-bb-acc-media="%s">%s</div>',
			esc_attr( $group ),
			$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- balises construites ci-dessus, valeurs échappées.
		);
	}
}

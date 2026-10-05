<?php
/**
 * Module Elementor de BB Scroll Reveal.
 *
 * Chargé uniquement quand Elementor est actif (voir bb-scroll-reveal.php).
 * Le reste du plugin n'en dépend pas : aucune fonction de ce fichier n'est
 * appelée par le front « classes CSS ».
 *
 * @package BB_Scroll_Reveal
 */

defined( 'ABSPATH' ) || exit;

final class BB_Reveal_Elementor {

	/** Catégorie de widgets dans le panneau Elementor. */
	const CATEGORY = 'bb-reveal';

	public static function init(): void {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		// Priorité 5 : les feuilles doivent être enregistrées avant qu'un widget ne les demande
		// via get_style_depends(). wp_enqueue_scripts tourne aussi dans l'aperçu de l'éditeur,
		// qui est un rendu front classique.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
	}

	/**
	 * @param \Elementor\Elements_Manager $manager
	 */
	public static function register_category( $manager ): void {
		$manager->add_category(
			self::CATEGORY,
			array(
				'title' => esc_html__( 'bleuebuzz', 'bb-scroll-reveal' ),
				'icon'  => 'eicon-animation',
			)
		);
	}

	public static function register_assets(): void {
		wp_register_style( 'bb-accordion', BB_REVEAL_URL . 'assets/bb-accordion.css', array(), BB_REVEAL_VERSION );
		wp_register_script( 'bb-accordion', BB_REVEAL_URL . 'assets/bb-accordion.js', array(), BB_REVEAL_VERSION, true );
		wp_register_style( 'bb-video', BB_REVEAL_URL . 'assets/bb-video.css', array(), BB_REVEAL_VERSION );
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public static function register_widgets( $widgets_manager ): void {
		require_once __DIR__ . '/widgets/class-bb-reveal-widget-accordion.php';
		require_once __DIR__ . '/widgets/class-bb-reveal-widget-accordion-media.php';
		require_once __DIR__ . '/widgets/class-bb-reveal-widget-video-scroll.php';

		$widgets_manager->register( new BB_Reveal_Widget_Accordion() );
		$widgets_manager->register( new BB_Reveal_Widget_Accordion_Media() );
		$widgets_manager->register( new BB_Reveal_Widget_Video_Scroll() );
	}

	/**
	 * Identifiant de liaison partagé par la liste et le cadre image. Réduit à un
	 * slug : il sert de sélecteur d'attribut côté JS, sans échappement.
	 */
	public static function group_id( $raw ): string {
		$id = sanitize_key( (string) $raw );

		return '' !== $id ? $id : 'default';
	}

	/**
	 * <img> d'un élément de la liste. La taille vient du group control
	 * « Taille de l'image » posé au niveau du widget ($size_key), l'alternative
	 * du média lui-même.
	 *
	 * @param array  $image    Valeur d'un contrôle MEDIA (id + url).
	 * @param string $size_key Préfixe du Group_Control_Image_Size.
	 * @param array  $settings Réglages du widget.
	 */
	public static function image_html( $image, string $size_key, array $settings, string $class = '' ): string {
		if ( ! is_array( $image ) ) {
			return '';
		}

		$id  = isset( $image['id'] ) ? (int) $image['id'] : 0;
		$url = isset( $image['url'] ) ? (string) $image['url'] : '';
		$alt = '';

		if ( $id ) {
			$sized = \Elementor\Group_Control_Image_Size::get_attachment_image_src( $id, $size_key, $settings );
			if ( $sized ) {
				$url = $sized;
			}
			$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		}

		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async" />',
			esc_url( $url ),
			esc_attr( $alt ),
			esc_attr( $class )
		);
	}
}

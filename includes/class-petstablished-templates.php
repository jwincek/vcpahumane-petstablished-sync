<?php
/**
 * Petstablished Templates
 *
 * Registers block templates for pet archive and single views.
 *
 * @package Petstablished_Sync
 * @since 2.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Petstablished_Templates {

	public function __construct() {
		add_filter( 'get_block_templates', array( $this, 'add_templates' ), 10, 3 );
		add_filter( 'pre_get_block_file_template', array( $this, 'get_template' ), 10, 3 );
	}

	public function add_templates( array $templates, array $query, string $template_type ): array {
		if ( 'wp_template' !== $template_type ) {
			return $templates;
		}

		$plugin_templates = $this->get_plugin_templates();

		foreach ( $plugin_templates as $slug => $data ) {
			// Skip if specific template requested and this isn't it.
			if ( ! empty( $query['slug__in'] ) && ! in_array( $slug, $query['slug__in'], true ) ) {
				continue;
			}

			// Skip if already exists in theme.
			$exists = false;
			foreach ( $templates as $template ) {
				if ( $template->slug === $slug ) {
					$exists = true;
					break;
				}
			}

			if ( ! $exists ) {
				$templates[] = $this->build_template_object( $slug, $data );
			}
		}

		return $templates;
	}

	public function get_template( $template, string $id, string $template_type ) {
		if ( $template || 'wp_template' !== $template_type ) {
			return $template;
		}

		$parts           = explode( '//', $id );
		$slug            = $parts[1] ?? $parts[0];
		$plugin_templates = $this->get_plugin_templates();

		if ( ! isset( $plugin_templates[ $slug ] ) ) {
			return $template;
		}

		return $this->build_template_object( $slug, $plugin_templates[ $slug ] );
	}

	private function get_plugin_templates(): array {
		return array(
			'archive-pet' => array(
				'title'       => __( 'Pet Archive', 'petstablished-sync' ),
				'description' => __( 'Displays the pet adoption listings.', 'petstablished-sync' ),
				'post_types'  => array( 'pet' ),
			),
			'single-pet'  => array(
				'title'       => __( 'Single Pet', 'petstablished-sync' ),
				'description' => __( 'Displays a single adoptable pet.', 'petstablished-sync' ),
				'post_types'  => array( 'pet' ),
			),
		);
	}

	private function build_template_object( string $slug, array $data ): WP_Block_Template {
		$template_file = PETSTABLISHED_SYNC_DIR . 'templates/' . $slug . '.html';
		$content       = file_exists( $template_file ) ? file_get_contents( $template_file ) : '';

		$template                 = new WP_Block_Template();
		$template->id             = 'petstablished-sync//' . $slug;
		$template->theme          = 'petstablished-sync';
		$template->slug           = $slug;
		$template->source         = 'plugin';
		$template->type           = 'wp_template';
		$template->title          = $data['title'];
		$template->description    = $data['description'];
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->is_custom      = false;
		$template->content        = $content;
		$template->post_types     = $data['post_types'] ?? array();

		return $template;
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WV_Publisher {

	public function __construct() {
		add_action( 'wp_ajax_wv_publish_post', [ $this, 'ajax_publish_post' ] );
	}

	public function ajax_publish_post() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		$post_title       = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) );
		$post_content     = wp_kses_post( wp_unslash( $_POST['post_content'] ?? '' ) );
		$post_status      = sanitize_text_field( wp_unslash( $_POST['post_status'] ?? 'draft' ) );
		$post_category    = absint( $_POST['category'] ?? 0 );
		$slug             = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
		$meta_title       = sanitize_text_field( wp_unslash( $_POST['meta_title'] ?? '' ) );
		$meta_description = sanitize_textarea_field( wp_unslash( $_POST['meta_description'] ?? '' ) );
		$tags             = sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) );
		$target_keyword   = sanitize_text_field( wp_unslash( $_POST['target_keyword'] ?? '' ) );
		$faq_schema_raw   = wp_unslash( $_POST['faq_schema'] ?? '[]' );
		$faq_schema       = json_decode( $faq_schema_raw, true );
		$post_id          = absint( $_POST['post_id'] ?? 0 );

		if ( empty( $post_title ) || empty( $post_content ) ) {
			wp_send_json_error( [ 'message' => 'Title and content are required.' ] );
			return;
		}

		$post_status = in_array( $post_status, [ 'draft', 'publish' ], true ) ? $post_status : 'draft';

		// Strip leading H1 (already used as post title) then convert to Gutenberg blocks.
		$post_content = preg_replace( '/<h1[^>]*>.*?<\/h1>\s*/is', '', $post_content, 1 );
		$post_content = $this->html_to_blocks( trim( $post_content ) );

		/**
		 * Filters the post types available for Wordvane-published content.
		 *
		 * Free tier allows 'post' only. Pro can add 'page', 'product' (WooCommerce),
		 * and any registered custom post type.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_publisher_post_types
		 * @param string[] $types Allowed post type slugs. Default ['post'].
		 */
		$allowed_post_types = (array) apply_filters( 'wordvane_publisher_post_types', [ 'post' ] );
		$post_type_input    = sanitize_text_field( wp_unslash( $_POST['post_type'] ?? 'post' ) );
		$post_type          = in_array( $post_type_input, $allowed_post_types, true ) ? $post_type_input : 'post';

		$post_data = [
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_status'  => $post_status,
			'post_type'    => $post_type,
		];

		if ( ! empty( $slug ) ) {
			$post_data['post_name'] = $slug;
		}

		if ( $post_category > 0 ) {
			$post_data['post_category'] = [ $post_category ];
		}

		if ( $post_id > 0 ) {
			$post_data['ID'] = $post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
			return;
		}

		$post_id = $result;

		if ( ! empty( $tags ) ) {
			$tag_array = array_map( 'trim', explode( ',', $tags ) );
			$tag_array = array_filter( $tag_array );
			wp_set_post_tags( $post_id, $tag_array );
		}

		update_post_meta( $post_id, '_wv_meta_title', $meta_title );
		update_post_meta( $post_id, '_wv_meta_description', $meta_description );
		update_post_meta( $post_id, '_wv_target_keyword', $target_keyword );
		if ( ! empty( $faq_schema ) && is_array( $faq_schema ) ) {
			update_post_meta( $post_id, '_wv_faq_schema', $faq_schema );
		}

		$seo = new WV_SEO();
		$seo->apply_seo_meta( $post_id, $meta_title, $meta_description, $target_keyword, is_array( $faq_schema ) ? $faq_schema : [] );

		wp_send_json_success( [
			'post_id'   => $post_id,
			'edit_link' => get_edit_post_link( $post_id, '' ),
			'view_link' => get_permalink( $post_id ),
			'message'   => 'publish' === $post_status ? 'Article published!' : 'Article saved as draft.',
		] );
	}

	/**
	 * Convert a flat HTML string (h1-h3, p, ul, ol) to Gutenberg block markup.
	 */
	private function html_to_blocks( $html ) {
		if ( empty( trim( $html ) ) ) {
			return '';
		}

		$dom = new DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		// Wrap in a div so LIBXML_HTML_NOIMPLIED works reliably.
		$dom->loadHTML(
			'<html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>',
			LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body ) {
			return $html;
		}

		$blocks = '';
		foreach ( $body->childNodes as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				continue;
			}
			$blocks .= $this->node_to_block( $dom, $node );
		}

		return trim( $blocks );
	}

	private function node_to_block( DOMDocument $dom, DOMNode $node ) {
		$tag   = strtolower( $node->nodeName );
		$inner = $this->inner_html( $dom, $node );

		switch ( $tag ) {
			case 'h1':
				return "<!-- wp:heading {\"level\":1} -->\n<h1 class=\"wp-block-heading\">{$inner}</h1>\n<!-- /wp:heading -->\n\n";

			case 'h2':
				return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$inner}</h2>\n<!-- /wp:heading -->\n\n";

			case 'h3':
				return "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">{$inner}</h3>\n<!-- /wp:heading -->\n\n";

			case 'p':
				$inner = trim( $inner );
				if ( '' === $inner ) {
					return '';
				}
				return "<!-- wp:paragraph -->\n<p>{$inner}</p>\n<!-- /wp:paragraph -->\n\n";

			case 'ul':
				return $this->list_to_block( $dom, $node, false );

			case 'ol':
				return $this->list_to_block( $dom, $node, true );

			default:
				return '';
		}
	}

	private function list_to_block( DOMDocument $dom, DOMNode $node, $ordered ) {
		$list_tag   = $ordered ? 'ol' : 'ul';
		$block_attr = $ordered ? ' {"ordered":true}' : '';
		$items      = '';

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}
			if ( 'li' === strtolower( $child->nodeName ) ) {
				$inner  = $this->inner_html( $dom, $child );
				$items .= "<!-- wp:list-item --><li>{$inner}</li><!-- /wp:list-item -->\n";
			}
		}

		if ( '' === $items ) {
			return '';
		}

		return "<!-- wp:list{$block_attr} -->\n<{$list_tag} class=\"wp-block-list\">\n{$items}</{$list_tag}>\n<!-- /wp:list -->\n\n";
	}

	private function inner_html( DOMDocument $dom, DOMNode $node ) {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}
		return $html;
	}
}

new WV_Publisher();

<?php
/**
 * Plugin Name: Post type listing block lite
 * Plugin URI: https://www.idomit.com/
 * Description: Custom Blocks for post to display with list and grid section with image, title, author and etc.
 * Author: idomit
 * Author URI: https://idomit.com/
 * Version: 1.0
 * Text-domain: post-type-listing-block-for-gb-lite
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
defined( 'ABSPATH' ) || exit;
/**
 * Enqueue the block's assets for the editor.
 *
 * @since 1.0.0
 */
function post_type_listing_block_lite_backend_enqueue() {
	wp_enqueue_script(
		'post-type-listing-block-lite-backend-script',
		plugins_url( 'js/block.build.js', __FILE__ ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' )
	);
}

add_action( 'enqueue_block_editor_assets', 'post_type_listing_block_lite_backend_enqueue' );

add_action( 'rest_api_init', 'post_type_listing_block_lite_register_rout' );

function post_type_listing_block_lite_register_rout() {
	register_rest_route( 'wp/v2', 'ptlb_lite_getPosts', array(
			'methods'  => 'GET',
			'callback' => 'post_type_listing_block_lite_get_items',
		)
	);
}

function post_type_listing_block_lite_get_items( WP_REST_Request $request ) {
	$params      = $request->get_params();
	$taxonomies  = get_object_taxonomies( $params['value'] );
	$terms_array = array();
	foreach ( $taxonomies as $value ) {
		$get_terms = get_terms( array(
				'taxonomy'   => $value,
				'hide_empty' => false
			)
		);
		if ( ! empty( $get_terms ) ) {
			$terms_array[ $value ] = $get_terms;
		}
	}

	return new WP_REST_Response( $terms_array, 200 );
}

/**
 * Render data.
 *
 * @since 1.0.0
 */
function post_type_listing_block_lite_render_callback( $attributes ) {
	$getPostType  = isset( $attributes['getPostType'] ) ? $attributes['getPostType'] : 'post';
	$postCategory = isset( $attributes['postCategory'] ) ? $attributes['postCategory'] : '';
	$postOrderBy  = $attributes['postOrderBy'];
	$args         = array(
		'post_status'    => 'publish',
		'post_type'      => $getPostType,
		'posts_per_page' => $attributes['number_of_items'],
	);
	if ( $postOrderBy ) {
		if ( 'date-desc' === $postOrderBy ) {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}
		if ( 'date-asc' === $postOrderBy ) {
			$args['orderby'] = 'date';
			$args['order']   = 'ASC';
		}
		if ( 'title-asc' === $postOrderBy ) {
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
		}
		if ( 'title-desc' === $postOrderBy ) {
			$args['orderby'] = 'title';
			$args['order']   = 'DESC';
		}
	} else {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	if ( $postCategory ) {
		$postCategory_decode = json_decode( $postCategory );
		if ( ! empty( $postCategory_decode ) ) {
			$tax_rel_args = [ 'relation' => 'AND' ];
			foreach ( $postCategory_decode as $taxonomy => $value ) {
				if ( isset( $taxonomy ) && ! empty( $value ) ) {
					$tax_rel_args[] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $value,
					];
				}
			}
			$count_tax_args = count( $tax_rel_args );
			if ( $count_tax_args > 1 ) {
				$args['tax_query'] = $tax_rel_args;
			}
		}
	}
	$args['fields'] = 'ids';
	$all_prd        = new WP_Query( $args );
	if ( $all_prd->posts ) {
		$all_ids_result = $all_prd->posts;
	}
	$all_prd_count = $all_prd->found_posts;
	if ( $all_prd_count > 0 ) {
		if ( $all_prd_count < $attributes['columns'] ) {
			$attributes['columns'] = $all_prd_count;
		}
	}
	$width_css = '';
	if ( 'grid' === $attributes['postLayout'] ) {
		if ( isset( $attributes['columns'] ) && isset( $attributes['postMargin'] ) ) {
			if ( 1 === $attributes['columns'] ) {
				$width_css .= 'width:calc(100% - ' . ( $attributes['rightMargin'] + $attributes['leftMargin'] ) . 'px);';
			}
			if ( 2 === $attributes['columns'] ) {
				$width_css .= 'width:calc(50% - ' . ( $attributes['rightMargin'] + $attributes['leftMargin'] ) . 'px);';
			}
			if ( 3 === $attributes['columns'] ) {
				$width_css .= 'width:calc(33.33% - ' . ( $attributes['rightMargin'] + $attributes['leftMargin'] ) . 'px);';
			}
			if ( 4 === $attributes['columns'] ) {
				$width_css .= 'width:calc(25% - ' . ( $attributes['rightMargin'] + $attributes['leftMargin'] ) . 'px);';
			}
			if ( 5 === $attributes['columns'] ) {
				$width_css .= 'width:calc(20% - ' . ( $attributes['rightMargin'] + $attributes['leftMargin'] ) . 'px);';
			}
			if ( 6 === $attributes['columns'] ) {
				$width_css .= 'width:calc(16.66% - ' . ( $attributes['rightMargin'] + $attributes['leftMargin'] ) . 'px);';
			}
		}
	}
	$combine_css = '';
	if ( $width_css ) {
		$combine_css .= $width_css;
	}
	$image_align_class = '';
	if ( 'none' !== $attributes['imageAlignment'] ) {
		$image_align_class .= 'idm-align-img idm-' . $attributes['imageAlignment'] . '-image';
	}
	$title_align_class = '';
	if ( 'none' !== $attributes['titleAlignment'] ) {
		$title_align_class .= 'idm-align-title idm-' . $attributes['titleAlignment'] . '-title';
	}

	$get_content = '';
	ob_start();
	if ( $all_ids_result ) {
		?>
    <div class="wp-block-idm-post-type-<?php echo esc_attr( $attributes['postLayout'] ); ?> idm-block-post-type-<?php echo esc_attr( $attributes['postLayout'] ); ?> blog-sec">
		<?php
		$count = 1;
		foreach ( $all_ids_result as $get_post_id ) {
			$post_id = $get_post_id;
			if ( 1 === $count % $attributes['columns'] ) {
				?>
                <div class="post_type_list_div row">
				<?php
			}
			$title_style = '';
			if ( $attributes['titleFontSize'] ) {
				$title_style .= 'font-size: ' . $attributes['titleFontSize'] . 'px;';
			}
			if ( $attributes['titleColor'] ) {
				$title_style .= 'color: ' . $attributes['titleColor'] . ';';
			}
			if ( $attributes['textTransfrom'] ) {
				$title_style .= 'text-transform: ' . $attributes['textTransfrom'] . ';';
			}
			?>
            <div class="default-mg columns-<?php echo esc_attr( $attributes['columns'] ); ?> column"
                 style="<?php echo esc_attr( $combine_css ); ?>">
				<?php
				if ( $attributes['display_feature_image'] ) {
					?>
                    <div class="imd-pt-image">
                        <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                           class="blog-img <?php echo esc_attr( $image_align_class ); ?>">
							<?php echo get_the_post_thumbnail( $post_id, $attributes['featureImageSetting'] ); ?>
                        </a>
                    </div>
					<?php
				}
				if ( $attributes['display_post_title'] ) {
					?>
                    <div class="pt-title">
						<?php
						if ( 'none' === $attributes['titleHeading'] ) {
							?>
                            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                               class="blog-title <?php echo esc_attr( $title_align_class ); ?>"
                               style="<?php echo esc_attr( $title_style ); ?>">
								<?php echo esc_html( get_the_title( $post_id ) ); ?>
                            </a>
							<?php
						} else {
							echo '<' . esc_html( $attributes['titleHeading'] ) . '>';
							?>
                            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                               class="blog-title <?php echo esc_attr( $title_align_class ); ?>"
                               style="<?php echo esc_attr( $title_style ); ?>">
								<?php echo esc_html( get_the_title( $post_id ) ); ?>
                            </a>
							<?php
							echo '</' . esc_html( $attributes['titleHeading'] ) . '>';
						}
						?>
                    </div>
					<?php
				}
				if ( $attributes['post_content'] ) {
					$post_content_show_type = $attributes['post_content_show_type'];
					$excerpt_words_length   = $attributes['excerpt_words_length'];
					$display_read_more_link = $attributes['display_read_more_link'];

					$post = get_post( $post_id );
					if ( 'excerpt' === $post_content_show_type ) {
						$excerpt = apply_filters( 'the_excerpt',
							get_post_field(
								'post_excerpt',
								$post_id,
								'display'
							)
						);
						if ( empty( $excerpt ) && isset( $excerpt_words_length ) ) {
							$new_content = apply_filters( 'the_excerpt',
								wp_trim_words(
									preg_replace(
										array(
											'/\<figcaption>.*\<\/figcaption>/',
											'/\[caption.*\[\/caption\]/',
										),
										'',
										apply_filters( 'the_content', $post->post_content )
									),
									$excerpt_words_length
								)
							);
						}
					} else {

						$new_content = apply_filters( 'the_content', $post->post_content );
					}

					?>
                    <div class="content">
						<?php
						echo wp_kses_post( $new_content );
						if ( $display_read_more_link ) {
							$readmore_text = $attributes['readmore_text'];
							?>
                            <p>
                                <a href="<?php echo esc_url( get_the_permalink( $post_id ) ); ?>"><?php echo esc_html( $readmore_text ); ?></a>
                            </p>
							<?php
						}
						?>
                    </div>
					<?php
				}
				?>
            </div>
			<?php
			if ( 0 === $count % $attributes['columns'] ) {
				?>
                </div>
				<?php
			}
			$count ++;
		}
		if ( 1 !== $count % $attributes['columns'] ) {
			?>
            </div>
			<?php
		}
		?>
        </div>
		<?php

	} else {
		?>
        <div class="return-msg">
			<?php echo esc_html__( 'Sorry, Not match your result', 'post-type-listing-block-for-gb-lite' ); ?>
        </div>
		<?php
	}
	$get_content .= ob_get_contents();
	ob_end_clean();

	return $get_content;
}

/**
 * Register block.
 *
 * @since 1.0.0
 */
function post_type_listing_block_lite_register_block() {
	register_block_type( 'post-type-listing-block-lite/post-listing-block-lite', array(
			'attributes'      => array(
				'postLayout'              => array(
					'type'    => 'string',
					'default' => 'grid'
				),
				'step'                    => array(
					'type' => 'number',
				),
				'getTaxonomyType'         => array(
					'type' => 'string',
				),
				'number_of_items'         => array(
					'type'    => 'integer',
					'default' => 3,
				),
				'columns'                 => array(
					'type'    => 'integer',
					'default' => 3,
				),
				'postCategory'            => array(
					'type' => 'string',
				),
				'setCat1'                 => array(
					'type'    => 'array',
					'default' => '[]',
					'items'   => array( 'type' => 'mixed' )
				),
				'titleFontSize'           => array(
					'type'    => 'integer',
					'default' => 16,
				),
				'getPostType'             => array(
					'type' => 'string',
				),
				'post_content'            => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'post_content_show_type'  => array(
					'type'    => 'string',
					'default' => 'excerpt',
				),
				'excerpt_words_length'    => array(
					'type'    => 'integer',
					'default' => 30,
				),
				'display_read_more_link'  => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'readmore_text'           => array(
					'type'    => 'string',
					'default' => 'Read More',
				),
				'postOrderBy'             => array(
					'type'    => 'string',
					'default' => 'date_asc',
				),
				'display_post_title'      => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'display_feature_image'   => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'postLayout'              => array(
					'type'    => 'string',
					'default' => 'grid'
				),
				'titleColor'              => array(
					'type'    => 'string',
					'default' => '',
				),
				'titleHeading'            => array(
					'type'    => 'string',
					'default' => 'none',
				),
				'textTransfrom'           => array(
					'type'    => 'string',
					'default' => 'none',
				),
				'featureImageSetting'     => array(
					'type'    => 'string',
					'default' => 'large',
				),
				'imageAlignment'          => array(
					'type'    => 'string',
					'default' => 'none',
				),
				'titleAlignment'          => array(
					'type'    => 'string',
					'default' => 'none',
				),
			),
			'render_callback' => 'post_type_listing_block_lite_render_callback',
		)
	);
}

add_action( 'init', 'post_type_listing_block_lite_register_block' );

add_action( 'enqueue_block_assets', 'post_type_listing_block_lite_enqueue_block_assets' );
/**
 * Enqueue the block's assets for the front.
 *
 * @since 1.0.0
 */
function post_type_listing_block_lite_enqueue_block_assets() {
	wp_enqueue_style(
		'post-type-listing-block-lite-enqueue-block-assets',
		plugins_url( 'css/ptlb-admin.css', __FILE__ ),
		array(),
		''
	);
}
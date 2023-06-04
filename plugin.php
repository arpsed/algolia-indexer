<?php
/**
 * Plugin Name: Algolia Indexer
 * Description: Implement Algolia indexing.
 * Author: Gogo
 * Author URI: https://github.com/arpsed
 * Version: 1.0.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aglidx
 * Domain Path: /languages
 *
 * @since 1.0.0
 * @package aglidx
 */

defined( 'ABSPATH' ) || exit;

define( 'AGLIDX_VER', '1.0.1' );
define( 'AGLIDX_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGLIDX_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook(
	__FILE__,
	function() {}
);
register_deactivation_hook(
	__FILE__,
	function() {}
);

require_once AGLIDX_PATH . 'vendor/autoload.php';

add_action( 'admin_menu', function() {
	$title = esc_html__( 'Algolia Indexer', 'aglidx' );

	add_management_page(
		$title,
		$title,
		'manage_options',
		'gg_algolia_indexer',
		function () {
			$title       = esc_html__( 'Algolia Indexer Plugin Settings', 'aglidx' );
			$this_url    = rawurlencode( admin_url( 'tools.php?page=gg_algolia_indexer' ) );
			$action_url  = esc_url( admin_url( 'admin-post.php' ) );
			$description = esc_html__( 'Enter your settings here.', 'aglidx' );
			$nonce_idx   = wp_nonce_field( 'gg_algolia_indexer', 'idx_nonce', false, false );
			$nonce_send  = wp_nonce_field( 'gg_send_products', 'send_nonce', false, false );
			$application = esc_html__( 'Application ID', 'aglidx' );
			$admin_key   = esc_html__( 'Admin/Write API Key', 'aglidx' );
			$index_name  = esc_html__( 'Index Name', 'aglidx' );
			$auto_add    = esc_html__( 'Automatically index new products', 'aglidx' );
			$button_save = esc_html__( 'Save Changes', 'aglidx' );
			$button_send = esc_html__( 'Send Products to Algolia', 'aglidx' );
			$options     = get_option( 'gg_algolia_indexer' );
			$start_index = esc_html__( 'Indexing started, please do not reload or navigated away from this page.', 'aglidx' );
			$end_index   = esc_html__( 'Indexing complete.', 'aglidx' );

			if ( empty( $options ) ) {
				$options = [
					'application_id' => '',
					'admin_key'      => '',
					'index_name'     => '',
					'auto_add'       => false,
				];
			}

			$checked  = checked( true, $options['auto_add'], false );
			$per_page = esc_html__( 'items each', 'aglidx' );

			// phpcs:ignore
			echo <<<HTML
<div class="wrap">
	<h1>{$title}</h1>
	<p>{$description}</p>
	<form method="POST" action="{$action_url}">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="appId">{$application}</label>
					</th>
					<td>
						<input id="appId" type="text" name="application_id" value="{$options['application_id']}">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="apiKey">{$admin_key}</label>
					</th>
					<td>
						<input id="apiKey" type="text" name="admin_key" value="{$options['admin_key']}">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="indexName">{$index_name}</label>
					</th>
					<td>
						<input id="indexName" type="text" name="index_name" value="{$options['index_name']}">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="autoAdd">{$auto_add}</label>
					</th>
					<td>
						<input id="autoAdd" type="checkbox" name="auto_add" value="1" {$checked}>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary">{$button_save}</button>
		</p>
		<input name="this_page" type="hidden" value="{$this_url}">
		<input name="action" type="hidden" value="gg_algolia_indexer">
		{$nonce_idx}
	</form>
	<hr>
	<div class="send-products-wrapper">
		<label>
			<input name="ppp" type="number" value="500">
			{$per_page}
		</label>
		{$nonce_send}
		<p class="submit">
			<button id="sendToAlgolia" type="button" class="button button-primary">{$button_send}</button>
		</p>
		<ul class="send-products-progress"></ul>
	</div>
</div>
<script>
var texts = {
	'start': '{$start_index}',
	'end': '{$end_index}',
};
</script>
HTML;

			echo <<<'SCRIPT'
<script>
jQuery(e=>{e("#sendToAlgolia").on("click",a=>{let t=document.querySelector('[name="ppp"]').value,p=e(".send-products-progress"),d=e("button"),s=0,n=0;function o(){e.ajax({method:"POST",url:ajaxurl,dataType:"json",data:{action:"gg_send_products",nonce:document.getElementById("send_nonce").value,ppp:t,page:s},success(a){s=a.data.page,n=a.data.max,p.append(e("<li>").text(`${s} of ${n}`)),s<n?o():(p.append(e("<li>").text(window.texts.end)),d.prop("disabled",!1))},error(a,t,s){d.prop("disabled",!1),void 0!==a.responseJSON.data&&void 0!==a.responseJSON.data.message?p.append(e("<li>").text(a.responseJSON.data.message)):p.append(e("<li>").text(s))}})}d.prop("disabled",!0),p.append(e("<li>").text(window.texts.start)),o()})});
</script>
SCRIPT;
		}
	);
} );

add_action( 'admin_post_gg_algolia_indexer', function() {
	if ( ! empty( $_POST ) && check_admin_referer( 'gg_algolia_indexer', 'idx_nonce' ) ) {
		$request = wp_unslash( $_POST );
		$options = [
			'application_id' => sanitize_text_field( $request['application_id'] ),
			'admin_key'      => sanitize_text_field( $request['admin_key'] ),
			'index_name'     => sanitize_text_field( $request['index_name'] ),
			'auto_add'       => isset( $request['auto_add'] ) ? true : false,
		];

		update_option( 'gg_algolia_indexer', $options, false );

		wp_safe_redirect( esc_url_raw( rawurldecode( $_POST['this_page'] ) ) ); // phpcs:ignore
		exit;
	}
} );

add_action( 'save_post_product', function( $post_id, $post ) {
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	$options = get_option( 'gg_algolia_indexer' );

	if ( empty( $options ) || ! $options['auto_add'] ) {
		return;
	}

	gg_send_products_to_algolia( $post_id );
}, 20, 2 );

add_action( 'wp_ajax_gg_send_products', function() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gg_send_products' ) ) {
		wp_send_json_error( [ 'message' => esc_attr__( 'Nonce.', 'aglidx' ) ], 401 );
	}

	if ( ! isset( $_POST['ppp'] ) ) {
		wp_send_json_error( [ 'message' => esc_attr__( 'Please include item per send, or enter -1.', 'aglidx' ) ], 406 );
	}

	$ppp  = (int) sanitize_text_field( wp_unslash( $_POST['ppp'] ) );
	$page = 0;

	if ( isset( $_POST['page'] ) ) {
		$page = (int) sanitize_text_field( wp_unslash( $_POST['page'] ) );
	}

	$page++;

	$return = gg_send_products_to_algolia( 0, $ppp, $page );

	if ( $return['error'] ) {
		wp_send_json_error( [ 'message' => $return['message'] ], 400 );
	}

	wp_send_json_success( [
		'page' => $page,
		'max'  => $return['max'],
	] );
} );

function gg_send_products_to_algolia( int $id, int $ppp = -1, int $page = 1 ): array {
	$options = get_option( 'gg_algolia_indexer' );

	if ( empty( $options ) ) {
		return [
			'error'   => true,
			'message' => esc_attr__( 'Please set Application ID & API Key', 'aglidx' ),
		];
	}

	$max  = 0;
	$args = [
		'status'   => 'publish',
		'paginate' => false,
		'limit'    => -1,
	];

	if ( -1 !== $ppp ) {
		$args['paginate'] = true;
		$args['limit']    = $ppp;
		$args['page']     = $page;
	}

	if ( ! empty( $id ) ) {
		$args['include'] = [ $id ];
	}

	$query    = wc_get_products( $args );
	$products = -1 !== $ppp ? $query->products : $query;
	$records  = [];

	if ( -1 !== $ppp ) {
		$max = $query->max_num_pages;
	}

	foreach ( $products as $product ) {
		$product_type_price = gg_get_product_type_price( $product );
		$sale_price         = $product_type_price['sale_price'];
		$regular_price      = $product_type_price['regular_price'];

		preg_match( '/<img(.*)src(.*)=(.*)"(.*)"/U', $product->get_image(), $result );

		$product_image    = array_pop( $result );
		$product_id       = (int) $product->get_id();
		$desc             = $product->get_short_description();
		$video            = get_field( 'upload_video', $product_id );
		$video_aws        = get_field( 'watermarked_video', $product_id );
		$selected_creator = get_field( 'creator', $product_id );
		$creator_keywords = '';
		$creator_rating   = 0.0;

		if ( $selected_creator ) {
			$creator_keywords = get_field( 'creator_keywords', $selected_creator->ID );
			$creator_rating   = (float) get_field( 'rating-creator', $selected_creator->ID );
		}

		$taxonomies = [];
		$record     = [];
		$keywords   = [];

		foreach ( [
			'product_cat',
			'gender',
			'ages',
			'ethnicity',
			'industry',
			'product_video_type',
			'type_product',
			'product_tag',
		] as $taxo ) {
			$the_taxo = get_the_terms( $product_id, $taxo );

			if ( false === $the_taxo ) {
				continue;
			}

			foreach ( $the_taxo as $term ) {
				if ( 'product_tag' === $taxo ) {
					$keywords[] = strtolower( $term->name );
				} else {
					$taxonomies[ $taxo ][] = $term->name;

					if ( 'product_cat' === $taxo ) {
						if ( 0 !== $term->parent ) {
							$parent      = get_term( $term->parent, $taxo );
							$parent_slug = str_replace( '-', '_', $parent->slug );

							if ( ! isset( $taxonomies[ $parent_slug ] ) || ! is_array( $taxonomies[ $parent_slug ] ) ) {
								$taxonomies[ $parent_slug ] = [];
							}

							if ( ! in_array( 'All ' . $parent->name, $taxonomies[ $parent_slug ], true ) ) {
								$taxonomies[ $parent_slug ][] = 'All ' . $parent->name;
							}

							if ( ! in_array( $term->name, $taxonomies[ $parent_slug ], true ) ) {
								$taxonomies[ $parent_slug ][] = $term->name;
							}
						} else {
							$slug = str_replace( '-', '_', $term->slug );

							if ( ! isset( $taxonomies[ $slug ] ) ) {
								$taxonomies[ $slug ] = 'designed_to_fit_all_categories' === $slug ?
									true : [];
							}
						}
					}
				}
			}
		}

		$record['objectID']        = $product_id;
		$record['productName']     = $product->get_name();
		$record['productLink']     = get_permalink( $product_id );
		$record['addToCart']       = sprintf( '?add-to-cart=%d&variation_id=%d', $product_id, $product->get_children()[0] );
		$record['productImage']    = $product_image;
		$record['productDesc']     = empty( $desc ) ? $product->get_description() : $desc;
		$record['priceRegular']    = $regular_price;
		$record['priceSale']       = $sale_price;
		$record['onSale']          = $product->is_on_sale();
		$record['taxonomies']      = $taxonomies;
		$record['duration']        = get_field( 'video_duration', $product_id );
		$record['videoURL']        = $video ? $video['url'] : null;
		$record['videoAWS']        = $video_aws ? do_shortcode( $video_aws ) : null;
		$record['creatorKeywords'] = $creator_keywords;
		$record['keywords']        = implode( ',', $keywords );
		$record['creatorRating']   = $creator_rating;

		$records[] = $record;
	}

	wp_reset_postdata();

	$client = Algolia\AlgoliaSearch\SearchClient::create( $options['application_id'], $options['admin_key'] );
	$index  = $client->initIndex( $options['index_name'] );

	$index->setSettings( [
		'attributesForFaceting' => [
			'searchable(taxonomies)',
			'filterOnly(onSale)',
			// 'searchable(taxonomies.cosmetics)',
			// 'searchable(taxonomies.health_wellness)',
			// 'searchable(taxonomies.consumer_goods)',
		],
		'searchableAttributes'  => [
			'productName',
			'productDesc',
			'creatorKeywords',
			'keywords',
		],
	] );

	try {
		$index->saveObjects( $records );
	} catch ( Exception $e ) {
		return [
			'error'   => true,
			'message' => $e->getMessage(),
		];
	}

	return [
		'error' => false,
		'max'   => $max,
	];
}

function gg_get_product_type_price( $product ) {
	$sale_price    = 0;
	$regular_price = 0;

	if ( $product->is_type( 'simple' ) ) {
		$sale_price    = $product->get_sale_price();
		$regular_price = $product->get_regular_price();
	} elseif ( $product->is_type( 'variable' ) ) {
		$sale_price    = $product->get_variation_sale_price( 'min', true );
		$regular_price = $product->get_variation_regular_price( 'max', true );
	}

	return [
		'sale_price'    => $sale_price,
		'regular_price' => $regular_price,
	];
}

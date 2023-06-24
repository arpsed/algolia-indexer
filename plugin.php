<?php
/**
 * Plugin Name: Algolia Indexer
 * Plugin URI: https://github.com/arpsed/algolia-indexer
 * Description: Implement Algolia indexing.
 * Author: Gogo
 * Author URI: https://github.com/arpsed
 * Version: 1.0.0
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain: aglidx
 * Domain Path: /languages
 *
 * @since 1.0.0
 * @package aglidx
 */

defined( 'ABSPATH' ) || exit;

define( 'AGLIDX_VER', '1.0.2' );
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
			$title       = esc_html__( 'Algolia Indexer Plugin', 'aglidx' );
			$this_url    = rawurlencode( admin_url( 'tools.php?page=gg_algolia_indexer' ) );
			$action_url  = esc_url( admin_url( 'admin-post.php' ) );
			$description = esc_html__( 'Enter your settings here.', 'aglidx' );
			$nonce_idx   = wp_nonce_field( 'gg_algolia_indexer', 'idx_nonce', false, false );
			$nonce_send  = wp_nonce_field( 'gg_send_items', 'send_nonce', false, false );
			$application = esc_html__( 'Application ID', 'aglidx' );
			$admin_key   = esc_html__( 'Admin/Write API Key', 'aglidx' );
			$index_name  = esc_html__( 'Index Name', 'aglidx' );
			$auto_add    = esc_html__( 'Automatically index new items', 'aglidx' );
			$post_type   = esc_html__( 'Post type', 'aglidx' );
			$button_save = esc_html__( 'Save Changes', 'aglidx' );
			$button_send = esc_html__( 'Send Items to Algolia', 'aglidx' );
			$options     = get_option( 'gg_algolia_indexer' );
			$start_index = esc_html__( 'Indexing started, please do not reload or navigated away from this page.', 'aglidx' );
			$end_index   = esc_html__( 'Indexing complete.', 'aglidx' );

			if ( empty( $options ) ) {
				$options = [
					'application_id' => '',
					'admin_key'      => '',
					'index_name'     => '',
					'auto_add'       => false,
					'post_type'      => '',
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
						<label for="postType">{$post_type}</label>
					</th>
					<td>
						<input id="postType" type="text" name="post_type" value="{$options['post_type']}">
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
		<ul class="send-items-progress"></ul>
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
jQuery(e=>{e("#sendToAlgolia").on("click",a=>{let t=document.querySelector('[name="ppp"]').value,p=e(".send-items-progress"),d=e("button"),s=0,n=0;function o(){e.ajax({method:"POST",url:ajaxurl,dataType:"json",data:{action:"gg_send_items",nonce:document.getElementById("send_nonce").value,ppp:t,page:s},success(a){s=a.data.page,n=a.data.max,p.append(e("<li>").text(`${s} of ${n}`)),s<n?o():(p.append(e("<li>").text(window.texts.end)),d.prop("disabled",!1))},error(a,t,s){d.prop("disabled",!1),void 0!==a.responseJSON&&void 0!==a.responseJSON.data&&void 0!==a.responseJSON.data.message?p.append(e("<li>").text(a.responseJSON.data.message)):p.append(e("<li>").text(s))}})}d.prop("disabled",!0),p.append(e("<li>").text(window.texts.start)),o()})});
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
			'post_type'      => sanitize_text_field( $request['post_type'] ),
		];

		update_option( 'gg_algolia_indexer', $options, false );

		wp_safe_redirect( esc_url_raw( rawurldecode( $_POST['this_page'] ) ) ); // phpcs:ignore
		exit;
	}
} );

add_action( 'save_post', function( $post_id, $post ) {
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	$options = get_option( 'gg_algolia_indexer' );

	if ( empty( $options ) || ! $options['auto_add'] ) {
		return;
	}

	if ( isset( $options['post_type'] ) && ! empty( $options['post_type'] ) ) {
		if ( $options['post_type'] !== $post->post_type ) {
			return;
		}
	}

	gg_send_items_to_algolia( $post_id );
}, 20, 2 );

add_action( 'wp_ajax_gg_send_items', function() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gg_send_items' ) ) {
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

	$return = gg_send_items_to_algolia( 0, $ppp, $page );

	if ( $return['error'] ) {
		wp_send_json_error( [ 'message' => $return['message'] ], 400 );
	}

	wp_send_json_success( [
		'page' => $page,
		'max'  => $return['max'],
	] );
} );

/**
 * Function to send items to Algolia
 *
 * @since 1.0.0
 *
 * @param int $id   Post ID. Used when sending only one item.
 * @param int $ppp  Posts per page, how many items send per request.
 * @param int $page Current page/request.
 *
 * @return array
 */
function gg_send_items_to_algolia( int $id = 0, int $ppp = -1, int $page = 1 ): array {
	$options = get_option( 'gg_algolia_indexer' );

	if ( empty( $options ) ) {
		return [
			'error'   => true,
			'message' => esc_attr__( 'Please set Application ID & API Key', 'aglidx' ),
		];
	}

	/**
	 * List of item to be indexed and total items.
	 *
	 * @link https://www.algolia.com/doc/api-reference/api-methods/save-objects/#replace-all-attributes-in-existing-records
	 * @since 1.0.2
	 *
	 * @param array $records {
	 *     @type array $data Items to be indexed in Algolia.
	 *     @type int   $max  Total items.
	 * }
	 * @param int   $id      Post ID. Used when sending only one item.
	 * @param int   $ppp     Posts per page.
	 * @param int   $page    Current page.
	 */
	$records = apply_filters( 'gg_algolia_records', [], $id, $ppp, $page );

	if ( ! empty( $records['data'] ) ) {
		$client = Algolia\AlgoliaSearch\SearchClient::create( $options['application_id'], $options['admin_key'] );
		$index  = $client->initIndex( $options['index_name'] );

		/**
		 * Change an index’s settings.
		 *
		 * Only specified settings are overridden; unspecified settings are left unchanged.
		 * Specifying null for a setting resets it to its default value.
		 *
		 * @link https://www.algolia.com/doc/api-reference/api-methods/set-settings/
		 * @since 1.0.2
		 *
		 * @param array $settings Index's settings.
		 */
		$settings = apply_filters( 'gg_algolia_settings', [] );

		if ( ! empty( $settings ) ) {
			$index->setSettings( $settings );
		}

		try {
			$index->saveObjects( $records['data'] );
		} catch ( Exception $e ) {
			return [
				'error'   => true,
				'message' => $e->getMessage(),
			];
		}
	}

	return [
		'error' => false,
		'max'   => $records['max'],
	];
}

// require_once AGLIDX_PATH . 'example.php';

<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'gg_algolia_records', function( $records, $id, $ppp, $page ) {
	$records = [];
	$max     = 0;
	$args    = [
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

	return [
		'data' => $records,
		'max'  => $max,
	];
}, 10, 3 );

add_filter( 'gg_algolia_settings', function( $settings ) {
	$settings = [
		'attributesForFaceting' => [
			'taxonomies',
			'filterOnly(onSale)',
			'searchable(taxonomies.consumer_goods)',
			'searchable(taxonomies.cosmetics)',
			'searchable(taxonomies.health_wellness)',
		],
		'searchableAttributes'  => [
			'productName',
			'productDesc',
			'creatorKeywords',
			'keywords',
			'taxonomies.product_cat',
			'taxonomies.type_product',
			'taxonomies.gender',
			'taxonomies.ethnicity',
			'taxonomies.ages',
			'taxonomies.product_video_type',
		],
	];

	return $settings;
} );

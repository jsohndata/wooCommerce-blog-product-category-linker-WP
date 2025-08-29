/**
 * Combined WooCommerce + Blog Category Linker
 * 
 * ✅ Adds “📚 Additional Reading” (blog posts) to product pages.
 * ✅ Adds “🪴 See Related Products” (product category link) to blog posts.
 * ✅ Fully automatic: matches by shared category slug (no manual input).
 * ✅ WPCode, Code Snippets, or child theme compatible.
 */

// ========================
// CONFIGURATION VARIABLES
// ========================

// -- Product Page: Show Blog Posts --
const SANSE_RELATED_POSTS_LIMIT      = 3;
const SANSE_SECTION_HEADING          = '📚 Additional Reading';
const SANSE_SECTION_CLASS            = 'sanse-additional-reading';
const SANSE_SECTION_MARGIN           = '2rem';

// -- Blog Post Page: Show Product Category Link --
const SANSE_SHOW_PRODUCTS_HEADING    = '🪴 See Related Products';
const SANSE_SHOW_PRODUCTS_COPY       = 'Looking to bring one of these beauties home? Explore all related products below.';
const SANSE_PRODUCTS_SECTION_CLASS   = 'sanse-related-products';
const SANSE_PRODUCTS_SECTION_MARGIN  = '2rem';


// =============================
// PRODUCT PAGE → BLOG POSTS
// =============================
add_action( 'woocommerce_after_single_product_summary', 'sanse_show_related_posts_by_category', 15 );

function sanse_show_related_posts_by_category() {
	if ( ! is_product() ) return;

	global $post;

	$product_terms = get_the_terms( $post->ID, 'product_cat' );
	if ( empty( $product_terms ) || is_wp_error( $product_terms ) ) return;

	$slugs = array_map( fn( $term ) => $term->slug, $product_terms );

	$related_posts = get_posts( [
		'category_name'  => implode( ',', $slugs ),
		'posts_per_page' => SANSE_RELATED_POSTS_LIMIT,
		'post_status'    => 'publish',
	] );

	if ( empty( $related_posts ) ) return;

	// Output
	printf(
		'<section class="woocommerce %s" style="margin-top:%s;">',
		esc_attr( SANSE_SECTION_CLASS ),
		esc_attr( SANSE_SECTION_MARGIN )
	);
	printf( '<h3>%s</h3>', esc_html( SANSE_SECTION_HEADING ) );
	echo '<ul>';
	foreach ( $related_posts as $post_item ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( get_permalink( $post_item ) ),
			esc_html( get_the_title( $post_item ) )
		);
	}
	echo '</ul></section>';
}


// =============================
// BLOG POST → PRODUCT CATEGORY
// =============================
add_filter( 'the_content', 'sanse_add_related_product_category_link' );

function sanse_add_related_product_category_link( $content ) {
	if ( ! is_single() || ! in_the_loop() || ! is_main_query() ) return $content;

	$post_terms = get_the_category();
	if ( empty( $post_terms ) ) return $content;

	$product_cats = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
	] );

	if ( is_wp_error( $product_cats ) || empty( $product_cats ) ) return $content;

	$post_slugs = array_map( fn( $term ) => $term->slug, $post_terms );
	$matched_cat = null;

	foreach ( $product_cats as $cat ) {
		if ( in_array( $cat->slug, $post_slugs, true ) ) {
			$matched_cat = $cat;
			break;
		}
	}

	if ( ! $matched_cat ) return $content;

	// Build section
	$section  = sprintf( '<section class="%s" style="margin-top:%s;">', esc_attr( SANSE_PRODUCTS_SECTION_CLASS ), esc_attr( SANSE_PRODUCTS_SECTION_MARGIN ) );
	$section .= sprintf( '<h3>%s</h3>', esc_html( SANSE_SHOW_PRODUCTS_HEADING ) );
	$section .= sprintf( '<p>%s</p>', esc_html( SANSE_SHOW_PRODUCTS_COPY ) );
	$section .= sprintf(
		'<p><a class="button" href="%s">%s</a></p>',
		esc_url( get_term_link( $matched_cat ) ),
		'Browse Products in ' . esc_html( $matched_cat->name )
	);
	$section .= '</section>';

	return $content . $section;
}

# WooCommerce ↔︎ Blog Category Linker — WPCode-Compatible

🤖 **AI-Enhanced (GPT-5)** snippet that **cross-links WooCommerce products and WordPress blog posts by shared category slug** — all automatic, no manual mapping.

- On **product pages**: adds a **“📚 Additional Reading”** section listing matching blog posts.
- On **blog posts**: adds a **“🪴 See Related Products”** section with a button to the matched **product category archive**.

Clean, lightweight, and compatible with **WPCode**, **Code Snippets**, or a child theme’s `functions.php`.

---

## Working Idea
Show plant care articles on matching product pages, and let blog readers jump to related product categories.

---

## Features

- 🔁 **Two-way linking** between products and posts via **shared category slug**
- 🛒 **Product ➜ Blog**: “📚 Additional Reading” list
- 📝 **Blog ➜ Products**: “🪴 See Related Products” button (links to the matched product category archive)
- ⚙️ **Configurable headings, copy, classes, and margins** at the top
- 🧩 **Theme-agnostic**: uses standard WooCommerce/WordPress hooks & filters
- 🧼 **Minimal markup** for easy styling
- 🧠 **Functional-style helpers** and early returns for clarity

---

## Requirements

- WordPress
- WooCommerce (active)
- One of the following for installation:
  - [WPCode plugin](https://wordpress.org/plugins/wpcode/) (recommended)
  - Code Snippets plugin
  - A child theme’s `functions.php`

---

## Installation

### Option 1: WPCode (Recommended)
1. Install and activate **WPCode**.
2. Go to **Code Snippets → Add New**.
3. Choose **“Add Your Custom Code (New Snippet)”** → **PHP Snippet**.
4. Name it: `Woo ↔︎ Blog Category Linker`.
5. Paste the code from the **Code** section below.
6. Set **Location** to `Run Everywhere`.
7. Save and **Activate**.

### Option 2: Add to `functions.php`
1. Open your child theme’s `functions.php`.
2. Paste the PHP code at the end.
3. Save the file.

---

## Customization

- **Change output text**: Edit the constants under **CONFIGURATION VARIABLES**.
- **Adjust count of related posts**: `SANSE_RELATED_POSTS_LIMIT`.
- **Style the sections**: Target `.sanse-additional-reading` and `.sanse-related-products` in your CSS.
- **Tighten matching**: Ensure your **post categories** and **product categories** share the **same slug** for an exact match.

> Tip: Keep slugs human-readable and consistent across content types for best UX and SEO.

---

## Code

```php
<?php
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
```
---

## Styling (Optional)

Add a small CSS file or a Customizer snippet:

```css
.sanse-additional-reading ul { list-style: disc; padding-left: 1.25rem; }
.sanse-additional-reading li + li { margin-top: .25rem; }
.sanse-related-products .button { display:inline-block; margin-top:.5rem; }
@media (max-width: 768px) {
  .sanse-related-products .button { width:100%; text-align:center; }
}
```

---

## FAQs

**Does this create categories automatically?**  
No — it matches **existing** slugs between post categories and product categories.

**What if there are multiple matching product categories?**  
The first matched category is used for the blog ➜ products button. You can expand logic if needed.

**Will this hurt SEO?**  
It’s lightweight, internal linking is good for discoverability. Keep anchor text natural and avoid duplicate blocks.

---

## Changelog

- **1.0.0** — Initial release: two-way category-based linking.

<?php
/**
 * Builds the Watchlist store inside WordPress Playground.
 * Run by the blueprint after WooCommerce and the theme are installed.
 * Product photos are read from /wordpress/wl-images.
 */
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Fast demo build: the photos are already web-sized, so skip making thumbnails of each one.
// (Real hosting can regenerate thumbnails later.)
if ( defined( 'WL_FAST' ) && WL_FAST ) {
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
	add_filter( 'big_image_size_threshold', '__return_false' );
	add_filter( 'woocommerce_background_image_regeneration', '__return_false' );
	add_filter( 'woocommerce_resize_images', '__return_false' );
}

// Store settings. The client quotes these pieces in US dollars.
foreach ( [
	'blogname'                               => 'Watchlist',
	'blogdescription'                        => 'Time flies, so do we. Genuine luxury watches in Nairobi, paid on delivery.',
	'timezone_string'                        => 'Africa/Nairobi',
	'woocommerce_currency'                   => 'USD',
	'woocommerce_currency_pos'               => 'left',
	'woocommerce_price_num_decimals'         => '0',
	'woocommerce_price_thousand_sep'         => ',',
	'woocommerce_default_country'            => 'KE:KE30',
	'woocommerce_store_city'                 => 'Nairobi',
	'woocommerce_manage_stock'               => 'yes',
	'woocommerce_notify_low_stock_amount'    => '0',
	'woocommerce_notify_no_stock_amount'     => '0',
	'woocommerce_allowed_countries'          => 'specific',
	'woocommerce_specific_allowed_countries' => [ 'KE' ],
	'woocommerce_ship_to_countries'          => '',
	'woocommerce_enable_reviews'             => 'yes',
	'woocommerce_review_rating_verification_label' => 'yes',
	'woocommerce_onboarding_profile'         => [ 'skipped' => true ],
	'woocommerce_task_list_hidden'           => 'yes',
	'woocommerce_coming_soon'                => 'no',
	'woocommerce_checkout_phone_field'       => 'required',
	'woocommerce_enable_coupons'             => 'no',
] as $k => $v ) {
	update_option( $k, $v );
}

// Remove sample content.
foreach ( get_posts( [ 'post_type' => [ 'post', 'page' ], 'name' => 'hello-world', 'numberposts' => 1 ] ) as $p ) wp_delete_post( $p->ID, true );
$sample = get_page_by_path( 'sample-page' );
if ( $sample ) wp_delete_post( $sample->ID, true );

// Categories (menu order = order in the header and on the home page).
$cat = [];
$i   = 0;
foreach ( [
	'luxury-sports'  => [ 'Luxury Sports', 'Integrated-bracelet icons and precious-metal divers, built to be worn every day.' ],
	'dress-prestige' => [ 'Dress & Prestige', 'Gold and prestige pieces for the boardroom and the big occasions.' ],
	'chronographs'   => [ 'Chronographs', 'Stopwatch complications, from classic dress chronographs to aviation instruments.' ],
	'pilot-aviation' => [ 'Pilot & Aviation', 'Instrument-inspired watches with big, legible dials.' ],
] as $slug => [ $name, $desc ] ) {
	$t = term_exists( $slug, 'product_cat' ) ?: wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'description' => $desc ] );
	$cat[ $slug ] = (int) $t['term_id'];
	update_term_meta( $cat[ $slug ], 'order', $i++ );
}

// Availability tags, used by the "Ready in Nairobi" / "On order" filter chips.
$tag = [];
foreach ( [ 'ready' => [ 'ready-in-nairobi', 'Ready in Nairobi' ], 'order' => [ 'on-order', 'On order' ] ] as $key => [ $slug, $name ] ) {
	$t = term_exists( $slug, 'product_tag' ) ?: wp_insert_term( $name, 'product_tag', [ 'slug' => $slug ] );
	$tag[ $key ] = (int) $t['term_id'];
}

// Delivery. PLACEHOLDER terms, to confirm with the client.
$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'Nairobi' );
$zone->add_location( 'KE:KE30', 'state' );
$zone->save();
$id = $zone->add_shipping_method( 'free_shipping' );
update_option( "woocommerce_free_shipping_{$id}_settings", [ 'title' => 'Free Nairobi hand delivery – inspect before you pay', 'requires' => '', 'min_amount' => '0' ] );

$rest = new WC_Shipping_Zone();
$rest->set_zone_name( 'Rest of Kenya' );
$rest->add_location( 'KE', 'country' );
$rest->save();
$id = $rest->add_shipping_method( 'free_shipping' );
update_option( "woocommerce_free_shipping_{$id}_settings", [ 'title' => 'Countrywide delivery – we call to arrange', 'requires' => '', 'min_amount' => '0' ] );

// Payment: cash on delivery, as on the client's Instagram bio and WhatsApp catalogue.
update_option( 'woocommerce_cod_settings', [
	'enabled'            => 'yes',
	'title'              => 'Pay on delivery',
	'description'        => 'Inspect the watch in person, then pay when it is handed over. We will call to confirm the amount, how you will pay and a delivery time.',
	'instructions'       => 'We will call you to confirm your order and arrange delivery.',
	'enable_for_methods' => [],
	'enable_for_virtual' => 'yes',
] );

// Products: the genuine pieces the client listed on WhatsApp (0718 995 761), at the prices quoted there.
// avail: ready = "Ready in Nairobi", order = "On order", ask = not stated.
// Reference details not in their messages are the published specs for that reference.
$products = [
	[
		'slug' => 'patek-philippe-nautilus-5712g', 'name' => 'Patek Philippe Nautilus 5712G Moon Phase', 'cat' => 'luxury-sports', 'brand' => 'Patek Philippe', 'price' => 86000,
		'avail' => 'order', 'condition' => 'Like new', 'set' => 'Watch and box',
		'desc' => 'The Nautilus 5712G in 18k white gold: Gérald Genta\'s porthole case with a dial showing power reserve, date, moon phase and small seconds, on a black strap. Like new, supplied with its box. On order for delivery to Nairobi.',
		'specs' => [ 'Reference' => '5712G', 'Case' => '18k white gold, 40 mm', 'Movement' => 'Automatic, micro-rotor', 'Functions' => 'Power reserve, date, moon phase, small seconds', 'Strap' => 'Black leather, folding clasp', 'Glass' => 'Sapphire crystal, front and back', 'Water resistance' => '60 m', 'Condition' => 'Like new', 'Box & papers' => 'Watch and box', 'Availability' => 'On order' ],
	],
	[
		'slug' => 'rolex-day-date-40-olive-228235', 'name' => 'Rolex Day-Date 40 Everose Olive 228235', 'cat' => 'dress-prestige', 'brand' => 'Rolex', 'price' => 68000,
		'avail' => 'order', 'condition' => 'New', 'set' => 'Watch and warranty card',
		'desc' => 'The "President" in 18k Everose gold with the olive-green sunray dial and Roman numerals, fluted bezel and President bracelet. New, with a warranty card dated September 2026. On order for delivery to Nairobi.',
		'specs' => [ 'Reference' => '228235', 'Case' => '18k Everose gold, 40 mm', 'Dial' => 'Olive green, Roman numerals', 'Bezel' => 'Fluted, 18k Everose gold', 'Bracelet' => 'President, Crownclasp', 'Movement' => 'Automatic, calibre 3255', 'Glass' => 'Sapphire crystal', 'Water resistance' => '100 m', 'Condition' => 'New, card dated 01 Sept 2026', 'Availability' => 'On order' ],
	],
	[
		'slug' => 'rolex-air-king-126900', 'name' => 'Rolex Air-King 126900 (2024)', 'cat' => 'pilot-aviation', 'brand' => 'Rolex', 'price' => 10800,
		'avail' => 'ready', 'condition' => 'New', 'set' => 'Full set + boutique receipt',
		'desc' => 'Rolex\'s aviation watch: a 40 mm Oystersteel case with crown guards and the black dial with its large 3, 6 and 9 and five-minute scale. A 2024 piece, new, with the full set: box, warranty card and the Rolex boutique receipt. Ready in Nairobi.',
		'specs' => [ 'Reference' => '126900', 'Year' => '2024', 'Case' => 'Oystersteel, 40 mm', 'Dial' => 'Black with green and yellow Rolex logo', 'Bracelet' => 'Oyster, Oysterlock clasp', 'Movement' => 'Automatic, calibre 3230', 'Glass' => 'Sapphire crystal', 'Water resistance' => '100 m', 'Condition' => 'New', 'Box & papers' => 'Box, warranty card and Rolex boutique receipt', 'Availability' => 'Ready in Nairobi' ],
	],
	[
		'slug' => 'iwc-portofino-chronograph-iw391037', 'name' => 'IWC Portofino Chronograph IW391037', 'cat' => 'chronographs', 'brand' => 'IWC Schaffhausen', 'price' => 10640,
		'avail' => 'ask', 'condition' => 'New', 'set' => 'Full set',
		'desc' => 'IWC\'s elegant dress chronograph: a 42 mm stainless steel case, blued hands and the automatic calibre 79350. New, full set.',
		'specs' => [ 'Reference' => 'IW391037', 'Case' => 'Stainless steel, 42 mm', 'Movement' => 'Automatic, calibre 79350', 'Jewels' => '30', 'Frequency' => '28,800 vph', 'Strap' => 'Leather', 'Condition' => 'New', 'Box & papers' => 'Full set' ],
	],
	[
		'slug' => 'cartier-calibre-de-cartier-diver-w7100055', 'name' => 'Cartier Calibre de Cartier Diver W7100055', 'cat' => 'luxury-sports', 'brand' => 'Cartier', 'price' => 8700,
		'avail' => 'ready', 'condition' => 'New', 'set' => 'Watch and box',
		'desc' => 'The Calibre de Cartier Diver in stainless steel with an 18k rose gold bezel and crown, on a black rubber strap. Cartier discontinued the Calibre line around 2019–2020, so it is no longer sold new in boutiques. This piece is new, with its box. Ready in Nairobi.',
		'specs' => [ 'Reference' => 'W7100055', 'Case' => 'Stainless steel with 18k rose gold bezel and crown, 42 mm', 'Dial' => 'Black, Roman numerals, small seconds and date', 'Movement' => 'Automatic, Cartier calibre 1904 MC', 'Strap' => 'Black rubber, pin buckle', 'Glass' => 'Sapphire crystal', 'Water resistance' => '300 m', 'Condition' => 'New', 'Box & papers' => 'Watch and box', 'Availability' => 'Ready in Nairobi' ],
	],
	[
		'slug' => 'bell-ross-br-03-94-aviation-type', 'name' => 'Bell & Ross BR 03-94 Aviation Type Chronograph', 'cat' => 'chronographs', 'brand' => 'Bell & Ross', 'price' => 5000,
		'avail' => 'ready', 'condition' => 'Pre-owned', 'set' => 'Watch, card and pouch',
		'desc' => 'Bell & Ross\'s square "instrument" chronograph, modelled on a cockpit gauge: a blue sunray dial with luminous numerals, two sub-dials and a date window, on a black leather strap. Automatic, 100 m water resistant. Comes with its card and a carry pouch. Ready in Nairobi.',
		'specs' => [ 'Reference' => 'BR03-94-S', 'Case' => 'Stainless steel, 42 mm square', 'Dial' => 'Blue sunray, chronograph sub-dials, date', 'Movement' => 'Automatic', 'Strap' => 'Black leather', 'Water resistance' => '100 m', 'Box & papers' => 'Watch, card and carry pouch', 'Availability' => 'Ready in Nairobi' ],
	],
	[
		'slug' => 'bell-ross-br-s-steel', 'name' => 'Bell & Ross BR S Steel (BRS-64-S)', 'cat' => 'pilot-aviation', 'brand' => 'Bell & Ross', 'price' => 2700,
		'avail' => 'ready', 'condition' => 'Pre-owned', 'set' => 'Watch and travel pouch',
		'desc' => 'The smaller BR S Instruments watch: a 39 mm square steel case with a black dial, large Arabic numerals and small seconds at 6 o\'clock, on a black rubber strap. Pre-owned, with a travel pouch. Ready in Nairobi.',
		'specs' => [ 'Reference' => 'BRS-64-S', 'Case' => 'Stainless steel, 39 mm square', 'Dial' => 'Black, Arabic numerals, small seconds at 6', 'Strap' => 'Black rubber', 'Condition' => 'Pre-owned', 'Box & papers' => 'Watch and travel pouch', 'Availability' => 'Ready in Nairobi' ],
	],
];

function wl_attach_images( $pid, $slug, $name ) {
	$files = glob( "/wordpress/wl-images/$slug*.webp" ) ?: [];
	$files = array_values( array_filter( $files, fn( $f ) => preg_match( '#/' . preg_quote( $slug, '#' ) . '(-\d+)?\.webp$#', $f ) ) );
	// slug.webp is the main photo, then slug-2.webp, slug-3.webp…
	$num = fn( $f ) => preg_match( '#-(\d+)\.webp$#', substr( $f, strlen( $slug ) ), $m ) ? (int) $m[1] : 1;
	usort( $files, fn( $a, $b ) => $num( basename( $a ) ) <=> $num( basename( $b ) ) );
	$ids = [];
	foreach ( $files as $i => $file ) {
		$base = basename( $file );
		$tmp  = wp_tempnam( $base );
		copy( $file, $tmp );
		$att = media_handle_sideload( [ 'name' => $base, 'tmp_name' => $tmp ], $pid, $name . ( $i ? ' – photo ' . ( $i + 1 ) : '' ) );
		if ( ! is_wp_error( $att ) ) $ids[] = $att;
	}
	return $ids;
}

foreach ( $products as $order => $d ) {
	$p = new WC_Product_Simple();
	$p->set_name( $d['name'] );
	$p->set_slug( $d['slug'] );
	$p->set_status( 'publish' );
	$p->set_menu_order( $order );
	$p->set_description( $d['desc'] );
	$p->set_short_description( $d['desc'] );
	$p->set_category_ids( [ $cat[ $d['cat'] ] ] );
	$p->set_featured( true );
	$p->set_regular_price( $d['price'] );
	// One of each piece. Pieces on order can still be reserved (backorder).
	$p->set_manage_stock( true );
	$p->set_sold_individually( true );
	$p->set_stock_quantity( $d['avail'] === 'order' ? 0 : 1 );
	$p->set_backorders( $d['avail'] === 'order' ? 'notify' : 'no' );
	if ( isset( $tag[ $d['avail'] ] ) ) {
		$p->set_tag_ids( [ $tag[ $d['avail'] ] ] );
	}
	$p->update_meta_data( '_wl_condition', $d['condition'] );
	$p->update_meta_data( '_wl_set', $d['set'] );
	$p->update_meta_data( '_wl_specs', implode( "\n", array_map( fn( $k, $v ) => "$k: $v", array_keys( $d['specs'] ), $d['specs'] ) ) );
	$pid = $p->save();

	if ( $d['brand'] && taxonomy_exists( 'product_brand' ) ) {
		wp_set_object_terms( $pid, $d['brand'], 'product_brand' );
	}

	$imgs = wl_attach_images( $pid, $d['slug'], $d['name'] );
	if ( $imgs ) {
		set_post_thumbnail( $pid, array_shift( $imgs ) );
		if ( $imgs ) {
			$p = wc_get_product( $pid );
			$p->set_gallery_image_ids( $imgs );
			$p->save();
		}
	}
}

// Pages.
function wl_page( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) return $existing->ID;
	return wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ] );
}
wl_page( 'wishlist', 'Your watchlist', "<!-- wp:shortcode -->\n[wl_wishlist]\n<!-- /wp:shortcode -->" );
$shop_page = (int) wc_get_page_id( 'shop' );
if ( $shop_page > 0 ) wp_update_post( [ 'ID' => $shop_page, 'post_title' => 'All watches' ] );

update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

// Skip WooCommerce's first-run redirect and setup checklist so the admin opens on the store itself.
delete_transient( '_wc_activation_redirect' );
update_option( 'woocommerce_task_list_hidden_lists', [ 'setup', 'extended' ] );
update_option( 'woocommerce_task_list_complete', 'yes' );
update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
update_option( 'woocommerce_admin_install_timestamp', time() - WEEK_IN_SECONDS );

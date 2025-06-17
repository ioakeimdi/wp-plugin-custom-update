<?php
/*
	Plugin Name: Timeless - Custom update
	Description: Custom update for timeless plugins
	Version: 1.0.0
	Author: Timeless Software P.C.
    Author URI: https://timeless.gr/
	Text Domain: wp-plugin-custom-update
	Domain Path: /languages
*/
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Plugin_Custom_Update' ) ) {
	return;
}

require_once plugin_dir_path( __FILE__ ) . 'class-wp-plugin-custom-update.php';

add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'wp-plugin-custom-update', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});


// Use
/* $plugin_names = array(
	'custom-plugin-name'   => 'http://example.com/info.json',
	'custom-plugin-name-2' => 'http://example.com/info-2.json',
);

foreach ( $plugin_names as $plugin_name => $json_url ) {
	new WP_Plugin_Custom_Update(
		"{$plugin_name}/{$plugin_name}.php",
		$json_url,
	);
} */
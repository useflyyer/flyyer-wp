<?php
/**
 * Plugin Name: FLAYYER Previews
 * Version: 1.0.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: flayyer-previews
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-flayyer-previews.php';
require_once 'includes/class-flayyer-previews-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-flayyer-previews-admin-api.php';
require_once 'includes/lib/class-flayyer-previews-post-type.php';
require_once 'includes/lib/class-flayyer-previews-taxonomy.php';

/**
 * Returns the main instance of FLAYYER_Previews to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object FLAYYER_Previews
 */
function flayyer_previews() {
	$instance = FLAYYER_Previews::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = FLAYYER_Previews_Settings::instance( $instance );
	}

	return $instance;
}

flayyer_previews();

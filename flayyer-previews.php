<?php

/**
 * Plugin Name: FLAYYER Previews
 * Version: 1.0.0
 * Plugin URI: https://flayyer.com/
 * Description: Generate dynamic and meaningful previews for your links. Use FLAYYER smart urls to pass values to our image-rendering service and make each URL's preview unique.
 * Author: FLAYYER.com
 * Author URI: https://flayyer.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: flayyer-previews
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Patricio Lopez
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

// Load plugin class files.
require_once 'includes/class-flayyer-previews.php';
require_once 'includes/class-flayyer-previews-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-flayyer-previews-admin-api.php';
require_once 'includes/lib/class-flayyer-previews-post-type.php';
require_once 'includes/lib/class-flayyer-previews-taxonomy.php';

// TODO: is autoloading not working?
require_once 'vendor/flayyer/flayyer/src/Flayyer.php';

/**
 * Returns the main instance of FLAYYER_Previews to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object FLAYYER_Previews
 */
function flayyer_previews(): FLAYYER_Previews // TODO: Remove this hint if necessary
{
  $instance = FLAYYER_Previews::instance(__FILE__, '1.0.0');

  if (is_null($instance->settings)) {
    $instance->settings = FLAYYER_Previews_Settings::instance($instance);
  }

  return $instance;
}

flayyer_previews(); // force init

add_filter('wpseo_opengraph_image', 'change_image');
function change_image($image)
{
  $tenant = get_option('flayyer_default_tenant');
  $deck = get_option('flayyer_default_deck');
  $template = get_option('flayyer_default_template');
  $version = get_option('flayyer_default_version');
  $extension = get_option('flayyer_default_extension');
  $flayyer = new Flayyer($tenant, $deck, $template, $version, $extension);
  try {
    return $flayyer->href();
  } catch (Exception $e) {
    return $image;
  }
}

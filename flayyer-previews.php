<?php

/**
 * Plugin Name: Flayyer Previews
 * Version: 1.1.6
 * Plugin URI: https://flayyer.com/
 * Description: FlayyerAI is the platform for your social media images. Generate images from your website's content and fit for every social platform format (no effort required).
 * Author: FLAYYER.com
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
require_once 'vendor/flayyer/flayyer/src/FlayyerAI.php';

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

function remove_default_image_presenters($presenters)
{
  return array_map(function ($presenter) {
    if ($presenter instanceof Yoast\WP\SEO\Presenters\Open_Graph\Image_Presenter) {
      return null;
    } else if ($presenter instanceof Yoast\WP\SEO\Presenters\Twitter\Image_Presenter) {
      return null;
    }
    return $presenter;
  }, $presenters);
}
add_action('wpseo_frontend_presenters', 'remove_default_image_presenters');

/**
 * Adds our custom presenter to the array of presenters.
 * https://developer.yoast.com/customization/apis/metadata-api/
 *
 * @param array $presenters The current array of presenters.
 *
 * @return array Presenters with our custom presenter added.
 */
function add_flayyer_presenter($presenters)
{
  if (\class_exists('FlayyerPresenter')) {
    // OK
  } else {
    /**
     * Based on Yoast\WP\SEO\Presenters\Open_Graph\Image_Presenter
     */
    class FlayyerPresenter extends Yoast\WP\SEO\Presenters\Abstract_Indexable_Presenter
    {
      public function present()
      {
        $flayyer = new FlayyerAI(get_option('flayyer_project_slug'), esc_url($_SERVER['REQUEST_URI']));
        // if (get_option('flayyer_secret_key') && get_option('flayyer_strategy') != "None") {
        //   $flayyer->secret = get_option('flayyer_secret_key');
        //   $flayyer->strategy = get_option('flayyer_strategy');
        // }

        $return = \PHP_EOL . "\t" . '<meta property="og:image" content="' . $flayyer->href() . '" />';
        $return .= \PHP_EOL . "\t" . '<meta property="twitter:image" content="' . $flayyer->href() . '" />';

        $is_landing = \is_front_page() || \is_home();
        $is_collection = \is_category();
        $is_page = \is_page() || \is_privacy_policy();
        $is_article = \is_single() || \is_author();

        if ($is_landing) {
          $return .= \PHP_EOL . "\t" . '<meta property="flayyer:type" content="landing" />';
        } else if ($is_collection) {
          $return .= \PHP_EOL . "\t" . '<meta property="flayyer:type" content="collection" />';
        } else if ($is_page) {
          $return .= \PHP_EOL . "\t" . '<meta property="flayyer:type" content="page" />';
        } else if ($is_article) {
          $return .= \PHP_EOL . "\t" . '<meta property="flayyer:type" content="article" />';
        }

        return $return;
      }

      public function get()
      {
      }
    }
  }

  $presenters[] = new FlayyerPresenter();
  return $presenters;
}
add_filter('wpseo_frontend_presenters', 'add_flayyer_presenter');

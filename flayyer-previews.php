<?php

/**
 * Plugin Name: FLAYYER Previews
  * Version: 1.0.5
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
      protected static $image_tags = [
        'width'     => 'width',
        'height'    => 'height',
        'mime-type' => 'type',
      ];

      public function present()
      {
        $images = $this->get();
        $return = '';
        foreach ($images as $image_index => $image_meta) {
          $image_url = $image_meta['url'];

          $return .= '<meta property="og:image" content="' . \esc_url($image_url) . '" />';
          $return .= '<meta name="twitter:image" content="' . \esc_url($image_url) . '" />';
          foreach (static::$image_tags as $key => $value) {
            if (empty($image_meta[$key])) {
              continue;
            }
            $return .= \PHP_EOL . "\t" . '<meta property="og:image:' . \esc_attr($value) . '" content="' . $image_meta[$key] . '" />';
            $return .= \PHP_EOL . "\t" . '<meta name="twitter:image:' . \esc_attr($value) . '" content="' . $image_meta[$key] . '" />';
          }
        }
        return $return;
      }

      public function get()
      {
        $images = [];
        foreach ($this->presentation->open_graph_images as $open_graph_image) {
          $images[] = $this->filter($open_graph_image);
        }
        $images = \array_filter($images);

        $take_url = function ($image) {
          return array('url' => $image['url']);
        };

        $tenant = get_option('flayyer_default_tenant');
        $deck = get_option('flayyer_default_deck');
        $template = get_option('flayyer_default_template');
        $version = get_option('flayyer_default_version');
        $extension = get_option('flayyer_default_extension');
        $variables_global = json_decode(get_option('flayyer_default_variables'), true);
        $variables = $variables_global ? $variables_global : [];

        $is_home = \is_front_page() && \is_home();
        $is_category = \is_category();
        $is_page = \is_page();
        $is_post = \is_single();
        $is_author = \is_author();

        if ($is_post) {
          // variable $post exists
          if (get_option('flayyer_default_post_template')) {
            $template = get_option('flayyer_default_post_template');
          }

          $post = \get_post();
          $author_id = $post->post_author;

          $author_variable = array(
            'first_name' => get_the_author_meta('first_name', $author_id),
            'last_name' => get_the_author_meta('last_name', $author_id),
            'display_name' => get_the_author_meta('display_name', $author_id),
            'nickname' => get_the_author_meta('nickname', $author_id),
            'avatar' => get_avatar_url($author_id, array('size' => 512)),
          );

          $variables_local = array(
            'title' => \get_the_title(),
            'description' => $this->helpers->post->get_the_excerpt(null),
            'images' => array_map($take_url, $images),
            'author' => $author_variable,
          );
          $variables = array_merge($variables_local, $variables);
        } else if ($is_author) {
          // variable $author exists
          if (get_option('flayyer_default_author_template')) {
            $template = get_option('flayyer_default_author_template');
          }

          // https://codex.wordpress.org/Author_Templates
          $author = (isset($_GET['author_name'])) ? get_user_by('slug', $_GET['author_name']) : get_userdata($_GET['author']);

          $author_variable = array(
            'first_name' => get_the_author_meta('first_name', $author),
            'last_name' => get_the_author_meta('last_name', $author),
            'display_name' => get_the_author_meta('display_name', $author),
            'nickname' => get_the_author_meta('nickname', $author),
            'avatar' => get_avatar_url($author),
          );

          $variables_local = $author_variable;
          $variables = array_merge($variables_local, $variables);
        } else if ($is_category) {
          // variable $category exists
          if (get_option('flayyer_default_category_template')) {
            $template = get_option('flayyer_default_category_template');
          }
          $variables_local = array(
            'title' => $this->replace_vars("%%term_title%%"),
            'description' => $this->replace_vars("%%term_description%%") ?? $this->replace_vars("%%category_description%%"),
            'images' => array_map($take_url, $images),
          );
          $variables = array_merge($variables_local, $variables);
        } else if ($is_page) {
          if (get_option('flayyer_default_page_template')) {
            $template = get_option('flayyer_default_page_template');
          }
          $variables_local = array(
            'title' => \get_the_title(),
            'description' => $this->helpers->post->get_the_excerpt(null),
            'images' => array_map($take_url, $images),
          );
          $variables = array_merge($variables_local, $variables);
        } else if ($is_home) {
          // Keep 'home' template
        }

        $flayyer = new Flayyer($tenant, $deck, $template, $version, $extension, $variables);
        try {
          $url = $flayyer->href();
          $formatted = array(
            'url' => $url,
            // 'width' => 1200,
            // 'height' => 630,
            'mime-type' => "image/{$extension}",
          );
          return array($formatted);
        } catch (Exception $e) {
          return $images;
        }
      }

      protected function filter($image)
      {
        $image_url = \trim(\apply_filters('wpseo_opengraph_image', $image['url'], $this->presentation));
        if (!empty($image_url) && \is_string($image_url)) {
          $image['url'] = $image_url;
        }
        return $image;
      }
    }
  }

  $presenters[] = new FlayyerPresenter();
  return $presenters;
}
add_filter('wpseo_frontend_presenters', 'add_flayyer_presenter');

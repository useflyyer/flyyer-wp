<?php

/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Settings class.
 */
class FLAYYER_Previews_Settings
{

  /**
   * The single instance of FLAYYER_Previews_Settings.
   *
   * @var     object
   * @access  private
   * @since   1.0.0
   */
  private static $_instance = null; //phpcs:ignore

  /**
   * The main plugin object.
   *
   * @var     object
   * @access  public
   * @since   1.0.0
   */
  public $parent = null;

  /**
   * Prefix for plugin settings.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $base = '';

  /**
   * Available settings for plugin.
   *
   * @var     array
   * @access  public
   * @since   1.0.0
   */
  public $settings = array();

  /**
   * Constructor function.
   *
   * @param object $parent Parent object.
   */
  public function __construct($parent)
  {
    $this->parent = $parent;

    $this->base = 'flayyer_';

    // Initialise settings.
    add_action('init', array($this, 'init_settings'), 11);

    // Register plugin settings.
    add_action('admin_init', array($this, 'register_settings'));

    // Add settings page to menu.
    add_action('admin_menu', array($this, 'add_menu_item'));

    // Add settings link to plugins page.
    add_filter(
      'plugin_action_links_' . plugin_basename($this->parent->file),
      array(
        $this,
        'add_settings_link',
      )
    );

    // Configure placement of plugin settings page. See readme for implementation.
    add_filter($this->base . 'menu_settings', array($this, 'configure_settings'));
  }

  /**
   * Initialise settings
   *
   * @return void
   */
  public function init_settings()
  {
    $this->settings = $this->settings_fields();
  }

  /**
   * Add settings page to admin menu
   *
   * @return void
   */
  public function add_menu_item()
  {

    $args = $this->menu_settings();

    // Do nothing if wrong location key is set.
    if (is_array($args) && isset($args['location']) && function_exists('add_' . $args['location'] . '_page')) {
      switch ($args['location']) {
        case 'options':
        case 'submenu':
          $page = add_submenu_page($args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function']);
          break;
        case 'menu':
          $page = add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position']);
          break;
        default:
          return;
      }
      add_action('admin_print_styles-' . $page, array($this, 'settings_assets'));
    }
  }

  /**
   * Prepare default settings page arguments
   *
   * @return mixed|void
   */
  private function menu_settings()
  {
    # HEY - Side-menu settings
    return apply_filters(
      $this->base . 'menu_settings',
      array(
        'location'    => 'menu', // Possible settings: options, menu, submenu.
        'parent_slug' => 'options-general.php',
        'page_title'  => __('FLAYYER', 'flayyer-previews'),
        'menu_title'  => __('FLAYYER', 'flayyer-previews'),
        'capability'  => 'manage_options',
        'menu_slug'   => $this->parent->_token . '_settings',
        'function'    => array($this, 'settings_page'),
        'icon_url'    => 'dashicons-slides', // https://developer.wordpress.org/resource/dashicons/#slides
        // 'icon_url'    => plugins_url( 'flayyer-previews/assets/flayyer-logo.png' ),
        'position'    => null,
      )
    );
  }

  /**
   * Container for settings page arguments
   *
   * @param array $settings Settings array.
   *
   * @return array
   */
  public function configure_settings($settings = array())
  {
    return $settings;
  }

  /**
   * Load settings JS & CSS
   *
   * @return void
   */
  public function settings_assets()
  {

    // We're including the farbtastic script & styles here because they're needed for the colour picker
    // If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
    wp_enqueue_style('farbtastic');
    wp_enqueue_script('farbtastic');

    // We're including the WP media scripts here because they're needed for the image upload field.
    // If you're not including an image upload then you can leave this function call out.
    wp_enqueue_media();

    wp_register_script($this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array('farbtastic', 'jquery'), '1.0.0', true);
    wp_enqueue_script($this->parent->_token . '-settings-js');
  }

  /**
   * Add settings link to plugin list table
   *
   * @param  array $links Existing links.
   * @return array        Modified links.
   */
  public function add_settings_link($links)
  {
    $settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __('Settings', 'flayyer-previews') . '</a>';
    array_push($links, $settings_link);
    return $links;
  }

  /**
   * Build settings fields
   *
   * @return array Fields to be displayed on settings page
   */
  private function settings_fields()
  {

    $settings['general'] = array(
      'title'       => __('FLAYYER general settings', 'flayyer-previews'),
      'description' => __('General configuration. Need help? Contact: <a href="mailto:help@flayyer.com">help@flayyer.com</a>', 'flayyer-previews'),
      'fields'      => array(
        array(
          'id'          => 'default_tenant',
          'label'       => __('Tenant/company', 'flayyer-previews'),
          'description' => __('This is the name of your company', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => '',
          'placeholder' => __('my-company', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_deck',
          'label'       => __('Deck', 'flayyer-previews'),
          'description' => __('The folder that contains all of your templates', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => '',
          'placeholder' => __('my-deck', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_template',
          'label'       => __('🌐 Template (default)', 'flayyer-previews'),
          'description' => __('Main template for your root path and the default/fallback template for other types.', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => 'main',
          'placeholder' => __('main', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_post_template',
          'label'       => __('✏️ Posts template', 'flayyer-previews'),
          'description' => __('Overrides "main" template. Each post will use this template to generate its own preview.', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => 'post',
          'placeholder' => __('post', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_author_template',
          'label'       => __('😃 Author/Profiles template', 'flayyer-previews'),
          'description' => __('Overrides "main" template. Each author/profile will use this template to generate its own preview.', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => 'author',
          'placeholder' => __('author', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_page_template',
          'label'       => __('📃 Pages template', 'flayyer-previews'),
          'description' => __('Overrides "main" template. Each page will use this template to generate its own preview.', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => 'page',
          'placeholder' => __('page', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_category_template',
          'label'       => __('🏷 Categories template', 'flayyer-previews'),
          'description' => __('Overrides "main" template. Each category/tag will use this template to generate its own preview.', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => 'category',
          'placeholder' => __('category', 'flayyer-previews'),
        ),

        array(
          'id'          => 'default_variables',
          'label'       => __('Variables (default)', 'flayyer-previews'),
          'description' => __('Advanced | This value is optional, but please enter JSON if you set it up.', 'flayyer-previews'),
          'type'        => 'textarea',
          'default'     => '',
          'placeholder' => __('{}', 'flayyer-previews'),
        ),
        array(
          'id'          => 'default_version',
          'label'       => __('Version', 'flayyer-previews'),
          'description' => __('Use "_" to always grab the latest version.', 'flayyer-previews'),
          'type'        => 'text',
          'default'     => '_',
          'placeholder' => __('_', 'flayyer-previews'),
        ),

        array(
          'id'          => 'default_extension',
          'label'       => __('Extension', 'flayyer-previews'),
          'description' => __('Use PNG for vector based and JPEG for templates with photos.', 'flayyer-previews'),
          'type'        => 'select',
          'options'     => array(
            'jpeg'    => 'JPEG',
            'png'    => 'PNG',
          ),
          'default'     => 'jpeg',
        ),
      ),
    );

    $settings = apply_filters($this->parent->_token . '_settings_fields', $settings);

    return $settings;
  }

  /**
   * Register plugin settings
   *
   * @return void
   */
  public function register_settings()
  {
    if (is_array($this->settings)) {

      // Check posted/selected tab.
      //phpcs:disable
      $current_section = '';
      if (isset($_POST['tab']) && $_POST['tab']) {
        $current_section = $_POST['tab'];
      } else {
        if (isset($_GET['tab']) && $_GET['tab']) {
          $current_section = $_GET['tab'];
        }
      }
      //phpcs:enable

      foreach ($this->settings as $section => $data) {

        if ($current_section && $current_section !== $section) {
          continue;
        }

        // Add section to page.
        add_settings_section($section, $data['title'], array($this, 'settings_section'), $this->parent->_token . '_settings');

        foreach ($data['fields'] as $field) {

          // Validation callback for field.
          $validation = '';
          if (isset($field['callback'])) {
            $validation = $field['callback'];
          }

          // Register field.
          $option_name = $this->base . $field['id'];
          register_setting($this->parent->_token . '_settings', $option_name, $validation);

          // Add field to page.
          add_settings_field(
            $field['id'],
            $field['label'],
            array($this->parent->admin, 'display_field'),
            $this->parent->_token . '_settings',
            $section,
            array(
              'field'  => $field,
              'prefix' => $this->base,
            )
          );
        }

        if (!$current_section) {
          break;
        }
      }
    }
  }

  /**
   * Settings section.
   *
   * @param array $section Array of section ids.
   * @return void
   */
  public function settings_section($section)
  {
    $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
    echo $html; //phpcs:ignore
  }

  /**
   * Load settings page content.
   *
   * @return void
   */
  public function settings_page()
  {

    // Build page HTML.
    $html      = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
    $html .= '<h2>' . __('Plugin Settings', 'flayyer-previews') . '</h2>' . "\n";

    $tab = '';
    //phpcs:disable
    if (isset($_GET['tab']) && $_GET['tab']) {
      $tab .= $_GET['tab'];
    }
    //phpcs:enable

    // Show page tabs.
    if (is_array($this->settings) && 1 < count($this->settings)) {

      $html .= '<h2 class="nav-tab-wrapper">' . "\n";

      $c = 0;
      foreach ($this->settings as $section => $data) {

        // Set tab class.
        $class = 'nav-tab';
        if (!isset($_GET['tab'])) { //phpcs:ignore
          if (0 === $c) {
            $class .= ' nav-tab-active';
          }
        } else {
          if (isset($_GET['tab']) && $section == $_GET['tab']) { //phpcs:ignore
            $class .= ' nav-tab-active';
          }
        }

        // Set tab link.
        $tab_link = add_query_arg(array('tab' => $section));
        if (isset($_GET['settings-updated'])) { //phpcs:ignore
          $tab_link = remove_query_arg('settings-updated', $tab_link);
        }

        // Output tab.
        $html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>' . "\n";

        ++$c;
      }

      $html .= '</h2>' . "\n";
    }

    $html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

    // Get settings fields.
    ob_start();
    settings_fields($this->parent->_token . '_settings');
    do_settings_sections($this->parent->_token . '_settings');
    $html .= ob_get_clean();

    $html     .= '<p class="submit">' . "\n";
    $html .= '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />' . "\n";
    $html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr(__('Save Settings', 'flayyer-previews')) . '" />' . "\n";
    $html     .= '</p>' . "\n";
    $html         .= '</form>' . "\n";
    $html             .= '</div>' . "\n";

    echo $html; //phpcs:ignore
  }

  /**
   * Main FLAYYER_Previews_Settings Instance
   *
   * Ensures only one instance of FLAYYER_Previews_Settings is loaded or can be loaded.
   *
   * @since 1.0.0
   * @static
   * @see FLAYYER_Previews()
   * @param object $parent Object instance.
   * @return object FLAYYER_Previews_Settings instance
   */
  public static function instance($parent)
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self($parent);
    }
    return self::$_instance;
  } // End instance()

  /**
   * Cloning is forbidden.
   *
   * @since 1.0.0
   */
  public function __clone()
  {
    _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of FLAYYER_Previews_API is forbidden.')), esc_attr($this->parent->_version));
  } // End __clone()

  /**
   * Unserializing instances of this class is forbidden.
   *
   * @since 1.0.0
   */
  public function __wakeup()
  {
    _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of FLAYYER_Previews_API is forbidden.')), esc_attr($this->parent->_version));
  } // End __wakeup()

}

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
class FLYYER_Previews_Settings
{

  /**
   * The single instance of FLYYER_Previews_Settings.
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

    $this->base = 'flyyer_';

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
        'page_title'  => __('FLYYER', 'flyyer-previews'),
        'menu_title'  => __('FLYYER', 'flyyer-previews'),
        'capability'  => 'manage_options',
        'menu_slug'   => $this->parent->_token . '_settings',
        'function'    => array($this, 'settings_page'),
        'icon_url'    => 'dashicons-slides', // https://developer.wordpress.org/resource/dashicons/#slides
        // 'icon_url'    => plugins_url( 'flyyer-previews/assets/flyyer-logo.png' ),
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
    $settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __('Settings', 'flyyer-previews') . '</a>';
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
    $home_url = home_url('');
    $project_default = str_replace(".", "-", str_replace("www.", "", substr($home_url, strpos($home_url, "://") + 3)));
    $settings['general'] = array(
      'title'       => __('FLYYER general settings', 'flyyer-previews'),
      'description' => __(<<<EOT
        <p>
          Flyyer is the platform for your link previews and social media images.
        </p>
        <p>
          They are automatically enriched from your website's content and fit for every social platform format (no effort required).
        </p>
        <p>
          Choose the template you like the most or just leave our AI system do the work for you.
        </p>
        <p>
          Find your <code>project-slug</code> in <a href="https://flyyer.io/auth/login?ref=wp-plugin" target="_blank" rel="noreferrer">your dashboard</a>. If you don't have a project yet, <a href="https://flyyer.io/get-started?ref=wp-plugin" target="_blank" rel="noreferrer">create one here</a>.
        </p>
        <p>
          Read the full integration guide <a href="https://docs.flyyer.io/guides/php/wordpress?ref=wp-plugin" target="_blank" rel="noreferrer">here</a>.
        </p>
      EOT, 'flyyer-previews'),
      'fields'      => array(
        array(
          'id'          => 'project_slug',
          'label'       => __('Project slug', 'flyyer-previews'),
          'description' => __('This is your project slug, find it in your dashboard (it defaults to your domain name). Your dashboard: https://flyyer.io/auth/login?ref=wp-plugin', 'flyyer-previews'),
          'type'        => 'text',
          'default'     => $project_default,
          'placeholder' => __('your-project-slug', 'flyyer-previews'),
        ),
        // array(
        //   'id'          => 'secret_key',
        //   'label'       => __('[Optional] Secret key', 'flyyer-previews'),
        //   'description' => __('For Signed URLs. Find it in Your dashboard > your-project > Advanced settings > Signed URLs.', 'flyyer-previews'),
        //   'type'        => 'password',
        //   'default'     => '',
        //   'placeholder' => '',
        //   'autocomplete' => 'off',
        // ),
        // array(
        //   'id'          => 'strategy',
        //   'label'       => __('[Optional] Signing method', 'flyyer-previews'),
        //   'description' => __('Define your signature strategy. You must have provided a secret key.', 'flyyer-previews'),
        //   'type'        => 'select',
        //   'options'     => array(
        //     'None'    => 'None',
        //     'HMAC'    => 'HMAC',
        //     // 'JWT'    => 'JWT', // TODO: JWT is failing
        //   ),
        //   'default'     => 'None',
        // ),
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
      if (isset($_POST['tab'])) {
        $current_section = sanitize_text_field($_POST['tab']);
      } else {
        if (isset($_GET['tab'])) {
          $current_section = sanitize_text_field($_GET['tab']);
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
    $html .= '<h2>' . __('Plugin Settings', 'flyyer-previews') . '</h2>' . "\n";

    $tab = '';
    //phpcs:disable
    if (isset($_GET['tab']) && $_GET['tab']) {
      $tab .= sanitize_text_field($_GET['tab']);
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
    $html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr(__('Save Settings', 'flyyer-previews')) . '" />' . "\n";
    $html     .= '</p>' . "\n";
    $html         .= '</form>' . "\n";
    $html             .= '</div>' . "\n";

    echo $html; //phpcs:ignore
  }

  /**
   * Main FLYYER_Previews_Settings Instance
   *
   * Ensures only one instance of FLYYER_Previews_Settings is loaded or can be loaded.
   *
   * @since 1.0.0
   * @static
   * @see FLYYER_Previews()
   * @param object $parent Object instance.
   * @return object FLYYER_Previews_Settings instance
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
    _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of FLYYER_Previews_API is forbidden.')), esc_attr($this->parent->_version));
  } // End __clone()

  /**
   * Unserializing instances of this class is forbidden.
   *
   * @since 1.0.0
   */
  public function __wakeup()
  {
    _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of FLYYER_Previews_API is forbidden.')), esc_attr($this->parent->_version));
  } // End __wakeup()

}

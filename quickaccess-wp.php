<?php
/**
 * QuickAccess WP
 *
 * @package           QuickAccessWP
 * @author            Razu Ahammad
 * @copyright         2025 Razu Ahammad
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       QuickAccess WP
 * Plugin URI:        https://github.com/razuahammad55/quickaccess-wp
 * Description:       Create custom URL slugs for automatic user login with advanced security features. Perfect for client portals, preview links, and secure sharing.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Razu Ahammad
 * Author URI:        https://github.com/razuahammad55
 * Text Domain:       quickaccess-wp
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/razuahammad55/quickaccess-wp
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'QAW_VERSION', '1.0.0' );
define( 'QAW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QAW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QAW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'QAW_PLUGIN_FILE', __FILE__ );

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class QuickAccess_WP {

    /**
     * Single instance of the class
     *
     * @var QuickAccess_WP|null
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @since 1.0.0
     * @return QuickAccess_WP
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_hooks();
    }

    /**
     * Prevent cloning
     *
     * @since 1.0.0
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    /**
     * Load required files
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-activator.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-database.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-security.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-frontend.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-admin.php';
    }

    /**
     * Set up hooks
     *
     * @since 1.0.0
     */
    private function set_hooks() {
        // Activation and deactivation
        register_activation_hook( QAW_PLUGIN_FILE, array( 'QAW_Activator', 'activate' ) );
        register_deactivation_hook( QAW_PLUGIN_FILE, array( 'QAW_Activator', 'deactivate' ) );

        // Initialize components
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        
        // Add settings link on plugins page
        add_filter( 'plugin_action_links_' . QAW_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Initialize plugin components
     *
     * @since 1.0.0
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain(
            'quickaccess-wp',
            false,
            dirname( QAW_PLUGIN_BASENAME ) . '/languages'
        );

        // Initialize classes
        new QAW_Frontend();
        
        if ( is_admin() ) {
            new QAW_Admin();
        }
    }

    /**
     * Add settings link to plugins page
     *
     * @since 1.0.0
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function add_settings_link( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=quickaccess-wp' ),
            __( 'Settings', 'quickaccess-wp' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }
}

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return QuickAccess_WP
 */
function quickaccess_wp() {
    return QuickAccess_WP::get_instance();
}

// Start the plugin
quickaccess_wp();

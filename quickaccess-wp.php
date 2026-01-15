<?php
/**
 * Plugin Name: QuickAccess WP
 * Plugin URI: https://github.com/razuahammad55/quickaccess-wp
 * Description: Create custom URL slugs for automatic user login with advanced security features. Perfect for client portals, preview links, and secure sharing.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Razu Ahammad
 * Author URI: https://github.com/razuahammad55
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quickaccess-wp
 * Domain Path: /languages
 *
 * @package QuickAccessWP
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
     * Single instance
     *
     * @var QuickAccess_WP|null
     */
    private static $instance = null;

    /**
     * Get instance
     *
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
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-activator.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-database.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-security.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-frontend.php';
        require_once QAW_PLUGIN_DIR . 'includes/class-qaw-admin.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook( QAW_PLUGIN_FILE, array( 'QAW_Activator', 'activate' ) );
        register_deactivation_hook( QAW_PLUGIN_FILE, array( 'QAW_Activator', 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_filter( 'plugin_action_links_' . QAW_PLUGIN_BASENAME, array( $this, 'plugin_links' ) );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain( 'quickaccess-wp', false, dirname( QAW_PLUGIN_BASENAME ) . '/languages' );

        new QAW_Frontend();

        if ( is_admin() ) {
            new QAW_Admin();
        }
    }

    /**
     * Plugin action links
     *
     * @param array $links Existing links.
     * @return array
     */
    public function plugin_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=quickaccess-wp' ) . '">' . __( 'Manage Links', 'quickaccess-wp' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=qaw-settings' ) . '">' . __( 'Settings', 'quickaccess-wp' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }
}

/**
 * Initialize plugin
 *
 * @return QuickAccess_WP
 */
function quickaccess_wp() {
    return QuickAccess_WP::get_instance();
}

quickaccess_wp();

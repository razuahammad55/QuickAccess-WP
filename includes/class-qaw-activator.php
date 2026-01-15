<?php
/**
 * Plugin Activator
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Activator Class
 */
class QAW_Activator {

    /**
     * Activate plugin
     */
    public static function activate() {
        self::check_requirements();
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'qaw_daily_cleanup' );
        flush_rewrite_rules();
    }

    /**
     * Check requirements
     */
    private static function check_requirements() {
        if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
            deactivate_plugins( QAW_PLUGIN_BASENAME );
            wp_die( __( 'QuickAccess WP requires WordPress 6.0 or higher.', 'quickaccess-wp' ) );
        }

        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( QAW_PLUGIN_BASENAME );
            wp_die( __( 'QuickAccess WP requires PHP 7.4 or higher.', 'quickaccess-wp' ) );
        }
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_slugs = "CREATE TABLE {$wpdb->prefix}qaw_slugs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(255) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            redirect_url varchar(500) DEFAULT '',
            max_uses int(11) DEFAULT 0,
            current_uses int(11) DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY user_id (user_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        $sql_logs = "CREATE TABLE {$wpdb->prefix}qaw_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug_id bigint(20) unsigned NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            status varchar(20) NOT NULL,
            message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slug_id (slug_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        $sql_rate = "CREATE TABLE {$wpdb->prefix}qaw_rate_limits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            attempts int(11) DEFAULT 1,
            first_attempt datetime DEFAULT CURRENT_TIMESTAMP,
            blocked_until datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY ip_address (ip_address)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_slugs );
        dbDelta( $sql_logs );
        dbDelta( $sql_rate );
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'qaw_rate_limit_attempts'  => 5,
            'qaw_rate_limit_window'    => 15,
            'qaw_block_duration'       => 60,
            'qaw_default_redirect'     => home_url(),
            'qaw_invalid_slug_message' => __( 'This access link is invalid or has expired.', 'quickaccess-wp' ),
            'qaw_enable_logging'       => 1,
            'qaw_log_retention_days'   => 30,
            'qaw_db_version'           => QAW_VERSION,
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}

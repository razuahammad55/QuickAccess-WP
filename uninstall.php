<?php
/**
 * QuickAccess WP Uninstall
 *
 * Fired when the plugin is deleted from WordPress.
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Uninstall function - removes all plugin data
 */
function qaw_uninstall() {
    global $wpdb;

    // Delete all plugin options
    $options = array(
        'qaw_rate_limit_attempts',
        'qaw_rate_limit_window',
        'qaw_block_duration',
        'qaw_default_redirect',
        'qaw_invalid_slug_message',
        'qaw_log_retention_days',
        'qaw_enable_logging',
        'qaw_db_version',
        'qaw_flush_rewrite_rules',
    );

    foreach ( $options as $option ) {
        delete_option( $option );
    }

    // Drop custom database tables
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qaw_slugs" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qaw_logs" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qaw_rate_limits" );

    // Clear scheduled cron events
    wp_clear_scheduled_hook( 'qaw_daily_cleanup' );

    // Delete transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_qaw\_%' OR option_name LIKE '%\_transient\_timeout\_qaw\_%'"
    );

    // Flush rewrite rules
    flush_rewrite_rules();

    // Clear cache
    wp_cache_flush();
}

// Handle multisite uninstall
if ( is_multisite() ) {
    global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
    
    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        qaw_uninstall();
        restore_current_blog();
    }
} else {
    qaw_uninstall();
}

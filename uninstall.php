<?php
/**
 * QuickAccess WP Uninstall
 * @package QuickAccessWP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Check if we should delete data
 */
$delete_data = get_option( 'qaw_delete_data_on_uninstall', 0 );

if ( ! $delete_data ) {
    // Only delete the "delete data" option itself so it resets if reinstalled
    delete_option( 'qaw_delete_data_on_uninstall' );
    return;
}

/**
 * Delete all plugin data
 */
function qaw_uninstall_plugin() {
    global $wpdb;

    // Delete options
    $options = array(
        'qaw_rate_limit_attempts',
        'qaw_rate_limit_window',
        'qaw_block_duration',
        'qaw_default_redirect',
        'qaw_invalid_slug_message',
        'qaw_enable_logging',
        'qaw_log_retention_days',
        'qaw_db_version',
        'qaw_delete_data_on_uninstall',
    );

    foreach ( $options as $option ) {
        delete_option( $option );
        delete_site_option( $option );
    }

    // Drop tables
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qaw_slugs" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qaw_logs" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qaw_rate_limits" );

    // Clear cron
    wp_clear_scheduled_hook( 'qaw_daily_cleanup' );

    // Delete transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '\_transient\_qaw\_%' 
         OR option_name LIKE '\_transient\_timeout\_qaw\_%'"
    );

    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Clear cache
    wp_cache_flush();
}

// Handle multisite
if ( is_multisite() ) {
    global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
    
    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        qaw_uninstall_plugin();
        restore_current_blog();
    }
} else {
    qaw_uninstall_plugin();
}

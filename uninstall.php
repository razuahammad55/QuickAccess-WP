<?php
/**
 * QuickAccess WP Uninstall
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data including database tables, options, and scheduled events.
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Main uninstall function
 * 
 * Cleans up all plugin data:
 * - Database tables
 * - WordPress options
 * - Scheduled cron events
 * - Transients
 * - User meta
 */
function qaw_uninstall_plugin() {
    global $wpdb;

    // ==========================================================================
    // 1. Delete Plugin Options
    // ==========================================================================
    
    $options = array(
        'qaw_rate_limit_attempts',
        'qaw_rate_limit_window',
        'qaw_block_duration',
        'qaw_default_redirect',
        'qaw_invalid_slug_message',
        'qaw_enable_logging',
        'qaw_log_retention_days',
        'qaw_db_version',
        'qaw_flush_rewrite_rules',
        'qaw_installed_at',
        'qaw_admin_notice_dismissed',
    );

    foreach ( $options as $option ) {
        delete_option( $option );
        delete_site_option( $option ); // For multisite
    }

    // ==========================================================================
    // 2. Drop Custom Database Tables
    // ==========================================================================
    
    $tables = array(
        $wpdb->prefix . 'qaw_slugs',
        $wpdb->prefix . 'qaw_logs',
        $wpdb->prefix . 'qaw_rate_limits',
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    // ==========================================================================
    // 3. Clear Scheduled Cron Events
    // ==========================================================================
    
    $cron_hooks = array(
        'qaw_daily_cleanup',
        'qaw_cleanup_logs',
        'qaw_cleanup_rate_limits',
        'qaw_send_reports',
    );

    foreach ( $cron_hooks as $hook ) {
        $timestamp = wp_next_scheduled( $hook );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
        }
        wp_clear_scheduled_hook( $hook );
    }

    // ==========================================================================
    // 4. Delete Transients
    // ==========================================================================
    
    // Delete standard transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '\_transient\_qaw\_%' 
         OR option_name LIKE '\_transient\_timeout\_qaw\_%'"
    );

    // Delete site transients for multisite
    if ( is_multisite() ) {
        $wpdb->query(
            "DELETE FROM {$wpdb->sitemeta} 
             WHERE meta_key LIKE '\_site\_transient\_qaw\_%' 
             OR meta_key LIKE '\_site\_transient\_timeout\_qaw\_%'"
        );
    }

    // ==========================================================================
    // 5. Delete User Meta
    // ==========================================================================
    
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} 
         WHERE meta_key LIKE 'qaw\_%' 
         OR meta_key LIKE '\_qaw\_%'"
    );

    // ==========================================================================
    // 6. Delete Post Meta (if any)
    // ==========================================================================
    
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
         WHERE meta_key LIKE 'qaw\_%' 
         OR meta_key LIKE '\_qaw\_%'"
    );

    // ==========================================================================
    // 7. Flush Rewrite Rules
    // ==========================================================================
    
    flush_rewrite_rules();

    // ==========================================================================
    // 8. Clear Object Cache
    // ==========================================================================
    
    wp_cache_flush();
}

/**
 * Handle multisite uninstall
 * 
 * Loops through all sites in a multisite network and runs uninstall for each.
 */
function qaw_uninstall_multisite() {
    global $wpdb;

    if ( is_multisite() ) {
        // Store current blog ID
        $current_blog_id = get_current_blog_id();

        // Get all blog IDs
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

        // Loop through each site
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            qaw_uninstall_plugin();
            restore_current_blog();
        }

        // Make sure we're back on the original blog
        switch_to_blog( $current_blog_id );

        // Delete network-wide options
        delete_site_option( 'qaw_network_settings' );
        delete_site_option( 'qaw_network_version' );

    } else {
        // Single site uninstall
        qaw_uninstall_plugin();
    }
}

// Execute uninstall
qaw_uninstall_multisite();

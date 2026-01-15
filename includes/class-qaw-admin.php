<?php
/**
 * Admin Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Admin Class
 */
class QAW_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // AJAX handlers
        add_action( 'wp_ajax_qaw_create_slug', array( $this, 'ajax_create_slug' ) );
        add_action( 'wp_ajax_qaw_update_slug', array( $this, 'ajax_update_slug' ) );
        add_action( 'wp_ajax_qaw_delete_slug', array( $this, 'ajax_delete_slug' ) );
        add_action( 'wp_ajax_qaw_toggle_slug', array( $this, 'ajax_toggle_slug' ) );
        add_action( 'wp_ajax_qaw_generate_slug', array( $this, 'ajax_generate_slug' ) );
        add_action( 'wp_ajax_qaw_check_slug', array( $this, 'ajax_check_slug' ) );

        // Cron
        if ( ! wp_next_scheduled( 'qaw_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'qaw_daily_cleanup' );
        }
        add_action( 'qaw_daily_cleanup', array( 'QAW_Database', 'cleanup' ) );
    }

    /**
     * Admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __( 'QuickAccess', 'quickaccess-wp' ),
            __( 'QuickAccess', 'quickaccess-wp' ),
            'manage_options',
            'quickaccess-wp',
            array( $this, 'page_slugs' ),
            'dashicons-unlock',
            80
        );

        add_submenu_page(
            'quickaccess-wp',
            __( 'Access Links', 'quickaccess-wp' ),
            __( 'Access Links', 'quickaccess-wp' ),
            'manage_options',
            'quickaccess-wp',
            array( $this, 'page_slugs' )
        );

        add_submenu_page(
            'quickaccess-wp',
            __( 'Access Logs', 'quickaccess-wp' ),
            __( 'Access Logs', 'quickaccess-wp' ),
            'manage_options',
            'qaw-logs',
            array( $this, 'page_logs' )
        );

        add_submenu_page(
            'quickaccess-wp',
            __( 'Settings', 'quickaccess-wp' ),
            __( 'Settings', 'quickaccess-wp' ),
            'manage_options',
            'qaw-settings',
            array( $this, 'page_settings' )
        );
    }

    /**
     * Enqueue assets
     *
     * @param string $hook Page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'quickaccess' ) === false && strpos( $hook, 'qaw-' ) === false ) {
            return;
        }

        wp_enqueue_style( 'qaw-admin', QAW_PLUGIN_URL . 'assets/css/admin-style.css', array(), QAW_VERSION );
        wp_enqueue_script( 'qaw-admin', QAW_PLUGIN_URL . 'assets/js/admin-script.js', array( 'jquery' ), QAW_VERSION, true );

        wp_localize_script( 'qaw-admin', 'qawAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'qaw_nonce' ),
            'homeUrl' => trailingslashit( home_url() ),
            'i18n'    => array(
                'confirmDelete' => __( 'Are you sure you want to delete this access link?', 'quickaccess-wp' ),
                'saved'         => __( 'Saved successfully!', 'quickaccess-wp' ),
                'error'         => __( 'An error occurred. Please try again.', 'quickaccess-wp' ),
                'copied'        => __( 'Copied to clipboard!', 'quickaccess-wp' ),
                'available'     => __( 'Slug is available!', 'quickaccess-wp' ),
                'unavailable'   => __( 'Slug is not available.', 'quickaccess-wp' ),
            ),
        ) );
    }

    /**
     * Register settings
     */
    public function register_settings() {
    register_setting( 'qaw_settings', 'qaw_rate_limit_attempts', 'absint' );
    register_setting( 'qaw_settings', 'qaw_rate_limit_window', 'absint' );
    register_setting( 'qaw_settings', 'qaw_block_duration', 'absint' );
    register_setting( 'qaw_settings', 'qaw_default_redirect', 'esc_url_raw' );
    register_setting( 'qaw_settings', 'qaw_invalid_slug_message', 'sanitize_textarea_field' );
    register_setting( 'qaw_settings', 'qaw_enable_logging', 'absint' );
    register_setting( 'qaw_settings', 'qaw_log_retention_days', 'absint' );
    register_setting( 'qaw_settings', 'qaw_delete_data_on_uninstall', 'absint' ); // NEW
}

    /**
     * Slugs page
     */
    public function page_slugs() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $slug_id = isset( $_GET['slug_id'] ) ? absint( $_GET['slug_id'] ) : 0;

        if ( 'new' === $action ) {
            $this->render_form();
        } elseif ( 'edit' === $action && $slug_id > 0 ) {
            $this->render_form( $slug_id );
        } else {
            $this->render_list();
        }
    }

    /**
     * Render list
     */
    private function render_list() {
        $stats = QAW_Database::get_stats();
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

        $slugs = QAW_Database::get_slugs( array(
            'status'   => $status,
            'search'   => $search,
            'page'     => $paged,
            'per_page' => 20,
        ) );

        $total = QAW_Database::count_slugs( $status );
        $total_pages = ceil( $total / 20 );

        include QAW_PLUGIN_DIR . 'templates/admin/list.php';
    }

    /**
     * Render form
     *
     * @param int $slug_id Slug ID.
     */
    private function render_form( $slug_id = 0 ) {
        $slug = $slug_id > 0 ? QAW_Database::get_slug( $slug_id ) : null;
        $users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ), 'orderby' => 'display_name' ) );

        include QAW_PLUGIN_DIR . 'templates/admin/form.php';
    }

    /**
     * Logs page
     */
    public function page_logs() {
        $slug_id = isset( $_GET['slug_id'] ) ? absint( $_GET['slug_id'] ) : 0;
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

        $logs = QAW_Database::get_logs( array(
            'slug_id'  => $slug_id,
            'status'   => $status,
            'page'     => $paged,
            'per_page' => 50,
        ) );

        $total = QAW_Database::count_logs( $slug_id );
        $total_pages = ceil( $total / 50 );

        include QAW_PLUGIN_DIR . 'templates/admin/logs.php';
    }

    /**
     * Settings page
     */
    public function page_settings() {
        include QAW_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
 * AJAX: Create slug
 */
public function ajax_create_slug() {
    check_ajax_referer( 'qaw_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
    }

    $slug = isset( $_POST['slug'] ) ? sanitize_title( $_POST['slug'] ) : '';
    $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

    if ( empty( $slug ) || empty( $user_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Slug and User are required.', 'quickaccess-wp' ) ) );
    }

    if ( QAW_Database::slug_exists( $slug ) ) {
        wp_send_json_error( array( 'message' => __( 'This slug already exists.', 'quickaccess-wp' ) ) );
    }

    if ( QAW_Database::slug_conflicts( $slug ) ) {
        wp_send_json_error( array( 'message' => __( 'This slug conflicts with existing content.', 'quickaccess-wp' ) ) );
    }

    // Fix: Properly format expires_at datetime
    $expires_at = null;
    if ( ! empty( $_POST['expires_at'] ) ) {
        $expires_at = sanitize_text_field( $_POST['expires_at'] );
        // Convert from datetime-local format to MySQL format
        $expires_at = str_replace( 'T', ' ', $expires_at ) . ':00';
    }

    $result = QAW_Database::create_slug( array(
        'slug'         => $slug,
        'user_id'      => $user_id,
        'redirect_url' => isset( $_POST['redirect_url'] ) ? esc_url_raw( $_POST['redirect_url'] ) : '',
        'max_uses'     => isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0,
        'expires_at'   => $expires_at,
    ) );

    if ( $result ) {
        wp_send_json_success( array(
            'message' => __( 'Access link created!', 'quickaccess-wp' ),
            'id'      => $result,
            'url'     => home_url( '/' . $slug ),
        ) );
    }

    wp_send_json_error( array( 'message' => __( 'Failed to create access link.', 'quickaccess-wp' ) ) );
}

/**
 * AJAX: Update slug
 */
public function ajax_update_slug() {
    check_ajax_referer( 'qaw_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    $slug = isset( $_POST['slug'] ) ? sanitize_title( $_POST['slug'] ) : '';

    if ( ! $id || empty( $slug ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid data.', 'quickaccess-wp' ) ) );
    }

    if ( QAW_Database::slug_exists( $slug, $id ) ) {
        wp_send_json_error( array( 'message' => __( 'This slug already exists.', 'quickaccess-wp' ) ) );
    }

    if ( QAW_Database::slug_conflicts( $slug ) ) {
        wp_send_json_error( array( 'message' => __( 'This slug conflicts with existing content.', 'quickaccess-wp' ) ) );
    }

    // Fix: Properly format expires_at datetime
    $expires_at = null;
    if ( ! empty( $_POST['expires_at'] ) ) {
        $expires_at = sanitize_text_field( $_POST['expires_at'] );
        // Convert from datetime-local format to MySQL format
        $expires_at = str_replace( 'T', ' ', $expires_at ) . ':00';
    }

    $result = QAW_Database::update_slug( $id, array(
        'slug'         => $slug,
        'user_id'      => isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0,
        'redirect_url' => isset( $_POST['redirect_url'] ) ? esc_url_raw( $_POST['redirect_url'] ) : '',
        'max_uses'     => isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0,
        'expires_at'   => $expires_at,
        'is_active'    => isset( $_POST['is_active'] ) ? absint( $_POST['is_active'] ) : 0,
    ) );

    if ( false !== $result ) {
        wp_send_json_success( array(
            'message' => __( 'Access link updated!', 'quickaccess-wp' ),
            'url'     => home_url( '/' . $slug ),
        ) );
    }

    wp_send_json_error( array( 'message' => __( 'Failed to update access link.', 'quickaccess-wp' ) ) );
}

    /**
     * AJAX: Delete slug
     */
    public function ajax_delete_slug() {
        check_ajax_referer( 'qaw_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( QAW_Database::delete_slug( $id ) ) {
            wp_send_json_success( array( 'message' => __( 'Access link deleted!', 'quickaccess-wp' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to delete.', 'quickaccess-wp' ) ) );
    }

    /**
     * AJAX: Toggle slug
     */
    public function ajax_toggle_slug() {
        check_ajax_referer( 'qaw_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $active = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

        $new_status = $active ? 0 : 1;

        if ( false !== QAW_Database::update_slug( $id, array( 'is_active' => $new_status ) ) ) {
            wp_send_json_success( array(
                'message'   => $new_status ? __( 'Link enabled.', 'quickaccess-wp' ) : __( 'Link disabled.', 'quickaccess-wp' ),
                'is_active' => $new_status,
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to update.', 'quickaccess-wp' ) ) );
    }

    /**
     * AJAX: Generate slug
     */
    public function ajax_generate_slug() {
        check_ajax_referer( 'qaw_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        wp_send_json_success( array( 'slug' => QAW_Security::generate_slug() ) );
    }

    /**
     * AJAX: Check slug
     */
    public function ajax_check_slug() {
        check_ajax_referer( 'qaw_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $slug = isset( $_POST['slug'] ) ? sanitize_title( $_POST['slug'] ) : '';
        $exclude = isset( $_POST['exclude_id'] ) ? absint( $_POST['exclude_id'] ) : 0;

        if ( empty( $slug ) ) {
            wp_send_json_error( array( 'available' => false ) );
        }

        if ( QAW_Database::slug_exists( $slug, $exclude ) || QAW_Database::slug_conflicts( $slug ) ) {
            wp_send_json_error( array( 'available' => false ) );
        }

        wp_send_json_success( array( 'available' => true ) );
    }
}

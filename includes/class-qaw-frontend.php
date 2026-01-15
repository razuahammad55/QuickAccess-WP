<?php
/**
 * Frontend Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Frontend Class
 */
class QAW_Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'template_redirect', array( $this, 'handle_request' ), 1 );
    }

    /**
     * Handle frontend request
     */
    public function handle_request() {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $path = trim( strtok( $request_uri, '?' ), '/' );

        // Skip if empty or has slashes
        if ( empty( $path ) || strpos( $path, '/' ) !== false ) {
            return;
        }

        // Skip reserved paths
        $reserved = array( 'wp-admin', 'wp-login.php', 'wp-content', 'wp-includes', 'wp-json', 'feed', 'xmlrpc.php', 'favicon.ico' );
        if ( in_array( $path, $reserved, true ) ) {
            return;
        }

        // Check if slug exists
        $slug = QAW_Database::get_slug_by_string( $path );
        if ( ! $slug ) {
            return;
        }

        // Rate limit check
        if ( QAW_Security::is_rate_limited() ) {
            $time = ceil( QAW_Security::get_block_time() / 60 );
            $this->show_error( sprintf(
                __( 'Too many attempts. Please try again in %d minutes.', 'quickaccess-wp' ),
                $time
            ) );
        }

        // Validate access
        $validation = QAW_Security::validate_access( $slug );

        if ( ! $validation['valid'] ) {
            QAW_Security::record_attempt( false );
            QAW_Database::log_access( $slug->id, 'denied', $validation['message'] );
            $this->show_error( $validation['message'] );
        }

        $user = $validation['user'];

        // Logout if different user
        if ( is_user_logged_in() && get_current_user_id() !== $user->ID ) {
            wp_logout();
        }

        // Login user
        if ( ! is_user_logged_in() ) {
            wp_set_auth_cookie( $user->ID, true );
            wp_set_current_user( $user->ID );
            do_action( 'wp_login', $user->user_login, $user );
        }

        // Update usage & log
        QAW_Database::increment_usage( $slug->id );
        QAW_Security::record_attempt( true );
        QAW_Database::log_access( $slug->id, 'success', 'User logged in: ' . $user->user_login );

        // Redirect
        $redirect = ! empty( $slug->redirect_url ) ? $slug->redirect_url : get_option( 'qaw_default_redirect', home_url() );
        $redirect = apply_filters( 'qaw_redirect_url', $redirect, $user, $slug );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Show error page
     *
     * @param string $message Error message.
     */
    private function show_error( $message ) {
        status_header( 403 );

        $template = locate_template( 'qaw-invalid-slug.php' );
        if ( ! $template ) {
            $template = QAW_PLUGIN_DIR . 'templates/invalid-slug.php';
        }

        set_query_var( 'qaw_error_message', $message );

        if ( file_exists( $template ) ) {
            include $template;
        } else {
            wp_die( esc_html( $message ), __( 'Access Denied', 'quickaccess-wp' ), array( 'response' => 403 ) );
        }

        exit;
    }
}

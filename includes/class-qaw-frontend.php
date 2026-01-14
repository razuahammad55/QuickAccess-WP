<?php
/**
 * Frontend Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Frontend Class
 *
 * Handles frontend slug requests and user login
 * Intercepts requests at the root level (yoursite.com/slug)
 *
 * @since 1.0.0
 */
class QAW_Frontend {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Hook early to intercept requests before WordPress processes them
        add_action( 'template_redirect', array( $this, 'handle_slug_request' ), 1 );
        add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 20 );
    }

    /**
     * Maybe flush rewrite rules
     *
     * @since 1.0.0
     */
    public function maybe_flush_rewrite_rules() {
        if ( get_option( 'qaw_flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
            delete_option( 'qaw_flush_rewrite_rules' );
        }
    }

    /**
     * Handle slug request
     * 
     * Checks if the current URL path matches a QuickAccess slug
     *
     * @since 1.0.0
     */
    public function handle_slug_request() {
        // Get the request URI and parse it
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        
        // Remove query string if present
        $request_path = strtok( $request_uri, '?' );
        
        // Remove leading/trailing slashes and get the slug
        $request_path = trim( $request_path, '/' );
        
        // Skip if empty or contains slashes (nested path)
        if ( empty( $request_path ) || strpos( $request_path, '/' ) !== false ) {
            return;
        }
        
        // Skip WordPress admin, login, and other system paths
        $reserved_paths = array(
            'wp-admin',
            'wp-login.php',
            'wp-content',
            'wp-includes',
            'wp-json',
            'feed',
            'rss',
            'rss2',
            'atom',
            'sitemap',
            'robots.txt',
            'favicon.ico',
            'xmlrpc.php',
        );
        
        if ( in_array( $request_path, $reserved_paths, true ) ) {
            return;
        }
        
        // Check if this slug exists in our database
        $slug_data = QAW_Database::get_slug_by_slug( $request_path );
        
        // If no slug found in our database, let WordPress handle it
        if ( ! $slug_data ) {
            return;
        }

        /**
         * Fires before slug validation
         *
         * @since 1.0.0
         * @param string $request_path The requested slug.
         */
        do_action( 'qaw_before_slug_validation', $request_path );

        // Check rate limiting first
        if ( QAW_Security::is_rate_limited() ) {
            $remaining = QAW_Security::get_block_remaining_time();
            $minutes   = ceil( $remaining / 60 );
            
            $this->show_error_page(
                sprintf(
                    /* translators: %d: minutes remaining */
                    __( 'Too many attempts. Please try again in %d minutes.', 'quickaccess-wp' ),
                    $minutes
                )
            );
            return;
        }

        // Validate slug access
        $validation = QAW_Security::validate_slug_access( $slug_data );

        if ( ! $validation['valid'] ) {
            QAW_Security::record_attempt( false );
            QAW_Database::log_access(
                $slug_data->id,
                'denied',
                $validation['message']
            );
            
            $this->show_error_page(
                apply_filters( 'qaw_error_message', $validation['message'], $validation['code'] ?? 'denied' )
            );
            return;
        }

        // Perform login
        $user = $validation['user'];

        // Check if already logged in as the same user
        if ( is_user_logged_in() && get_current_user_id() === $user->ID ) {
            $redirect_url = $this->get_redirect_url( $slug_data, $user );
            
            QAW_Database::increment_slug_usage( $slug_data->id );
            QAW_Database::log_access(
                $slug_data->id,
                'success',
                'Already logged in, redirected'
            );
            
            wp_safe_redirect( $redirect_url );
            exit;
        }

        // Log out current user if different
        if ( is_user_logged_in() ) {
            wp_logout();
        }

        // Set auth cookie
        wp_set_auth_cookie( $user->ID, true );
        wp_set_current_user( $user->ID );

        // Fire WordPress login action
        do_action( 'wp_login', $user->user_login, $user );

        /**
         * Fires after successful login via QuickAccess
         *
         * @since 1.0.0
         * @param WP_User $user      The logged in user.
         * @param object  $slug_data The slug data.
         */
        do_action( 'qaw_user_logged_in', $user, $slug_data );

        // Update usage and log
        QAW_Database::increment_slug_usage( $slug_data->id );
        QAW_Security::record_attempt( true );
        QAW_Database::log_access(
            $slug_data->id,
            'success',
            'User logged in: ' . $user->user_login
        );

        // Redirect
        $redirect_url = $this->get_redirect_url( $slug_data, $user );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Get redirect URL
     *
     * @since 1.0.0
     * @param object  $slug_data Slug data.
     * @param WP_User $user      User object.
     * @return string Redirect URL.
     */
    private function get_redirect_url( $slug_data, $user ) {
        $url = ! empty( $slug_data->redirect_url )
            ? $slug_data->redirect_url
            : get_option( 'qaw_default_redirect', home_url() );

        /**
         * Filter the redirect URL after login
         *
         * @since 1.0.0
         * @param string  $url       The redirect URL.
         * @param WP_User $user      The logged in user.
         * @param object  $slug_data The slug data.
         */
        return apply_filters( 'qaw_redirect_url', $url, $user, $slug_data );
    }

    /**
     * Show error page
     *
     * @since 1.0.0
     * @param string $message Error message.
     */
    private function show_error_page( $message ) {
        status_header( 403 );

        // Check for theme template override
        $template = locate_template( 'qaw-invalid-slug.php' );

        if ( ! $template ) {
            $template = QAW_PLUGIN_DIR . 'templates/invalid-slug.php';
        }

        // Make message available to template
        set_query_var( 'qaw_error_message', $message );

        /**
         * Filter the error template path
         *
         * @since 1.0.0
         * @param string $template The template path.
         * @param string $message  The error message.
         */
        $template = apply_filters( 'qaw_error_template', $template, $message );

        if ( file_exists( $template ) ) {
            include $template;
        } else {
            wp_die(
                esc_html( $message ),
                esc_html__( 'Access Denied', 'quickaccess-wp' ),
                array( 'response' => 403 )
            );
        }

        exit;
    }
}

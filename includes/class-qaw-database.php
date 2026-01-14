<?php
/**
 * Database Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Database Class
 *
 * Handles all database operations
 *
 * @since 1.0.0
 */
class QAW_Database {

    /**
     * Get slugs table name
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_slugs_table() {
        global $wpdb;
        return $wpdb->prefix . 'qaw_slugs';
    }

    /**
     * Get logs table name
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'qaw_logs';
    }

    /**
     * Get rate limits table name
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_rate_table() {
        global $wpdb;
        return $wpdb->prefix . 'qaw_rate_limits';
    }

    /**
     * Create a new slug
     *
     * @since 1.0.0
     * @param array $data Slug data.
     * @return int|false Insert ID or false on failure.
     */
    public static function create_slug( $data ) {
        global $wpdb;

        $slug      = sanitize_title( $data['slug'] );
        $slug_hash = self::hash_slug( $slug );

        // Check for conflicts with existing WordPress content
        if ( self::slug_conflicts_with_wp( $slug ) ) {
            return false;
        }

        $insert_data = array(
            'slug'         => $slug,
            'slug_hash'    => $slug_hash,
            'user_id'      => absint( $data['user_id'] ),
            'redirect_url' => esc_url_raw( $data['redirect_url'] ?? '' ),
            'max_uses'     => absint( $data['max_uses'] ?? 0 ),
            'expires_at'   => ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null,
            'is_active'    => 1,
            'created_by'   => get_current_user_id(),
        );

        $result = $wpdb->insert(
            self::get_slugs_table(),
            $insert_data,
            array( '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d' )
        );

        if ( $result ) {
            $insert_id = $wpdb->insert_id;
            
            /**
             * Fires after a slug is created
             *
             * @since 1.0.0
             * @param int   $insert_id The new slug ID.
             * @param array $data      The slug data.
             */
            do_action( 'qaw_slug_created', $insert_id, $data );
            
            return $insert_id;
        }

        return false;
    }

    /**
     * Check if slug conflicts with WordPress content
     *
     * @since 1.0.0
     * @param string $slug Slug to check.
     * @return bool True if conflicts.
     */
    public static function slug_conflicts_with_wp( $slug ) {
        global $wpdb;

        // Check posts/pages
        $post_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_status IN ('publish', 'private', 'draft') LIMIT 1",
                $slug
            )
        );

        if ( $post_exists ) {
            return true;
        }

        // Check terms (categories, tags)
        $term_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT term_id FROM {$wpdb->terms} WHERE slug = %s LIMIT 1",
                $slug
            )
        );

        if ( $term_exists ) {
            return true;
        }

        // Check users
        $user_exists = get_user_by( 'slug', $slug );
        if ( $user_exists ) {
            return true;
        }

        // Reserved WordPress slugs
        $reserved = array(
            'admin', 'login', 'wp-admin', 'wp-login', 'wp-content', 'wp-includes',
            'feed', 'rss', 'rss2', 'atom', 'comments', 'search', 'author',
            'page', 'attachment', 'trackback', 'category', 'tag', 'sitemap',
            'dashboard', 'register', 'signup', 'activate', 'cron', 'xmlrpc',
        );

        if ( in_array( strtolower( $slug ), $reserved, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Update a slug
     *
     * @since 1.0.0
     * @param int   $id   Slug ID.
     * @param array $data Slug data.
     * @return int|false Number of rows updated or false on failure.
     */
    public static function update_slug( $id, $data ) {
        global $wpdb;

        $update_data = array();
        $format      = array();

        if ( isset( $data['slug'] ) ) {
            $new_slug = sanitize_title( $data['slug'] );
            
            // Check for conflicts
            if ( self::slug_conflicts_with_wp( $new_slug ) ) {
                return false;
            }
            
            $update_data['slug']      = $new_slug;
            $update_data['slug_hash'] = self::hash_slug( $new_slug );
            $format[]                 = '%s';
            $format[]                 = '%s';
        }

        if ( isset( $data['user_id'] ) ) {
            $update_data['user_id'] = absint( $data['user_id'] );
            $format[]               = '%d';
        }

        if ( isset( $data['redirect_url'] ) ) {
            $update_data['redirect_url'] = esc_url_raw( $data['redirect_url'] );
            $format[]                    = '%s';
        }

        if ( isset( $data['max_uses'] ) ) {
            $update_data['max_uses'] = absint( $data['max_uses'] );
            $format[]                = '%d';
        }

        if ( array_key_exists( 'expires_at', $data ) ) {
            $update_data['expires_at'] = ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null;
            $format[]                  = '%s';
        }

        if ( isset( $data['is_active'] ) ) {
            $update_data['is_active'] = absint( $data['is_active'] );
            $format[]                 = '%d';
        }

        $result = $wpdb->update(
            self::get_slugs_table(),
            $update_data,
            array( 'id' => absint( $id ) ),
            $format,
            array( '%d' )
        );

        if ( false !== $result ) {
            /**
             * Fires after a slug is updated
             *
             * @since 1.0.0
             * @param int   $id   The slug ID.
             * @param array $data The updated data.
             */
            do_action( 'qaw_slug_updated', $id, $data );
        }

        return $result;
    }

    /**
     * Delete a slug
     *
     * @since 1.0.0
     * @param int $id Slug ID.
     * @return int|false Number of rows deleted or false on failure.
     */
    public static function delete_slug( $id ) {
        global $wpdb;

        $slug = self::get_slug_by_id( $id );

        $result = $wpdb->delete(
            self::get_slugs_table(),
            array( 'id' => absint( $id ) ),
            array( '%d' )
        );

        if ( $result ) {
            /**
             * Fires after a slug is deleted
             *
             * @since 1.0.0
             * @param int    $id   The deleted slug ID.
             * @param object $slug The deleted slug data.
             */
            do_action( 'qaw_slug_deleted', $id, $slug );
        }

        return $result;
    }

    /**
     * Get slug by ID
     *
     * @since 1.0.0
     * @param int $id Slug ID.
     * @return object|null Slug data or null if not found.
     */
    public static function get_slug_by_id( $id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_slugs_table() . " WHERE id = %d",
                absint( $id )
            )
        );
    }

    /**
     * Get slug by slug string (direct lookup - for URL matching)
     *
     * @since 1.0.0
     * @param string $slug Slug string.
     * @return object|null Slug data or null if not found.
     */
    public static function get_slug_by_slug( $slug ) {
        global $wpdb;

        $slug = sanitize_title( $slug );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_slugs_table() . " WHERE slug = %s",
                $slug
            )
        );
    }

    /**
     * Get slug by slug hash (secure lookup)
     *
     * @since 1.0.0
     * @param string $slug Slug string.
     * @return object|null Slug data or null if not found.
     */
    public static function get_slug_by_string( $slug ) {
        global $wpdb;

        $slug_hash = self::hash_slug( sanitize_title( $slug ) );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_slugs_table() . " WHERE slug_hash = %s",
                $slug_hash
            )
        );
    }

    /**
     * Get all slugs
     *
     * @since 1.0.0
     * @param array $args Query arguments.
     * @return array Array of slug objects.
     */
    public static function get_all_slugs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'orderby'  => 'created_at',
            'order'    => 'DESC',
            'per_page' => 20,
            'page'     => 1,
            'status'   => '',
            'search'   => '',
        );

        $args = wp_parse_args( $args, $defaults );

        // Validate orderby
        $allowed_orderby = array( 'id', 'slug', 'created_at', 'updated_at', 'current_uses', 'expires_at' );
        $orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

        $where = '1=1';
        
        if ( 'active' === $args['status'] ) {
            $where .= ' AND s.is_active = 1';
        } elseif ( 'inactive' === $args['status'] ) {
            $where .= ' AND s.is_active = 0';
        }

        if ( ! empty( $args['search'] ) ) {
            $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where .= $wpdb->prepare( ' AND (s.slug LIKE %s OR u.display_name LIKE %s)', $search, $search );
        }

        $sql = $wpdb->prepare(
            "SELECT s.*, u.display_name as user_display_name, u.user_email as user_email
             FROM " . self::get_slugs_table() . " s
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE {$where}
             ORDER BY {$orderby} {$order}
             LIMIT %d OFFSET %d",
            absint( $args['per_page'] ),
            $offset
        );

        return $wpdb->get_results( $sql );
    }

    /**
     * Get total slugs count
     *
     * @since 1.0.0
     * @param string $status Optional status filter.
     * @return int Total count.
     */
    public static function get_slugs_count( $status = '' ) {
        global $wpdb;

        $where = '1=1';
        
        if ( 'active' === $status ) {
            $where .= ' AND is_active = 1';
        } elseif ( 'inactive' === $status ) {
            $where .= ' AND is_active = 0';
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::get_slugs_table() . " WHERE {$where}"
        );
    }

    /**
     * Increment slug usage
     *
     * @since 1.0.0
     * @param int $id Slug ID.
     * @return int|false Number of rows updated or false on failure.
     */
    public static function increment_slug_usage( $id ) {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE " . self::get_slugs_table() . " SET current_uses = current_uses + 1 WHERE id = %d",
                absint( $id )
            )
        );
    }

    /**
     * Log access attempt
     *
     * @since 1.0.0
     * @param int    $slug_id Slug ID.
     * @param string $status  Access status.
     * @param string $message Optional message.
     * @return int|false Insert ID or false on failure.
     */
    public static function log_access( $slug_id, $status, $message = '' ) {
        global $wpdb;

        if ( ! get_option( 'qaw_enable_logging', 1 ) ) {
            return false;
        }

        return $wpdb->insert(
            self::get_logs_table(),
            array(
                'slug_id'    => absint( $slug_id ),
                'user_id'    => get_current_user_id() ?: null,
                'ip_address' => QAW_Security::get_client_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) 
                    ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) 
                    : '',
                'status'     => sanitize_text_field( $status ),
                'message'    => sanitize_text_field( $message ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Get access logs
     *
     * @since 1.0.0
     * @param array $args Query arguments.
     * @return array Array of log objects.
     */
    public static function get_logs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'per_page' => 50,
            'page'     => 1,
            'slug_id'  => 0,
            'status'   => '',
        );

        $args   = wp_parse_args( $args, $defaults );
        $offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

        $where = '1=1';
        
        if ( $args['slug_id'] > 0 ) {
            $where .= $wpdb->prepare( ' AND l.slug_id = %d', absint( $args['slug_id'] ) );
        }

        if ( ! empty( $args['status'] ) ) {
            $where .= $wpdb->prepare( ' AND l.status = %s', sanitize_text_field( $args['status'] ) );
        }

        $sql = $wpdb->prepare(
            "SELECT l.*, s.slug as slug_name, u.display_name as user_display_name
             FROM " . self::get_logs_table() . " l
             LEFT JOIN " . self::get_slugs_table() . " s ON l.slug_id = s.id
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE {$where}
             ORDER BY l.created_at DESC
             LIMIT %d OFFSET %d",
            absint( $args['per_page'] ),
            $offset
        );

        return $wpdb->get_results( $sql );
    }

    /**
     * Get logs count
     *
     * @since 1.0.0
     * @param int $slug_id Optional slug ID filter.
     * @return int Total count.
     */
    public static function get_logs_count( $slug_id = 0 ) {
        global $wpdb;

        $where = '1=1';
        
        if ( $slug_id > 0 ) {
            $where .= $wpdb->prepare( ' AND slug_id = %d', absint( $slug_id ) );
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::get_logs_table() . " WHERE {$where}"
        );
    }

    /**
     * Clean old logs
     *
     * @since 1.0.0
     * @return int|false Number of rows deleted or false on failure.
     */
    public static function clean_old_logs() {
        global $wpdb;

        $retention_days = absint( get_option( 'qaw_log_retention_days', 30 ) );

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::get_logs_table() . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }

    /**
     * Check if slug exists
     *
     * @since 1.0.0
     * @param string $slug       Slug string.
     * @param int    $exclude_id Optional ID to exclude from check.
     * @return bool True if slug exists.
     */
    public static function slug_exists( $slug, $exclude_id = 0 ) {
        global $wpdb;

        $slug = sanitize_title( $slug );

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_slugs_table() . " WHERE slug = %s",
            $slug
        );

        if ( $exclude_id > 0 ) {
            $sql .= $wpdb->prepare( " AND id != %d", absint( $exclude_id ) );
        }

        return (int) $wpdb->get_var( $sql ) > 0;
    }

    /**
     * Hash slug for secure storage
     *
     * @since 1.0.0
     * @param string $slug Slug string.
     * @return string Hashed slug.
     */
    public static function hash_slug( $slug ) {
        return hash( 'sha256', $slug . wp_salt( 'auth' ) );
    }

    /**
     * Get slug statistics
     *
     * @since 1.0.0
     * @return array Statistics array.
     */
    public static function get_statistics() {
        global $wpdb;

        $stats = array(
            'total_slugs'      => self::get_slugs_count(),
            'active_slugs'     => self::get_slugs_count( 'active' ),
            'inactive_slugs'   => self::get_slugs_count( 'inactive' ),
            'total_logins'     => 0,
            'logins_today'     => 0,
            'logins_this_week' => 0,
        );

        // Total successful logins
        $stats['total_logins'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::get_logs_table() . " WHERE status = 'success'"
        );

        // Logins today
        $stats['logins_today'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::get_logs_table() . " 
             WHERE status = 'success' AND DATE(created_at) = CURDATE()"
        );

        // Logins this week
        $stats['logins_this_week'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::get_logs_table() . " 
             WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        return $stats;
    }
}

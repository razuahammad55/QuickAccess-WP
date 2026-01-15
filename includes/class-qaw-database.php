<?php
/**
 * Database Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Database Class
 */
class QAW_Database {

    /**
     * Get slugs table
     *
     * @return string
     */
    public static function slugs_table() {
        global $wpdb;
        return $wpdb->prefix . 'qaw_slugs';
    }

    /**
     * Get logs table
     *
     * @return string
     */
    public static function logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'qaw_logs';
    }

    /**
     * Get rate limits table
     *
     * @return string
     */
    public static function rate_table() {
        global $wpdb;
        return $wpdb->prefix . 'qaw_rate_limits';
    }

    /**
     * Create slug
     *
     * @param array $data Slug data.
     * @return int|false
     */
    public static function create_slug( $data ) {
        global $wpdb;

        $result = $wpdb->insert(
            self::slugs_table(),
            array(
                'slug'         => sanitize_title( $data['slug'] ),
                'user_id'      => absint( $data['user_id'] ),
                'redirect_url' => esc_url_raw( $data['redirect_url'] ?? '' ),
                'max_uses'     => absint( $data['max_uses'] ?? 0 ),
                'expires_at'   => ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null,
                'is_active'    => 1,
                'created_by'   => get_current_user_id(),
            ),
            array( '%s', '%d', '%s', '%d', '%s', '%d', '%d' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update slug
     *
     * @param int   $id   Slug ID.
     * @param array $data Slug data.
     * @return int|false
     */
    public static function update_slug( $id, $data ) {
        global $wpdb;

        $update = array();
        $format = array();

        if ( isset( $data['slug'] ) ) {
            $update['slug'] = sanitize_title( $data['slug'] );
            $format[] = '%s';
        }
        if ( isset( $data['user_id'] ) ) {
            $update['user_id'] = absint( $data['user_id'] );
            $format[] = '%d';
        }
        if ( isset( $data['redirect_url'] ) ) {
            $update['redirect_url'] = esc_url_raw( $data['redirect_url'] );
            $format[] = '%s';
        }
        if ( isset( $data['max_uses'] ) ) {
            $update['max_uses'] = absint( $data['max_uses'] );
            $format[] = '%d';
        }
        if ( array_key_exists( 'expires_at', $data ) ) {
            $update['expires_at'] = ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null;
            $format[] = '%s';
        }
        if ( isset( $data['is_active'] ) ) {
            $update['is_active'] = absint( $data['is_active'] );
            $format[] = '%d';
        }

        return $wpdb->update( self::slugs_table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
    }

    /**
     * Delete slug
     *
     * @param int $id Slug ID.
     * @return int|false
     */
    public static function delete_slug( $id ) {
        global $wpdb;
        return $wpdb->delete( self::slugs_table(), array( 'id' => absint( $id ) ), array( '%d' ) );
    }

    /**
     * Get slug by ID
     *
     * @param int $id Slug ID.
     * @return object|null
     */
    public static function get_slug( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::slugs_table() . " WHERE id = %d",
            absint( $id )
        ) );
    }

    /**
     * Get slug by slug string
     *
     * @param string $slug Slug string.
     * @return object|null
     */
    public static function get_slug_by_string( $slug ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::slugs_table() . " WHERE slug = %s",
            sanitize_title( $slug )
        ) );
    }

    /**
     * Get all slugs
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_slugs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'   => '',
            'search'   => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
            'per_page' => 20,
            'page'     => 1,
        );
        $args = wp_parse_args( $args, $defaults );

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

        $allowed_orderby = array( 'id', 'slug', 'created_at', 'current_uses' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
        $offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, u.display_name, u.user_email 
             FROM " . self::slugs_table() . " s 
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
             WHERE {$where} 
             ORDER BY {$orderby} {$order} 
             LIMIT %d OFFSET %d",
            absint( $args['per_page'] ),
            $offset
        ) );
    }

    /**
     * Count slugs
     *
     * @param string $status Status filter.
     * @return int
     */
    public static function count_slugs( $status = '' ) {
        global $wpdb;

        $where = '1=1';
        if ( 'active' === $status ) {
            $where .= ' AND is_active = 1';
        } elseif ( 'inactive' === $status ) {
            $where .= ' AND is_active = 0';
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::slugs_table() . " WHERE {$where}" );
    }

    /**
     * Increment slug usage
     *
     * @param int $id Slug ID.
     * @return int|false
     */
    public static function increment_usage( $id ) {
        global $wpdb;
        return $wpdb->query( $wpdb->prepare(
            "UPDATE " . self::slugs_table() . " SET current_uses = current_uses + 1 WHERE id = %d",
            absint( $id )
        ) );
    }

    /**
     * Check slug exists
     *
     * @param string $slug       Slug string.
     * @param int    $exclude_id Exclude ID.
     * @return bool
     */
    public static function slug_exists( $slug, $exclude_id = 0 ) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::slugs_table() . " WHERE slug = %s",
            sanitize_title( $slug )
        );

        if ( $exclude_id > 0 ) {
            $sql .= $wpdb->prepare( " AND id != %d", $exclude_id );
        }

        return (int) $wpdb->get_var( $sql ) > 0;
    }

    /**
     * Check slug conflicts with WordPress
     *
     * @param string $slug Slug string.
     * @return bool
     */
    public static function slug_conflicts( $slug ) {
        global $wpdb;

        // Check posts/pages
        $post = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_status IN ('publish','private','draft') LIMIT 1",
            $slug
        ) );
        if ( $post ) return true;

        // Check terms
        $term = $wpdb->get_var( $wpdb->prepare(
            "SELECT term_id FROM {$wpdb->terms} WHERE slug = %s LIMIT 1",
            $slug
        ) );
        if ( $term ) return true;

        // Reserved slugs
        $reserved = array(
            'admin', 'login', 'wp-admin', 'wp-login', 'wp-content', 'wp-includes',
            'feed', 'rss', 'sitemap', 'dashboard', 'register', 'signup', 'cart', 'checkout'
        );
        if ( in_array( strtolower( $slug ), $reserved, true ) ) return true;

        return false;
    }

    /**
     * Log access
     *
     * @param int    $slug_id Slug ID.
     * @param string $status  Status.
     * @param string $message Message.
     */
    public static function log_access( $slug_id, $status, $message = '' ) {
        global $wpdb;

        if ( ! get_option( 'qaw_enable_logging', 1 ) ) return;

        $wpdb->insert(
            self::logs_table(),
            array(
                'slug_id'    => absint( $slug_id ),
                'ip_address' => QAW_Security::get_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'status'     => sanitize_text_field( $status ),
                'message'    => sanitize_text_field( $message ),
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Get logs
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_logs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'slug_id'  => 0,
            'status'   => '',
            'per_page' => 50,
            'page'     => 1,
        );
        $args = wp_parse_args( $args, $defaults );

        $where = '1=1';
        if ( $args['slug_id'] > 0 ) {
            $where .= $wpdb->prepare( ' AND l.slug_id = %d', $args['slug_id'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $where .= $wpdb->prepare( ' AND l.status = %s', $args['status'] );
        }

        $offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT l.*, s.slug 
             FROM " . self::logs_table() . " l 
             LEFT JOIN " . self::slugs_table() . " s ON l.slug_id = s.id 
             WHERE {$where} 
             ORDER BY l.created_at DESC 
             LIMIT %d OFFSET %d",
            absint( $args['per_page'] ),
            $offset
        ) );
    }

    /**
     * Count logs
     *
     * @param int $slug_id Slug ID filter.
     * @return int
     */
    public static function count_logs( $slug_id = 0 ) {
        global $wpdb;

        $where = '1=1';
        if ( $slug_id > 0 ) {
            $where .= $wpdb->prepare( ' AND slug_id = %d', $slug_id );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::logs_table() . " WHERE {$where}" );
    }

    /**
     * Get statistics
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;

        return array(
            'total'        => self::count_slugs(),
            'active'       => self::count_slugs( 'active' ),
            'inactive'     => self::count_slugs( 'inactive' ),
            'total_logins' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::logs_table() . " WHERE status = 'success'" ),
            'logins_today' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::logs_table() . " WHERE status = 'success' AND DATE(created_at) = CURDATE()" ),
            'logins_week'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::logs_table() . " WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
        );
    }

    /**
     * Cleanup old data
     */
    public static function cleanup() {
        global $wpdb;

        $days = absint( get_option( 'qaw_log_retention_days', 30 ) );

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . self::logs_table() . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );

        $wpdb->query( "DELETE FROM " . self::rate_table() . " WHERE blocked_until < NOW() OR blocked_until IS NULL" );
    }
}

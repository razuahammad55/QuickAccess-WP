<?php
/**
 * Security Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Security Class
 */
class QAW_Security {

    /**
     * Get client IP
     *
     * @return string
     */
    public static function get_ip() {
        $keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'REMOTE_ADDR' );

        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check if IP is rate limited
     *
     * @return bool
     */
    public static function is_rate_limited() {
        global $wpdb;

        $ip = self::get_ip();
        $table = QAW_Database::rate_table();

        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE ip_address = %s",
            $ip
        ) );

        if ( $record && $record->blocked_until && strtotime( $record->blocked_until ) > time() ) {
            return true;
        }

        return false;
    }

    /**
     * Get block remaining time
     *
     * @return int
     */
    public static function get_block_time() {
        global $wpdb;

        $ip = self::get_ip();
        $table = QAW_Database::rate_table();

        $blocked_until = $wpdb->get_var( $wpdb->prepare(
            "SELECT blocked_until FROM {$table} WHERE ip_address = %s",
            $ip
        ) );

        if ( $blocked_until ) {
            return max( 0, strtotime( $blocked_until ) - time() );
        }

        return 0;
    }

    /**
     * Record access attempt
     *
     * @param bool $success Whether attempt was successful.
     */
    public static function record_attempt( $success = false ) {
        global $wpdb;

        $ip = self::get_ip();
        $table = QAW_Database::rate_table();
        $max_attempts = absint( get_option( 'qaw_rate_limit_attempts', 5 ) );
        $window = absint( get_option( 'qaw_rate_limit_window', 15 ) );
        $block_duration = absint( get_option( 'qaw_block_duration', 60 ) );

        if ( $success ) {
            $wpdb->delete( $table, array( 'ip_address' => $ip ), array( '%s' ) );
            return;
        }

        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE ip_address = %s",
            $ip
        ) );

        if ( ! $record ) {
            $wpdb->insert( $table, array(
                'ip_address'    => $ip,
                'attempts'      => 1,
                'first_attempt' => current_time( 'mysql' ),
            ), array( '%s', '%d', '%s' ) );
        } else {
            $window_start = strtotime( "-{$window} minutes" );

            if ( strtotime( $record->first_attempt ) < $window_start ) {
                $wpdb->update( $table, array(
                    'attempts'      => 1,
                    'first_attempt' => current_time( 'mysql' ),
                    'blocked_until' => null,
                ), array( 'ip_address' => $ip ), array( '%d', '%s', '%s' ), array( '%s' ) );
            } else {
                $new_attempts = $record->attempts + 1;
                $blocked_until = ( $new_attempts >= $max_attempts )
                    ? gmdate( 'Y-m-d H:i:s', strtotime( "+{$block_duration} minutes" ) )
                    : null;

                $wpdb->update( $table, array(
                    'attempts'      => $new_attempts,
                    'blocked_until' => $blocked_until,
                ), array( 'ip_address' => $ip ), array( '%d', '%s' ), array( '%s' ) );
            }
        }
    }

    /**
     * Validate slug access
     *
     * @param object $slug Slug data.
     * @return array
     */
    public static function validate_access( $slug ) {
        if ( ! $slug->is_active ) {
            return array(
                'valid'   => false,
                'code'    => 'inactive',
                'message' => __( 'This access link has been disabled.', 'quickaccess-wp' ),
            );
        }

        if ( $slug->expires_at && strtotime( $slug->expires_at ) < time() ) {
            return array(
                'valid'   => false,
                'code'    => 'expired',
                'message' => __( 'This access link has expired.', 'quickaccess-wp' ),
            );
        }

        if ( $slug->max_uses > 0 && $slug->current_uses >= $slug->max_uses ) {
            return array(
                'valid'   => false,
                'code'    => 'maxed',
                'message' => __( 'This access link has reached its usage limit.', 'quickaccess-wp' ),
            );
        }

        $user = get_user_by( 'ID', $slug->user_id );
        if ( ! $user ) {
            return array(
                'valid'   => false,
                'code'    => 'no_user',
                'message' => __( 'User account not found.', 'quickaccess-wp' ),
            );
        }

        return array(
            'valid' => true,
            'user'  => $user,
        );
    }

    /**
     * Generate random slug
     *
     * @param int $length Length.
     * @return string
     */
    public static function generate_slug( $length = 12 ) {
        $slug = strtolower( wp_generate_password( $length, false, false ) );

        while ( QAW_Database::slug_exists( $slug ) || QAW_Database::slug_conflicts( $slug ) ) {
            $slug = strtolower( wp_generate_password( $length, false, false ) );
        }

        return $slug;
    }
}

<?php
/**
 * Admin Logs Template
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap qaw-wrap">
    <h1><?php esc_html_e( 'Access Logs', 'quickaccess-wp' ); ?></h1>

    <?php if ( ! get_option( 'qaw_enable_logging', 1 ) ) : ?>
        <div class="notice notice-warning">
            <p>
                <?php esc_html_e( 'Logging is currently disabled.', 'quickaccess-wp' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-settings' ) ); ?>">
                    <?php esc_html_e( 'Enable it in settings', 'quickaccess-wp' ); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="qaw-filter-bar">
        <div class="qaw-filter-tabs">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs' ) ); ?>" class="<?php echo empty( $status ) ? 'active' : ''; ?>">
                <?php esc_html_e( 'All', 'quickaccess-wp' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&status=success' ) ); ?>" class="<?php echo 'success' === $status ? 'active' : ''; ?>">
                <?php esc_html_e( 'Successful', 'quickaccess-wp' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&status=denied' ) ); ?>" class="<?php echo 'denied' === $status ? 'active' : ''; ?>">
                <?php esc_html_e( 'Denied', 'quickaccess-wp' ); ?>
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="qaw-table-wrap">
        <table class="qaw-table">
            <thead>
                <tr>
                    <th style="width: 18%;"><?php esc_html_e( 'Date & Time', 'quickaccess-wp' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Slug', 'quickaccess-wp' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'IP Address', 'quickaccess-wp' ); ?></th>
                    <th style="width: 12%;"><?php esc_html_e( 'Status', 'quickaccess-wp' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'quickaccess-wp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr>
                        <td colspan="5">
                            <div class="qaw-empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3><?php esc_html_e( 'No logs found', 'quickaccess-wp' ); ?></h3>
                                <p><?php esc_html_e( 'Access logs will appear here once links are used.', 'quickaccess-wp' ); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td>
                                <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?>
                            </td>
                            <td>
                                <?php if ( $log->slug ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&slug_id=' . $log->slug_id ) ); ?>">
                                        <?php echo esc_html( $log->slug ); ?>
                                    </a>
                                <?php else : ?>
                                    <span style="color: var(--qaw-gray-400);"><?php esc_html_e( 'Deleted', 'quickaccess-wp' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 12px;"><?php echo esc_html( $log->ip_address ); ?></code>
                            </td>
                            <td>
                                <span class="qaw-badge qaw-badge-<?php echo esc_attr( $log->status ); ?>">
                                    <?php echo esc_html( ucfirst( $log->status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $log->message ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="qaw-pagination">
                <?php
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $paged,
                ) );
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Admin List Template
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap qaw-wrap">
    <h1>
        <?php esc_html_e( 'Access Links', 'quickaccess-wp' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=new' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New', 'quickaccess-wp' ); ?>
        </a>
    </h1>

    <!-- Statistics -->
    <div class="qaw-stats">
        <div class="qaw-stat-card">
            <div class="qaw-stat-icon primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </div>
            <div class="qaw-stat-number"><?php echo esc_html( $stats['total'] ); ?></div>
            <div class="qaw-stat-label"><?php esc_html_e( 'Total Links', 'quickaccess-wp' ); ?></div>
        </div>
        
        <div class="qaw-stat-card">
            <div class="qaw-stat-icon success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="qaw-stat-number"><?php echo esc_html( $stats['active'] ); ?></div>
            <div class="qaw-stat-label"><?php esc_html_e( 'Active', 'quickaccess-wp' ); ?></div>
        </div>
        
        <div class="qaw-stat-card">
            <div class="qaw-stat-icon warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="qaw-stat-number"><?php echo esc_html( $stats['logins_today'] ); ?></div>
            <div class="qaw-stat-label"><?php esc_html_e( 'Logins Today', 'quickaccess-wp' ); ?></div>
        </div>
        
        <div class="qaw-stat-card">
            <div class="qaw-stat-icon info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="qaw-stat-number"><?php echo esc_html( $stats['total_logins'] ); ?></div>
            <div class="qaw-stat-label"><?php esc_html_e( 'Total Logins', 'quickaccess-wp' ); ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="qaw-filter-bar">
        <div class="qaw-filter-tabs">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp' ) ); ?>" class="<?php echo empty( $status ) ? 'active' : ''; ?>">
                <?php esc_html_e( 'All', 'quickaccess-wp' ); ?>
                <span class="count"><?php echo esc_html( $stats['total'] ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&status=active' ) ); ?>" class="<?php echo 'active' === $status ? 'active' : ''; ?>">
                <?php esc_html_e( 'Active', 'quickaccess-wp' ); ?>
                <span class="count"><?php echo esc_html( $stats['active'] ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&status=inactive' ) ); ?>" class="<?php echo 'inactive' === $status ? 'active' : ''; ?>">
                <?php esc_html_e( 'Inactive', 'quickaccess-wp' ); ?>
                <span class="count"><?php echo esc_html( $stats['inactive'] ); ?></span>
            </a>
        </div>
        
        <form method="get" class="qaw-search-box">
            <input type="hidden" name="page" value="quickaccess-wp">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search links...', 'quickaccess-wp' ); ?>">
            <button type="submit"><?php esc_html_e( 'Search', 'quickaccess-wp' ); ?></button>
        </form>
    </div>

    <!-- Table -->
    <div class="qaw-table-wrap">
        <table class="qaw-table">
            <thead>
                <tr>
                    <th class="column-slug"><?php esc_html_e( 'Slug', 'quickaccess-wp' ); ?></th>
                    <th class="column-url"><?php esc_html_e( 'Access URL', 'quickaccess-wp' ); ?></th>
                    <th class="column-user"><?php esc_html_e( 'User', 'quickaccess-wp' ); ?></th>
                    <th class="column-usage"><?php esc_html_e( 'Usage', 'quickaccess-wp' ); ?></th>
                    <th class="column-expires"><?php esc_html_e( 'Expires', 'quickaccess-wp' ); ?></th>
                    <th class="column-status"><?php esc_html_e( 'Status', 'quickaccess-wp' ); ?></th>
                    <th class="column-actions"><?php esc_html_e( 'Actions', 'quickaccess-wp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $slugs ) ) : ?>
                    <tr>
                        <td colspan="7">
                            <div class="qaw-empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <h3><?php esc_html_e( 'No access links found', 'quickaccess-wp' ); ?></h3>
                                <p><?php esc_html_e( 'Create your first access link to get started.', 'quickaccess-wp' ); ?></p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=new' ) ); ?>" class="qaw-btn qaw-btn-primary">
                                    <?php esc_html_e( 'Create Access Link', 'quickaccess-wp' ); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $slugs as $item ) : 
                        $access_url = home_url( '/' . $item->slug );
                        $is_expired = $item->expires_at && strtotime( $item->expires_at ) < time();
                        $is_maxed = $item->max_uses > 0 && $item->current_uses >= $item->max_uses;
                        $is_active = $item->is_active && ! $is_expired && ! $is_maxed;
                        $initials = strtoupper( substr( $item->display_name ?: 'U', 0, 1 ) );
                    ?>
                        <tr>
                            <td class="column-slug">
                                <span class="qaw-slug-name">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=edit&slug_id=' . $item->id ) ); ?>">
                                        <?php echo esc_html( $item->slug ); ?>
                                    </a>
                                </span>
                            </td>
                            <td class="column-url">
                                <div class="qaw-url-wrap">
                                    <code class="qaw-url"><?php echo esc_url( $access_url ); ?></code>
                                    <button type="button" class="qaw-copy-btn" data-url="<?php echo esc_url( $access_url ); ?>" title="<?php esc_attr_e( 'Copy URL', 'quickaccess-wp' ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="column-user">
                                <div class="qaw-user-info">
                                    <div class="qaw-user-avatar"><?php echo esc_html( $initials ); ?></div>
                                    <div class="qaw-user-details">
                                        <div class="qaw-user-name"><?php echo esc_html( $item->display_name ?: __( 'Unknown', 'quickaccess-wp' ) ); ?></div>
                                        <div class="qaw-user-email"><?php echo esc_html( $item->user_email ?: '' ); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="column-usage">
                                <span class="qaw-usage">
                                    <?php echo esc_html( $item->current_uses ); ?>
                                    <span class="qaw-usage-unlimited">/ <?php echo $item->max_uses > 0 ? esc_html( $item->max_uses ) : '∞'; ?></span>
                                </span>
                            </td>
                            <td class="column-expires">
                                <?php if ( $item->expires_at ) : ?>
                                    <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->expires_at ) ) ); ?>
                                    <?php if ( $is_expired ) : ?>
                                        <br><span class="qaw-badge qaw-badge-expired"><?php esc_html_e( 'Expired', 'quickaccess-wp' ); ?></span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span style="color: var(--qaw-gray-400);"><?php esc_html_e( 'Never', 'quickaccess-wp' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <?php if ( $is_active ) : ?>
                                    <span class="qaw-badge qaw-badge-active"><?php esc_html_e( 'Active', 'quickaccess-wp' ); ?></span>
                                <?php elseif ( $is_expired ) : ?>
                                    <span class="qaw-badge qaw-badge-expired"><?php esc_html_e( 'Expired', 'quickaccess-wp' ); ?></span>
                                <?php elseif ( $is_maxed ) : ?>
                                    <span class="qaw-badge qaw-badge-maxed"><?php esc_html_e( 'Maxed', 'quickaccess-wp' ); ?></span>
                                <?php else : ?>
                                    <span class="qaw-badge qaw-badge-inactive"><?php esc_html_e( 'Disabled', 'quickaccess-wp' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <div class="qaw-actions">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=edit&slug_id=' . $item->id ) ); ?>" class="qaw-action-btn edit" title="<?php esc_attr_e( 'Edit', 'quickaccess-wp' ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button type="button" class="qaw-action-btn qaw-toggle-btn" data-id="<?php echo esc_attr( $item->id ); ?>" data-active="<?php echo esc_attr( $item->is_active ); ?>" title="<?php echo $item->is_active ? esc_attr__( 'Disable', 'quickaccess-wp' ) : esc_attr__( 'Enable', 'quickaccess-wp' ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <?php if ( $item->is_active ) : ?>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            <?php else : ?>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            <?php endif; ?>
                                        </svg>
                                    </button>
                                    <button type="button" class="qaw-action-btn delete qaw-delete-btn" data-id="<?php echo esc_attr( $item->id ); ?>" title="<?php esc_attr_e( 'Delete', 'quickaccess-wp' ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
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

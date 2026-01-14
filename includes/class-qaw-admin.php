<?php
/**
 * Admin Handler
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * QAW_Admin Class
 *
 * Handles admin interface and AJAX requests
 *
 * @since 1.0.0
 */
class QAW_Admin {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_qaw_create_slug', array( $this, 'ajax_create_slug' ) );
        add_action( 'wp_ajax_qaw_update_slug', array( $this, 'ajax_update_slug' ) );
        add_action( 'wp_ajax_qaw_delete_slug', array( $this, 'ajax_delete_slug' ) );
        add_action( 'wp_ajax_qaw_toggle_slug', array( $this, 'ajax_toggle_slug' ) );
        add_action( 'wp_ajax_qaw_generate_slug', array( $this, 'ajax_generate_slug' ) );
        add_action( 'wp_ajax_qaw_check_slug', array( $this, 'ajax_check_slug' ) );

        // Schedule cleanup
        if ( ! wp_next_scheduled( 'qaw_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'qaw_daily_cleanup' );
        }
        add_action( 'qaw_daily_cleanup', array( $this, 'daily_cleanup' ) );
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        $capability = apply_filters( 'qaw_manage_capability', 'manage_options' );

        add_menu_page(
            __( 'QuickAccess', 'quickaccess-wp' ),
            __( 'QuickAccess', 'quickaccess-wp' ),
            $capability,
            'quickaccess-wp',
            array( $this, 'render_main_page' ),
            'dashicons-unlock',
            80
        );

        add_submenu_page(
            'quickaccess-wp',
            __( 'Access Links', 'quickaccess-wp' ),
            __( 'Access Links', 'quickaccess-wp' ),
            $capability,
            'quickaccess-wp',
            array( $this, 'render_main_page' )
        );

        add_submenu_page(
            'quickaccess-wp',
            __( 'Access Logs', 'quickaccess-wp' ),
            __( 'Access Logs', 'quickaccess-wp' ),
            $capability,
            'qaw-logs',
            array( $this, 'render_logs_page' )
        );

        add_submenu_page(
            'quickaccess-wp',
            __( 'Settings', 'quickaccess-wp' ),
            __( 'Settings', 'quickaccess-wp' ),
            $capability,
            'qaw-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin scripts
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'quickaccess' ) === false && strpos( $hook, 'qaw-' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'qaw-admin-style',
            QAW_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            QAW_VERSION
        );

        wp_enqueue_script(
            'qaw-admin-script',
            QAW_PLUGIN_URL . 'assets/js/admin-script.js',
            array( 'jquery' ),
            QAW_VERSION,
            true
        );

        wp_localize_script( 'qaw-admin-script', 'qawAdmin', array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'qaw_admin_nonce' ),
            'homeUrl'   => trailingslashit( home_url() ),
            'adminUrl'  => admin_url( 'admin.php?page=quickaccess-wp' ),
            'strings'   => array(
                'confirmDelete'   => __( 'Are you sure you want to delete this access link?', 'quickaccess-wp' ),
                'confirmBulk'     => __( 'Are you sure you want to perform this action?', 'quickaccess-wp' ),
                'saved'           => __( 'Saved successfully!', 'quickaccess-wp' ),
                'error'           => __( 'An error occurred. Please try again.', 'quickaccess-wp' ),
                'slugExists'      => __( 'This slug already exists or conflicts with existing content.', 'quickaccess-wp' ),
                'slugAvailable'   => __( 'Slug is available!', 'quickaccess-wp' ),
                'slugConflict'    => __( 'This slug conflicts with existing WordPress content.', 'quickaccess-wp' ),
                'copied'          => __( 'Copied to clipboard!', 'quickaccess-wp' ),
                'copyFailed'      => __( 'Failed to copy. Please copy manually.', 'quickaccess-wp' ),
                'generating'      => __( 'Generating...', 'quickaccess-wp' ),
                'checking'        => __( 'Checking...', 'quickaccess-wp' ),
                'selectUser'      => __( 'Please select a user.', 'quickaccess-wp' ),
                'enterSlug'       => __( 'Please enter a slug.', 'quickaccess-wp' ),
            ),
        ) );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting( 'qaw_settings', 'qaw_rate_limit_attempts', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 5,
        ) );
        
        register_setting( 'qaw_settings', 'qaw_rate_limit_window', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 15,
        ) );
        
        register_setting( 'qaw_settings', 'qaw_block_duration', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 60,
        ) );
        
        register_setting( 'qaw_settings', 'qaw_default_redirect', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => home_url(),
        ) );
        
        register_setting( 'qaw_settings', 'qaw_invalid_slug_message', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'This access link is invalid or has expired.', 'quickaccess-wp' ),
        ) );
        
        register_setting( 'qaw_settings', 'qaw_log_retention_days', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 30,
        ) );
        
        register_setting( 'qaw_settings', 'qaw_enable_logging', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ) );
    }

    /**
     * Render main page
     *
     * @since 1.0.0
     */
    public function render_main_page() {
        $action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $slug_id = isset( $_GET['slug_id'] ) ? absint( $_GET['slug_id'] ) : 0;

        if ( 'edit' === $action && $slug_id > 0 ) {
            $this->render_edit_page( $slug_id );
            return;
        }

        if ( 'new' === $action ) {
            $this->render_new_page();
            return;
        }

        $this->render_list_page();
    }

    /**
     * Render list page
     *
     * @since 1.0.0
     */
    private function render_list_page() {
        $per_page     = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        $slugs = QAW_Database::get_all_slugs( array(
            'per_page' => $per_page,
            'page'     => $current_page,
            'status'   => $status,
            'search'   => $search,
        ) );

        $total_items = QAW_Database::get_slugs_count( $status );
        $total_pages = ceil( $total_items / $per_page );
        $stats       = QAW_Database::get_statistics();

        ?>
        <div class="wrap qaw-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Access Links', 'quickaccess-wp' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=new' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Add New', 'quickaccess-wp' ); ?>
            </a>
            <hr class="wp-header-end">

            <div class="qaw-stats">
                <div class="qaw-stat-card">
                    <span class="qaw-stat-number"><?php echo esc_html( $stats['total_slugs'] ); ?></span>
                    <span class="qaw-stat-label"><?php esc_html_e( 'Total Links', 'quickaccess-wp' ); ?></span>
                </div>
                <div class="qaw-stat-card">
                    <span class="qaw-stat-number"><?php echo esc_html( $stats['active_slugs'] ); ?></span>
                    <span class="qaw-stat-label"><?php esc_html_e( 'Active', 'quickaccess-wp' ); ?></span>
                </div>
                <div class="qaw-stat-card">
                    <span class="qaw-stat-number"><?php echo esc_html( $stats['total_logins'] ); ?></span>
                    <span class="qaw-stat-label"><?php esc_html_e( 'Total Logins', 'quickaccess-wp' ); ?></span>
                </div>
                <div class="qaw-stat-card">
                    <span class="qaw-stat-number"><?php echo esc_html( $stats['logins_today'] ); ?></span>
                    <span class="qaw-stat-label"><?php esc_html_e( 'Logins Today', 'quickaccess-wp' ); ?></span>
                </div>
            </div>

            <div class="qaw-filters">
                <ul class="subsubsub">
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp' ) ); ?>" 
                           <?php echo empty( $status ) ? 'class="current"' : ''; ?>>
                            <?php esc_html_e( 'All', 'quickaccess-wp' ); ?>
                            <span class="count">(<?php echo esc_html( QAW_Database::get_slugs_count() ); ?>)</span>
                        </a> |
                    </li>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&status=active' ) ); ?>" 
                           <?php echo 'active' === $status ? 'class="current"' : ''; ?>>
                            <?php esc_html_e( 'Active', 'quickaccess-wp' ); ?>
                            <span class="count">(<?php echo esc_html( QAW_Database::get_slugs_count( 'active' ) ); ?>)</span>
                        </a> |
                    </li>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&status=inactive' ) ); ?>" 
                           <?php echo 'inactive' === $status ? 'class="current"' : ''; ?>>
                            <?php esc_html_e( 'Inactive', 'quickaccess-wp' ); ?>
                            <span class="count">(<?php echo esc_html( QAW_Database::get_slugs_count( 'inactive' ) ); ?>)</span>
                        </a>
                    </li>
                </ul>

                <form method="get" class="qaw-search-form">
                    <input type="hidden" name="page" value="quickaccess-wp">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" 
                           placeholder="<?php esc_attr_e( 'Search links...', 'quickaccess-wp' ); ?>">
                    <button type="submit" class="button"><?php esc_html_e( 'Search', 'quickaccess-wp' ); ?></button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped qaw-table">
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
                            <td colspan="7" class="qaw-no-items">
                                <?php esc_html_e( 'No access links found.', 'quickaccess-wp' ); ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=new' ) ); ?>">
                                    <?php esc_html_e( 'Create one now', 'quickaccess-wp' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $slugs as $slug ) : ?>
                            <?php
                            // Direct URL without prefix
                            $access_url = trailingslashit( home_url() ) . $slug->slug;
                            $is_expired = $slug->expires_at && strtotime( $slug->expires_at ) < current_time( 'timestamp' );
                            $is_maxed   = $slug->max_uses > 0 && $slug->current_uses >= $slug->max_uses;
                            $is_active  = $slug->is_active && ! $is_expired && ! $is_maxed;
                            ?>
                            <tr data-id="<?php echo esc_attr( $slug->id ); ?>">
                                <td class="column-slug">
                                    <strong>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=edit&slug_id=' . $slug->id ) ); ?>">
                                            <?php echo esc_html( $slug->slug ); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td class="column-url">
                                    <code class="qaw-url"><?php echo esc_url( $access_url ); ?></code>
                                    <button type="button" class="button button-small qaw-copy-btn" 
                                            data-url="<?php echo esc_url( $access_url ); ?>"
                                            title="<?php esc_attr_e( 'Copy to clipboard', 'quickaccess-wp' ); ?>">
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </button>
                                </td>
                                <td class="column-user">
                                    <?php if ( $slug->user_display_name ) : ?>
                                        <?php echo esc_html( $slug->user_display_name ); ?>
                                        <br><small><?php echo esc_html( $slug->user_email ); ?></small>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Unknown', 'quickaccess-wp' ); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td class="column-usage">
                                    <?php
                                    if ( $slug->max_uses > 0 ) {
                                        echo esc_html( $slug->current_uses . ' / ' . $slug->max_uses );
                                    } else {
                                        echo esc_html( $slug->current_uses . ' / ∞' );
                                    }
                                    ?>
                                </td>
                                <td class="column-expires">
                                    <?php
                                    if ( $slug->expires_at ) {
                                        $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                                        echo esc_html( wp_date( $date_format, strtotime( $slug->expires_at ) ) );
                                        if ( $is_expired ) {
                                            echo ' <span class="qaw-badge qaw-badge-expired">' . esc_html__( 'Expired', 'quickaccess-wp' ) . '</span>';
                                        }
                                    } else {
                                        esc_html_e( 'Never', 'quickaccess-wp' );
                                    }
                                    ?>
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
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp&action=edit&slug_id=' . $slug->id ) ); ?>" 
                                       class="button button-small" title="<?php esc_attr_e( 'Edit', 'quickaccess-wp' ); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <button type="button" class="button button-small qaw-toggle-btn" 
                                            data-id="<?php echo esc_attr( $slug->id ); ?>" 
                                            data-active="<?php echo esc_attr( $slug->is_active ); ?>"
                                            title="<?php echo $slug->is_active ? esc_attr__( 'Disable', 'quickaccess-wp' ) : esc_attr__( 'Enable', 'quickaccess-wp' ); ?>">
                                        <span class="dashicons <?php echo $slug->is_active ? 'dashicons-hidden' : 'dashicons-visibility'; ?>"></span>
                                    </button>
                                    <button type="button" class="button button-small qaw-delete-btn" 
                                            data-id="<?php echo esc_attr( $slug->id ); ?>"
                                            title="<?php esc_attr_e( 'Delete', 'quickaccess-wp' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo wp_kses_post( paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ) ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render new slug page
     *
     * @since 1.0.0
     */
    private function render_new_page() {
        $users = get_users( array(
            'fields'  => array( 'ID', 'display_name', 'user_email' ),
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ) );

        ?>
        <div class="wrap qaw-wrap">
            <h1><?php esc_html_e( 'Add New Access Link', 'quickaccess-wp' ); ?></h1>
            <hr class="wp-header-end">

            <form id="qaw-new-slug-form" class="qaw-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="slug"><?php esc_html_e( 'Slug', 'quickaccess-wp' ); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <div class="qaw-slug-input">
                                <input type="text" name="slug" id="slug" class="regular-text" required 
                                       pattern="[a-zA-Z0-9\-_]+" autocomplete="off">
                                <button type="button" id="generate-slug" class="button">
                                    <span class="dashicons dashicons-randomize"></span>
                                    <?php esc_html_e( 'Generate', 'quickaccess-wp' ); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Only letters, numbers, hyphens, and underscores allowed. Must not conflict with existing pages, posts, or WordPress reserved slugs.', 'quickaccess-wp' ); ?>
                            </p>
                            <p class="qaw-url-preview">
                                <?php esc_html_e( 'Access URL:', 'quickaccess-wp' ); ?>
                                <code><?php echo esc_url( trailingslashit( home_url() ) ); ?><span id="slug-preview">your-slug</span></code>
                            </p>
                            <p id="slug-status" class="qaw-slug-status"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php esc_html_e( 'User', 'quickaccess-wp' ); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select name="user_id" id="user_id" class="regular-text" required>
                                <option value=""><?php esc_html_e( '— Select User —', 'quickaccess-wp' ); ?></option>
                                <?php foreach ( $users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>">
                                        <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Select the user who will be logged in when this link is accessed.', 'quickaccess-wp' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="redirect_url"><?php esc_html_e( 'Redirect URL', 'quickaccess-wp' ); ?></label>
                        </th>
                        <td>
                            <input type="url" name="redirect_url" id="redirect_url" class="regular-text" 
                                   placeholder="<?php echo esc_url( home_url() ); ?>">
                            <p class="description">
                                <?php esc_html_e( 'Where to redirect after login. Leave empty for default.', 'quickaccess-wp' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_uses"><?php esc_html_e( 'Max Uses', 'quickaccess-wp' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_uses" id="max_uses" class="small-text" min="0" value="0">
                            <p class="description">
                                <?php esc_html_e( 'Maximum number of times this link can be used. Set to 0 for unlimited.', 'quickaccess-wp' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expires_at"><?php esc_html_e( 'Expiration', 'quickaccess-wp' ); ?></label>
                        </th>
                        <td>
                            <input type="datetime-local" name="expires_at" id="expires_at">
                            <p class="description">
                                <?php esc_html_e( 'When this link should expire. Leave empty for no expiration.', 'quickaccess-wp' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php wp_nonce_field( 'qaw_admin_nonce', 'qaw_nonce' ); ?>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php esc_html_e( 'Create Access Link', 'quickaccess-wp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp' ) ); ?>" class="button button-large">
                        <?php esc_html_e( 'Cancel', 'quickaccess-wp' ); ?>
                    </a>
                </p>

                <div id="qaw-form-message"></div>
            </form>
        </div>
        <?php
    }

    /**
     * Render edit slug page
     *
     * @since 1.0.0
     * @param int $slug_id Slug ID to edit.
     */
    private function render_edit_page( $slug_id ) {
        $slug = QAW_Database::get_slug_by_id( $slug_id );

        if ( ! $slug ) {
            wp_die( esc_html__( 'Access link not found.', 'quickaccess-wp' ) );
        }

        $users      = get_users( array(
            'fields'  => array( 'ID', 'display_name', 'user_email' ),
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ) );
        $expires_at = $slug->expires_at ? wp_date( 'Y-m-d\TH:i', strtotime( $slug->expires_at ) ) : '';
        $access_url = trailingslashit( home_url() ) . $slug->slug;

        ?>
        <div class="wrap qaw-wrap">
            <h1><?php esc_html_e( 'Edit Access Link', 'quickaccess-wp' ); ?></h1>
            <hr class="wp-header-end">

            <form id="qaw-edit-slug-form" class="qaw-form" data-id="<?php echo esc_attr( $slug->id ); ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="slug"><?php esc_html_e( 'Slug', 'quickaccess-wp' ); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="slug" id="slug" class="regular-text" 
                                   value="<?php echo esc_attr( $slug->slug ); ?>" required pattern="[a-zA-Z0-9\-_]+">
                            <p class="description">
                                <?php esc_html_e( 'Access URL:', 'quickaccess-wp' ); ?>
                                <code id="edit-url-preview"><?php echo esc_url( $access_url ); ?></code>
                                <button type="button" class="button button-small qaw-copy-btn" 
                                        data-url="<?php echo esc_url( $access_url ); ?>">
                                    <?php esc_html_e( 'Copy', 'quickaccess-wp' ); ?>
                                </button>
                            </p>
                            <p id="slug-status" class="qaw-slug-status"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php esc_html_e( 'User', 'quickaccess-wp' ); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select name="user_id" id="user_id" class="regular-text" required>
                                <option value=""><?php esc_html_e( '— Select User —', 'quickaccess-wp' ); ?></option>
                                <?php foreach ( $users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $slug->user_id, $user->ID ); ?>>
                                        <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="redirect_url"><?php esc_html_e( 'Redirect URL', 'quickaccess-wp' ); ?></label>
                        </th>
                        <td>
                            <input type="url" name="redirect_url" id="redirect_url" class="regular-text" 
                                   value="<?php echo esc_url( $slug->redirect_url ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_uses"><?php esc_html_e( 'Max Uses', 'quickaccess-wp' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_uses" id="max_uses" class="small-text" min="0" 
                                   value="<?php echo esc_attr( $slug->max_uses ); ?>">
                            <span class="description">
                                <?php
                                printf(
                                    /* translators: %d: current usage count */
                                    esc_html__( 'Current uses: %d', 'quickaccess-wp' ),
                                    $slug->current_uses
                                );
                                ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expires_at"><?php esc_html_e( 'Expiration', 'quickaccess-wp' ); ?></label>
                        </th>
                        <td>
                            <input type="datetime-local" name="expires_at" id="expires_at" 
                                   value="<?php echo esc_attr( $expires_at ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status', 'quickaccess-wp' ); ?></th>
                        <td>
                            <label class="qaw-toggle">
                                <input type="checkbox" name="is_active" value="1" <?php checked( $slug->is_active, 1 ); ?>>
                                <span class="qaw-toggle-slider"></span>
                                <span class="qaw-toggle-label"><?php esc_html_e( 'Active', 'quickaccess-wp' ); ?></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Created', 'quickaccess-wp' ); ?></th>
                        <td>
                            <?php
                            $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                            echo esc_html( wp_date( $date_format, strtotime( $slug->created_at ) ) );
                            ?>
                        </td>
                    </tr>
                </table>

                <?php wp_nonce_field( 'qaw_admin_nonce', 'qaw_nonce' ); ?>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php esc_html_e( 'Update Access Link', 'quickaccess-wp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp' ) ); ?>" class="button button-large">
                        <?php esc_html_e( 'Cancel', 'quickaccess-wp' ); ?>
                    </a>
                    <button type="button" class="button button-large button-link-delete qaw-delete-btn" 
                            data-id="<?php echo esc_attr( $slug->id ); ?>" style="float: right;">
                        <?php esc_html_e( 'Delete', 'quickaccess-wp' ); ?>
                    </button>
                </p>

                <div id="qaw-form-message"></div>
            </form>
        </div>
        <?php
    }

    /**
     * Render logs page
     *
     * @since 1.0.0
     */
    public function render_logs_page() {
        $per_page     = 50;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $slug_id      = isset( $_GET['slug_id'] ) ? absint( $_GET['slug_id'] ) : 0;
        $status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

        $logs        = QAW_Database::get_logs( array(
            'per_page' => $per_page,
            'page'     => $current_page,
            'slug_id'  => $slug_id,
            'status'   => $status,
        ) );
        $total_items = QAW_Database::get_logs_count( $slug_id );
        $total_pages = ceil( $total_items / $per_page );

        ?>
        <div class="wrap qaw-wrap">
            <h1><?php esc_html_e( 'Access Logs', 'quickaccess-wp' ); ?></h1>
            <hr class="wp-header-end">

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

            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs' ) ); ?>" 
                       <?php echo empty( $status ) ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'All', 'quickaccess-wp' ); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&status=success' ) ); ?>" 
                       <?php echo 'success' === $status ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'Successful', 'quickaccess-wp' ); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&status=denied' ) ); ?>" 
                       <?php echo 'denied' === $status ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'Denied', 'quickaccess-wp' ); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&status=invalid' ) ); ?>" 
                       <?php echo 'invalid' === $status ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'Invalid', 'quickaccess-wp' ); ?>
                    </a>
                </li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-date"><?php esc_html_e( 'Date', 'quickaccess-wp' ); ?></th>
                        <th class="column-slug"><?php esc_html_e( 'Slug', 'quickaccess-wp' ); ?></th>
                        <th class="column-ip"><?php esc_html_e( 'IP Address', 'quickaccess-wp' ); ?></th>
                        <th class="column-status"><?php esc_html_e( 'Status', 'quickaccess-wp' ); ?></th>
                        <th class="column-message"><?php esc_html_e( 'Details', 'quickaccess-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $logs ) ) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No logs found.', 'quickaccess-wp' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td class="column-date">
                                    <?php
                                    $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                                    echo esc_html( wp_date( $date_format, strtotime( $log->created_at ) ) );
                                    ?>
                                </td>
                                <td class="column-slug">
                                    <?php if ( $log->slug_name ) : ?>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=qaw-logs&slug_id=' . $log->slug_id ) ); ?>">
                                            <?php echo esc_html( $log->slug_name ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'N/A', 'quickaccess-wp' ); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td class="column-ip">
                                    <code><?php echo esc_html( $log->ip_address ); ?></code>
                                </td>
                                <td class="column-status">
                                    <span class="qaw-badge qaw-badge-<?php echo esc_attr( $log->status ); ?>">
                                        <?php echo esc_html( ucfirst( $log->status ) ); ?>
                                    </span>
                                </td>
                                <td class="column-message">
                                    <?php echo esc_html( $log->message ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo wp_kses_post( paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ) ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        ?>
        <div class="wrap qaw-wrap">
            <h1><?php esc_html_e( 'QuickAccess Settings', 'quickaccess-wp' ); ?></h1>
            <hr class="wp-header-end">

            <form method="post" action="options.php">
                <?php settings_fields( 'qaw_settings' ); ?>

                <div class="qaw-settings-section">
                    <h2><?php esc_html_e( 'Security Settings', 'quickaccess-wp' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure rate limiting to protect against brute-force attacks.', 'quickaccess-wp' ); ?>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="qaw_rate_limit_attempts">
                                    <?php esc_html_e( 'Max Failed Attempts', 'quickaccess-wp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" name="qaw_rate_limit_attempts" id="qaw_rate_limit_attempts" 
                                       value="<?php echo esc_attr( get_option( 'qaw_rate_limit_attempts', 5 ) ); ?>" 
                                       min="1" max="100" class="small-text">
                                <p class="description">
                                    <?php esc_html_e( 'Number of failed attempts before blocking an IP.', 'quickaccess-wp' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="qaw_rate_limit_window">
                                    <?php esc_html_e( 'Time Window (minutes)', 'quickaccess-wp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" name="qaw_rate_limit_window" id="qaw_rate_limit_window" 
                                       value="<?php echo esc_attr( get_option( 'qaw_rate_limit_window', 15 ) ); ?>" 
                                       min="1" max="1440" class="small-text">
                                <p class="description">
                                    <?php esc_html_e( 'Time window for counting failed attempts.', 'quickaccess-wp' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="qaw_block_duration">
                                    <?php esc_html_e( 'Block Duration (minutes)', 'quickaccess-wp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" name="qaw_block_duration" id="qaw_block_duration" 
                                       value="<?php echo esc_attr( get_option( 'qaw_block_duration', 60 ) ); ?>" 
                                       min="1" max="10080" class="small-text">
                                <p class="description">
                                    <?php esc_html_e( 'How long to block an IP after exceeding the limit.', 'quickaccess-wp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="qaw-settings-section">
                    <h2><?php esc_html_e( 'General Settings', 'quickaccess-wp' ); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="qaw_default_redirect">
                                    <?php esc_html_e( 'Default Redirect URL', 'quickaccess-wp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="url" name="qaw_default_redirect" id="qaw_default_redirect" 
                                       value="<?php echo esc_url( get_option( 'qaw_default_redirect', home_url() ) ); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php esc_html_e( 'Default redirect URL when no custom URL is set for a slug.', 'quickaccess-wp' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="qaw_invalid_slug_message">
                                    <?php esc_html_e( 'Invalid Link Message', 'quickaccess-wp' ); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="qaw_invalid_slug_message" id="qaw_invalid_slug_message" 
                                          class="large-text" rows="3"><?php 
                                    echo esc_textarea( get_option( 
                                        'qaw_invalid_slug_message', 
                                        __( 'This access link is invalid or has expired.', 'quickaccess-wp' ) 
                                    ) ); 
                                ?></textarea>
                                <p class="description">
                                    <?php esc_html_e( 'Message shown when an invalid or expired link is accessed.', 'quickaccess-wp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="qaw-settings-section">
                    <h2><?php esc_html_e( 'Logging Settings', 'quickaccess-wp' ); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Logging', 'quickaccess-wp' ); ?></th>
                            <td>
                                <label class="qaw-toggle">
                                    <input type="checkbox" name="qaw_enable_logging" value="1" 
                                           <?php checked( get_option( 'qaw_enable_logging', 1 ), 1 ); ?>>
                                    <span class="qaw-toggle-slider"></span>
                                    <span class="qaw-toggle-label"><?php esc_html_e( 'Log all access attempts', 'quickaccess-wp' ); ?></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="qaw_log_retention_days">
                                    <?php esc_html_e( 'Log Retention (days)', 'quickaccess-wp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" name="qaw_log_retention_days" id="qaw_log_retention_days" 
                                       value="<?php echo esc_attr( get_option( 'qaw_log_retention_days', 30 ) ); ?>" 
                                       min="1" max="365" class="small-text">
                                <p class="description">
                                    <?php esc_html_e( 'Logs older than this will be automatically deleted.', 'quickaccess-wp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX: Create slug
     *
     * @since 1.0.0
     */
    public function ajax_create_slug() {
        check_ajax_referer( 'qaw_admin_nonce', 'nonce' );

        if ( ! QAW_Security::current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

        if ( empty( $slug ) ) {
            wp_send_json_error( array( 'message' => __( 'Slug is required.', 'quickaccess-wp' ) ) );
        }

        // Check for conflicts with WordPress content
        if ( QAW_Database::slug_conflicts_with_wp( $slug ) ) {
            wp_send_json_error( array( 
                'message' => __( 'This slug conflicts with existing WordPress content (page, post, category, etc.).', 'quickaccess-wp' ) 
            ) );
        }

        if ( QAW_Database::slug_exists( $slug ) ) {
            wp_send_json_error( array( 'message' => __( 'This slug already exists.', 'quickaccess-wp' ) ) );
        }

        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        
        if ( ! $user_id || ! get_user_by( 'ID', $user_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a valid user.', 'quickaccess-wp' ) ) );
        }

        $data = array(
            'slug'         => $slug,
            'user_id'      => $user_id,
            'redirect_url' => isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '',
            'max_uses'     => isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0,
            'expires_at'   => isset( $_POST['expires_at'] ) && ! empty( $_POST['expires_at'] ) 
                ? sanitize_text_field( wp_unslash( $_POST['expires_at'] ) ) 
                : null,
        );

        $result = QAW_Database::create_slug( $data );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Access link created successfully!', 'quickaccess-wp' ),
                'id'      => $result,
                'url'     => trailingslashit( home_url() ) . $slug,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to create access link. The slug may conflict with existing content.', 'quickaccess-wp' ) ) );
        }
    }

    /**
     * AJAX: Update slug
     *
     * @since 1.0.0
     */
    public function ajax_update_slug() {
        check_ajax_referer( 'qaw_admin_nonce', 'nonce' );

        if ( ! QAW_Security::current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

        if ( ! $id || empty( $slug ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'quickaccess-wp' ) ) );
        }

        // Check for conflicts
        if ( QAW_Database::slug_conflicts_with_wp( $slug ) ) {
            wp_send_json_error( array( 
                'message' => __( 'This slug conflicts with existing WordPress content.', 'quickaccess-wp' ) 
            ) );
        }

        if ( QAW_Database::slug_exists( $slug, $id ) ) {
            wp_send_json_error( array( 'message' => __( 'This slug already exists.', 'quickaccess-wp' ) ) );
        }

        $data = array(
            'slug'         => $slug,
            'user_id'      => isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0,
            'redirect_url' => isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '',
            'max_uses'     => isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0,
            'expires_at'   => isset( $_POST['expires_at'] ) && ! empty( $_POST['expires_at'] ) 
                ? sanitize_text_field( wp_unslash( $_POST['expires_at'] ) ) 
                : null,
            'is_active'    => isset( $_POST['is_active'] ) ? 1 : 0,
        );

        $result = QAW_Database::update_slug( $id, $data );

        if ( false !== $result ) {
            wp_send_json_success( array( 
                'message' => __( 'Access link updated successfully!', 'quickaccess-wp' ),
                'url'     => trailingslashit( home_url() ) . $slug,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update access link.', 'quickaccess-wp' ) ) );
        }
    }

    /**
     * AJAX: Delete slug
     *
     * @since 1.0.0
     */
    public function ajax_delete_slug() {
        check_ajax_referer( 'qaw_admin_nonce', 'nonce' );

        if ( ! QAW_Security::current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'quickaccess-wp' ) ) );
        }

        $result = QAW_Database::delete_slug( $id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Access link deleted successfully!', 'quickaccess-wp' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete access link.', 'quickaccess-wp' ) ) );
        }
    }

    /**
     * AJAX: Toggle slug status
     *
     * @since 1.0.0
     */
    public function ajax_toggle_slug() {
        check_ajax_referer( 'qaw_admin_nonce', 'nonce' );

        if ( ! QAW_Security::current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $active = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'quickaccess-wp' ) ) );
        }

        $new_status = $active ? 0 : 1;
        $result     = QAW_Database::update_slug( $id, array( 'is_active' => $new_status ) );

        if ( false !== $result ) {
            wp_send_json_success( array(
                'message'   => $new_status ? __( 'Access link enabled.', 'quickaccess-wp' ) : __( 'Access link disabled.', 'quickaccess-wp' ),
                'is_active' => $new_status,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update status.', 'quickaccess-wp' ) ) );
        }
    }

    /**
     * AJAX: Generate random slug
     *
     * @since 1.0.0
     */
    public function ajax_generate_slug() {
        check_ajax_referer( 'qaw_admin_nonce', 'nonce' );

        if ( ! QAW_Security::current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $slug = QAW_Security::generate_random_slug( 12 );

        // Make sure it doesn't conflict with WordPress
        $attempts = 0;
        while ( QAW_Database::slug_conflicts_with_wp( $slug ) && $attempts < 10 ) {
            $slug = QAW_Security::generate_random_slug( 12 );
            $attempts++;
        }

        wp_send_json_success( array( 'slug' => $slug ) );
    }

    /**
     * AJAX: Check slug availability
     *
     * @since 1.0.0
     */
    public function ajax_check_slug() {
        check_ajax_referer( 'qaw_admin_nonce', 'nonce' );

        if ( ! QAW_Security::current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'quickaccess-wp' ) ) );
        }

        $slug       = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
        $exclude_id = isset( $_POST['exclude_id'] ) ? absint( $_POST['exclude_id'] ) : 0;

        if ( empty( $slug ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a slug.', 'quickaccess-wp' ) ) );
        }

        // Check WordPress conflicts
        if ( QAW_Database::slug_conflicts_with_wp( $slug ) ) {
            wp_send_json_error( array( 
                'message'   => __( 'This slug conflicts with existing WordPress content.', 'quickaccess-wp' ),
                'available' => false,
            ) );
        }

        // Check if already used
        if ( QAW_Database::slug_exists( $slug, $exclude_id ) ) {
            wp_send_json_error( array( 
                'message'   => __( 'This slug is already in use.', 'quickaccess-wp' ),
                'available' => false,
            ) );
        }

        wp_send_json_success( array( 
            'message'   => __( 'Slug is available!', 'quickaccess-wp' ),
            'available' => true,
        ) );
    }

    /**
     * Daily cleanup task
     *
     * @since 1.0.0
     */
    public function daily_cleanup() {
        QAW_Database::clean_old_logs();
        QAW_Security::cleanup_rate_limits();
    }
}

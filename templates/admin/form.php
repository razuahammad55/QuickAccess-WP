<?php
/**
 * Admin Form Template
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_edit = ! empty( $slug );
$page_title = $is_edit ? __( 'Edit Access Link', 'quickaccess-wp' ) : __( 'Add New Access Link', 'quickaccess-wp' );
$expires_value = $is_edit && $slug->expires_at ? wp_date( 'Y-m-d\TH:i', strtotime( $slug->expires_at ) ) : '';
?>

<div class="wrap qaw-wrap">
    <h1><?php echo esc_html( $page_title ); ?></h1>

    <div class="qaw-form-wrap">
        <form id="qaw-form" data-id="<?php echo $is_edit ? esc_attr( $slug->id ) : ''; ?>">
            
            <!-- Basic Settings -->
            <div class="qaw-card">
                <div class="qaw-card-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <?php esc_html_e( 'Link Settings', 'quickaccess-wp' ); ?>
                    </h2>
                </div>
                <div class="qaw-card-body">
                    
                    <!-- Slug -->
                    <div class="qaw-form-row">
                        <label for="qaw-slug">
                            <?php esc_html_e( 'Slug', 'quickaccess-wp' ); ?>
                            <span class="required">*</span>
                        </label>
                        <div class="qaw-slug-input-group">
                            <input type="text" id="qaw-slug" name="slug" value="<?php echo $is_edit ? esc_attr( $slug->slug ) : ''; ?>" required pattern="[a-zA-Z0-9\-_]+" autocomplete="off">
                            <button type="button" id="qaw-generate-slug" class="qaw-btn qaw-btn-secondary qaw-btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <?php esc_html_e( 'Generate', 'quickaccess-wp' ); ?>
                            </button>
                        </div>
                        <div class="qaw-url-preview">
                            <?php esc_html_e( 'Access URL:', 'quickaccess-wp' ); ?>
                            <code><?php echo esc_url( home_url( '/' ) ); ?><span id="slug-preview"><?php echo $is_edit ? esc_html( $slug->slug ) : 'your-slug'; ?></span></code>
                        </div>
                        <div id="slug-status" class="qaw-slug-status"></div>
                        <p class="description"><?php esc_html_e( 'Only letters, numbers, hyphens, and underscores. Must not conflict with existing pages or posts.', 'quickaccess-wp' ); ?></p>
                    </div>

                    <!-- User -->
                    <div class="qaw-form-row">
                        <label for="qaw-user">
                            <?php esc_html_e( 'User', 'quickaccess-wp' ); ?>
                            <span class="required">*</span>
                        </label>
                        <select id="qaw-user" name="user_id" required>
                            <option value=""><?php esc_html_e( '— Select User —', 'quickaccess-wp' ); ?></option>
                            <?php foreach ( $users as $user ) : ?>
                                <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $is_edit ? $slug->user_id : 0, $user->ID ); ?>>
                                    <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'The user who will be logged in when this link is accessed.', 'quickaccess-wp' ); ?></p>
                    </div>

                    <!-- Redirect URL -->
                    <div class="qaw-form-row">
                        <label for="qaw-redirect"><?php esc_html_e( 'Redirect URL', 'quickaccess-wp' ); ?></label>
                        <input type="url" id="qaw-redirect" name="redirect_url" value="<?php echo $is_edit ? esc_url( $slug->redirect_url ) : ''; ?>" placeholder="<?php echo esc_url( home_url() ); ?>">
                        <p class="description"><?php esc_html_e( 'Where to redirect after login. Leave empty for default homepage.', 'quickaccess-wp' ); ?></p>
                    </div>

                </div>
            </div>

            <!-- Restrictions -->
            <div class="qaw-card">
                <div class="qaw-card-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <?php esc_html_e( 'Restrictions', 'quickaccess-wp' ); ?>
                    </h2>
                </div>
                <div class="qaw-card-body">
                    
                    <!-- Max Uses -->
                    <div class="qaw-form-row">
                        <label for="qaw-max-uses"><?php esc_html_e( 'Maximum Uses', 'quickaccess-wp' ); ?></label>
                        <input type="number" id="qaw-max-uses" name="max_uses" value="<?php echo $is_edit ? esc_attr( $slug->max_uses ) : '0'; ?>" min="0" style="max-width: 150px;">
                        <?php if ( $is_edit ) : ?>
                            <span class="description" style="margin-left: 10px;">
                                <?php printf( esc_html__( 'Current uses: %d', 'quickaccess-wp' ), $slug->current_uses ); ?>
                            </span>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Maximum number of times this link can be used. Set to 0 for unlimited.', 'quickaccess-wp' ); ?></p>
                    </div>

                    <!-- Expiration -->
                    <div class="qaw-form-row">
                        <label for="qaw-expires"><?php esc_html_e( 'Expiration Date', 'quickaccess-wp' ); ?></label>
                        <input type="datetime-local" id="qaw-expires" name="expires_at" value="<?php echo esc_attr( $expires_value ); ?>" style="max-width: 250px;">
                        <p class="description"><?php esc_html_e( 'When this link should expire. Leave empty for no expiration.', 'quickaccess-wp' ); ?></p>
                    </div>

                    <?php if ( $is_edit ) : ?>
                        <!-- Status -->
                        <div class="qaw-form-row">
                            <label><?php esc_html_e( 'Status', 'quickaccess-wp' ); ?></label>
                            <label class="qaw-toggle">
                                <input type="checkbox" id="qaw-active" name="is_active" value="1" <?php checked( $slug->is_active, 1 ); ?>>
                                <span class="qaw-toggle-slider"></span>
                                <span class="qaw-toggle-label"><?php esc_html_e( 'Active', 'quickaccess-wp' ); ?></span>
                            </label>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Form Actions -->
            <div class="qaw-form-actions">
                <button type="submit" class="qaw-btn qaw-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <?php echo $is_edit ? esc_html__( 'Update Link', 'quickaccess-wp' ) : esc_html__( 'Create Link', 'quickaccess-wp' ); ?>
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=quickaccess-wp' ) ); ?>" class="qaw-btn qaw-btn-secondary">
                    <?php esc_html_e( 'Cancel', 'quickaccess-wp' ); ?>
                </a>
                <?php if ( $is_edit ) : ?>
                    <button type="button" class="qaw-btn qaw-btn-danger qaw-delete-btn" data-id="<?php echo esc_attr( $slug->id ); ?>" style="margin-left: auto;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <?php esc_html_e( 'Delete', 'quickaccess-wp' ); ?>
                    </button>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<?php
/**
 * Admin Settings Template
 * @package QuickAccessWP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap qaw-wrap">
    <h1><?php esc_html_e( 'QuickAccess Settings', 'quickaccess-wp' ); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'qaw_settings' ); ?>

        <div class="qaw-form-wrap" style="max-width: 900px;">
            
            <!-- Security Settings -->
            <div class="qaw-card">
                <div class="qaw-card-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <?php esc_html_e( 'Security Settings', 'quickaccess-wp' ); ?>
                    </h2>
                </div>
                <div class="qaw-card-body">
                    <div class="qaw-settings-grid">
                        <div class="qaw-form-row">
                            <label for="qaw_rate_limit_attempts"><?php esc_html_e( 'Max Failed Attempts', 'quickaccess-wp' ); ?></label>
                            <input type="number" id="qaw_rate_limit_attempts" name="qaw_rate_limit_attempts" value="<?php echo esc_attr( get_option( 'qaw_rate_limit_attempts', 5 ) ); ?>" min="1" max="100" style="max-width: 100px;">
                            <p class="description"><?php esc_html_e( 'Number of failed attempts before blocking.', 'quickaccess-wp' ); ?></p>
                        </div>

                        <div class="qaw-form-row">
                            <label for="qaw_rate_limit_window"><?php esc_html_e( 'Time Window (minutes)', 'quickaccess-wp' ); ?></label>
                            <input type="number" id="qaw_rate_limit_window" name="qaw_rate_limit_window" value="<?php echo esc_attr( get_option( 'qaw_rate_limit_window', 15 ) ); ?>" min="1" max="1440" style="max-width: 100px;">
                            <p class="description"><?php esc_html_e( 'Time window for counting attempts.', 'quickaccess-wp' ); ?></p>
                        </div>

                        <div class="qaw-form-row">
                            <label for="qaw_block_duration"><?php esc_html_e( 'Block Duration (minutes)', 'quickaccess-wp' ); ?></label>
                            <input type="number" id="qaw_block_duration" name="qaw_block_duration" value="<?php echo esc_attr( get_option( 'qaw_block_duration', 60 ) ); ?>" min="1" max="10080" style="max-width: 100px;">
                            <p class="description"><?php esc_html_e( 'How long to block after limit exceeded.', 'quickaccess-wp' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Settings -->
            <div class="qaw-card">
                <div class="qaw-card-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <?php esc_html_e( 'General Settings', 'quickaccess-wp' ); ?>
                    </h2>
                </div>
                <div class="qaw-card-body">
                    <div class="qaw-form-row">
                        <label for="qaw_default_redirect"><?php esc_html_e( 'Default Redirect URL', 'quickaccess-wp' ); ?></label>
                        <input type="url" id="qaw_default_redirect" name="qaw_default_redirect" value="<?php echo esc_url( get_option( 'qaw_default_redirect', home_url() ) ); ?>" placeholder="<?php echo esc_url( home_url() ); ?>">
                        <p class="description"><?php esc_html_e( 'Default redirect URL when no custom URL is set.', 'quickaccess-wp' ); ?></p>
                    </div>

                    <div class="qaw-form-row">
                        <label for="qaw_invalid_slug_message"><?php esc_html_e( 'Invalid Link Message', 'quickaccess-wp' ); ?></label>
                        <textarea id="qaw_invalid_slug_message" name="qaw_invalid_slug_message" rows="3"><?php echo esc_textarea( get_option( 'qaw_invalid_slug_message', __( 'This access link is invalid or has expired.', 'quickaccess-wp' ) ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Message shown when an invalid link is accessed.', 'quickaccess-wp' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Logging Settings -->
            <div class="qaw-card">
                <div class="qaw-card-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <?php esc_html_e( 'Logging Settings', 'quickaccess-wp' ); ?>
                    </h2>
                </div>
                <div class="qaw-card-body">
                    <div class="qaw-form-row">
                        <label><?php esc_html_e( 'Enable Logging', 'quickaccess-wp' ); ?></label>
                        <div class="qaw-switch-wrap">
                            <label class="qaw-switch">
                                <input type="checkbox" name="qaw_enable_logging" value="1" <?php checked( get_option( 'qaw_enable_logging', 1 ), 1 ); ?>>
                                <span class="qaw-slider"></span>
                            </label>
                            <span class="qaw-switch-label"><?php esc_html_e( 'Log all access attempts', 'quickaccess-wp' ); ?></span>
                        </div>
                    </div>

                    <div class="qaw-form-row">
                        <label for="qaw_log_retention_days"><?php esc_html_e( 'Log Retention (days)', 'quickaccess-wp' ); ?></label>
                        <input type="number" id="qaw_log_retention_days" name="qaw_log_retention_days" value="<?php echo esc_attr( get_option( 'qaw_log_retention_days', 30 ) ); ?>" min="1" max="365" style="max-width: 100px;">
                        <p class="description"><?php esc_html_e( 'Logs older than this will be automatically deleted.', 'quickaccess-wp' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Data Management -->
            <div class="qaw-card">
                <div class="qaw-card-header">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <?php esc_html_e( 'Data Management', 'quickaccess-wp' ); ?>
                    </h2>
                </div>
                <div class="qaw-card-body">
                    <div class="qaw-form-row">
                        <label><?php esc_html_e( 'Delete Data on Uninstall', 'quickaccess-wp' ); ?></label>
                        <div class="qaw-switch-wrap">
                            <label class="qaw-switch">
                                <input type="checkbox" name="qaw_delete_data_on_uninstall" value="1" <?php checked( get_option( 'qaw_delete_data_on_uninstall', 0 ), 1 ); ?>>
                                <span class="qaw-slider"></span>
                            </label>
                            <span class="qaw-switch-label"><?php esc_html_e( 'Remove all plugin data when uninstalled', 'quickaccess-wp' ); ?></span>
                        </div>
                        <p class="description" style="color: var(--qaw-danger); margin-top: 10px;">
                            <strong><?php esc_html_e( 'Warning:', 'quickaccess-wp' ); ?></strong>
                            <?php esc_html_e( 'If enabled, all access links, logs, and settings will be permanently deleted when the plugin is uninstalled.', 'quickaccess-wp' ); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="qaw-form-actions" style="border-top: none; padding-top: 0;">
                <?php submit_button( __( 'Save Settings', 'quickaccess-wp' ), 'qaw-btn qaw-btn-primary', 'submit', false ); ?>
            </div>

        </div>
    </form>
</div>

<?php
/**
 * Invalid Slug Error Template
 *
 * This template is displayed when accessing an invalid, expired, or disabled link.
 * Override by copying to: yourtheme/qaw-invalid-slug.php
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$message = get_query_var( 'qaw_error_message', __( 'This access link is invalid or has expired.', 'quickaccess-wp' ) );
$site_name = get_bloginfo( 'name' );
$site_url = home_url( '/' );
$login_url = wp_login_url();
$is_logged_in = is_user_logged_in();
$custom_logo_id = get_theme_mod( 'custom_logo' );
$logo_url = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'medium' ) : '';

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php esc_html_e( 'Access Denied', 'quickaccess-wp' ); ?> — <?php echo esc_html( $site_name ); ?></title>
    
    <?php if ( function_exists( 'wp_site_icon' ) ) : ?>
        <?php wp_site_icon(); ?>
    <?php endif; ?>
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .qaw-container {
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .qaw-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .qaw-card-header {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            padding: 40px 40px 60px;
            text-align: center;
            position: relative;
        }

        .qaw-card-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 40px;
            background: #ffffff;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }

        .qaw-icon-wrapper {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
            backdrop-filter: blur(10px);
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
            }
        }

        .qaw-icon-wrapper svg {
            width: 45px;
            height: 45px;
            color: #ffffff;
            stroke-width: 1.5;
        }

        .qaw-card-header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .qaw-card-body {
            padding: 20px 40px 40px;
            text-align: center;
        }

        .qaw-message {
            font-size: 16px;
            color: var(--gray-600);
            margin-bottom: 32px;
            line-height: 1.7;
        }

        .qaw-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .qaw-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .qaw-btn svg {
            width: 20px;
            height: 20px;
        }

        .qaw-btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.4);
        }

        .qaw-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(99, 102, 241, 0.5);
        }

        .qaw-btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 2px solid var(--gray-200);
        }

        .qaw-btn-secondary:hover {
            background: var(--gray-200);
            border-color: var(--gray-300);
        }

        .qaw-divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: var(--gray-400);
            font-size: 13px;
        }

        .qaw-divider::before,
        .qaw-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }

        .qaw-divider::before {
            margin-right: 16px;
        }

        .qaw-divider::after {
            margin-left: 16px;
        }

        .qaw-footer {
            padding: 20px 40px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            text-align: center;
        }

        .qaw-footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .qaw-footer-logo:hover {
            color: var(--primary);
        }

        .qaw-footer-logo img {
            height: 28px;
            width: auto;
            border-radius: 6px;
        }

        .qaw-help-text {
            margin-top: 12px;
            font-size: 13px;
            color: var(--gray-400);
        }

        /* Error Code Badge */
        .qaw-error-code {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 12px;
            letter-spacing: 0.5px;
        }

        /* Responsive */
        @media (max-width: 540px) {
            body {
                padding: 16px;
            }

            .qaw-card-header {
                padding: 32px 24px 50px;
            }

            .qaw-card-body {
                padding: 16px 24px 32px;
            }

            .qaw-footer {
                padding: 16px 24px;
            }

            .qaw-icon-wrapper {
                width: 80px;
                height: 80px;
            }

            .qaw-icon-wrapper svg {
                width: 40px;
                height: 40px;
            }

            .qaw-card-header h1 {
                font-size: 24px;
            }

            .qaw-message {
                font-size: 15px;
            }

            .qaw-btn {
                padding: 14px 24px;
                font-size: 14px;
            }
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            }

            .qaw-card {
                background: var(--gray-900);
                box-shadow: 
                    0 25px 50px -12px rgba(0, 0, 0, 0.5),
                    0 0 0 1px rgba(255, 255, 255, 0.05);
            }

            .qaw-card-header::after {
                background: var(--gray-900);
            }

            .qaw-card-header h1 {
                color: #ffffff;
            }

            .qaw-message {
                color: var(--gray-400);
            }

            .qaw-btn-secondary {
                background: var(--gray-800);
                color: var(--gray-200);
                border-color: var(--gray-700);
            }

            .qaw-btn-secondary:hover {
                background: var(--gray-700);
                border-color: var(--gray-600);
            }

            .qaw-divider {
                color: var(--gray-600);
            }

            .qaw-divider::before,
            .qaw-divider::after {
                background: var(--gray-700);
            }

            .qaw-footer {
                background: var(--gray-800);
                border-top-color: var(--gray-700);
            }

            .qaw-footer-logo {
                color: var(--gray-400);
            }

            .qaw-help-text {
                color: var(--gray-500);
            }
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            .qaw-container {
                animation: none;
            }

            .qaw-icon-wrapper {
                animation: none;
            }

            .qaw-btn {
                transition: none;
            }
        }

        /* Print */
        @media print {
            body {
                background: #ffffff;
            }

            .qaw-card {
                box-shadow: none;
                border: 1px solid var(--gray-200);
            }

            .qaw-btn {
                border: 1px solid currentColor;
            }
        }
    </style>
</head>
<body>
    <div class="qaw-container">
        <div class="qaw-card">
            <!-- Header -->
            <div class="qaw-card-header">
                <div class="qaw-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1><?php esc_html_e( 'Access Denied', 'quickaccess-wp' ); ?></h1>
                <span class="qaw-error-code"><?php esc_html_e( 'Error 403', 'quickaccess-wp' ); ?></span>
            </div>

            <!-- Body -->
            <div class="qaw-card-body">
                <p class="qaw-message"><?php echo esc_html( $message ); ?></p>

                <div class="qaw-actions">
                    <a href="<?php echo esc_url( $site_url ); ?>" class="qaw-btn qaw-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <?php esc_html_e( 'Go to Homepage', 'quickaccess-wp' ); ?>
                    </a>

                    <?php if ( ! $is_logged_in ) : ?>
                        <div class="qaw-divider"><?php esc_html_e( 'or', 'quickaccess-wp' ); ?></div>
                        
                        <a href="<?php echo esc_url( $login_url ); ?>" class="qaw-btn qaw-btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <?php esc_html_e( 'Login to Your Account', 'quickaccess-wp' ); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( admin_url() ); ?>" class="qaw-btn qaw-btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <?php esc_html_e( 'Go to Dashboard', 'quickaccess-wp' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="qaw-footer">
                <a href="<?php echo esc_url( $site_url ); ?>" class="qaw-footer-logo">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
                    <?php else : ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    <?php endif; ?>
                    <?php echo esc_html( $site_name ); ?>
                </a>
                <p class="qaw-help-text">
                    <?php esc_html_e( 'If you believe this is an error, please contact the site administrator.', 'quickaccess-wp' ); ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

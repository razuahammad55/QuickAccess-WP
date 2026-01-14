<?php
/**
 * Invalid Slug Error Template
 *
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

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php esc_html_e( 'Access Denied', 'quickaccess-wp' ); ?> - <?php echo esc_html( $site_name ); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .qaw-error-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 460px;
            width: 100%;
            padding: 50px 40px;
            text-align: center;
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .qaw-error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(238, 90, 90, 0.4);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(238, 90, 90, 0);
            }
        }
        
        .qaw-error-icon svg {
            width: 40px;
            height: 40px;
            color: #ffffff;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        
        .qaw-error-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 15px;
        }
        
        .qaw-error-message {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 35px;
            padding: 0 10px;
        }
        
        .qaw-error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .qaw-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .qaw-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
        }
        
        .qaw-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: #ffffff;
        }
        
        .qaw-btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #e9ecef;
        }
        
        .qaw-btn-secondary:hover {
            background: #e9ecef;
            color: #212529;
        }
        
        .qaw-footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        
        .qaw-site-link {
            font-size: 14px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .qaw-site-link:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .qaw-error-container {
                padding: 40px 25px;
                margin: 10px;
            }
            
            .qaw-error-title {
                font-size: 24px;
            }
            
            .qaw-error-message {
                font-size: 15px;
            }
            
            .qaw-error-actions {
                flex-direction: column;
            }
            
            .qaw-btn {
                width: 100%;
            }
        }
        
        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            }
            
            .qaw-error-container {
                background: #1e1e2e;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            
            .qaw-error-title {
                color: #f8f9fa;
            }
            
            .qaw-error-message {
                color: #adb5bd;
            }
            
            .qaw-btn-secondary {
                background: #2d2d3d;
                color: #f8f9fa;
                border-color: #3d3d4d;
            }
            
            .qaw-btn-secondary:hover {
                background: #3d3d4d;
                color: #ffffff;
            }
            
            .qaw-footer {
                border-top-color: #3d3d4d;
            }
            
            .qaw-site-link {
                color: #a5b4fc;
            }
        }
    </style>
</head>
<body>
    <div class="qaw-error-container">
        <!-- Icon -->
        <div class="qaw-error-icon">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        
        <!-- Title -->
        <h1 class="qaw-error-title">
            <?php esc_html_e( 'Access Denied', 'quickaccess-wp' ); ?>
        </h1>
        
        <!-- Message -->
        <p class="qaw-error-message">
            <?php echo esc_html( $message ); ?>
        </p>
        
        <!-- Action Buttons -->
        <div class="qaw-error-actions">
            <a href="<?php echo esc_url( $site_url ); ?>" class="qaw-btn qaw-btn-primary">
                <?php esc_html_e( 'Go to Homepage', 'quickaccess-wp' ); ?>
            </a>
            
            <?php if ( ! $is_logged_in ) : ?>
                <a href="<?php echo esc_url( $login_url ); ?>" class="qaw-btn qaw-btn-secondary">
                    <?php esc_html_e( 'Login', 'quickaccess-wp' ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( admin_url() ); ?>" class="qaw-btn qaw-btn-secondary">
                    <?php esc_html_e( 'Dashboard', 'quickaccess-wp' ); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="qaw-footer">
            <a href="<?php echo esc_url( $site_url ); ?>" class="qaw-site-link">
                <?php echo esc_html( $site_name ); ?>
            </a>
        </div>
    </div>
</body>
</html>

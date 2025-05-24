<?php
/**
 * Plugin Name: Lili Business Banking
 * Description: A business checking account designed for e-commerce business owners with built-in accounting and tax preparation software.
 * Version: 1.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Author: Lili
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('LILI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LILI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LILI_API_URL', 'https://prod.lili.co/lili/wordpress');
define('LILI_WORDPRESS_CLIENT_ID', 'wordpress');
define('LILI_WORDPRESS_SECRET', 'KfKaQRqEZnQL8Mk6zZft');

// Include plugin files
require_once LILI_PLUGIN_PATH . 'includes/lili-admin.php';
require_once LILI_PLUGIN_PATH . 'includes/lili-api.php';
require_once LILI_PLUGIN_PATH . 'includes/lili-interface.php';

/**
 * Handle the login form submission and display the login form or user details.
 */
function lili_admin_page()
{
    // Handle logout
    if (isset($_POST['lili_logout_submit'])) {
        if (!isset($_POST['lili_logout_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['lili_logout_nonce'])),
                'lili_logout_action'
            )) {
            wp_die('Security check failed');
        }

        delete_option('lili_auth_token');
        delete_option('lili_auth_token_timestamp');
        delete_option('lili_user_id'); // Also clear user ID

        // Clear transients for user details and transactions
        delete_transient('lili_user_details_' . md5(get_option('lili_auth_token')));
        delete_transient('lili_user_transactions_' . md5(get_option('lili_auth_token')));

        echo '<div class="lili-message success"><p>Successfully logged out!</p></div>';
    }

    // Handle login form submission
    if (isset($_POST['lili_login_submit'])) {
        // Check if fields exist before processing
        if (!isset($_POST['lili_username']) || !isset($_POST['lili_password'])) {
            echo '<div class="lili-message error"><p>Username and password are required.</p></div>';
            return;
        }

        $username = sanitize_text_field(wp_unslash($_POST['lili_username']));
        $password = sanitize_text_field(wp_unslash($_POST['lili_password']));

        $auth_result = lili_get_user_auth_token($username, $password);

        if (is_wp_error($auth_result) && $auth_result->get_error_code() === 'mfa_required') {
            // Challenge is now saved in options, form will show MFA selection
            echo '<div class="lili-message info"><p>Please select your verification method.</p></div>';
        } else if (is_wp_error($auth_result)) {
            echo '<div class="lili-message error"><p>' . esc_html($auth_result->get_error_message()) . '</p></div>';
            delete_option('lili_challenge'); // Clear any existing challenge
        } else {
            // Successful non-MFA login
            update_option('lili_auth_token_timestamp', time());
            echo '<div class="lili-message success"><p>Successfully logged in!</p></div>';
        }
    }

    // Check if we have a valid token
    $auth_token = get_option('lili_auth_token');

    if ($auth_token) {
        $user_data = lili_get_logged_in_user_full_details();

        if (is_wp_error($user_data)) {
            echo '<div class="lili-message error"><p>Error: ' . esc_html($user_data->get_error_message()) . '</p></div>';
            // Don't display login form yet if it's just a token refresh issue
            if ($user_data->get_error_code() === 'unauthorized') {
                lili_display_login_form();
            }
        } else {
            lili_display_user_details($user_data['user_details'], $user_data['user_transactions']); // Pass null for transactions for now
        }
    } else {
        lili_display_login_form();
    }
}

/**
 * Uninstall the plugin and remove all options.
 */
register_uninstall_hook(__FILE__, 'lili_plugin_uninstall');
function lili_plugin_uninstall()
{
    delete_option('lili_auth_token');
    delete_option('lili_auth_token_timestamp');
}
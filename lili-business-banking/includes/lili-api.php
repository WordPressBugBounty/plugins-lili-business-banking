<?php
/**
 * Handles API requests with automatic token refresh and retry mechanism
 *
 * @param string $endpoint The API endpoint URL
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array $body Request body data (optional)
 * @param array $additional_headers Additional headers to include (optional)
 * @param int $max_retries Maximum number of retry attempts (default: 5)
 * @return array|WP_Error Response array or WP_Error on failure
 */
function lili_make_api_request($endpoint, $method = 'GET', $body = [], $additional_headers = [], $max_retries = 5)
{
    $current_try = 0;
    $token_option_name = 'lili_auth_token';

    do {
        $current_try++;

        // Get the current token
        $auth_token = get_option($token_option_name);

        // Prepare headers
        $headers = array_merge([
            'X-WORDPRESS-CLIENT-ID' => LILI_WORDPRESS_CLIENT_ID,
            'X-WORDPRESS-SECRET' => LILI_WORDPRESS_SECRET,
            'X-WORDPRESS-AUTH-TOKEN' => $auth_token,
            'Content-Type' => 'application/json'
        ], $additional_headers);

        // Prepare request arguments
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'sslverify' => false
        ];

        // Add body for non-GET requests
        if ($method !== 'GET' && !empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        // Make the request
        $response = wp_remote_request($endpoint, $args);

        // Check for WordPress error
        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_headers = wp_remote_retrieve_headers($response);

        // Check for 205 response (Reset Content)
        if ($response_code === 205) {

            // Extract new token from response headers
            $new_token = isset($response_headers['X-WORDPRESS-AUTH-TOKEN'])
                ? $response_headers['X-WORDPRESS-AUTH-TOKEN']
                : null;

            if ($new_token) {

                // Update stored token
                update_option($token_option_name, $new_token);

                // Continue to retry with new token
                continue;
            }
        }

        // Check for 401 response (Unauthorized)
        if ($response_code === 401) {
            // Clear the token and retry
            delete_option($token_option_name);
            continue;
        }

        return $response;

    } while ($current_try < $max_retries);

    // If we've exhausted all retries
    $error_message = sprintf(
        'API request failed after %d attempts. Last response code: %d',
        $max_retries,
        $response_code
    );

    return new WP_Error(
        'api_retry_failed',
        $error_message,
        ['response' => $response]
    );
}

/**
 * Get user details from the API
 * @return array
 */
function lili_get_user_details()
{
    // Try to get cached data first
    $transient_key = 'lili_user_details24_' . get_option('lili_user_id');
    $cached_data = get_transient($transient_key);

    // If we have valid cached data, return it
    if (false !== $cached_data) {
        return $cached_data;
    }

    // If no cache, make the API request
    $response = lili_make_api_request(LILI_API_URL . '/users/' . get_option('lili_user_id'));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    // Cache the data for 10 minutes (600 seconds)
    set_transient($transient_key, $data, 600);

    return $data;
}

/**
 * Get user transactions from the API
 * @return array
 */
function lili_get_user_transactions()
{
    // Try to get cached transactions first
    $transient_key = 'lili_user_transactions24_' . get_option('lili_user_id');
    $cached_data = get_transient($transient_key);

    // If we have valid cached data, return it
    if (false !== $cached_data) {
        return $cached_data;
    }

    // If no cache, make the API request
    $response = lili_make_api_request(LILI_API_URL . '/users/' . get_option('lili_user_id') . '/transactions?start=1&limit=5');

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    // Cache the data for 10 minutes (600 seconds)
    set_transient($transient_key, $data, 600);

    return $data;
}

/**
 * Get the full user details including transactions
 * @return array
 */
function lili_get_logged_in_user_full_details()
{

    return [
        'user_details' => lili_get_user_details(),
        'user_transactions' => lili_get_user_transactions()
    ];

}

/**
 * Get the user's account balance
 */
function lili_get_user_auth_token($username = null, $password = null)
{

    // Prepare API request
    $response = wp_remote_post(LILI_API_URL . "/users/auth_token", [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-WORDPRESS-CLIENT-ID' => LILI_WORDPRESS_CLIENT_ID,
            'X-WORDPRESS-SECRET' => LILI_WORDPRESS_SECRET,
        ],
        'body' => [
            'username' => $username,
            'password' => $password
        ],
        'timeout' => 30,
        'redirection' => 5,
        'sslverify' => false,
        'httpversion' => '1.1'
    ]);

    // Log API communication
    if (is_wp_error($response)) {
        return new WP_Error('api_error', $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    // Handle specific errors
    if (isset($data->httpStatus) && $data->httpStatus === 401) {
        if (!empty($data->errors) && isset($data->errors[0]->codeName) && $data->errors[0]->codeName === 'WORDPRESS_CUSTOMER_NOT_FOUND') {
            return new WP_Error('customer_not_found', 'Customer not found. Please check your username and password.');
        }
        return new WP_Error('unauthorized', 'Unauthorized access. Please try again.');
    }

    // Handle successful login
    $response_code = wp_remote_retrieve_response_code($response);

    // Handle direct auth token (non-MFA case)
    if ($response_code === 200 && isset($data->auth_token)) {
        update_option('lili_user_id', $data->user_id);
        update_option('lili_auth_token', $data->auth_token);
        return $data->auth_token;
    }

    if ($response_code === 202) {
        update_option('lili_user_id', $data->user_id);
        update_option('lili_challenge_id', $data->challenge->id);
        update_option('lili_challenge', $data->challenge);  // Save the entire challenge object

        return new WP_Error('mfa_required', 'MFA verification required', array(
            'challenge' => $data->challenge,
            'user_id' => $data->user_id
        ));
    }

    return new WP_Error('invalid_response', 'Invalid response from API');

}

/**
 * Handle AJAX request to send OTP
 */
function lili_handle_send_otp_ajax()
{
    $user_id = get_option('lili_user_id');
    $challenge_id = get_option('lili_challenge_id');

    // Verify nonce
    if (!check_ajax_referer('lili_send_otp_nonce', 'send_otp_nonce', false)) {
        wp_send_json([
            'success' => false,
            'message' => 'Security check failed - lili_handle_send_otp_ajax'
        ]);
    }

    $send_method_id = isset($_POST['send_method_id']) ? sanitize_text_field(wp_unslash($_POST['send_method_id'])) : 'sms_id';

    $response = wp_remote_post(LILI_API_URL . "/users/{$user_id}/sendOtp", [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-WORDPRESS-CLIENT-ID' => LILI_WORDPRESS_CLIENT_ID,
            'X-WORDPRESS-SECRET' => LILI_WORDPRESS_SECRET
        ],
        'body' => [
            'challenge_id' => $challenge_id,
            'send_method_id' => $send_method_id
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json([
            'success' => false,
            'message' => $response->get_error_message()
        ]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    wp_send_json([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * Handle AJAX request to login
 */
function lili_handle_login_ajax()
{
    // Verify nonce
    if (!check_ajax_referer('lili_login_nonce', 'login_nonce', false)) {
        wp_send_json([
            'success' => false,
            'message' => 'Security check failed - lili_handle_login_ajax'
        ]);
    }

    $username = isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '';
    $password = isset($_POST['password']) ? sanitize_text_field(wp_unslash($_POST['password'])) : '';

    $auth_result = lili_get_user_auth_token($username, $password);

    if (is_wp_error($auth_result) && $auth_result->get_error_code() === 'mfa_required') {
        wp_send_json([
            'success' => true,
            'challenge' => $auth_result->get_error_data()['challenge']
        ]);
    } else if (is_wp_error($auth_result)) {
        wp_send_json([
            'success' => false,
            'message' => $auth_result->get_error_message()
        ]);
    } else {
        // Successful non-MFA login
        wp_send_json([
            'success' => true,
            'data' => [
                'auth_token' => $auth_result
            ]
        ]);
    }
}

/**
 * Handle AJAX request to verify OTP
 */
function lili_handle_verify_otp_ajax()
{

    $user_id = get_option('lili_user_id');
    $challenge_id = get_option('lili_challenge_id');

    // Verify nonce
    if (!check_ajax_referer('lili_validate_otp_nonce', 'validate_otp_nonce', false)) {
        wp_send_json([
            'success' => false,
            'message' => 'Security check failed - handle_lili_validate_otp_ajax'
        ]);
    }

    $passcode = isset($_POST['otp_code']) ? sanitize_text_field(wp_unslash($_POST['otp_code'])) : '';

    $response = wp_remote_post(LILI_API_URL . "/users/{$user_id}/2fa", [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-WORDPRESS-CLIENT-ID' => LILI_WORDPRESS_CLIENT_ID,
            'X-WORDPRESS-SECRET' => LILI_WORDPRESS_SECRET
        ],
        'body' => [
            'challenge_id' => $challenge_id,
            'passcode' => $passcode
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json([
            'success' => false,
            'message' => $response->get_error_message()
        ]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (isset($data->auth_token)) {
        update_option('lili_auth_token', $data->auth_token);
        update_option('lili_auth_token_timestamp', current_time('mysql'));
    }

    wp_send_json([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * Action Hooks
 */
add_action('wp_ajax_lili_login', 'lili_handle_login_ajax');
add_action('wp_ajax_lili_send_otp', 'lili_handle_send_otp_ajax');
add_action('wp_ajax_lili_verify_otp', 'lili_handle_verify_otp_ajax');
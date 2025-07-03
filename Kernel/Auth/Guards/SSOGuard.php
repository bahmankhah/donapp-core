<?php

namespace Kernel\Auth\Guards;

use Kernel\Adapters\Adapter;
use Kernel\Contracts\Auth\Guard;

class SSOGuard extends Adapter implements Guard
{

    public function getLoginUrl(){
        return replacePlaceholders($this->config['login_url'], ['clientId'=>$this->config['client_id']]);
    }
    public function check(): bool
    {
        $user = wp_get_current_user();

        if ($user && $user->ID) {
            $expiresAt = (int) get_user_meta($user->ID, 'sso_expires_at', true);

            if (time() >= $expiresAt) {
                $this->refreshToken($user);
            }
            return true;
        }

        return false;
    }


    public function user() {}

    public function login($user)
    {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
    }


    public function logout()
    {
        $user = wp_get_current_user();
        if ($user && $user->ID) {
            delete_user_meta($user->ID, 'sso_access_token');
            delete_user_meta($user->ID, 'sso_refresh_token');
            delete_user_meta($user->ID, 'sso_expires_at');
        }

        wp_logout();
    }



    public function attempt(array $credential)
    {
        $api_url = $this->config['validate_url'];
        $clientId = $this->config['client_id'];

        appLogger(json_encode($credential));
        // Exchange code for token
        $response = wp_remote_post($api_url, [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'scope' => 'openid profile',
                'code' => $credential['code'],
                'session_state'=>$credential['code'],
            ],
        ]);

        appLogger(json_encode($response));

        if (is_wp_error($response)) {
            appLogger('error in response');
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        appLogger(json_encode($body));
        if (!isset($body['access_token'])) {
            return false;
        }

        $jwt = $body['access_token'];
        $payload = $this->decodeJwt($jwt);
        if (!$payload || !isset($payload['sub'])) {
            return false;
        }

        $globalId = $payload['sub'];

        // Check for existing user by meta
        $users = get_users([
            'meta_key' => 'sso_global_id',
            'meta_value' => $globalId,
            'number' => 1,
            'count_total' => false,
        ]);

        if (!empty($users)) {
            $user = $users[0];
        } else {
            // Create user
            $firstName = sanitize_text_field($payload['given_name'] ?? '');
            $lastName = sanitize_text_field($payload['family_name'] ?? '');
            $displayName = trim($firstName . ' ' . $lastName);

            $username = sanitize_user($payload['preferred_username'] ?? 'user_' . wp_generate_password(5, false));
            $email = sanitize_email($payload['email'] ?? $username . '@donap.ir');

            $user_id = wp_create_user($username, wp_generate_password(), $email);
            if (is_wp_error($user_id)) {
                return false;
            }
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'display_name' => $displayName,
            ]);

            update_user_meta($user_id, 'sso_global_id', $globalId);
            update_user_meta($user_id, 'sso_access_token', $body['access_token']);
            update_user_meta($user_id, 'sso_refresh_token', $body['refresh_token']);
            update_user_meta($user_id, 'sso_expires_at', time() + $body['exp']);
            update_user_meta($user_id, 'sso_mobile_number', $body['mobileNumber']);
            update_user_meta($user_id, 'sso_national_id', $body['nationalId']);

            $user = get_user_by('id', $user_id);
        }

        $this->login($user);


        return $user;
    }

    public function refreshToken($user)
    {
        $refreshToken = get_user_meta($user->ID, 'sso_refresh_token', true);
        if (!$refreshToken) {
            return false;
        }

        $response = wp_remote_post($this->config['validate_url'], [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->config['client_id'],
                'refresh_token' => $refreshToken,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['access_token'])) {
            return false;
        }

        update_user_meta($user->ID, 'sso_access_token', $body['access_token']);
        update_user_meta($user->ID, 'sso_refresh_token', $body['refresh_token']);
        update_user_meta($user->ID, 'sso_expires_at', time() + $body['exp']);

        return true;
    }
    private function decodeJwt($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        return json_decode($payload, true);
    }
}

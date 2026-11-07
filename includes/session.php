<?php
/**
 * Secure Session Handler for HealthCheck
 * Ensures HttpOnly, SameSite=Lax, Secure (if HTTPS), and strict mode.
 */

if (!function_exists('init_secure_session')) {
    function init_secure_session(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_only_cookies', '1');

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                session_start();
            } else {
                @session_start();
            }
        }
    }
}

init_secure_session();

<?php
/**
 * CSRF protection middleware.
 * Generates and validates CSRF tokens per session.
 */

function generateCsrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Generate new token if none exists or it's expired
    if (
        empty($_SESSION['csrf_token']) ||
        empty($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

function getCsrfTokenField(): string {
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function validateCsrfToken(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($submittedToken) || empty($sessionToken)) {
        return false;
    }

    // Constant-time comparison to prevent timing attacks
    return hash_equals($sessionToken, $submittedToken);
}

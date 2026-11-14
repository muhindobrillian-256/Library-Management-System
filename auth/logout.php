
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove all session variables.
$_SESSION = [];

// Destroy the current session.
session_destroy();

// Remove the session cookie from the browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Return to the login page.
header('Location: /Library_system/auth/login.php');
exit;

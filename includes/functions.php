
<?php
// Start a session if one has not already been started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Safely display text in HTML.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Redirect to another page.
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

// Store a message to display on the next page.
function set_flash($type, $message)
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Retrieve and remove a stored message.
function get_flash()
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $message;
}

// Generate a CSRF security token for forms.
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify a submitted CSRF token.
function verify_csrf_token()
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        empty($sessionToken) ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(403);
        exit('Invalid form submission. Please go back and try again.');
    }
}

// Ensure a page receives POST requests only.
function require_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
}


<?php

// Start the session if it has not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check whether a user is logged in.
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Please log in to access this page.'
    ];

    header('Location: /Library_system/auth/login.php');
    exit;
}

// Check the user's role.
if (!isset($_SESSION['role'])) {
    session_unset();
    session_destroy();

    header('Location: /Library_system/auth/login.php');
    exit;
}

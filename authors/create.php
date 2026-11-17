
<?php
/**
 * Add Author Page
 * 
 * Handles the registration of new authors in the library system, 
 * including server-side validation, duplicate checking, and CSRF verification.
 */

// Load necessary configuration, helper functions, and enforce authentication
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Initialize variables for form state and error tracking
$errors = [];
$name = '';
$country = '';
// Process form submission when a POST request is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf_token();

    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($name === '') {
        $errors[] = 'Author name is required.';
    } elseif (strlen($name) > 150) {
        $errors[] = 'Author name must not exceed 150 characters.';
    }

    if ($country === '') {
        $errors[] = 'Country is required.';
    } elseif (strlen($country) > 100) {
        $errors[] = 'Country must not exceed 100 characters.';
    }

    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare(
                "SELECT author_id FROM authors
                 WHERE LOWER(name) = LOWER(?) LIMIT 1"
            );

            $checkStmt->execute([$name]);

            if ($checkStmt->fetch()) {
                $errors[] = 'An author with this name already exists.';
            } else {
                $insertStmt = $pdo->prepare(
                    "INSERT INTO authors (name, country)
                     VALUES (?, ?)"
                );

                $insertStmt->execute([$name, $country]);

                set_flash('success', 'Author added successfully.');

                redirect('/Library_system/authors/index.php');
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Author | Library Management System</title>

    <link rel="stylesheet" href="/Library_system/assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #F1F3F6;
            color: #263445;
            font-family: Arial, sans-serif;
        }

        .top-header {
            background: #172B4D;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .brand h2 {
            margin: 0;
            font-size: 22px;
        }

        .brand p {
            margin: 5px 0 0;
            color: #CBD5E1;
            font-size: 13px;
        }

        .logout-link {
            background: #334A6E;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
        }

        .logout-link:hover {
            background: #405C85;
        }

        .page-container {
            max-width: 750px;
            margin: 35px auto;
            padding: 0 20px 30px;
        }

        .page-heading {
            margin-bottom: 22px;
        }

        .page-heading h2 {
            color: #172B4D;
            font-size: 26px;
            margin: 0 0 8px;
        }

        .page-heading p {
            color: #64748B;
            margin: 0;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 5px solid #2563EB;
        }

        .form-title {
            color: #172B4D;
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 19px;
        }

        .form-description {
            color: #64748B;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #334155;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            font-size: 14px;
            background: #FFFFFF;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
        }

        .form-group input::placeholder {
            color: #94A3B8;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .submit-button {
            background: #2563EB;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }

        .submit-button:hover {
            background: #1D4ED8;
        }

        .cancel-button {
            background: #E9EDF3;
            color: #334155;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .cancel-button:hover {
            background: #DCE3EC;
        }

        .error-message {
            background: #FEE2E2;
            color: #991B1B;
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #DC2626;
        }

        .error-message p {
            margin: 5px 0;
        }

        .back-link {
            display: inline-block;
            margin-top: 22px;
            color: #2563EB;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .top-header {
                padding: 17px 20px;
            }

            .page-container {
                margin-top: 25px;
                padding: 0 12px 25px;
            }

            .form-container {
                padding: 22px;
            }

            .form-actions {
                flex-direction: column;
            }

            .submit-button,
            .cancel-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header class="top-header">
    <div class="brand">
        <h2>📚 Library System</h2>
        <p>MANAGEMENT PORTAL</p>
    </div>

    <a href="/Library_system/auth/logout.php" class="logout-link">
        Logout
    </a>
</header>

<div class="page-container">

    <div class="page-heading">
        <h2>Add New Author</h2>
        <p>Register a new author in the library system.</p>
    </div>

    <div class="form-container">

        <h3 class="form-title">Author Information</h3>

        <p class="form-description">
            Enter the author's details below.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-group">
                <label for="name">Author Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?= e($name) ?>"
                    placeholder="Enter author name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="country">Country</label>

                <input
                    type="text"
                    id="country"
                    name="country"
                    maxlength="100"
                    value="<?= e($country) ?>"
                    placeholder="Enter author's country"
                    required
                >
            </div>

            <div class="form-actions">

                <button type="submit" class="submit-button">
                    Save Author
                </button>

                <a href="index.php" class="cancel-button">
                    Cancel
                </a>

            </div>

        </form>

    </div>

    <a href="index.php" class="back-link">
        ← Back to Authors
    </a>

</div>

</body>
</html>

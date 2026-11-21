
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT user_id, username, password, full_name, role
                 FROM users
                 WHERE username = :username
                 LIMIT 1"
            );

            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                redirect('/Library_system/dashboard/index.php');
            } else {
                $error = 'Incorrect username or password.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to log in at the moment. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Library Management System</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, #263e60 0%, transparent 40%),
                linear-gradient(135deg, #101e33, #172b47 60%, #0d192b);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            color: #1e293b;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            min-height: 570px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
        }

        /* LEFT SIDE */

        .welcome-section {
            background: linear-gradient(145deg, #142943, #203c5d);
            color: white;
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 50%;
            top: -140px;
            right: -100px;
        }

        .welcome-section::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 50%;
            bottom: -120px;
            left: -100px;
        }

        .book-icon {
            font-size: 65px;
            margin-bottom: 25px;
        }

        .welcome-section h1 {
            font-size: 38px;
            line-height: 1.3;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .gold-line {
            width: 65px;
            height: 4px;
            background: #d4af37;
            border-radius: 5px;
            margin-bottom: 22px;
        }

        .welcome-section p {
            color: #d4deeb;
            font-size: 15px;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .welcome-note {
            margin-top: 35px;
            padding: 15px;
            border-left: 3px solid #d4af37;
            background: rgba(255,255,255,0.06);
            color: #e7edf5;
            font-size: 13px;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        /* RIGHT SIDE */

        .login-section {
            padding: 65px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-heading {
            margin-bottom: 35px;
        }

        .login-heading h2 {
            color: #172b47;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .login-heading p {
            color: #7b8798;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 23px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #26374c;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 15px;
            border: 1px solid #dce2e9;
            border-radius: 6px;
            font-size: 15px;
            color: #26374c;
            background: #f8fafc;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #c39a28;
            background: white;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
        }

        .login-button {
            width: 100%;
            padding: 15px;
            background: #d4af37;
            color: #172b47;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 5px;
        }

        .login-button:hover {
            background: #bd9829;
            transform: translateY(-2px);
            box-shadow: 0 7px 18px rgba(212,175,55,0.25);
        }

        .error {
            background: #fff0f0;
            color: #a52828;
            border-left: 4px solid #dc4545;
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: #929cac;
            font-size: 12px;
            line-height: 1.7;
        }

        .login-footer span {
            color: #bd9829;
            font-weight: bold;
        }

        @media (max-width: 750px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 480px;
            }

            .welcome-section {
                padding: 35px 30px;
                text-align: center;
            }

            .book-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }

            .welcome-section h1 {
                font-size: 27px;
            }

            .gold-line {
                margin: 0 auto 18px;
            }

            .welcome-note {
                display: none;
            }

            .login-section {
                padding: 40px 30px;
            }
        }

        @media (max-width: 380px) {
            body {
                padding: 12px;
            }

            .login-section {
                padding: 30px 20px;;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <section class="welcome-section">

        <div class="book-icon">📚</div>

        <h1>Welcome to Your Library</h1>

        <div class="gold-line"></div>

        <p>
            Library Management System
        </p>

        <p style="margin-top: 15px;">
            A smarter way to manage books, members,
            authors and borrowing activities.
        </p>

        <div class="welcome-note">
            "A library is not just a collection of books.
            It is a gateway to knowledge and discovery."
        </div>

    </section>

    <section class="login-section">

        <div class="login-heading">
            <h2>Welcome Back!</h2>
            <p>Sign in to access your library dashboard.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-group">
                <label for="username">Username</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($username) ?>"
                    placeholder="Enter your username"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-button">
                Sign In →
            </button>

        </form>

        <div class="login-footer">
            <span>📚 Library Management System</span>
            <br>
            Secure Administrator and Librarian Access
            <br>
            © 2026 All Rights Reserved
        </div>

    </section>

</div>

</body>
</html>

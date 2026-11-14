
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$errors = [];

$memberNumber = '';
$fullName = '';
$email = '';
$phone = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_post();
    verify_csrf_token();

    $memberNumber = trim($_POST['member_number'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($memberNumber === '') {
        $errors[] = 'Member number is required.';
    }

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }

    if (!in_array($status, ['active', 'suspended'], true)) {
        $errors[] = 'Select a valid membership status.';
    }

    // Check for duplicate member number and email.
    if (!$errors) {

        $check = $pdo->prepare("
            SELECT member_id
            FROM members
            WHERE member_number = ? OR email = ?
        ");

        $check->execute([$memberNumber, $email]);

        if ($check->fetch()) {
            $errors[] = 'Member number or email address already exists.';
        }
    }

    // Save member.
    if (!$errors) {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO members
                (member_number, full_name, email, phone, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $memberNumber,
                $fullName,
                $email,
                $phone,
                $status
            ]);

            set_flash('success', 'New member registered successfully.');

            redirect('/Library_system/members/index.php');

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] = 'Unable to register member. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register Member | Library Management System</title>

    <link rel="stylesheet" href="/Library_system/assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #F1F3F6;
            color: #333333;
        }

        header {
            background: #172B4D;
            color: white;
            padding: 22px 30px;
        }

        header h2 {
            margin: 0;
            font-size: 22px;
        }

        .container {
            max-width: 750px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .form-header {
            background: #172B4D;
            color: white;
            padding: 25px 30px;
        }

        .form-header h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .form-header p {
            margin: 0;
            color: #DCE3ED;
        }

        .form-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #172B4D;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #CBD2DC;
            border-radius: 6px;
            font-size: 15px;
            background: white;
            color: #333333;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        .errors {
            background: #FEE2E2;
            color: #991B1B;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 22px;
        }

        .errors p {
            margin: 5px 0;
        }

        .buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        button {
            background: #2563EB;
            color: white;
            border: none;
            padding: 13px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        button:hover {
            background: #1D4ED8;
        }

        .cancel {
            background: #64748B;
            color: white;
            padding: 13px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .cancel:hover {
            background: #475569;
        }

        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
                padding: 0 12px;
            }

            .form-body {
                padding: 20px;
            }

            .form-header {
                padding: 22px 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>Library Management System</h2>
</header>

<main class="container">

    <div class="form-card">

        <div class="form-header">
            <h1>Register New Member</h1>
            <p>Enter the member's details below.</p>
        </div>

        <div class="form-body">

            <?php if ($errors): ?>
                <div class="errors">
                    <strong>Please correct the following:</strong>

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
                    <label for="member_number">Member Number</label>

                    <input
                        type="text"
                        id="member_number"
                        name="member_number"
                        placeholder="Example: MEM011"
                        value="<?= e($memberNumber) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter full name"
                        value="<?= e($fullName) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter email address"
                        value="<?= e($email) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        placeholder="Enter phone number"
                        value="<?= e($phone) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="status">Membership Status</label>

                    <select id="status" name="status" required>

                        <option value="active"
                            <?= $status === 'active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="suspended"
                            <?= $status === 'suspended' ? 'selected' : '' ?>>
                            Suspended
                        </option>

                    </select>
                </div>

                <div class="buttons">
                    <button type="submit">Register Member</button>

                    <a href="index.php" class="cancel">
                        Cancel
                    </a>
                </div>

            </form>

        </div>

    </div>

</main>

</body>
</html>

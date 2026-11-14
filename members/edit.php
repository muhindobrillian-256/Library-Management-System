
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$memberId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$memberId || $memberId < 1) {
    set_flash('error', 'Invalid member selected.');
    redirect('/Library_system/members/index.php');
}

$errors = [];

// Get member details.
$stmt = $pdo->prepare("
    SELECT * FROM members
    WHERE member_id = ?
");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    set_flash('error', 'Member not found.');
    redirect('/Library_system/members/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf_token();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? '';

    // Validation.
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

    // Check whether another member uses this email.
    if (!$errors) {
        $check = $pdo->prepare("
            SELECT member_id
            FROM members
            WHERE email = ?
            AND member_id != ?
        ");

        $check->execute([$email, $memberId]);

        if ($check->fetch()) {
            $errors[] = 'This email address belongs to another member.';
        }
    }

    // Update member.
    if (!$errors) {
        try {
            $update = $pdo->prepare("
                UPDATE members
                SET full_name = ?,
                    email = ?,
                    phone = ?,
                    status = ?
                WHERE member_id = ?
            ");

            $update->execute([
                $fullName,
                $email,
                $phone,
                $status,
                $memberId
            ]);

            set_flash('success', 'Member details updated successfully.');
            redirect('/Library_system/members/index.php');

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Unable to update member. Please try again.';
        }
    }

    // Preserve entered values if validation fails.
    $member['full_name'] = $fullName;
    $member['email'] = $email;
    $member['phone'] = $phone;
    $member['status'] = $status;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Member | Library System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #F1F3F6;
            color: #263238;
        }

        .container {
            max-width: 650px;
            margin: 25px auto;
        }

        .form-card {
            background: #FFFFFF;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .page-header {
            background: #172B4D;
            color: #FFFFFF;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 22px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .page-header p {
            margin: 0;
            color: #D9E1EF;
            font-size: 14px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563EB;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
            color: #172B4D;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #CBD2DC;
            border-radius: 6px;
            font-size: 15px;
            background: #FFFFFF;
            color: #263238;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        input:disabled {
            background: #E9EDF3;
            color: #667085;
            cursor: not-allowed;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
        }

        button {
            background: #172B4D;
            color: #FFFFFF;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #243F6B;
        }

        .cancel-link {
            display: inline-block;
            padding: 12px 20px;
            background: #E9EDF3;
            color: #344054;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .cancel-link:hover {
            background: #D9DEE7;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert.error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .alert p {
            margin: 5px 0;
        }

        @media (max-width: 600px) {
            .form-card {
                padding: 22px;
            }

            .page-header {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="page-header">
        <h1>Edit Member</h1>
        <p>Update the member's information below.</p>
    </div>

    <a class="back-link" href="index.php">
        &larr; Back to Members List
    </a>

    <div class="form-card">

        <?php if ($errors): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <label>Member Number</label>
            <input
                type="text"
                value="<?= e($member['member_number']) ?>"
                disabled
            >

            <label>Full Name</label>
            <input
                type="text"
                name="full_name"
                value="<?= e($member['full_name']) ?>"
                required
            >

            <label>Email Address</label>
            <input
                type="email"
                name="email"
                value="<?= e($member['email']) ?>"
                required
            >

            <label>Phone Number</label>
            <input
                type="text"
                name="phone"
                value="<?= e($member['phone']) ?>"
                required
            >

            <label>Membership Status</label>
            <select name="status" required>
                <option value="active"
                    <?= $member['status'] === 'active' ? 'selected' : '' ?>>
                    Active
                </option>

                <option value="suspended"
                    <?= $member['status'] === 'suspended' ? 'selected' : '' ?>>
                    Suspended
                </option>
            </select>

            <div class="form-actions">
                <button type="submit">Save Changes</button>
                <a class="cancel-link" href="index.php">Cancel</a>
            </div>

        </form>

    </div>

</div>

</body>
</html>

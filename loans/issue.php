
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf_token();

    $memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

    if (!$memberId || $memberId < 1) {
        $errors[] = 'Please select a valid member.';
    }

    if (!$bookId || $bookId < 1) {
        $errors[] = 'Please select a valid book.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lock and check the member.
            $memberStmt = $pdo->prepare(
                "SELECT member_id, status
                 FROM members
                 WHERE member_id = ?
                 FOR UPDATE"
            );
            $memberStmt->execute([$memberId]);
            $member = $memberStmt->fetch();

            if (!$member) {
                throw new Exception('The selected member does not exist.');
            }

            if ($member['status'] !== 'active') {
                throw new Exception(
                    'This member is suspended and cannot borrow books.'
                );
            }

            // Lock and check the selected book.
            $bookStmt = $pdo->prepare(
                "SELECT book_id, title, available_copies
                 FROM books
                 WHERE book_id = ?
                 FOR UPDATE"
            );
            $bookStmt->execute([$bookId]);
            $book = $bookStmt->fetch();

            if (!$book) {
                throw new Exception('The selected book does not exist.');
            }

            if ((int)$book['available_copies'] < 1) {
                throw new Exception(
                    'This book has no available copies.'
                );
            }

            // Prevent duplicate outstanding loans.
            $duplicateStmt = $pdo->prepare(
                "SELECT loan_id
                 FROM loans
                 WHERE member_id = ?
                 AND book_id = ?
                 AND status = 'borrowed'
                 LIMIT 1"
            );
            $duplicateStmt->execute([$memberId, $bookId]);

            if ($duplicateStmt->fetch()) {
                throw new Exception(
                    'This member already has an unreturned copy of this book.'
                );
            }

            // Calculate issue date and due date.
            $issueDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days'));

            // Create the loan record.
            $insertStmt = $pdo->prepare(
                "INSERT INTO loans
                    (member_id, book_id, date_issued, due_date, status)
                 VALUES (?, ?, ?, ?, 'borrowed')"
            );

            $insertStmt->execute([
                $memberId,
                $bookId,
                $issueDate,
                $dueDate
            ]);

            // Reduce the available copies.
            $updateBookStmt = $pdo->prepare(
                "UPDATE books
                 SET available_copies = available_copies - 1
                 WHERE book_id = ?
                 AND available_copies > 0"
            );

            $updateBookStmt->execute([$bookId]);

            if ($updateBookStmt->rowCount() !== 1) {
                throw new Exception(
                    'Unable to update book availability. Please try again.'
                );
            }

            $pdo->commit();

            set_flash(
                'success',
                'Book issued successfully. Due date: ' . $dueDate
            );

            redirect('/Library_system/loans/index.php');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e->getMessage();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}

// Get active members.
$membersStmt = $pdo->query(
    "SELECT member_id, member_number, full_name
     FROM members
     WHERE status = 'active'
     ORDER BY full_name ASC"
);
$members = $membersStmt->fetchAll();
echo '<pre>';
print_r($members);
echo '</pre>';
exit;
// Get books with available copies.
$booksStmt = $pdo->query(
    "SELECT book_id, title, isbn, available_copies
     FROM books
     WHERE available_copies > 0
     ORDER BY title ASC"
);
$books = $booksStmt->fetchAll();

$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue Book | Library Management System</title>

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

        .issue-container {
            max-width: 650px;
            margin: 25px auto;
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

        .form-card {
            background: #FFFFFF;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #172B4D;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #CBD2DC;
            border-radius: 6px;
            background: #FFFFFF;
            color: #263238;
            font-size: 15px;
        }

        .form-group select:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .loan-info {
            background: #E9EDF3;
            padding: 15px;
            border-radius: 6px;
            margin: 22px 0;
            color: #344054;
            font-size: 14px;
        }

        .loan-info strong {
            color: #172B4D;
        }

        .issue-button {
            background: #172B4D;
            color: #FFFFFF;
            padding: 12px 22px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .issue-button:hover {
            background: #243F6B;
        }

        .issue-button:disabled {
            background: #98A2B3;
            cursor: not-allowed;
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

        .message {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .message p {
            margin: 5px 0;
        }

        .error-message {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .success-message {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #BBF7D0;
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

<div class="issue-container">

    <div class="page-header">
        <h1>Issue a Book</h1>
        <p>Register a new book borrowing transaction.</p>
    </div>

    <div class="form-card">

        <?php if ($flash): ?>
            <div class="message <?= e($flash['type']) === 'error' ? 'error-message' : 'success-message' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($members)): ?>
            <div class="message error-message">
                There are no active members available for borrowing.
            </div>
        <?php endif; ?>

        <?php if (empty($books)): ?>
            <div class="message error-message">
                There are currently no books available for borrowing.
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-group">
                <label for="member_id">Select Member</label>

                <select name="member_id" id="member_id" required>
                    <option value="">-- Choose Member --</option>

                    <?php foreach ($members as $member): ?>
                        <option
                            value="<?= e($member['member_id']) ?>"
                            <?= (isset($memberId) && (int)$memberId === (int)$member['member_id']) ? 'selected' : '' ?>
                        >
                            <?= e($member['member_number']) ?>
                            - <?= e($member['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="book_id">Select Book</label>

                <select name="book_id" id="book_id" required>
                    <option value="">-- Choose Book --</option>

                    <?php foreach ($books as $book): ?>
                        <option
                            value="<?= e($book['book_id']) ?>"
                            <?= (isset($bookId) && (int)$bookId === (int)$book['book_id']) ? 'selected' : '' ?>
                        >
                            <?= e($book['title']) ?>
                            (<?= e($book['isbn']) ?>)
                            - <?= e($book['available_copies']) ?> available
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="loan-info">
                <strong>Loan period:</strong>
                14 days from the issue date.
            </div>

            <button
                type="submit"
                class="issue-button"
                <?= (empty($members) || empty($books)) ? 'disabled' : '' ?>
            >
                Issue Book
            </button>

        </form>

        <a href="/Library_system/loans/index.php" class="back-link">
            &larr; Back to Loans
        </a>

    </div>

</div>

</body>
</html>


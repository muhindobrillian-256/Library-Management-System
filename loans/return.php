
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only accept POST requests.
require_post();
verify_csrf_token();

$loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);

if (!$loanId || $loanId < 1) {
    set_flash('error', 'Invalid loan selected.');
    redirect('/Library_system/loans/index.php');
}

try {
    $pdo->beginTransaction();

    // Lock the loan record.
    $loanStmt = $pdo->prepare(
        "SELECT loan_id, book_id, status
         FROM loans
         WHERE loan_id = ?
         FOR UPDATE"
    );

    $loanStmt->execute([$loanId]);
    $loan = $loanStmt->fetch();

    if (!$loan) {
        throw new Exception('The selected loan record does not exist.');
    }

    if ($loan['status'] !== 'borrowed') {
        throw new Exception('This book has already been returned.');
    }

    // Mark the loan as returned.
    $returnDate = date('Y-m-d');

    $updateLoanStmt = $pdo->prepare(
        "UPDATE loans
         SET status = 'returned',
             date_returned = ?
         WHERE loan_id = ?
         AND status = 'borrowed'"
    );

    $updateLoanStmt->execute([$returnDate, $loanId]);

    if ($updateLoanStmt->rowCount() !== 1) {
        throw new Exception('Unable to process this return.');
    }

    // Restore one available copy without exceeding total copies.
    $updateBookStmt = $pdo->prepare(
        "UPDATE books
         SET available_copies = LEAST(
             available_copies + 1,
             total_copies
         )
         WHERE book_id = ?"
    );

    $updateBookStmt->execute([$loan['book_id']]);

    if ($updateBookStmt->rowCount() !== 1) {
        throw new Exception('Unable to update book availability.');
    }

    $pdo->commit();

    set_flash(
        'success',
        'Book returned successfully. Book availability has been updated.'
    );

    redirect('/Library_system/loans/index.php');

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    set_flash(
        'error',
        'A database error occurred while processing the return.'
    );

    redirect('/Library_system/loans/index.php');

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', $e->getMessage());

    redirect('/Library_system/loans/index.php');
}
?>

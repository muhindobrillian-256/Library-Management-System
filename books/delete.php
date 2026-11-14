
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Allow deletion through POST only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify the security token.
verify_csrf_token();

// Validate book ID.
$bookId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId < 1) {
    set_flash('error', 'Invalid book selected.');
    redirect('/Library_system/books/index.php');
}

try {
    $pdo->beginTransaction();

    // Check and lock the selected book.
    $bookQuery = $pdo->prepare("
        SELECT book_id
        FROM books
        WHERE book_id = ?
        FOR UPDATE
    ");

    $bookQuery->execute([$bookId]);
    $book = $bookQuery->fetch();

    if (!$book) {
        throw new Exception('The selected book was not found.');
    }

    // Check whether the book has any borrowing history.
    $loanQuery = $pdo->prepare("
        SELECT COUNT(*)
        FROM loans
        WHERE book_id = ?
    ");

    $loanQuery->execute([$bookId]);
    $loanCount = (int) $loanQuery->fetchColumn();

    if ($loanCount > 0) {
        throw new Exception(
            'This book cannot be deleted because it has borrowing history. Its records must be preserved.'
        );
    }

    // Delete related author records first.
    $authorQuery = $pdo->prepare("
        DELETE FROM book_authors
        WHERE book_id = ?
    ");

    $authorQuery->execute([$bookId]);

    // Delete the book.
    $deleteQuery = $pdo->prepare("
        DELETE FROM books
        WHERE book_id = ?
    ");

    $deleteQuery->execute([$bookId]);

    $pdo->commit();

    set_flash('success', 'Book deleted successfully.');

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    set_flash(
        'error',
        'The book could not be deleted. Please try again.'
    );

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', $e->getMessage());
}

// Return to Books Management.
redirect('/Library_system/books/index.php');
?>

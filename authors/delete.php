
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_post();
verify_csrf_token();

$authorId = filter_input(INPUT_POST, 'author_id', FILTER_VALIDATE_INT);

if (!$authorId || $authorId < 1) {
    set_flash('error', 'Invalid author selected.');
    redirect('/Library_system/authors/index.php');
}

try {
    $pdo->beginTransaction();

    // Lock the author record.
    $authorStmt = $pdo->prepare(
        "SELECT author_id, name
         FROM authors
         WHERE author_id = ?
         FOR UPDATE"
    );

    $authorStmt->execute([$authorId]);
    $author = $authorStmt->fetch();

    if (!$author) {
        throw new Exception('The selected author does not exist.');
    }

    // Check whether the author is associated with any books.
    $bookStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM book_authors
         WHERE author_id = ?"
    );

    $bookStmt->execute([$authorId]);
    $bookCount = (int) $bookStmt->fetchColumn();

    if ($bookCount > 0) {
        throw new Exception(
            'This author cannot be deleted because they are linked to books. Remove or reassign those book-author relationships first.'
        );
    }

    // Delete the author.
    $deleteStmt = $pdo->prepare(
        "DELETE FROM authors WHERE author_id = ?"
    );

    $deleteStmt->execute([$authorId]);

    $pdo->commit();

    set_flash('success', 'Author deleted successfully.');

    redirect('/Library_system/authors/index.php');

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    set_flash(
        'error',
        'A database error occurred. The author could not be deleted.'
    );

    redirect('/Library_system/authors/index.php');

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', $e->getMessage());

    redirect('/Library_system/authors/index.php');
}
?>

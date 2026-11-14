
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf_token();

$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId < 1) {
    set_flash('error', 'Invalid category selected.');
    redirect('index.php');
}

try {
    $pdo->beginTransaction();

    // Confirm the category exists and lock it.
    $stmt = $pdo->prepare(
        "SELECT category_id, name FROM categories
         WHERE category_id = ? FOR UPDATE"
    );
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        $pdo->rollBack();
        set_flash('error', 'Category not found.');
        redirect('index.php');
    }

    // Do not delete categories that are assigned to books.
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM books WHERE category_id = ?"
    );
    $stmt->execute([$categoryId]);
    $bookCount = (int) $stmt->fetchColumn();

    if ($bookCount > 0) {
        $pdo->rollBack();
        set_flash(
            'error',
            'This category cannot be deleted because it is assigned to books. Reassign those books first.'
        );
        redirect('index.php');
    }

    $stmt = $pdo->prepare(
        "DELETE FROM categories WHERE category_id = ?"
    );
    $stmt->execute([$categoryId]);

    $pdo->commit();

    set_flash('success', 'Category deleted successfully.');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    set_flash('error', 'Unable to delete this category. Please try again.');
}

redirect('index.php');

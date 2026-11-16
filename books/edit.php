
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId < 1) {
    set_flash('error', 'Invalid book selected.');
    redirect('/Library_system/books/index.php');
}

$errors = [];

$bookQuery = $pdo->prepare("
    SELECT * FROM books WHERE book_id = ?
");
$bookQuery->execute([$bookId]);
$book = $bookQuery->fetch();

if (!$book) {
    set_flash('error', 'Book not found.');
    redirect('/Library_system/books/index.php');
}

$categories = $pdo->query("
    SELECT category_id, name FROM categories ORDER BY name
")->fetchAll();

$authors = $pdo->query("
    SELECT author_id, name FROM authors ORDER BY name
")->fetchAll();

$authorQuery = $pdo->prepare("
    SELECT author_id FROM book_authors WHERE book_id = ?
");
$authorQuery->execute([$bookId]);

$selectedAuthors = array_map(
    'intval',
    $authorQuery->fetchAll(PDO::FETCH_COLUMN)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf_token();

    $isbn = trim($_POST['isbn'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = filter_var($_POST['year_published'] ?? '', FILTER_VALIDATE_INT);
    $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $totalCopies = filter_var($_POST['total_copies'] ?? '', FILTER_VALIDATE_INT);

    $postedAuthors = $_POST['authors'] ?? [];

    if (!is_array($postedAuthors)) {
        $postedAuthors = [];
    }

    $authorIds = array_values(array_unique(array_filter(
        array_map('intval', $postedAuthors),
        fn($id) => $id > 0
    )));

    if ($isbn === '' || $title === '') {
        $errors[] = 'ISBN and book title are required.';
    }

    if (!$year || $year < 1000 || $year > ((int)date('Y') + 1)) {
        $errors[] = 'Enter a valid publication year.';
    }

    if (!$categoryId || $categoryId < 1) {
        $errors[] = 'Please select a category.';
    }

    if ($totalCopies === false || $totalCopies < 1) {
        $errors[] = 'Total copies must be at least 1.';
    }

    if (!$errors) {
        $isbnCheck = $pdo->prepare("
            SELECT book_id FROM books
            WHERE isbn = ? AND book_id != ?
        ");

        $isbnCheck->execute([$isbn, $bookId]);

        if ($isbnCheck->fetch()) {
            $errors[] = 'That ISBN is already assigned to another book.';
        }

        $categoryCheck = $pdo->prepare("
            SELECT category_id FROM categories WHERE category_id = ?
        ");

        $categoryCheck->execute([$categoryId]);

        if (!$categoryCheck->fetch()) {
            $errors[] = 'The selected category does not exist.';
        }

        if ($authorIds) {
            $placeholders = implode(',', array_fill(0, count($authorIds), '?'));

            $authorCheck = $pdo->prepare("
                SELECT COUNT(*) FROM authors
                WHERE author_id IN ($placeholders)
            ");

            $authorCheck->execute($authorIds);

            if ((int)$authorCheck->fetchColumn() !== count($authorIds)) {
                $errors[] = 'One or more selected authors do not exist.';
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $lockQuery = $pdo->prepare("
                SELECT total_copies, available_copies
                FROM books
                WHERE book_id = ?
                FOR UPDATE
            ");

            $lockQuery->execute([$bookId]);
            $currentBook = $lockQuery->fetch();

            if (!$currentBook) {
                throw new Exception('Book not found.');
            }

            $borrowedCopies =
                (int)$currentBook['total_copies'] -
                (int)$currentBook['available_copies'];

            if ($totalCopies < $borrowedCopies) {
                throw new Exception(
                    "You cannot set total copies below the $borrowedCopies currently borrowed."
                );
            }

            $newAvailableCopies = $totalCopies - $borrowedCopies;

            $updateBook = $pdo->prepare("
                UPDATE books
                SET isbn = ?,
                    title = ?,
                    publisher = ?,
                    year_published = ?,
                    category_id = ?,
                    total_copies = ?,
                    available_copies = ?
                WHERE book_id = ?
            ");

            $updateBook->execute([
                $isbn,
                $title,
                $publisher,
                $year,
                $categoryId,
                $totalCopies,
                $newAvailableCopies,
                $bookId
            ]);

            // Replace the book's author relationships.
            $deleteAuthors = $pdo->prepare("
                DELETE FROM book_authors WHERE book_id = ?
            ");

            $deleteAuthors->execute([$bookId]);

            $insertAuthor = $pdo->prepare("
                INSERT INTO book_authors (book_id, author_id)
                VALUES (?, ?)
            ");

            foreach ($authorIds as $authorId) {
                $insertAuthor->execute([$bookId, $authorId]);
            }

            $pdo->commit();

            set_flash('success', 'Book details updated successfully.');
            redirect('/Library_system/books/index.php');

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

            $errors[] = 'The book could not be updated. Please check the details and try again.';
        }
    }

    // Keep submitted values in the form if validation fails.
    $book['isbn'] = $isbn;
    $book['title'] = $title;
    $book['publisher'] = $publisher;
    $book['year_published'] = $_POST['year_published'] ?? '';
    $book['category_id'] = $categoryId ?: '';
    $book['total_copies'] = $_POST['total_copies'] ?? '';

    $selectedAuthors = $authorIds;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Book | Library Management System</title>

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
            max-width: 850px;
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

        .form-group > label {
            display: block;
            font-weight: 600;
            color: #172B4D;
            margin-bottom: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
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

        .author-list {
            border: 1px solid #CBD2DC;
            border-radius: 6px;
            padding: 14px;
            background: #F8FAFC;
            max-height: 220px;
            overflow-y: auto;
        }

        .author-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 5px;
            border-bottom: 1px solid #E2E8F0;
            color: #333333;
            cursor: pointer;
        }

        .author-option:last-child {
            border-bottom: none;
        }

        .author-option input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: #2563EB;
            cursor: pointer;
        }

        .hint {
            color: #64748B;
            font-size: 13px;
            margin-top: 8px;
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

        footer {
            text-align: center;
            padding: 25px;
            color: #64748B;
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
            <h1>Edit Book</h1>
            <p>Update the information for this book.</p>
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

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="form-group">
                    <label for="isbn">ISBN</label>

                    <input
                        type="text"
                        id="isbn"
                        name="isbn"
                        required
                        maxlength="20"
                        value="<?= e($book['isbn']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="title">Book Title</label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        required
                        value="<?= e($book['title']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="publisher">Publisher</label>

                    <input
                        type="text"
                        id="publisher"
                        name="publisher"
                        value="<?= e($book['publisher']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="year_published">Year Published</label>

                    <input
                        type="number"
                        id="year_published"
                        name="year_published"
                        min="1000"
                        max="<?= (int)date('Y') + 1 ?>"
                        required
                        value="<?= e($book['year_published']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Book Category</label>

                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select Category --</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int)$category['category_id'] ?>"
                                <?= (int)$book['category_id'] === (int)$category['category_id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Authors</label>

                    <div class="author-list">

                        <?php foreach ($authors as $author): ?>
                            <label class="author-option">

                                <input
                                    type="checkbox"
                                    name="authors[]"
                                    value="<?= (int)$author['author_id'] ?>"
                                    <?= in_array(
                                        (int)$author['author_id'],
                                        $selectedAuthors,
                                        true
                                    ) ? 'checked' : '' ?>
                                >

                                <span><?= e($author['name']) ?></span>

                            </label>
                        <?php endforeach; ?>

                    </div>

                    <p class="hint">
                        Select one or more authors for this book.
                    </p>
                </div>

                <div class="form-group">
                    <label for="total_copies">Total Copies</label>

                    <input
                        type="number"
                        id="total_copies"
                        name="total_copies"
                        min="1"
                        required
                        value="<?= e($book['total_copies']) ?>"
                    >
                </div>

                <div class="buttons">
                    <button type="submit">Save Changes</button>

                    <a
                        href="/Library_system/books/index.php"
                        class="cancel"
                    >
                        Cancel
                    </a>
                </div>

            </form>

        </div>

    </div>

</main>

<footer>
    Library Management System &copy; <?= date('Y') ?>
</footer>

</body>
</html>

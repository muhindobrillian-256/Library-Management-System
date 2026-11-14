
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];

$isbn = '';
$title = '';
$publisher = '';
$year = '';
$categoryId = '';
$totalCopies = '1';
$selectedAuthors = [];

try {
    $categories = $pdo->query(
        "SELECT category_id, name FROM categories ORDER BY name"
    )->fetchAll();

    $authors = $pdo->query(
        "SELECT author_id, name FROM authors ORDER BY name"
    )->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Unable to load authors and categories.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf_token();

    $isbn = trim($_POST['isbn'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = trim($_POST['year_published'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $totalCopies = trim($_POST['total_copies'] ?? '');
    $selectedAuthors = $_POST['authors'] ?? [];

    // Validate book details.
    if ($isbn === '') {
        $errors[] = 'ISBN is required.';
    }

    if ($title === '') {
        $errors[] = 'Book title is required.';
    }

    if ($publisher === '') {
        $errors[] = 'Publisher is required.';
    }

    if (
        $year === '' ||
        !ctype_digit($year) ||
        (int)$year < 1901 ||
        (int)$year > (int)date('Y')
    ) {
        $errors[] = 'Enter a valid publication year.';
    }

    if (
        !ctype_digit((string)$categoryId) ||
        (int)$categoryId < 1
    ) {
        $errors[] = 'Please select a category.';
    }

    if (
        !ctype_digit($totalCopies) ||
        (int)$totalCopies < 1
    ) {
        $errors[] = 'Total copies must be at least 1.';
    }

    if (!is_array($selectedAuthors)) {
        $selectedAuthors = [];
        $errors[] = 'Invalid author selection.';
    }

    // Validate that the selected category exists.
    if (!$errors) {
        $checkCategory = $pdo->prepare(
            "SELECT category_id FROM categories WHERE category_id = ?"
        );

        $checkCategory->execute([(int)$categoryId]);

        if (!$checkCategory->fetch()) {
            $errors[] = 'The selected category does not exist.';
        }
    }

    // Validate selected authors.
    $authorIds = [];

    if (!$errors) {
        foreach ($selectedAuthors as $authorId) {
            if (!ctype_digit((string)$authorId) || (int)$authorId < 1) {
                $errors[] = 'Invalid author selection.';
                break;
            }

            $authorIds[] = (int)$authorId;
        }

        $authorIds = array_unique($authorIds);

        if (!$errors && count($authorIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($authorIds), '?'));

            $checkAuthors = $pdo->prepare(
                "SELECT author_id FROM authors WHERE author_id IN ($placeholders)"
            );

            $checkAuthors->execute(array_values($authorIds));

            if ($checkAuthors->rowCount() !== count($authorIds)) {
                $errors[] = 'One or more selected authors do not exist.';
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Check whether the ISBN already exists.
            $checkISBN = $pdo->prepare(
                "SELECT book_id FROM books WHERE isbn = ?"
            );

            $checkISBN->execute([$isbn]);

            if ($checkISBN->fetch()) {
                throw new Exception('A book with this ISBN already exists.');
            }

            // Insert the book.
            $stmt = $pdo->prepare(
                "INSERT INTO books
                (isbn, title, category_id, publisher, year_published,
                 total_copies, available_copies)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $isbn,
                $title,
                (int)$categoryId,
                $publisher,
                (int)$year,
                (int)$totalCopies,
                (int)$totalCopies
            ]);

            $bookId = $pdo->lastInsertId();

            // Connect the book to its selected authors.
            if (count($authorIds) > 0) {
                $authorStmt = $pdo->prepare(
                    "INSERT INTO book_authors (book_id, author_id)
                     VALUES (?, ?)"
                );

                foreach ($authorIds as $authorId) {
                    $authorStmt->execute([$bookId, $authorId]);
                }
            }

            $pdo->commit();

            set_flash('success', 'Book added successfully!');
            redirect('/Library_system/books/index.php');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = $e instanceof PDOException
                ? 'Unable to save the book. Check that the ISBN is unique.'
                : $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Book | Library Management System</title>

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

        .form-group label {
            display: block;
            font-weight: 600;
            color: #172B4D;
            margin-bottom: 8px;
        }

        input,
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #CBD2DC;
            border-radius: 6px;
            font-size: 15px;
            background: white;
            color: #333333;
            font-family: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        select[multiple] {
            min-height: 150px;
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

        .errors ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .errors li {
            margin-bottom: 5px;
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
            <h1>Add New Book</h1>
            <p>Enter the book details below to add it to the library.</p>
        </div>

        <div class="form-body">

            <?php if ($errors): ?>
                <div class="errors">
                    <strong>Please correct the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
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
                        value="<?= e($isbn) ?>"
                        required
                        maxlength="20"
                        placeholder="Enter book ISBN"
                    >
                </div>

                <div class="form-group">
                    <label for="title">Book Title</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?= e($title) ?>"
                        required
                        placeholder="Enter book title"
                    >
                </div>

                <div class="form-group">
                    <label for="publisher">Publisher</label>
                    <input
                        type="text"
                        id="publisher"
                        name="publisher"
                        value="<?= e($publisher) ?>"
                        required
                        placeholder="Enter publisher name"
                    >
                </div>

                <div class="form-group">
                    <label for="year_published">Year Published</label>
                    <input
                        type="number"
                        id="year_published"
                        name="year_published"
                        min="1901"
                        max="<?= date('Y') ?>"
                        value="<?= e($year) ?>"
                        required
                        placeholder="Enter publication year"
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Book Category</label>

                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select Category --</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= e($category['category_id']) ?>"
                                <?= (string)$categoryId === (string)$category['category_id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="authors">Author(s)</label>

                    <select
                        id="authors"
                        name="authors[]"
                        multiple
                    >
                        <?php foreach ($authors as $author): ?>
                            <option
                                value="<?= e($author['author_id']) ?>"
                                <?= in_array((string)$author['author_id'], array_map('strval', $selectedAuthors), true) ? 'selected' : '' ?>
                            >
                                <?= e($author['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class="hint">
                        Hold CTRL while clicking to select more than one author.
                    </p>
                </div>

                <div class="form-group">
                    <label for="total_copies">Total Copies</label>

                    <input
                        type="number"
                        id="total_copies"
                        name="total_copies"
                        min="1"
                        value="<?= e($totalCopies) ?>"
                        required
                    >
                </div>

                <div class="buttons">
                    <button type="submit">Save Book</button>

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

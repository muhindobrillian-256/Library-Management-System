
<?php
//This file manages book records in the library management system.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$search = trim($_GET['search'] ?? '');

try {
    $sql = "
        SELECT
            b.book_id,
            b.isbn,
            b.title,
            b.publisher,
            b.year_published,
            b.total_copies,
            b.available_copies,
            c.name AS category_name,
            GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ', ') AS authors
        FROM books b
        LEFT JOIN categories c
            ON b.category_id = c.category_id
        LEFT JOIN book_authors ba
            ON b.book_id = ba.book_id
        LEFT JOIN authors a
            ON ba.author_id = a.author_id
    ";

    $params = [];

    if ($search !== '') {
        $sql .= "
            WHERE b.title LIKE :title
               OR b.isbn LIKE :isbn
               OR b.publisher LIKE :publisher
               OR a.name LIKE :author
               OR c.name LIKE :category
        ";

        $keyword = "%{$search}%";

        $params = [
            'title' => $keyword,
            'isbn' => $keyword,
            'publisher' => $keyword,
            'author' => $keyword,
            'category' => $keyword
        ];
    }

    $sql .= "
        GROUP BY
            b.book_id,
            b.isbn,
            b.title,
            b.publisher,
            b.year_published,
            b.total_copies,
            b.available_copies,
            c.name
        ORDER BY b.title ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Unable to retrieve books. Please check your database.');
}

$flash = get_flash();

$totalBooks = count($books);
$availableBooks = 0;
$unavailableBooks = 0;

foreach ($books as $book) {
    if ((int)$book['available_copies'] > 0) {
        $availableBooks++;
    } else {
        $unavailableBooks++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Books | Library Management System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #F1F3F6;
            color: #263449;
        }

        header {
            background: #172B4D;
            color: #FFFFFF;
            padding: 20px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 3px 12px rgba(16, 33, 58, 0.15);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #334968;
            border-radius: 11px;
            font-size: 23px;
        }

        header h2 {
            margin: 0;
            font-size: 20px;
        }

        header p {
            margin: 4px 0 0;
            color: #C4CEDC;
            font-size: 12px;
        }

        .logout {
            color: #FFFFFF;
            background: #405675;
            padding: 11px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .logout:hover {
            background: #536C90;
        }

        .container {
            max-width: 1500px;
            margin: 35px auto;
            padding: 0 25px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0;
            font-size: 29px;
            color: #172B4D;
        }

        .page-heading p {
            margin: 7px 0 0;
            color: #718096;
            font-size: 14px;
        }

        .add-button {
            background: #2563EB;
            color: #FFFFFF;
            padding: 13px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s;
        }

        .add-button:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: #FFFFFF;
            border: 1px solid #E0E5EC;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(23, 43, 77, 0.04);
        }

        .summary-card p {
            margin: 0 0 9px;
            color: #718096;
            font-size: 13px;
            font-weight: 600;
        }

        .summary-card h2 {
            margin: 0;
            color: #172B4D;
            font-size: 27px;
        }

        .summary-card.unavailable-card h2 {
            color: #DC2626;
        }

        .search-box {
            background: #FFFFFF;
            padding: 21px;
            border: 1px solid #E0E5EC;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(23, 43, 77, 0.04);
        }

        .search-box form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-box input {
            flex: 1;
            min-width: 220px;
            padding: 13px 15px;
            border: 1px solid #D9DEE7;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            color: #263449;
        }

        .search-box input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .search-box button {
            background: #172B4D;
            color: #FFFFFF;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .search-box button:hover {
            background: #10213A;
        }

        .clear-button {
            padding: 12px 14px;
            color: #52647D;
            text-decoration: none;
            font-weight: 600;
            align-self: center;
            font-size: 14px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
        }

        .section-heading h2 {
            margin: 0;
            color: #172B4D;
            font-size: 19px;
        }

        .count {
            background: #E3EAF4;
            color: #172B4D;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .table-container {
            background: #FFFFFF;
            border: 1px solid #E0E5EC;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(23, 43, 77, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th, td {
            padding: 16px 14px;
            text-align: left;
            border-bottom: 1px solid #E9EDF2;
            font-size: 13px;
        }

        th {
            background: #E9EDF3;
            color: #172B4D;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        td {
            color: #526174;
        }

        tbody tr:hover {
            background: #F7F9FC;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .book-id {
            color: #172B4D;
            font-weight: 700;
        }

        .book-title {
            color: #172B4D;
            font-weight: 600;
        }

        .available {
            display: inline-block;
            background: #DCFCE7;
            color: #166534;
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 11px;
        }

        .unavailable {
            display: inline-block;
            background: #FEE2E2;
            color: #B91C1C;
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 11px;
        }

        .edit {
            display: inline-block;
            color: #1D4ED8;
            background: #DBEAFE;
            padding: 7px 11px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            margin-right: 5px;
        }

        .edit:hover {
            background: #BFDBFE;
        }

        .delete {
            color: #B91C1C;
            background: #FEE2E2;
            border: none;
            padding: 7px 11px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
        }

        .delete:hover {
            background: #FECACA;
        }

        .message {
            padding: 14px 17px;
            margin-bottom: 20px;
            border-radius: 9px;
            font-size: 14px;
        }

        .message.success {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #BBE5C6;
        }

        .message.error {
            background: #FEE2E2;
            color: #B42318;
            border: 1px solid #F5C7C2;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .back {
            display: inline-block;
            margin-top: 23px;
            color: #2563EB;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .back:hover {
            color: #172B4D;
        }

        footer {
            text-align: center;
            padding: 28px 15px;
            margin-top: 35px;
            color: #8290A3;
            font-size: 12px;
        }

        @media (max-width: 700px) {
            header {
                padding: 17px 18px;
            }

            .container {
                margin: 25px auto;
                padding: 0 14px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .add-button {
                width: 100%;
                text-align: center;
            }

            .summary-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .search-box {
                padding: 15px;
            }

            .search-box input {
                min-width: 100%;
            }

            .search-box button {
                flex: 1;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="brand">
        <div class="brand-icon">📚</div>
        <div>
            <h2>Library Management System</h2>
            <p>Manage your library collection</p>
        </div>
    </div>

    <a href="/Library_system/auth/logout.php" class="logout">
        ↪ Logout
    </a>
</header>

<main class="container">

    <div class="page-heading">
        <div>
            <h1>Books Management</h1>
            <p>View, search and manage all books in your library.</p>
        </div>

        <a href="/Library_system/books/create.php" class="add-button">
            + Add New Book
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="summary-grid">

        <div class="summary-card">
            <p>Books Displayed</p>
            <h2><?= $totalBooks ?></h2>
        </div>

        <div class="summary-card">
            <p>Available Titles</p>
            <h2><?= $availableBooks ?></h2>
        </div>

        <div class="summary-card unavailable-card">
            <p>Out of Stock Titles</p>
            <h2><?= $unavailableBooks ?></h2>
        </div>

    </div>

    <div class="search-box">
        <form method="GET" action="">

            <input
                type="text"
                name="search"
                placeholder="Search by title, ISBN, publisher, author or category..."
                value="<?= e($search) ?>"
            >

            <button type="submit">Search</button>

            <?php if ($search !== ''): ?>
                <a href="index.php" class="clear-button">Clear Search</a>
            <?php endif; ?>

        </form>
    </div>

    <div class="section-heading">
        <h2>Library Book Records</h2>

        <span class="count">
            <?= $totalBooks ?> Books Displayed
        </span>
    </div>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>ISBN</th>
                    <th>Book Title</th>
                    <th>Author(s)</th>
                    <th>Category</th>
                    <th>Publisher</th>
                    <th>Year</th>
                    <th>Total Copies</th>
                    <th>Available Copies</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($books) > 0): ?>

                    <?php foreach ($books as $book): ?>

                        <tr>
                            <td class="book-id">
                                #<?= (int)$book['book_id'] ?>
                            </td>

                            <td><?= e($book['isbn']) ?></td>

                            <td class="book-title">
                                <?= e($book['title']) ?>
                            </td>

                            <td>
                                <?= e($book['authors'] ?: 'Not assigned') ?>
                            </td>

                            <td>
                                <?= e($book['category_name'] ?: 'Uncategorised') ?>
                            </td>

                            <td><?= e($book['publisher']) ?></td>

                            <td><?= e($book['year_published']) ?></td>

                            <td><?= (int)$book['total_copies'] ?></td>

                            <td><?= (int)$book['available_copies'] ?></td>

                            <td>
                                <?php if ((int)$book['available_copies'] > 0): ?>
                                    <span class="available">Available</span>
                                <?php else: ?>
                                    <span class="unavailable">Out of Stock</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    class="edit"
                                    href="edit.php?id=<?= (int)$book['book_id'] ?>"
                                >Edit</a>

                                <form
                                    method="POST"
                                    action="/Library_system/books/delete.php"
                                    style="display:inline;"
                                    onsubmit="return confirm('Are you sure you want to delete this book?');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$book['book_id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <button type="submit" class="delete">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="11" class="empty">
                            No books found. Try changing your search.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>


    </div>

    <a href="/Library_system/dashboard/index.php" class="back">
        &larr; Back to Dashboard
    </a>

</main>

<footer>
    Library Management System &copy; <?= date('Y') ?>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('table');
    if (!table) return;
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Quick filter books by title, author, or ISBN...';
    searchInput.className = 'form-control mb-3';
    table.parentNode.insertBefore(searchInput, table);

    searchInput.addEventListener('keyup', function() {
        const term = this.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
});
</script>
</body>
</html>

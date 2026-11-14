
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        a.author_id,
        a.name,
        a.country,
        COUNT(DISTINCT ba.book_id) AS total_books
    FROM authors a
    LEFT JOIN book_authors ba ON a.author_id = ba.author_id
";

$params = [];

if ($search !== '') {
    $sql .= " WHERE a.name LIKE ? OR a.country LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= "
    GROUP BY a.author_id, a.name, a.country
    ORDER BY a.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$authors = $stmt->fetchAll();

$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Authors Management | Library Management System</title>

    <link rel="stylesheet" href="/Library_system/assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #F1F3F6;
            color: #263445;
            font-family: Arial, sans-serif;
        }

        .top-header {
            background: #172B4D;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .brand h2 {
            margin: 0;
            font-size: 22px;
        }

        .brand p {
            margin: 5px 0 0;
            color: #CBD5E1;
            font-size: 13px;
        }

        .logout-link {
            background: #334A6E;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
        }

        .logout-link:hover {
            background: #405C85;
        }

        .page-container {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 20px 30px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .page-heading h2 {
            color: #172B4D;
            margin: 0 0 7px;
            font-size: 25px;
        }

        .page-heading p {
            color: #64748B;
            margin: 0;
        }

        .add-button {
            background: #2563EB;
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .add-button:hover {
            background: #1D4ED8;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 9px;
            border-left: 5px solid #2563EB;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 22px;
        }

        .summary-card p {
            color: #64748B;
            margin: 0 0 8px;
        }

        .summary-card h3 {
            color: #172B4D;
            font-size: 28px;
            margin: 0;
        }

        .search-card {
            background: white;
            padding: 20px;
            border-radius: 9px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-form input {
            padding: 11px 13px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            flex: 1;
            min-width: 220px;
            font-size: 14px;
        }

        .search-form input:focus {
            outline: none;
            border-color: #2563EB;
        }

        .search-form button {
            padding: 11px 18px;
            background: #2563EB;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .search-form button:hover {
            background: #1D4ED8;
        }

        .clear-link {
            padding: 11px 15px;
            color: #475569;
            text-decoration: none;
            background: #E9EDF3;
            border-radius: 6px;
        }

        .clear-link:hover {
            background: #DCE3EC;
        }

        .table-card {
            background: white;
            border-radius: 9px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table-title {
            padding: 18px 20px;
            border-bottom: 1px solid #E2E8F0;
        }

        .table-title h3 {
            margin: 0;
            color: #172B4D;
            font-size: 17px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #E9EDF3;
        }

        th {
            background: #E9EDF3;
            color: #334155;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            color: #475569;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #F8FAFC;
        }

        .author-name {
            color: #172B4D;
            font-weight: bold;
        }

        .book-count {
            display: inline-block;
            background: #DBEAFE;
            color: #1D4ED8;
            padding: 5px 11px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }

        .edit-link {
            display: inline-block;
            color: #2563EB;
            text-decoration: none;
            font-weight: bold;
            margin-right: 12px;
        }

        .edit-link:hover {
            text-decoration: underline;
        }

        .delete-button {
            background: #DC2626;
            color: white;
            border: none;
            padding: 8px 11px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #B91C1C;
        }

        .message {
            padding: 13px 16px;
            margin-bottom: 18px;
            border-radius: 6px;
        }

        .success {
            background: #DCFCE7;
            color: #166534;
        }

        .error {
            background: #FEE2E2;
            color: #991B1B;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: #64748B;
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

        @media (max-width: 600px) {
            .top-header {
                padding: 17px 20px;
            }

            .page-container {
                margin-top: 22px;
                padding: 0 12px 25px;
            }

            .page-heading h2 {
                font-size: 22px;
            }

            .add-button {
                width: 100%;
                text-align: center;
            }

            .search-form {
                flex-direction: column;
            }

            .search-form input,
            .search-form button,
            .clear-link {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header class="top-header">
    <div class="brand">
        <h2>📚 Library System</h2>
        <p>MANAGEMENT PORTAL</p>
    </div>

    <a href="/Library_system/auth/logout.php" class="logout-link">
        Logout
    </a>
</header>

<div class="page-container">

    <div class="page-heading">
        <div>
            <h2>Authors Management</h2>
            <p>Manage the authors registered in the library.</p>
        </div>

        <a href="create.php" class="add-button">
            + Add Author
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['type']) === 'error' ? 'error' : 'success' ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="summary-card">
        <p>Authors Found</p>
        <h3><?= count($authors) ?></h3>
    </div>

    <div class="search-card">
        <form method="GET" action="index.php" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by author name or country..."
                value="<?= e($search) ?>"
            >

            <button type="submit">Search</button>

            <a href="index.php" class="clear-link">Clear</a>

        </form>
    </div>

    <div class="table-card">

        <div class="table-title">
            <h3>Registered Authors</h3>
        </div>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Author Name</th>
                        <th>Country</th>
                        <th>Total Books</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($authors)): ?>

                    <tr>
                        <td colspan="5" class="empty-message">
                            No authors found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($authors as $index => $author): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>

                            <td class="author-name">
                                <?= e($author['name']) ?>
                            </td>

                            <td><?= e($author['country']) ?></td>

                            <td>
                                <span class="book-count">
                                    <?= e($author['total_books']) ?> Books
                                </span>
                            </td>

                            <td>
                                <a
                                    href="edit.php?id=<?= e($author['author_id']) ?>"
                                    class="edit-link"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="delete.php"
                                    style="display:inline;"
                                    onsubmit="return confirm('Are you sure you want to delete this author?');"
                                >
                                    <input
                                        type="hidden"
                                        name="author_id"
                                        value="<?= e($author['author_id']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <button type="submit" class="delete-button">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

    <a href="/Library_system/dashboard/index.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>

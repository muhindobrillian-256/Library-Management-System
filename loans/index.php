
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        l.loan_id,
        l.date_issued,
        l.due_date,
        l.date_returned,
        l.status,
        m.member_number,
        m.full_name,
        b.isbn,
        b.title
    FROM loans l
    INNER JOIN members m ON l.member_id = m.member_id
    INNER JOIN books b ON l.book_id = b.book_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            m.full_name LIKE ?
            OR m.member_number LIKE ?
            OR b.title LIKE ?
            OR b.isbn LIKE ?
        )
    ";

    $searchTerm = "%$search%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (in_array($status, ['borrowed', 'returned'], true)) {
    $sql .= " AND l.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY l.loan_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$loans = $stmt->fetchAll();

$flash = get_flash();
$overdueCount = 0;

foreach ($loans as $loan) {
    if (
        $loan['status'] === 'borrowed'
        && $loan['due_date'] < date('Y-m-d')
    ) {
        $overdueCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Loans Management | Library System</title>

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
            color: #172B4D;
            font-size: 29px;
        }

        .page-heading p {
            margin: 7px 0 0;
            color: #718096;
            font-size: 14px;
        }

        .issue-button {
            background: #2563EB;
            color: #FFFFFF;
            padding: 13px 19px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .issue-button:hover {
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

        .summary-card.overdue h2 {
            color: #DC2626;
        }

        .filter-card {
            background: #FFFFFF;
            padding: 21px;
            border: 1px solid #E0E5EC;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(23, 43, 77, 0.04);
        }

        .filter-card form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-card input,
        .filter-card select {
            flex: 1;
            min-width: 200px;
            padding: 13px 14px;
            border: 1px solid #D9DEE7;
            border-radius: 8px;
            background: #FFFFFF;
            color: #263449;
            font-size: 14px;
            outline: none;
        }

        .filter-card input:focus,
        .filter-card select:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .search-button {
            background: #172B4D;
            color: #FFFFFF;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .search-button:hover {
            background: #10213A;
        }

        .clear-button {
            color: #52647D;
            text-decoration: none;
            padding: 12px 10px;
            align-self: center;
            font-size: 14px;
            font-weight: 600;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
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
            min-width: 1150px;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px 13px;
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

        .loan-id {
            color: #172B4D;
            font-weight: 700;
        }

        .member-name,
        .book-title {
            color: #172B4D;
            font-weight: 600;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .borrowed {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .returned {
            background: #DCFCE7;
            color: #166534;
        }

        .overdue {
            display: inline-block;
            margin-top: 5px;
            padding: 5px 9px;
            background: #FEE2E2;
            color: #B91C1C;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
        }

        .return-button {
            background: #172B4D;
            color: #FFFFFF;
            border: none;
            padding: 9px 12px;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .return-button:hover {
            background: #2563EB;
        }

        .returned-text {
            color: #16803D;
            font-weight: 600;
            font-size: 12px;
        }

        .alert {
            padding: 14px 17px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert.success {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #BBE5C6;
        }

        .alert.error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #F5C2C2;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .back {
            display: inline-block;
            margin-top: 24px;
            color: #2563EB;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
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

            .issue-button {
                width: 100%;
                text-align: center;
            }

            .summary-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .filter-card {
                padding: 15px;
            }

            .filter-card input,
            .filter-card select {
                min-width: 100%;
            }

            .search-button {
                flex: 1;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="brand">
        <div class="brand-icon">📋</div>
        <div>
            <h2>Library Management System</h2>
            <p>Book borrowing and returns portal</p>
        </div>
    </div>

    <a href="/Library_system/auth/logout.php" class="logout">
        ↪ Logout
    </a>
</header>

<main class="container">

    <div class="page-heading">
        <div>
            <h1>Loans Management</h1>
            <p>Manage book borrowing, returns and overdue records.</p>
        </div>

        <a href="issue.php" class="issue-button">
            + Issue Book
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="summary-grid">

        <div class="summary-card">
            <p>Total Loan Records</p>
            <h2><?= count($loans) ?></h2>
        </div>

        <div class="summary-card">
            <p>Currently Borrowed</p>
            <h2>
                <?php
                $borrowedCount = 0;
                foreach ($loans as $loan) {
                    if ($loan['status'] === 'borrowed') {
                        $borrowedCount++;
                    }
                }
                echo $borrowedCount;
                ?>
            </h2>
        </div>

        <div class="summary-card overdue">
            <p>Overdue Loans</p>
            <h2><?= $overdueCount ?></h2>
        </div>

    </div>

    <div class="filter-card">
        <form method="GET" action="index.php">

            <input
                type="text"
                name="search"
                placeholder="Search member name, member number or book..."
                value="<?= e($search) ?>"
            >

            <select name="status">

                <option value="">All Loans</option>

                <option value="borrowed"
                    <?= $status === 'borrowed' ? 'selected' : '' ?>>
                    Borrowed
                </option>

                <option value="returned"
                    <?= $status === 'returned' ? 'selected' : '' ?>>
                    Returned
                </option>

            </select>

            <button type="submit" class="search-button">
                Search
            </button>

            <a href="index.php" class="clear-button">
                Clear
            </a>

        </form>
    </div>

    <div class="section-heading">
        <h2>Loan Records</h2>

        <span class="count">
            <?= count($loans) ?> Records Displayed
        </span>
    </div>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Member Number</th>
                    <th>Member Name</th>
                    <th>Book ISBN</th>
                    <th>Book Title</th>
                    <th>Date Issued</th>
                    <th>Due Date</th>
                    <th>Date Returned</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($loans): ?>

                <?php foreach ($loans as $loan): ?>

                    <?php
                    $isOverdue = (
                        $loan['status'] === 'borrowed'
                        && $loan['due_date'] < date('Y-m-d')
                    );
                    ?>

                    <tr>

                        <td class="loan-id">
                            #<?= (int)$loan['loan_id'] ?>
                        </td>

                        <td>
                            <?= e($loan['member_number']) ?>
                        </td>

                        <td class="member-name">
                            <?= e($loan['full_name']) ?>
                        </td>

                        <td>
                            <?= e($loan['isbn']) ?>
                        </td>

                        <td class="book-title">
                            <?= e($loan['title']) ?>
                        </td>

                        <td>
                            <?= e($loan['date_issued']) ?>
                        </td>

                        <td>
                            <?= e($loan['due_date']) ?>
                        </td>

                        <td>
                            <?= $loan['date_returned']
                                ? e($loan['date_returned'])
                                : 'Not returned' ?>
                        </td>

                        <td>
                            <span class="status <?= e($loan['status']) ?>">
                                <?= e(ucfirst($loan['status'])) ?>
                            </span>

                            <?php if ($isOverdue): ?>
                                <br>
                                <span class="overdue">
                                    ⚠ OVERDUE
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>

                            <?php if ($loan['status'] === 'borrowed'): ?>

                                <form
                                    method="POST"
                                    action="return.php"
                                    onsubmit="return confirm('Confirm that this book has been returned?');"
                                >

                                    <input
                                        type="hidden"
                                        name="loan_id"
                                        value="<?= (int)$loan['loan_id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <button type="submit" class="return-button">
                                        Return Book
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="returned-text">
                                    ✓ Already Returned
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="10" class="empty">
                        No loan records found. Try changing your search or filter.
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

</body>
</html>

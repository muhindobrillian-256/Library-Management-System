
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Search and filter values
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

// Build the query
$sql = "
    SELECT *
    FROM members
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            full_name LIKE ?
            OR member_number LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchTerm = "%$search%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (in_array($status, ['active', 'suspended'], true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY member_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Members Management | Library System</title>

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
            max-width: 1450px;
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

        .add-button {
            background: #2563EB;
            color: #FFFFFF;
            padding: 13px 19px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .add-button:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
        }

        .filter-card {
            background: #FFFFFF;
            padding: 21px;
            border: 1px solid #E0E5EC;
            border-radius: 12px;
            margin-bottom: 23px;
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
            min-width: 190px;
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
            margin: 28px 0 15px;
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
            min-width: 950px;
            border-collapse: collapse;
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

        .member-name {
            color: #172B4D;
            font-weight: 700;
        }

        .member-number {
            color: #52647D;
            font-weight: 600;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .active {
            background: #DCFCE7;
            color: #166534;
        }

        .suspended {
            background: #FEE2E2;
            color: #991B1B;
        }

        .edit-button {
            display: inline-block;
            color: #1D4ED8;
            background: #E8F0FF;
            padding: 7px 11px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            margin-right: 5px;
        }

        .edit-button:hover {
            background: #D6E4FF;
        }

        .delete-button {
            color: #B42318;
            background: #FEECEC;
            border: none;
            padding: 7px 11px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #FBD5D5;
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

        @media (max-width: 600px) {
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
        <div class="brand-icon">👥</div>
        <div>
            <h2>Library Management System</h2>
            <p>Member administration portal</p>
        </div>
    </div>

    <a href="/Library_system/auth/logout.php" class="logout">
        ↪ Logout
    </a>
</header>

<main class="container">

    <div class="page-heading">
        <div>
            <h1>Members Management</h1>
            <p>Register, search and manage your library members.</p>
        </div>

        <a href="create.php" class="add-button">
            + Register New Member
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="filter-card">
        <form method="GET" action="index.php">

            <input
                type="text"
                name="search"
                placeholder="Search name, member number, email or phone..."
                value="<?= e($search) ?>"
            >

            <select name="status">
                <option value="">All Members</option>

                <option value="active"
                    <?= $status === 'active' ? 'selected' : '' ?>>
                    Active
                </option>

                <option value="suspended"
                    <?= $status === 'suspended' ? 'selected' : '' ?>>
                    Suspended
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
        <h2>Registered Members</h2>

        <span class="count">
            <?= count($members) ?> Members Displayed
        </span>
    </div>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member Number</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if (count($members) > 0): ?>

                <?php foreach ($members as $member): ?>

                    <tr>

                        <td>
                            <?= (int)$member['member_id'] ?>
                        </td>

                        <td class="member-number">
                            <?= e($member['member_number']) ?>
                        </td>

                        <td class="member-name">
                            <?= e($member['full_name']) ?>
                        </td>

                        <td>
                            <?= e($member['email']) ?>
                        </td>

                        <td>
                            <?= e($member['phone']) ?>
                        </td>

                        <td>
                            <span class="status <?= e($member['status']) ?>">
                                <?= e(ucfirst($member['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($member['date_joined']) ?>
                        </td>

                        <td>
                            <a
                                class="edit-button"
                                href="edit.php?id=<?= (int)$member['member_id'] ?>"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="/Library_system/members/delete.php"
                                style="display:inline;"
                                onsubmit="return confirm('Are you sure you want to delete this member?');"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$member['member_id'] ?>"
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

            <?php else: ?>

                <tr>
                    <td colspan="8" class="empty">
                        No members found. Try changing your search or filter.
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

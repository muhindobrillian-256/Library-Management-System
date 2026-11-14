
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Get library statistics.
try {
    $totalBooks = $pdo->query(
        "SELECT COUNT(*) FROM books"
    )->fetchColumn();

    $totalMembers = $pdo->query(
        "SELECT COUNT(*) FROM members"
    )->fetchColumn();

    $borrowedBooks = $pdo->query(
        "SELECT COUNT(*) FROM loans WHERE status = 'borrowed'"
    )->fetchColumn();

    $overdueBooks = $pdo->query(
        "SELECT COUNT(*) FROM loans
         WHERE status = 'borrowed' AND due_date < CURDATE()"
    )->fetchColumn();

    $availableCopies = $pdo->query(
        "SELECT COALESCE(SUM(available_copies), 0) FROM books"
    )->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Unable to load dashboard statistics. Please check your database.');
}

$fullName = $_SESSION['full_name'] ?? 'Library User';
$role = $_SESSION['role'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Library Management System</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f3f5f9;
            color: #26374c;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 255px;
            background: #142943;
            color: white;
            padding: 28px 18px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .brand-icon {
            font-size: 32px;
        }

        .brand h2 {
            font-size: 17px;
            line-height: 1.4;
        }

        .brand small {
            display: block;
            color: #d4af37;
            font-size: 11px;
            font-weight: normal;
            letter-spacing: 1px;
        }

        .nav-heading {
            color: #aab8ca;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 30px 10px 13px;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-menu a {
            color: #dce5ef;
            text-decoration: none;
            padding: 13px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            transition: 0.2s;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: #263f5e;
            color: #ffffff;
            border-left: 4px solid #d4af37;
        }

        .nav-icon {
            font-size: 19px;
            width: 23px;
            text-align: center;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.12);
        }

        .logout-link {
            display: block;
            padding: 13px;
            background: rgba(220, 38, 38, 0.15);
            color: #ffb4b4;
            text-decoration: none;
            text-align: center;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .logout-link:hover {
            background: #b91c1c;
            color: white;
        }

        /* MAIN CONTENT */

        .main-content {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            background: white;
            padding: 19px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #e5eaf0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.025);
        }

        .topbar h2 {
            color: #172b47;
            font-size: 20px;
        }

        .top-logout {
            background: #d4af37;
            color: #172b47;
            padding: 11px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
        }

        .top-logout:hover {
            background: #bd9829;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e8edf4;
            color: #172b47;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 17px;
        }

        .profile-details strong {
            display: block;
            color: #26374c;
            font-size: 13px;
        }

        .profile-details small {
            color: #8491a3;
            font-size: 12px;
        }

        .content {
            padding: 35px;
            max-width: 1500px;
            margin: auto;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            font-size: 27px;
            color: #172b47;
            margin-bottom: 9px;
        }

        .welcome p {
            color: #78869a;
            font-size: 14px;
        }

        .gold-line {
            width: 55px;
            height: 4px;
            background: #d4af37;
            border-radius: 5px;
            margin-top: 15px;
        }

        /* STATISTICS */

        .stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 42px;
        }

        .stat-card {
            background: white;
            padding: 23px 19px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(25,45,70,0.05);
            border: 1px solid #edf0f4;
            border-top: 4px solid #d4af37;
            transition: 0.25s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 9px 22px rgba(25,45,70,0.09);
        }

        .stat-icon {
            font-size: 25px;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 13px;
            color: #7c899a;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .stat-card p {
            font-size: 30px;
            font-weight: 700;
            color: #172b47;
        }

        .stat-card.overdue {
            border-top-color: #d9534f;
        }

        .stat-card.overdue p {
            color: #c0392b;
        }

        /* MANAGEMENT MENU */

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .section-heading h2 {
            color: #172b47;
            font-size: 21px;
        }

        .section-heading span {
            color: #8a96a7;
            font-size: 12px;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            border: 1px solid #e9edf2;
            padding: 25px;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 3px 12px rgba(25,45,70,0.04);
            transition: 0.25s;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #d4af37;
            box-shadow: 0 9px 22px rgba(25,45,70,0.09);
        }

        .menu-card .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #edf2f8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 18px;
        }

        .menu-card h3 {
            color: #172b47;
            font-size: 17px;
            margin-bottom: 10px;
        }

        .menu-card p {
            color: #7b8797;
            font-size: 13px;
            line-height: 1.7;
        }

        .menu-card .arrow {
            display: block;
            text-align: right;
            color: #bd9829;
            font-size: 20px;
            margin-top: 12px;
        }

        footer {
            text-align: center;
            padding: 30px 15px;
            color: #929cac;
            font-size: 12px;
        }

        /* RESPONSIVE DESIGN */

        @media(max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .menu {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 750px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 18px;
            }

            .brand {
                padding-bottom: 15px;
            }

            .nav-heading {
                margin-top: 20px;
            }

            .nav-menu {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
            }

            .sidebar-bottom {
                margin-top: 18px;
                padding-top: 15px;
            }

            .topbar {
                padding: 17px 20px;
            }

            .content {
                padding: 25px 18px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 13px;
            }

            .stat-card {
                padding: 18px;
            }
        }

        @media(max-width: 430px) {
            .stats,
            .menu {
                grid-template-columns: 1fr;
            }

            .profile-details {
                display: none;
            }

            .welcome h1 {
                font-size: 23px;
            }

            .topbar h2 {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">📚</div>

            <div>
                <h2>Library System</h2>
                <small>MANAGEMENT PORTAL</small>
            </div>
        </div>

        <p class="nav-heading">Main Menu</p>

        <nav class="nav-menu">

            <a href="/Library_system/dashboard/index.php" class="active">
                <span class="nav-icon">▦</span>
                Dashboard
            </a>

            <a href="/Library_system/books/index.php">
                <span class="nav-icon">📖</span>
                Books
            </a>

            <a href="/Library_system/members/index.php">
                <span class="nav-icon">👥</span>
                Members
            </a>

            <a href="/Library_system/loans/index.php">
                <span class="nav-icon">📋</span>
                Loans
            </a>

            <a href="/Library_system/authors/index.php">
                <span class="nav-icon">✍️</span>
                Authors
            </a>

            <a href="/Library_system/categories/index.php">
                <span class="nav-icon">🗂️</span>
                Categories
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="/Library_system/auth/logout.php">
                ↪ Logout
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="main-content">

        <header class="topbar">

            <h2>Dashboard Overview</h2>

            <div class="profile">

                <div class="profile-icon">
                    <?= e(strtoupper(substr($fullName, 0, 1))) ?>
                </div>

                <div class="profile-details">
                    <strong><?= e($fullName) ?></strong>
                    <small><?= e(ucfirst($role)) ?></small>
                </div>

            </div>

            <a class="top-logout" href="/Library_system/auth/logout.php">
                ↪ Logout
            </a>

        </header>

        <div class="content">

            <section class="welcome">

                <h1>Welcome back, <?= e($fullName) ?>!</h1>

                <p>
                    Here is an overview of your library's activities
                    and current records.
                </p>

                <div class="gold-line"></div>

            </section>

            <!-- STATISTICS -->

            <section class="stats">

                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <h3>Total Books</h3>
                    <p><?= e($totalBooks) ?></p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <h3>Total Members</h3>
                    <p><?= e($totalMembers) ?></p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📖</div>
                    <h3>Books Borrowed</h3>
                    <p><?= e($borrowedBooks) ?></p>
                </div>

                <div class="stat-card overdue">
                    <div class="stat-icon">⚠️</div>
                    <h3>Overdue Books</h3>
                    <p><?= e($overdueBooks) ?></p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <h3>Available Copies</h3>
                    <p><?= e($availableCopies) ?></p>
                </div>

            </section>

            <!-- MANAGEMENT MODULES -->

            <div class="section-heading">
                <h2>Library Management</h2>
                <span>Manage your library resources</span>
            </div>

            <section class="menu">

                <a class="menu-card" href="/Library_system/books/index.php">
                    <div class="card-icon">📚</div>
                    <h3>Books Management</h3>
                    <p>Add, view, edit, delete and search books in your library collection.</p>
                    <span class="arrow">→</span>
                </a>

                <a class="menu-card" href="/Library_system/members/index.php">
                    <div class="card-icon">👥</div>
                    <h3>Members Management</h3>
                    <p>Register new members and manage existing library members.</p>
                    <span class="arrow">→</span>
                </a>

                <a class="menu-card" href="/Library_system/loans/index.php">
                    <div class="card-icon">📋</div>
                    <h3>Loans Management</h3>
                    <p>Issue books, record returns and monitor overdue borrowing records.</p>
                    <span class="arrow">→</span>
                </a>

                <a class="menu-card" href="/Library_system/authors/index.php">
                    <div class="card-icon">✍️</div>
                    <h3>Authors Management</h3>
                    <p>Add, view and manage information about book authors.</p>
                    <span class="arrow">→</span>
                </a>

                <a class="menu-card" href="/Library_system/categories/index.php">
                    <div class="card-icon">🗂️</div>
                    <h3>Categories Management</h3>
                    <p>Organise and manage books according to their categories.</p>
                    <span class="arrow">→</span>
                </a>

            </section>

        </div>

        <footer>
            Library Management System &copy; <?= date('Y') ?>
            <br>
            Designed for efficient library management.
        </footer>

    </main>

</div>

</body>
</html>


<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$errors = [];
$name = '';
$description = '';
$categoryId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf_token();

    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$categoryId || $categoryId < 1) {
        $errors[] = 'Invalid category selected.';
    }

    if ($name === '') {
        $errors[] = 'Category name is required.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Category name must not exceed 100 characters.';
    }

    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare(
                "SELECT category_id
                 FROM categories
                 WHERE name = ?
                 AND category_id != ?
                 LIMIT 1"
            );

            $checkStmt->execute([$name, $categoryId]);

            if ($checkStmt->fetch()) {
                $errors[] = 'Another category already uses this name.';
            } else {
                $updateStmt = $pdo->prepare(
                    "UPDATE categories
                     SET name = ?, description = ?
                     WHERE category_id = ?"
                );

                $updateStmt->execute([
                    $name,
                    $description !== '' ? $description : null,
                    $categoryId
                ]);

                set_flash('success', 'Category updated successfully.');

                redirect('/Library_system/categories/index.php');
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'A database error occurred. Please try again.';
        }
    }

    // Confirm the category still exists.
    if ($categoryId) {
        $checkExists = $pdo->prepare(
            "SELECT category_id FROM categories WHERE category_id = ?"
        );
        $checkExists->execute([$categoryId]);

        if (!$checkExists->fetch()) {
            set_flash('error', 'Category not found.');
            redirect('/Library_system/categories/index.php');
        }
    }

} else {
    $categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$categoryId || $categoryId < 1) {
        redirect('/Library_system/categories/index.php');
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM categories WHERE category_id = ?"
    );

    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        set_flash('error', 'Category not found.');
        redirect('/Library_system/categories/index.php');
    }

    $name = $category['name'];
    $description = $category['description'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Category | Library Management System</title>

    <link rel="stylesheet" href="/Library_system/assets/css/style.css">

    <style>
        body {
            background: #F1F3F6;
        }

        .form-container {
            max-width: 650px;
            margin: 45px auto;
            padding: 0;
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

        .form-header h2 {
            margin: 0 0 8px;
            font-size: 24px;
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
            margin-bottom: 8px;
            font-weight: 600;
            color: #172B4D;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #CBD2DC;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 15px;
            background: #FFFFFF;
            color: #333333;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        .form-group textarea {
            min-height: 130px;
            resize: vertical;
        }

        .submit-button {
            background: #2563EB;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        .submit-button:hover {
            background: #1D4ED8;
        }

        .error-message {
            background: #FEE2E2;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error-message p {
            margin: 5px 0;
        }

        .back-link {
            display: inline-block;
            margin-top: 22px;
            color: #2563EB;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .form-container {
                margin: 20px 12px;
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

<div class="form-container">

    <div class="form-header">
        <h2>Edit Category</h2>
        <p>Update the category information below.</p>
    </div>

    <div class="form-body">

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="category_id"
                value="<?= e($categoryId) ?>"
            >

            <div class="form-group">
                <label for="name">Category Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    value="<?= e($name) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter category description (optional)"
                ><?= e($description) ?></textarea>
            </div>

            <button type="submit" class="submit-button">
                Update Category
            </button>

        </form>

        <a href="index.php" class="back-link">
            ← Back to Categories
        </a>

    </div>

</div>

</body>
</html>

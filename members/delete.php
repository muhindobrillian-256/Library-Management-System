
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only allow POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify CSRF security token.
verify_csrf_token();

// Validate member ID.
$memberId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$memberId || $memberId < 1) {
    set_flash('error', 'Invalid member selected.');
    redirect('/Library_system/members/index.php');
}

try {
    $pdo->beginTransaction();

    // Lock and check the member.
    $memberQuery = $pdo->prepare("
        SELECT member_id
        FROM members
        WHERE member_id = ?
        FOR UPDATE
    ");

    $memberQuery->execute([$memberId]);

    if (!$memberQuery->fetch()) {
        throw new Exception('The selected member was not found.');
    }

    // Check for unreturned loans.
    $loanQuery = $pdo->prepare("
        SELECT COUNT(*)
        FROM loans
        WHERE member_id = ?
        AND status = 'borrowed'
    ");

    $loanQuery->execute([$memberId]);

    if ((int)$loanQuery->fetchColumn() > 0) {
        throw new Exception(
            'This member cannot be deleted because they have unreturned books. Please process the returns first.'
        );
    }

    // Preserve historical loan records.
    $historyQuery = $pdo->prepare("
        SELECT COUNT(*)
        FROM loans
        WHERE member_id = ?
    ");

    $historyQuery->execute([$memberId]);

    if ((int)$historyQuery->fetchColumn() > 0) {
        throw new Exception(
            'This member has borrowing history. The member cannot be deleted because library records must be preserved.'
        );
    }

    // Delete the member.
    $deleteQuery = $pdo->prepare("
        DELETE FROM members
        WHERE member_id = ?
    ");

    $deleteQuery->execute([$memberId]);

    $pdo->commit();

    set_flash('success', 'Member deleted successfully.');

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    set_flash(
        'error',
        'The member could not be deleted. Please try again.'
    );

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', $e->getMessage());
}

redirect('/Library_system/members/index.php');
?>

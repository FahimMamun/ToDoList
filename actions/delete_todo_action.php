<?php
session_start();
require_once '../includes/db_connect.php'; // Adjust path as needed

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize todo_id
    $todo_id_raw = $_POST['id'] ?? '';
    $todo_id = filter_var($todo_id_raw, FILTER_VALIDATE_INT);

    if ($todo_id === false || $todo_id <= 0) { // Check if filter_var failed or non-positive ID
        $response['message'] = 'Invalid or missing task ID provided.';
    } else {
        // Prepare a delete statement
        $sql = "DELETE FROM todos WHERE id = ? AND user_id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $todo_id, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Task deleted successfully.';
                } else {
                    // This can mean task doesn't exist OR it doesn't belong to the user
                    $response['message'] = 'Task not found, already deleted, or you do not have permission to delete it.';
                }
            } else {
                // Error during execute
                $response['message'] = 'Error deleting task: ' . mysqli_stmt_error($stmt); // Use mysqli_stmt_error
                error_log("Error deleting task for user ID {$user_id}, Task ID {$todo_id}: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            // Error during prepare
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($link);
            error_log("Database query preparation failed for delete_todo (Task ID: {$todo_id}, User ID: {$user_id}): " . mysqli_error($link));
        }
    }
} else {
    $response['message'] = 'Invalid request method. Must be POST.';
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}

echo json_encode($response);
?>

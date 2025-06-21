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
    $todo_id_raw = $_POST['id'] ?? '';
    $task_text = trim($_POST['task'] ?? ''); // Use 'task' as per user's JS modal (editTaskInput)

    $todo_id = filter_var($todo_id_raw, FILTER_VALIDATE_INT);

    if ($todo_id === false || $todo_id <= 0) {
        $response['message'] = 'Invalid or missing task ID provided.';
    } elseif (empty($task_text)) {
        $response['message'] = 'Task description cannot be empty.';
    } else {
        // Prepare an update statement
        // Also updates 'updated_at' timestamp
        $sql = "UPDATE todos SET task = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sii", $task_text, $todo_id, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Task updated successfully.';
                    // Optionally, fetch and return the updated task if needed by frontend
                    // $response['todo'] = ['id' => $todo_id, 'task' => htmlspecialchars($task_text), /* other fields */];
                } else {
                    // This can mean task not found, not owned by user, or task text was not changed
                    $response['message'] = 'Task not found, not owned by user, or no changes were made to the task text.';
                }
            } else {
                $response['message'] = 'Error updating task: ' . mysqli_stmt_error($stmt);
                error_log("Error updating task for user ID {$user_id}, Task ID {$todo_id}: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($link);
            error_log("Database query preparation failed for edit_todo (Task ID: {$todo_id}, User ID: {$user_id}): " . mysqli_error($link));
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

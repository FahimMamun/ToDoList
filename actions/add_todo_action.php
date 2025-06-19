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
    $task = trim($_POST['task'] ?? '');

    if (empty($task)) {
        $response['message'] = 'Task cannot be empty.';
    } else {
        // Prepare an insert statement
        // Assumes a table named 'todos' with columns 'user_id', 'task', 'status'
        $sql = "INSERT INTO todos (user_id, task, status) VALUES (?, ?, 'pending')";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_id, $task);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Task added successfully.';
                $response['todo'] = [
                    'id' => mysqli_insert_id($link),
                    'user_id' => $user_id,
                    'task' => htmlspecialchars($task), // Sanitize for display
                    'status' => 'pending',
                    // These are placeholders; ideally, fetch the actual created_at/updated_at if needed from DB
                    // or rely on DB defaults and don't return them unless specifically queried.
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $response['message'] = 'Error adding task: ' . mysqli_error($link); // More specific error
                error_log("Error adding task for user ID {$user_id}: " . mysqli_stmt_error($stmt)); // Use mysqli_stmt_error for prepared statements
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($link);
            error_log("Database query preparation failed for add_todo: " . mysqli_error($link));
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

if ($link && mysqli_ping($link)) { // Check if connection is still alive before closing
    mysqli_close($link);
}

echo json_encode($response);
?>

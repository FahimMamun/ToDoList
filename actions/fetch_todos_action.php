<?php
session_start();
require_once '../includes/db_connect.php'; // Adjust path as needed

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'pending_todos' => [], 'completed_todos' => []];

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['id'];

// Fetch all todos for the logged-in user
$sql = "SELECT id, task, status, created_at, updated_at FROM todos WHERE user_id = ? ORDER BY created_at DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $todos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Ensure all expected fields are present, provide defaults if not (though DB should ensure this)
            $todos[] = [
                'id' => $row['id'] ?? null,
                'task' => $row['task'] ?? '',
                'status' => $row['status'] ?? 'pending',
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null
            ];
        }

        // Separate todos into pending and completed
        foreach ($todos as $todo) {
            $sanitized_todo = [
                'id' => htmlspecialchars((string)($todo['id'] ?? '')), // Cast to string before htmlspecialchars
                'task' => htmlspecialchars($todo['task'] ?? ''),
                'status' => htmlspecialchars($todo['status'] ?? ''),
                'created_at' => htmlspecialchars((string)($todo['created_at'] ?? '')),
                'updated_at' => htmlspecialchars((string)($todo['updated_at'] ?? ''))
            ];
            if ($todo['status'] === 'pending') {
                $response['pending_todos'][] = $sanitized_todo;
            } else { // Assuming any other status means completed for this categorization
                $response['completed_todos'][] = $sanitized_todo;
            }
        }

        $response['success'] = true;
        $response['message'] = 'Todos fetched successfully.';

    } else {
        $response['message'] = 'Error fetching tasks: ' . mysqli_stmt_error($stmt); // Use mysqli_stmt_error
        error_log("Error fetching tasks for user ID {$user_id}: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($link);
    error_log("Database query preparation failed for fetch_todos (User ID: {$user_id}): " . mysqli_error($link));
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}

echo json_encode($response);
?>

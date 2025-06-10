<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// --- Debug block to show exactly what's going on ---
$debug = [
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'post_data' => $_POST,
    'session_status' => session_status(),
];

// No email or password posted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Request must be POST',
        'debug' => $debug
    ]);
    exit;
}

if (!isset($_POST['email']) || !isset($_POST['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing email or password in request',
        'debug' => $debug
    ]);
    exit;
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);

$response = ['success' => false];

if (!$link) {
    $response['message'] = 'Database connection error.';
    $response['debug'] = $debug; // Add debug info to this error too
    echo json_encode($response);
    exit;
}

// Email validation
if (empty($email)) {
    $response['message'] = 'Please enter an email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
} else {
    // Check if email exists
    $sql_check = "SELECT id FROM users WHERE email = ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check)) {
        mysqli_stmt_bind_param($stmt_check, "s", $email); // Simplified binding
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $response['message'] = 'This email is already registered.';
        }

        mysqli_stmt_close($stmt_check);
    } else {
        $response['message'] = 'Database query error (email check).';
    }
}

// Password validation (only if email validation passed and no error message is set yet)
if (empty($response['message'])) {
    if (empty($password)) {
        $response['message'] = 'Please enter a password.';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'Password must have at least 6 characters.';
    }
}

// Register user (only if all previous checks passed and no error message is set)
if (empty($response['message'])) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql_insert = "INSERT INTO users (email, password) VALUES (?, ?)";

    if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
        mysqli_stmt_bind_param($stmt_insert, "ss", $email, $hashed_password);

        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = mysqli_insert_id($link);
            $_SESSION["email"] = $email;

            $response['success'] = true;
            $response['message'] = 'Registration successful!';
            $response['redirect'] = 'dashboard.php';
        } else {
            $response['message'] = 'Error inserting user.';
        }

        mysqli_stmt_close($stmt_insert);
    } else {
        $response['message'] = 'Database query error (insert).';
    }
}

// If there was any validation error, include debug info
if (!$response['success'] && isset($response['message'])) {
    $response['debug'] = $debug;
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];
$debug_info = [
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'post_data' => $_POST,
    'session_status' => session_status() // Added for more debug context
];

if (!$link) {
    $response['message'] = 'Database connection error.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
    } else {
        $sql = "SELECT id, email, password, first_name, last_name, is_verified FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $id = null;
                    $db_email = null;
                    $hashed_password = null;
                    $first_name = null;
                    $last_name = null;
                    $is_verified = 0; // Default to 0 (false)

                    mysqli_stmt_bind_result($stmt, $id, $db_email, $hashed_password, $first_name, $last_name, $is_verified);

                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, now check if account is verified
                            if ($is_verified == 1 || $is_verified === true) { // Check for 1 or true
                                session_regenerate_id(true);

                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["email"] = $db_email;
                                $_SESSION["first_name"] = $first_name;
                                $_SESSION["last_name"] = $last_name;

                                $response['success'] = true;
                                $response['message'] = 'Login successful!';
                                $response['redirect'] = 'dashboard.php';
                            } else {
                                // Account is not verified
                                $response['success'] = false; // Ensure success is false
                                $response['message'] = 'Your account is not verified. Please check your email for the verification code.';
                                // $response['needs_verification'] = true; // Optional flag for frontend
                            }
                        } else {
                            $response['message'] = 'Invalid email or password.';
                        }
                    } else {
                         $response['message'] = 'Error fetching user data after query execution.';
                    }
                } else {
                    $response['message'] = 'Invalid email or password.'; // Email not found
                }
            } else {
                $response['message'] = 'Error executing login query: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
             $response['message'] = 'Database query preparation failed: ' . mysqli_error($link);
        }
    }
} else {
    if ($response['message'] === 'Invalid request.') { // Only overwrite if it's the default generic one
        $response['message'] = 'Invalid request method or missing credentials.';
    }
}

if (!$response['success']) {
    $response['debug_info_on_error'] = $debug_info;
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

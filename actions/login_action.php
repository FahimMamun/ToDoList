<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];
$debug_info = [ // Initialize debug info array
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'post_data' => $_POST
];

if (!$link) {
    $response['message'] = 'Database connection error.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

// Basic validation for POST request and expected fields
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
    } else {
        // Prepare a select statement to get user details including first_name and last_name
        $sql = "SELECT id, email, password, first_name, last_name FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Declare variables for binding results
                    $id = null;
                    $db_email = null;
                    $hashed_password = null;
                    $first_name = null;
                    $last_name = null;

                    mysqli_stmt_bind_result($stmt, $id, $db_email, $hashed_password, $first_name, $last_name);

                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start/regenerate session
                            session_regenerate_id(true); // Regenerate session ID for security

                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $db_email;
                            $_SESSION["first_name"] = $first_name; // Store first name
                            $_SESSION["last_name"] = $last_name;   // Store last name

                            $response['success'] = true;
                            $response['message'] = 'Login successful!';
                            $response['redirect'] = 'dashboard.php';
                        } else {
                            // Password is not valid
                            $response['message'] = 'Invalid email or password.';
                        }
                    } else {
                         $response['message'] = 'Error fetching user data after verification.';
                    }
                } else {
                    // Email doesn't exist
                    $response['message'] = 'Invalid email or password.';
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
    // If not POST or email/password not set, update message if it's still the default
    if ($response['message'] === 'Invalid request.') {
        $response['message'] = 'Invalid request method or missing credentials.';
    }
}

// Add debug info if there was an error
if (!$response['success']) {
    $response['debug_info_on_error'] = $debug_info;
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

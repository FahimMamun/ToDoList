<?php
session_start(); // Start session at the very beginning
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $link_error_message = 'Database connection error. Please try again later.';

    if (!$link) {
        $response['message'] = $link_error_message;
    } else {
        // Validate email
        if (empty($email)) {
            $response['message'] = 'Please enter an email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format.';
        } else {
            // Check if email already exists
            $sql_check = "SELECT id FROM users WHERE email = ?";
            if ($stmt_check = mysqli_prepare($link, $sql_check)) {
                mysqli_stmt_bind_param($stmt_check, "s", $param_email_check);
                $param_email_check = $email;
                if (mysqli_stmt_execute($stmt_check)) {
                    mysqli_stmt_store_result($stmt_check);
                    if (mysqli_stmt_num_rows($stmt_check) > 0) {
                        $response['message'] = 'This email is already registered.';
                    }
                } else {
                    $response['message'] = 'Error checking email uniqueness. Please try again.';
                }
                mysqli_stmt_close($stmt_check);
            } else {
                $response['message'] = $link_error_message . " (email check prep)";
            }
        }

        // Validate password (only if email validation passed and no message is set yet)
        if (empty($response['message'])) { // Check if a message has already been set by email validation
            if (empty($password)) {
                $response['message'] = 'Please enter a password.';
            } elseif (strlen($password) < 6) {
                $response['message'] = 'Password must have at least 6 characters.';
            }
        }

        // If all validations pass and no error message is set, proceed to insert
        if (empty($response['message'])) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO users (email, password) VALUES (?, ?)";

            if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
                // Use different variable names for parameters to be bound, to avoid confusion
                $bind_email = $email;
                $bind_password = $hashed_password;
                mysqli_stmt_bind_param($stmt_insert, "ss", $bind_email, $bind_password);

                if (mysqli_stmt_execute($stmt_insert)) {
                    $new_user_id = mysqli_insert_id($link);

                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $new_user_id;
                    $_SESSION["email"] = $email; // Use the original validated $email

                    $response['success'] = true;
                    $response['message'] = 'Registration successful! Redirecting...';
                    $response['redirect'] = 'dashboard.php';
                } else {
                    $response['message'] = 'Something went wrong with storing user data. Please try again later.';
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                $response['message'] = $link_error_message . " (insert prep)";
            }
        }
    }
}

if ($link && mysqli_ping($link)) { // Check if $link is valid and connection is alive
    mysqli_close($link);
}
echo json_encode($response);
?>

<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];

// Basic validation for POST request and expected fields
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
    } else {
        // Prepare a select statement
        $sql = "SELECT id, email, password FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $db_email, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $db_email;

                            $response['success'] = true;
                            $response['message'] = 'Login successful!';
                            $response['redirect'] = 'dashboard.php';
                        } else {
                            // Password is not valid
                            $response['message'] = 'Invalid email or password.';
                        }
                    }
                } else {
                    // Email doesn't exist
                    $response['message'] = 'Invalid email or password.';
                }
            } else {
                $response['message'] = 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        } else {
             $response['message'] = 'Database query failed.';
        }
    }
}

mysqli_close($link);
echo json_encode($response);
?>

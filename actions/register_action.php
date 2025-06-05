<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

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
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $response['message'] = 'This email is already registered.';
            }
            mysqli_stmt_close($stmt_check);
        } else {
            $response['message'] = 'Database error (email check).';
        }
    }

    // Validate password
    if (empty($response['message']) && empty($password)) { // Only proceed if email validation passed and no error message set
        $response['message'] = 'Please enter a password.';
    } elseif (empty($response['message']) && strlen($password) < 6) { // Example: password min length
        $response['message'] = 'Password must have at least 6 characters.';
    }

    // If all validations pass and no error message is set, proceed to insert
    if (empty($response['message'])) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO users (email, password) VALUES (?, ?)";

        if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, "ss", $param_email, $param_password);
            $param_email = $email;
            $param_password = $hashed_password;

            if (mysqli_stmt_execute($stmt_insert)) {
                $response['success'] = true;
                $response['message'] = 'Registration successful! You can now login.';
            } else {
                $response['message'] = 'Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $response['message'] = 'Database error (insert).';
        }
    }
}

mysqli_close($link);
echo json_encode($response);
?>

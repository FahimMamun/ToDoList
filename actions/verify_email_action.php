<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];
$debug_info = [
    'post_data' => $_POST,
    'session_data_at_start' => $_SESSION ?? []
];

// Check if email and code were submitted
if (!isset($_POST['email']) || !isset($_POST['code'])) {
    $response['message'] = 'Email or verification code is missing.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email']);
$submitted_code = trim($_POST['code']); // Changed from verification_code to code

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

// Validate code format (6 digits)
if (!preg_match('/^\d{6}$/', $submitted_code)) {
    $response['message'] = 'Invalid verification code format. It must be 6 digits.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

if (!$link) {
    $response['message'] = 'Database connection error.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

// Fetch user by email to get ID and current verification status
$user_id_to_verify = null;
$is_already_verified = false;
$user_first_name = null; // To be fetched for session
$user_last_name = null;  // To be fetched for session

$sql_get_user_by_email = "SELECT id, is_verified, first_name, last_name FROM users WHERE email = ? LIMIT 1";
if ($stmt_get_user = mysqli_prepare($link, $sql_get_user_by_email)) {
    mysqli_stmt_bind_param($stmt_get_user, "s", $email);
    if (mysqli_stmt_execute($stmt_get_user)) {
        mysqli_stmt_store_result($stmt_get_user);
        if (mysqli_stmt_num_rows($stmt_get_user) == 1) {
            mysqli_stmt_bind_result($stmt_get_user, $user_id_to_verify, $is_already_verified, $user_first_name, $user_last_name);
            mysqli_stmt_fetch($stmt_get_user);
        } else {
            $response['message'] = 'Email address not found or not registered for verification.';
        }
    } else {
        $response['message'] = 'Error fetching user details by email: ' . mysqli_stmt_error($stmt_get_user);
    }
    mysqli_stmt_close($stmt_get_user);
} else {
    $response['message'] = 'Database error: Could not prepare user lookup statement: ' . mysqli_error($link);
}

if (empty($user_id_to_verify)) { // If user not found from email, exit
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

if ($is_already_verified) {
    // User is already verified, attempt to log them in directly if not already.
    // This makes the verification link idempotent for already verified users.
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user_id_to_verify;
        $_SESSION['email'] = $email; // email from POST is confirmed
        $_SESSION['first_name'] = $user_first_name; // Fetched above
        $_SESSION['last_name'] = $user_last_name;   // Fetched above
    }
    unset($_SESSION['verification_user_id']); // Clean up old session vars if any
    unset($_SESSION['verification_email']);

    $response['success'] = true;
    $response['message'] = 'Account already verified. Logging you in...';
    $response['redirect'] = 'dashboard.php';
    echo json_encode($response);
    exit;
}

// User found and not yet verified, now check the code
$sql_check_code = "SELECT verification_code, expires_at FROM email_verifications WHERE user_id = ? AND verification_code = ? ORDER BY created_at DESC LIMIT 1";
if ($stmt_check = mysqli_prepare($link, $sql_check_code)) {
    mysqli_stmt_bind_param($stmt_check, "is", $user_id_to_verify, $submitted_code);

    if (mysqli_stmt_execute($stmt_check)) {
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) == 1) {
            $db_code = null;
            $db_expires_at_str = null;
            mysqli_stmt_bind_result($stmt_check, $db_code, $db_expires_at_str);
            mysqli_stmt_fetch($stmt_check);

            try {
                $expires_at_dt = new DateTime($db_expires_at_str);
                $current_dt = new DateTime();

                if ($current_dt > $expires_at_dt) {
                    $response['message'] = 'Verification code has expired. Please request a new code.';
                    // Delete this specific expired code for this user attempt
                    $sql_delete_expired = "DELETE FROM email_verifications WHERE user_id = ? AND verification_code = ?";
                    if ($stmt_del_exp = mysqli_prepare($link, $sql_delete_expired)) {
                        mysqli_stmt_bind_param($stmt_del_exp, "is", $user_id_to_verify, $submitted_code);
                        mysqli_stmt_execute($stmt_del_exp);
                        mysqli_stmt_close($stmt_del_exp);
                    }
                } else {
                    // Code is valid and not expired - Mark user as verified
                    $sql_verify_user = "UPDATE users SET is_verified = 1 WHERE id = ?";
                    if ($stmt_verify = mysqli_prepare($link, $sql_verify_user)) {
                        mysqli_stmt_bind_param($stmt_verify, "i", $user_id_to_verify);
                        if (mysqli_stmt_execute($stmt_verify)) {
                            $sql_delete_code = "DELETE FROM email_verifications WHERE user_id = ? AND verification_code = ?";
                            // ... (delete code logic as before)
                            if ($stmt_delete = mysqli_prepare($link, $sql_delete_code)) {
                                mysqli_stmt_bind_param($stmt_delete, "is", $user_id_to_verify, $submitted_code);
                                mysqli_stmt_execute($stmt_delete);
                                mysqli_stmt_close($stmt_delete);
                            }

                            session_regenerate_id(true);
                            $_SESSION['loggedin'] = true;
                            $_SESSION['id'] = $user_id_to_verify;
                            $_SESSION['email'] = $email; // email from POST is confirmed
                            $_SESSION['first_name'] = $user_first_name; // Fetched earlier
                            $_SESSION['last_name'] = $user_last_name;   // Fetched earlier

                            unset($_SESSION['verification_user_id']); // Clean up old session vars
                            unset($_SESSION['verification_email']);

                            $response['success'] = true;
                            $response['message'] = 'Email verified successfully! Logging you in...';
                            $response['redirect'] = 'dashboard.php';
                        } else {
                            $response['message'] = 'Failed to update user verification status: ' . mysqli_stmt_error($stmt_verify);
                        }
                        mysqli_stmt_close($stmt_verify);
                    } else {
                         $response['message'] = 'Database error: Could not prepare user verification update: ' . mysqli_error($link);
                    }
                }
            } catch (Exception $e) {
                $response['message'] = 'Error processing code validity (date conversion). Please try again.';
                error_log("DateTime error in verify_email_action for user_id {$user_id_to_verify}: " . $e->getMessage() . " with date string: " . ($db_expires_at_str ?? 'NOT_FETCHED'));
            }
        } else {
            $response['message'] = 'Invalid verification code.';
        }
    } else {
        $response['message'] = 'Error executing code check query: ' . mysqli_stmt_error($stmt_check);
    }
    mysqli_stmt_close($stmt_check);
} else {
    $response['message'] = 'Database error: Could not prepare code check statement: ' . mysqli_error($link);
}

if (!$response['success']) {
    $response['debug_info_on_error'] = $debug_info;
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

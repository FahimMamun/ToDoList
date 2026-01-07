<?php
session_start(); // Session might not be strictly needed here unless we add rate limiting based on session
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred while resending the code.'];
$debug_info = [
    'post_data' => $_POST,
    'session_data' => $_SESSION ?? [] // Include session for context if needed later
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = 'Invalid request method. Must be POST.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

if (!isset($_POST['email'])) {
    $response['message'] = 'Email address is missing.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format provided.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    exit;
}

if (!$link) {
    $response['message'] = 'Database connection error. Cannot resend code.';
    $response['debug_info_on_error'] = $debug_info;
    error_log("Resend Code: Database connection failed."); // Log critical error
    echo json_encode($response);
    exit;
}

// Fetch user by email to get ID and current verification status
$user_id = null;
$is_verified = null;
$first_name = null; // For personalizing the email

$sql_get_user = "SELECT id, is_verified, first_name FROM users WHERE email = ? LIMIT 1";
if ($stmt_get_user = mysqli_prepare($link, $sql_get_user)) {
    mysqli_stmt_bind_param($stmt_get_user, "s", $email);
    if (mysqli_stmt_execute($stmt_get_user)) {
        mysqli_stmt_store_result($stmt_get_user);
        if (mysqli_stmt_num_rows($stmt_get_user) == 1) {
            mysqli_stmt_bind_result($stmt_get_user, $user_id, $is_verified, $first_name);
            mysqli_stmt_fetch($stmt_get_user);
        } else {
            $response['message'] = 'This email address is not registered.';
        }
    } else {
        $response['message'] = 'Error fetching user details: ' . mysqli_stmt_error($stmt_get_user);
    }
    mysqli_stmt_close($stmt_get_user);
} else {
    $response['message'] = 'Database error: Could not prepare user lookup: ' . mysqli_error($link);
}

if (empty($user_id)) { // User not found or DB error during lookup
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    mysqli_close($link);
    exit;
}

if ($is_verified) {
    $response['success'] = false; // Or true with an informational message
    $response['message'] = 'This account is already verified. You can try logging in.';
    // $response['already_verified'] = true; // Optional flag for frontend
    echo json_encode($response);
    mysqli_close($link);
    exit;
}

// User found and not verified, proceed to generate and send new code

// 1. Delete existing codes for this user to prevent multiple valid codes
$sql_delete_old_codes = "DELETE FROM email_verifications WHERE user_id = ?";
if ($stmt_delete_old = mysqli_prepare($link, $sql_delete_old_codes)) {
    mysqli_stmt_bind_param($stmt_delete_old, "i", $user_id);
    if (!mysqli_stmt_execute($stmt_delete_old)) {
        error_log("Resend Code: Failed to delete old verification codes for user_id: {$user_id}. Error: " . mysqli_stmt_error($stmt_delete_old));
        // Continue anyway, as inserting a new one is more critical
    }
    mysqli_stmt_close($stmt_delete_old);
} else {
    error_log("Resend Code: Failed to prepare statement for deleting old codes for user_id: {$user_id}. Error: " . mysqli_error($link));
}


// 2. Generate new code and expiry
$new_verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$validity_minutes_resend = 10; // Resend validity, can be same or different
$expires_at_str_resend = '';
try {
    $expires_at_dt_resend = (new DateTime())->add(new DateInterval("PT{$validity_minutes_resend}M"));
    $expires_at_str_resend = $expires_at_dt_resend->format('Y-m-d H:i:s');
    // created_at will use its DEFAULT CURRENT_TIMESTAMP in the table
} catch (Exception $e) {
    error_log("Resend Code: Error creating expiry date for user_id {$user_id}: " . $e->getMessage());
    $response['message'] = 'Could not prepare new verification code. Please try again later.';
    $response['debug_info_on_error'] = $debug_info;
    echo json_encode($response);
    mysqli_close($link);
    exit;
}

// 3. Store new verification code
$sql_insert_new_code = "INSERT INTO email_verifications (user_id, verification_code, expires_at) VALUES (?, ?, ?)";
if ($stmt_insert_new = mysqli_prepare($link, $sql_insert_new_code)) {
    mysqli_stmt_bind_param($stmt_insert_new, "iss", $user_id, $new_verification_code, $expires_at_str_resend);
    if (mysqli_stmt_execute($stmt_insert_new)) {
        // 4. Attempt to send email with the new code
        $to = $email;
        $subject = "Your New ToDo App Verification Code";
        $message_body = "Hello " . htmlspecialchars($first_name ?: 'User') . ",<br><br>Your new verification code is: <b>" . $new_verification_code . "</b><br><br>This code will expire in approximately " . $validity_minutes_resend . " minutes (around " . $expires_at_dt_resend->format('H:i T') . ").<br><br>Thank you,<br>ToDo App Team";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ToDo App <noreply@todoapp.example.com>' . "\r\n";

        if (mail($to, $subject, $message_body, $headers)) {
            $response['success'] = true;
            $response['message'] = 'A new verification code has been sent to ' . htmlspecialchars($email) . '.';
        } else {
            error_log("Resend Code: Failed to send new verification email to " . $email . " for user_id: " . $user_id);
            $response['message'] = 'New code generated, but failed to send verification email. Please contact support if this persists.';
            // $response['success'] could still be false here depending on desired UX
        }
    } else {
        $response['message'] = 'Error saving new verification code: ' . mysqli_stmt_error($stmt_insert_new);
        error_log("Resend Code: Error saving new verification code for user_id {$user_id}: " . mysqli_stmt_error($stmt_insert_new));
    }
    mysqli_stmt_close($stmt_insert_new);
} else {
    $response['message'] = 'Database error: Could not prepare new code insertion: ' . mysqli_error($link);
    error_log("Resend Code: Database query preparation failed for new code insertion for user_id {$user_id}: " . mysqli_error($link));
}


if (!$response['success']) {
    $response['debug_info_on_error'] = $debug_info;
}

if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

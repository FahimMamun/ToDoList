<?php
session_start();
require_once '../includes/db_connect.php'; // Adjust path as needed

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '']; // Initialize message

// --- Debug block to show exactly what's going on ---
$debug = [
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'post_data' => $_POST,
    'session_status' => session_status(),
    'current_session_data' => $_SESSION ?? [] // Log current session data at start
];

// Default response (already initialized $response, so this is redundant here, but okay)
// $response = ['success' => false];

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = 'Request must be POST';
    $response['debug_info_on_error'] = $debug;
    echo json_encode($response);
    exit;
}

// Check for mandatory fields
if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['first_name']) || !isset($_POST['last_name'])) {
    $response['message'] = 'Missing mandatory fields (email, password, first name, last name) in request';
    $response['debug_info_on_error'] = $debug;
    echo json_encode($response);
    exit;
}

// Trim and assign POST variables
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);

// Optional fields - assign null if empty or not set
$phone_no = isset($_POST['phone_no']) && trim($_POST['phone_no']) !== '' ? trim($_POST['phone_no']) : null;
$country = isset($_POST['country']) && trim($_POST['country']) !== '' ? trim($_POST['country']) : null;
$gender = isset($_POST['gender']) && trim($_POST['gender']) !== '' ? trim($_POST['gender']) : null;
$date_of_birth_str = isset($_POST['date_of_birth']) && trim($_POST['date_of_birth']) !== '' ? trim($_POST['date_of_birth']) : null;
$date_of_birth = null; // Initialize as null

if (!$link) {
    $response['message'] = 'Database connection error.';
    $response['debug_info_on_error'] = $debug;
    echo json_encode($response);
    exit;
}

// --- Start Validations ---
// Email validation
if (empty($email)) {
    $response['message'] = 'Please enter an email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
} else {
    $sql_check = "SELECT id FROM users WHERE email = ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check)) {
        mysqli_stmt_bind_param($stmt_check, "s", $email);
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

// Password validation (if no previous error)
if (empty($response['message'])) {
    if (empty($password)) {
        $response['message'] = 'Please enter a password.';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'Password must have at least 6 characters.';
    }
}
// First Name validation (if no previous error)
if (empty($response['message']) && empty($first_name)) {
    $response['message'] = 'Please enter your first name.';
}
// Last Name validation (if no previous error)
if (empty($response['message']) && empty($last_name)) {
    $response['message'] = 'Please enter your last name.';
}
// Date of Birth validation (if provided and no previous error)
if (empty($response['message']) && $date_of_birth_str !== null) {
    try {
        $d = new DateTime($date_of_birth_str);
        if (!($d && $d->format('Y-m-d') === $date_of_birth_str)) {
            $response['message'] = 'Invalid date of birth format. Please use YYYY-MM-DD.';
        } else {
            $date_of_birth = $date_of_birth_str; // Valid date
        }
    } catch (Exception $e){
         $response['message'] = 'Invalid date of birth format provided.';
    }
}
// --- End Validations ---

// Register user (only if all previous checks passed and no error message is set)
if (empty($response['message'])) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verified_status = 0; // User is not verified yet

    $sql_insert_user = "INSERT INTO users (email, password, is_verified, first_name, last_name, phone_no, country, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt_insert_user = mysqli_prepare($link, $sql_insert_user)) {
        mysqli_stmt_bind_param($stmt_insert_user, "ssissssss",
            $email, $hashed_password, $verified_status,
            $first_name, $last_name, $phone_no, $country, $gender, $date_of_birth
        );

        if (mysqli_stmt_execute($stmt_insert_user)) {
            $new_user_id = mysqli_insert_id($link);
            $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $expires_at_str = '';
            try {
                $expires_at_dt = (new DateTime())->add(new DateInterval('PT10M')); // 10 minutes expiry
                $expires_at_str = $expires_at_dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // This should ideally not happen with 'PT10M'
                error_log("Error creating expiry date: " . $e->getMessage());
                $response['message'] = 'Could not prepare verification process. Please try again later.';
                // No point continuing if we can't set an expiry
            }

            if (empty($response['message'])) { // Proceed only if expiry date was created
                $sql_insert_code = "INSERT INTO email_verifications (user_id, verification_code, expires_at) VALUES (?, ?, ?)";
                // created_at will use its DEFAULT CURRENT_TIMESTAMP

                if ($stmt_code = mysqli_prepare($link, $sql_insert_code)) {
                    mysqli_stmt_bind_param($stmt_code, "iss", $new_user_id, $verification_code, $expires_at_str);
                    if (mysqli_stmt_execute($stmt_code)) {
                        $to = $email;
                        $subject = "Your ToDo App Verification Code";
                        $message = "Your verification code is: " . $verification_code . "\n\nThis code will expire in 10 minutes (around " . $expires_at_dt->format('H:i T') . ").";
                        $headers = "From: ToDo App <noreply@todoapp.example.com>\r\n"; // Replace with your actual from email
                        $headers .= "Reply-To: noreply@todoapp.example.com\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                        if (mail($to, $subject, $message, $headers)) {
                            // Set session variables for verification step
                            $_SESSION['verification_user_id'] = $new_user_id;
                            $_SESSION['verification_email'] = $email;
                            // Ensure user is not considered logged in
                            unset($_SESSION['loggedin'], $_SESSION['id'], $_SESSION['first_name'], $_SESSION['last_name']);

                            $response['success'] = true;
                            $response['message'] = 'Registration successful! A verification code has been sent to ' . htmlspecialchars($email) . '. Please check your inbox (and spam folder).';
                            $response['redirect'] = 'verify_email.php?email=' . urlencode($email);
                        } else {
                            error_log("Failed to send verification email to " . $email . " for user ID " . $new_user_id);
                            $response['message'] = 'Registration successful, but failed to send verification email. Please try registering again or contact support if this issue persists.';
                            // Still redirect to allow manual code entry if they contact support and get the code
                            $response['redirect'] = 'verify_email.php?email=' . urlencode($email);
                            // \$response['success'] can be true here if you consider data saving a success
                            // but for UX, maybe keep it false if email fails, as user can't proceed easily.
                            // For now, let's assume data saving is a partial success but overall flow is hindered.
                            // The user code has it as success = true. Let's stick to that.
                            $response['success'] = true;
                        }
                    } else {
                        $response['message'] = 'Error saving verification code: ' . mysqli_stmt_error($stmt_code);
                        error_log("Error saving verification code for user ID {$new_user_id}: " . mysqli_stmt_error($stmt_code));
                    }
                    mysqli_stmt_close($stmt_code);
                } else {
                    $response['message'] = 'Database query preparation failed for saving verification code: ' . mysqli_error($link);
                    error_log("Database query preparation failed for saving verification code: " . mysqli_error($link));
                }
            }
        } else {
            $response['message'] = 'Error inserting user: ' . mysqli_stmt_error($stmt_insert_user);
            error_log("Error inserting user during registration: " . mysqli_stmt_error($stmt_insert_user));
        }
        mysqli_stmt_close($stmt_insert_user);
    } else {
        $response['message'] = 'Database query error (user insert prep failed): ' . mysqli_error($link);
        error_log("Database query error (user insert preparation failed for register): " . mysqli_error($link));
    }
}

// If there was any validation error or other failure, include debug info
if (!$response['success'] && isset($response['message'])) {
    $response['debug_info_on_error'] = $debug;
} elseif (empty($response['message']) && !$response['success']) {
    $response['message'] = 'An unknown error occurred during registration.'; // Fallback
}


if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

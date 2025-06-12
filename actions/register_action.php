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

// Default response
$response = ['success' => false];

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = 'Request must be POST';
    $response['debug'] = $debug;
    echo json_encode($response);
    exit;
}

// Check for mandatory fields
if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['first_name']) || !isset($_POST['last_name'])) {
    $response['message'] = 'Missing mandatory fields (email, password, first name, last name) in request';
    $response['debug'] = $debug;
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
    $response['debug'] = $debug;
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
    // Check if email exists (only if email format is valid)
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
    $d = DateTime::createFromFormat('Y-m-d', $date_of_birth_str);
    if ($d && $d->format('Y-m-d') === $date_of_birth_str) {
        $date_of_birth = $date_of_birth_str; // Valid date
    } else {
        $response['message'] = 'Invalid date of birth format. Please use YYYY-MM-DD.';
    }
}

// --- End Validations ---


// Register user (only if all previous checks passed and no error message is set)
if (empty($response['message'])) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql_insert = "INSERT INTO users (email, password, first_name, last_name, phone_no, country, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
        // All 8 parameters: email, password, first_name, last_name, phone_no, country, gender, date_of_birth
        mysqli_stmt_bind_param($stmt_insert, "ssssssss",
            $email,
            $hashed_password,
            $first_name,
            $last_name,
            $phone_no,    // Will be bound as NULL if $phone_no is null
            $country,     // Will be bound as NULL if $country is null
            $gender,      // Will be bound as NULL if $gender is null
            $date_of_birth // Will be bound as NULL if $date_of_birth is null
        );

        if (mysqli_stmt_execute($stmt_insert)) {
            $new_user_id = mysqli_insert_id($link);

            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $new_user_id;
            $_SESSION["email"] = $email;
            $_SESSION["first_name"] = $first_name; // Store first name in session
            $_SESSION["last_name"] = $last_name;   // Store last name in session

            $response['success'] = true;
            $response['message'] = 'Registration successful!';
            $response['redirect'] = 'dashboard.php';
        } else {
            $response['message'] = 'Error inserting user: ' . mysqli_stmt_error($stmt_insert);
        }
        mysqli_stmt_close($stmt_insert);
    } else {
        $response['message'] = 'Database query error (insert preparation failed): ' . mysqli_error($link);
    }
}

// If there was any validation error or other failure, include debug info
if (!$response['success'] && isset($response['message'])) {
    $response['debug_info_on_error'] = $debug; // Changed key for clarity
}


if ($link && mysqli_ping($link)) {
    mysqli_close($link);
}
echo json_encode($response);
?>

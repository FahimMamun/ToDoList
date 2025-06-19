<?php
session_start();

// If the verification_email is not set in session, or if user is already fully logged in,
// redirect them away, perhaps to login page or dashboard.
// Also check verification_user_id to be more robust.
if (!isset($_SESSION['verification_email']) || !isset($_SESSION['verification_user_id'])) {
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        header("location: dashboard.php"); // Already logged in and verified
    } else {
        header("location: index.php"); // No verification process started, or session lost
    }
    exit;
}

// Prefer email from session for security, but allow GET as a fallback for display if session somehow cleared
// but user still has the link from register_action.php's redirect.
// The actual verification in verify_email_action.php will primarily use the email from POST.
$email_to_display = htmlspecialchars($_SESSION['verification_email']);
if (empty($email_to_display) && isset($_GET['email'])) {
    $email_to_display = htmlspecialchars(urldecode($_GET['email']));
}
if (empty($email_to_display)) { // Fallback if somehow both are empty
    $email_to_display = "your email address";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - ToDo App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gradient-to-r from-teal-400 to-blue-500 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Check Your Email</h2>
        <p class="text-center text-gray-600 mb-6">
            We've sent a 6-digit verification code to <strong class="text-gray-700"><?php echo $email_to_display; ?></strong>.
            Please enter it below to verify your account. The code expires in about 10 minutes.
        </p>

        <form id="verifyEmailForm"> <!-- Changed ID -->
            <!-- Email field (readonly, pre-filled) -->
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2 sr-only">Email Address (for form submission)</label>
                <input type="hidden" id="email" name="email" value="<?php echo $email_to_display; ?>" required>
            </div>

            <div class="mb-6">
                <label for="verification_code" class="block text-gray-700 text-sm font-semibold mb-2">Verification Code</label>
                <input type="text" id="verification_code" name="verification_code" required maxlength="6"
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 text-center text-2xl tracking-widest"
                       placeholder="••••••">
            </div>

            <div id="verificationMessageArea" class="mb-4 text-sm text-center"></div> <!-- Changed ID -->

            <div>
                <button type="submit" id="verifyButton" <!-- Changed ID -->
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-150">
                    Verify Account
                </button>
            </div>
        </form>
        <div class="mt-6 text-center text-sm">
            <p class="text-gray-600">Didn't receive the code?
                <a href="#" id="resendCodeLink" class="text-blue-600 hover:text-blue-800 hover:underline">Resend Code</a>
                <!-- Removed "(Feature not implemented yet)" -->
            </p>
            <p class="mt-2"><a href="index.php" class="text-gray-600 hover:text-gray-800 hover:underline">Back to Login/Register</a></p>
        </div>
    </div>
    <script src="js/verify_email_script.js"></script> <!-- Changed script src -->
</body>
</html>

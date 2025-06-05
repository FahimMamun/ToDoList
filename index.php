<?php
session_start(); // Start the session to check session variables

// Check if the user is already logged in, if yes then redirect to dashboard page
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 id="formTitle" class="text-3xl font-bold text-center text-gray-800 mb-8">Login</h2> <!-- Initial Title -->

        <form id="authForm">
            <div class="mb-6">
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150"
                       placeholder="you@example.com">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150"
                       placeholder="••••••••">
            </div>

            <div id="messageArea" class="mb-4 text-sm"></div>

            <div>
                <button type="submit" id="submitButton"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-150">
                    Login <!-- Initial Button Text -->
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="#" id="toggleToRegisterLink" class="text-blue-600 hover:text-blue-800 hover:underline">Don’t have an account? Register</a>
            <a href="#" id="toggleToLoginLink" class="text-blue-600 hover:text-blue-800 hover:underline hidden">Already have an account? Login</a>
        </div>

        <div class="mt-6 text-center"> <!-- Kept Forgot Password separate for clarity -->
            <a href="#" id="forgotPasswordLink" class="text-sm text-gray-600 hover:text-gray-800 hover:underline">Forgot Password?</a>
        </div>

        <!-- Social Login Buttons (no changes here from previous Tailwind version) -->
        <div class="mt-8">
            <div class="relative flex py-5 items-center">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="flex-shrink mx-4 text-gray-400 text-sm">OR</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>
            <div class="flex justify-center space-x-3">
                <button aria-label="Login with Facebook" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition duration-150">
                    <svg class="w-5 h-5 text-blue-700" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                </button>
                <button aria-label="Login with Google" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition duration-150">
                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.026 11.02H12V11h.026z"/><path d="M12.24 10.27c-.14-.04-.28-.07-.42-.1L12 10.17l-.41.01c-.03 0-.06.01-.09.01a3.37 3.37 0 00-.43.04L12 10.22l.24.05zM21.8 12.03c0-.63-.06-1.25-.16-1.84H12v3.49h5.51c-.24 1.12-.9 2.08-1.85 2.71v2.26h2.9c1.69-1.55 2.69-3.86 2.69-6.62z"/><path d="M12 22c2.99 0 5.48-1 7.24-2.72l-2.9-2.26c-.99.66-2.26 1.06-3.62 1.06-2.76 0-5.1-1.87-5.94-4.39H3.07v2.34C4.82 19.5 8.15 22 12 22z"/><path d="M5.94 14.06A6.88 6.88 0 015.12 12a6.88 6.88 0 01.82-2.06V9.67H3.07C2.4 10.75 2 11.36 2 12s.4 1.25 1.07 2.33l2.87-2.27z"/></svg>
                </button>
                 <button aria-label="Login with LinkedIn" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition duration-150">
                    <svg class="w-5 h-5 text-blue-700" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </button>
                <button aria-label="Login with X (Twitter)" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition duration-150">
                    <svg class="w-5 h-5 text-black" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </button>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>

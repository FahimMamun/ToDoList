<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php"); // Redirect to your login page
    exit;
}

// Include database connection
// Make sure the path is correct relative to dashboard.php
require_once 'includes/db_connect.php';

// --- Debugging: Check database connection status (error_log only, no echo) ---
if (!$link) {
    error_log("DEBUG: Dashboard - Database connection failed: " . mysqli_connect_error());
    // Removed echo for cleaner production output
} else {
    error_log("DEBUG: Dashboard - Database connected successfully.");
}


// --- Update last_activity for the current user ---
// This ensures we track active users based on their last page load.
if (isset($_SESSION['id']) && $link) {
    $user_id = $_SESSION['id'];
    $current_time = date('Y-m-d H:i:s'); // Get current timestamp in MySQL DATETIME format

    error_log("DEBUG: Dashboard - Attempting to update last_activity for user ID: " . $user_id . " at time: " . $current_time);

    $sql_update_activity = "UPDATE users SET last_activity = ? WHERE id = ?";
    if ($stmt_update = mysqli_prepare($link, $sql_update_activity)) {
        mysqli_stmt_bind_param($stmt_update, "si", $current_time, $user_id);
        if (mysqli_stmt_execute($stmt_update)) {
            error_log("DEBUG: Dashboard - Successfully updated last_activity for user ID: " . $user_id);
        } else {
            error_log("Error updating last_activity for user ID {$user_id}: " . mysqli_stmt_error($stmt_update));
            // Removed echo for cleaner production output
        }
        mysqli_stmt_close($stmt_update);
    } else {
        error_log("Error preparing last_activity update statement: " . mysqli_error($link));
        // Removed echo for cleaner production output
    }
} else {
    error_log("DEBUG: Dashboard - Session ID not set or DB link not available for activity update. Session ID: " . ($_SESSION['id'] ?? 'NOT SET') . ", DB Link: " . ($link ? 'AVAILABLE' : 'NOT AVAILABLE'));
    // Removed echo for cleaner production output
}

// --- Fetch Statistics ---
$total_users = 0;
$logged_in_users = 0; // "Logged-in" means active in the last 10 minutes

if ($link) { // Ensure $link is valid before querying statistics
    // 1. Total Registered Users
    $sql_total_users = "SELECT COUNT(id) AS total_count FROM users";
    if ($result_total = mysqli_query($link, $sql_total_users)) {
        $row_total = mysqli_fetch_assoc($result_total);
        $total_users = $row_total['total_count'];
        mysqli_free_result($result_total);
        error_log("DEBUG: Dashboard - Total registered users: " . $total_users);
    } else {
        error_log("Error fetching total users: " . mysqli_error($link));
        // Removed echo for cleaner production output
    }

    // 2. Currently Logged-in Users (active in the last 10 minutes)
    // Using DATE_SUB(NOW(), INTERVAL 10 MINUTE) for MySQL to compare timestamps.
    $sql_logged_in_users = "SELECT COUNT(id) AS logged_in_count FROM users WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
    error_log("DEBUG: Dashboard - Query for logged-in users: " . $sql_logged_in_users);
    if ($result_logged_in = mysqli_query($link, $sql_logged_in_users)) {
        $row_logged_in = mysqli_fetch_assoc($result_logged_in);
        $logged_in_users = $row_logged_in['logged_in_count'];
        mysqli_free_result($result_logged_in);
        error_log("DEBUG: Dashboard - Currently logged-in users (active in last 10 mins): " . $logged_in_users);
    } else {
        error_log("Error fetching logged-in users: " . mysqli_error($link));
        // Removed echo for cleaner production output
    }
} else {
    error_log("DEBUG: Dashboard - Database connection not established for statistics fetching.");
    // Removed echo for cleaner production output
}

// Close database connection after all PHP processing for this page
if ($link && mysqli_ping($link)) {
    mysqli_close($link);
    error_log("DEBUG: Dashboard - Database connection closed.");
} else {
    error_log("DEBUG: Dashboard - Database connection not closed (was not open or ping failed).");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ToDo App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Custom styles for consistent appearance */
        body {
            font-family: 'Inter', sans-serif; /* Using Inter font as per instructions */
        }
        .card {
            border-radius: 0.75rem; /* Rounded corners for cards */
        }
        .tab-button {
            @apply px-4 py-2 text-sm font-medium rounded-t-lg transition duration-200;
        }
        .tab-button.active {
            @apply bg-blue-600 text-white;
        }
        .tab-button:not(.active) {
            @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center shadow-md">
        <h1 class="text-xl font-semibold">ToDo App</h1>
        <div>
            <span class="mr-4">Welcome, <?php echo isset($_SESSION["first_name"]) && !empty($_SESSION["first_name"]) ? htmlspecialchars($_SESSION["first_name"]) : htmlspecialchars($_SESSION["email"] ?? 'User'); ?>!</span>
            <a href="actions/logout_action.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-150">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <!-- User Statistics Cards Section -->
        <h2 class="text-2xl font-bold text-gray-800 mb-6">User Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Total Registered Users Card -->
            <div class="card bg-white p-6 shadow-lg flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700">Total Registered Users</h3>
                    <p class="text-4xl font-bold text-blue-600 mt-2"><?php echo $total_users; ?></p>
                </div>
                <!-- Optional: Add an icon here, e.g., using an SVG or Font Awesome -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M12 20.25a8.001 8.001 0 004.835-1.921c.07-.058.14-.108.21-.153M12 20.25a8.001 8.001 0 01-4.835-1.921c-.07-.058-.14-.108-.21-.153M12 20.25c-.246-.077-.493-.153-.74-.23A7.985 7.985 0 0112 4.354zm-4.009 5.867M4 12c0 2.21 3.582 4 8 4s8-1.79 8-4M4 12c0-2.21 3.582-4 8-4s8 1.79 8 4m-4.009 5.867C15.753 20.8 13.9 21.75 12 21.75s-3.753-.95-5.991-2.013A8.001 8.001 0 0112 4.354z" />
                </svg>
            </div>

            <!-- Currently Logged-in Users Card -->
            <div class="card bg-white p-6 shadow-lg flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700">Currently Logged In Users</h3>
                    <p class="text-4xl font-bold text-green-600 mt-2"><?php echo $logged_in_users; ?></p>
                </div>
                <!-- Optional: Add an icon here -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 18.364A9 9 0 0112 5.06V3a1 1 0 00-1-1H4a1 1 0 00-1 1v7a1 1 0 001 1h7a1 1 0 001-1V9a1 1 0 00-1-1H5.636zM17 14.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM17 14.5c.276 0 .5-.224.5-.5s-.224-.5-.5-.5-.5.224-.5.5.224.5.5.5z" />
                </svg>
            </div>
        </div>

        <!-- To-Do List Section -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">My To-Do List</h2>

            <!-- Message area for To-Do operations -->
            <div id="todoMessageArea" class="mb-4 text-sm text-center"></div>

            <!-- Add New Task Form -->
            <form id="addTaskForm" class="mb-6 flex flex-col sm:flex-row gap-2">
                <input type="text" id="newTaskInput" name="task" placeholder="Enter a new task..." required
                       class="flex-grow px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150">
                <button type="submit" id="addTaskButton"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 whitespace-nowrap">
                    Add Task
                </button>
            </form>

            <!-- Tabs for Pending/Completed Tasks -->
            <div class="flex border-b border-gray-200 mb-4">
                <button id="pendingTab" class="tab-button active" data-tab="pending">Pending Tasks</button>
                <button id="completedTab" class="tab-button" data-tab="completed">Completed Tasks</button>
            </div>

            <!-- Tab Content -->
            <div id="pendingTasksContent" class="tab-content">
                <!-- Pending tasks will be loaded here by JavaScript -->
                <p id="noPendingTasks" class="text-gray-500 italic text-center py-4 hidden">No pending tasks.</p>
                <div id="pendingTasksList" class="space-y-4"></div>
            </div>
            <div id="completedTasksContent" class="tab-content hidden">
                <!-- Completed tasks will be loaded here by JavaScript -->
                <p id="noCompletedTasks" class="text-gray-500 italic text-center py-4 hidden">No completed tasks.</p>
                <div id="completedTasksList" class="space-y-4"></div>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal (Hidden by default) -->
    <div id="editTaskModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-sm relative">
            <h3 class="text-xl font-bold mb-6 text-gray-800">Edit Task</h3>
            <button id="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl font-bold">&times;</button>
            <form id="editTaskForm">
                <input type="hidden" id="editTaskId">
                <div class="mb-4">
                    <label for="editTaskInput" class="block text-gray-700 text-sm font-semibold mb-2">Task Description</label>
                    <input type="text" id="editTaskInput" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150">
                </div>
                <button type="submit" id="saveEditButton"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-150">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <footer class="text-center text-gray-600 mt-12 pb-4">
        <p>&copy; <?php echo date("Y"); ?> ToDo App. All rights reserved.</p>
    </footer>

    <!-- New JavaScript file for To-Do list functionality -->
    <script src="js/dashboard_script.js"></script>
</body>
</html>

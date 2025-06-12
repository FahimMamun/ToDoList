<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php'; // Need the DB connection

// Update last_activity for the current user
if ($link && isset($_SESSION['id'])) {
    $sql_update_activity = "UPDATE users SET last_activity = NOW() WHERE id = ?";
    if ($stmt_update_activity = mysqli_prepare($link, $sql_update_activity)) {
        mysqli_stmt_bind_param($stmt_update_activity, "i", $_SESSION['id']);
        if (!mysqli_stmt_execute($stmt_update_activity)) {
            // error_log("Error executing last_activity update: " . mysqli_stmt_error($stmt_update_activity));
        }
        mysqli_stmt_close($stmt_update_activity);
    } else {
        // error_log("Error preparing statement for last_activity update: " . mysqli_error($link));
    }
}

// Initialize statistic variables
$total_users_count = 0;
$active_users_count = 0;

if ($link) { // Check if $link is valid
    // Fetch Total Users
    $sql_total_users = "SELECT COUNT(*) AS total_users FROM users;";
    if ($result_total = mysqli_query($link, $sql_total_users)) {
        $row_total = mysqli_fetch_assoc($result_total);
        $total_users_count = $row_total['total_users'];
        mysqli_free_result($result_total);
    } else {
        // error_log("Error fetching total users: " . mysqli_error($link));
        $total_users_count = 'N/A'; // Display N/A or Error on failure
    }

    // Fetch Active Users (e.g., active in the last 15 minutes)
    $active_interval_minutes = 15;
    $sql_active_users = "SELECT COUNT(*) AS active_users FROM users WHERE last_activity >= (NOW() - INTERVAL ? MINUTE);";
    if ($stmt_active = mysqli_prepare($link, $sql_active_users)) {
        mysqli_stmt_bind_param($stmt_active, "i", $active_interval_minutes);
        if (mysqli_stmt_execute($stmt_active)) {
            mysqli_stmt_bind_result($stmt_active, $active_users_result_val); // Use a different var name
            if (mysqli_stmt_fetch($stmt_active)) {
                $active_users_count = $active_users_result_val;
            } else {
                 $active_users_count = 0; // No active users found or error during fetch
            }
        } else {
            // error_log("Error executing active users query: " . mysqli_stmt_error($stmt_active));
            $active_users_count = 'N/A';
        }
        mysqli_stmt_close($stmt_active);
    } else {
        // error_log("Error preparing active users statement: " . mysqli_error($link));
        $active_users_count = 'N/A';
    }
} else {
    $total_users_count = 'DB Err';
    $active_users_count = 'DB Err';
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
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-semibold">ToDo App</h1>
        <div>
            <span class="mr-4">Welcome, <?php echo isset($_SESSION["first_name"]) && !empty($_SESSION["first_name"]) ? htmlspecialchars($_SESSION["first_name"]) : htmlspecialchars($_SESSION["email"] ?? 'User'); ?>!</span>
            <a href="actions/logout_action.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-150">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">

        <!-- Statistics Cards -->
        <div class="flex flex-wrap -mx-2 mb-8">
            <div class="w-full md:w-1/2 px-2 mb-4 md:mb-0">
                <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Total Registered Users</h3>
                    <p class="text-4xl font-bold text-blue-600"><?php echo $total_users_count; ?></p>
                </div>
            </div>
            <div class="w-full md:w-1/2 px-2">
                <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Online Users (15 min)</h3>
                    <p class="text-4xl font-bold text-green-600"><?php echo $active_users_count; ?></p>
                </div>
            </div>
        </div>
        <!-- End Statistics Cards -->

        <h2 class="text-2xl font-bold text-gray-800 mb-6">My To-Do List</h2>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-700">Your to-do list is currently empty. Start by adding a new task!</p>
            <form class="mt-4">
                <input type="text" placeholder="Enter a new task" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 mb-2">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Add Task
                </button>
            </form>
        </div>
    </div>

    <footer class="text-center text-gray-600 mt-12 pb-4">
        <p>&copy; <?php echo date("Y"); ?> ToDo App. All rights reserved.</p>
    </footer>
    <?php
    if ($link && function_exists('mysqli_close') && is_a($link, 'mysqli') && mysqli_ping($link)) {
        mysqli_close($link);
    }
    ?>
</body>
</html>

<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php"); // Assuming your login page is index.html
    exit;
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
            <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION["email"]); ?>!</span>
            <a href="actions/logout_action.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-150">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">My To-Do List</h2>

        <!-- To-Do list items will go here -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-700">Your to-do list is currently empty. Start by adding a new task!</p>
            <!-- Example: Add task form -->
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
</body>
</html>

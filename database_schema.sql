-- SQL script to create the 'users' and 'tasks' tables for the to-do list application

-- Select the database to use (ensure 'todo_app' is created first if not using CREATE DATABASE)
-- CREATE DATABASE IF NOT EXISTS todo_app;
-- USE todo_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Changed from password_hash to password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_description TEXT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Instructions for use:
-- 1. Access your MySQL database management tool (e.g., phpMyAdmin in WAMPP/XAMPP, MySQL Workbench).
-- 2. Create and/or select your application's database (e.g., 'todo_app').
-- 3. Run this SQL script to create the 'users' and 'tasks' tables.

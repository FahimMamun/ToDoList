-- SQL script to create the 'users' and 'tasks' tables for the to-do list application

-- Select the database to use (ensure 'todo_app' is created first if not using CREATE DATABASE)
-- CREATE DATABASE IF NOT EXISTS todo_app;
-- USE todo_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone_no VARCHAR(25) NULL,
    country VARCHAR(100) NULL,
    gender VARCHAR(20) NULL,
    date_of_birth DATE NULL,
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
-- 3. If the 'users' table already exists, you may need to ALTER it or DROP and recreate it.
--    Example ALTER statements (run one by one if table exists):
--    ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER password;
--    ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NOT NULL AFTER first_name;
--    ALTER TABLE users ADD COLUMN phone_no VARCHAR(25) NULL AFTER last_name;
--    ALTER TABLE users ADD COLUMN country VARCHAR(100) NULL AFTER phone_no;
--    ALTER TABLE users ADD COLUMN gender VARCHAR(20) NULL AFTER country;
--    ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER gender;
-- 4. Run this SQL script (or relevant parts) to create/update the tables.

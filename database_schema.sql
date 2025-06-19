-- SQL script to create the 'users', 'todos', and 'email_verifications' tables for the to-do list application

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
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP NULL,
    is_verified BOOLEAN DEFAULT FALSE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP -- Changed from TIMESTAMP for consistency
);

-- Definition for todos table (replaces tasks table)
CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' NOT NULL, -- e.g., 'pending', 'completed', 'in_progress'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Definition for email_verifications table
CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    verification_code VARCHAR(10) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Instructions for use:
-- 1. Access your MySQL database management tool (e.g., phpMyAdmin in WAMPP/XAMPP, MySQL Workbench).
-- 2. Create and/or select your application's database (e.g., 'todo_app').
-- 3. If the 'users' table already exists, you may need to ALTER it. See examples below.
--    Example ALTER statements for 'users' table (run one by one if table exists):
--    ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER password;
--    ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NOT NULL AFTER first_name;
--    ALTER TABLE users ADD COLUMN phone_no VARCHAR(25) NULL AFTER last_name;
--    ALTER TABLE users ADD COLUMN country VARCHAR(100) NULL AFTER phone_no;
--    ALTER TABLE users ADD COLUMN gender VARCHAR(20) NULL AFTER country;
--    ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER gender;
--    ALTER TABLE users ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP NULL AFTER date_of_birth;
--    ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE NOT NULL AFTER last_activity;
--    ALTER TABLE users MODIFY COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP; -- If changing from TIMESTAMP
-- 4. If 'tasks' table exists, DROP it: DROP TABLE IF EXISTS tasks;
-- 5. If 'email_verifications' table exists with old structure, DROP it before running this script to create new one.
-- 6. Run this SQL script (or relevant parts) to create/update the tables.

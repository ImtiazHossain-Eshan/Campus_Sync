-- Updated Database Schema for CampusSync

CREATE DATABASE IF NOT EXISTS campussync;
USE campussync;

-- 1. Users table with extended profile fields
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    social VARCHAR(100),
    semester VARCHAR(20),
    university VARCHAR(100) DEFAULT 'BRAC University',
    gender ENUM('Male', 'Female'),
    department VARCHAR(100),
    profile_pic VARCHAR(255)
);


-- 2. Course sections table (cache for scraped data)
CREATE TABLE IF NOT EXISTS course_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    faculty_initials VARCHAR(20),
    room VARCHAR(20),
    start_time TIME,
    end_time TIME,
    last_updated DATETIME NULL
);

ALTER TABLE course_sections
ADD COLUMN raw_time VARCHAR(255) NULL AFTER end_time;

-- 3. Routines table
CREATE TABLE IF NOT EXISTS routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    day VARCHAR(20),
    start_time TIME,
    end_time TIME,
    course_code VARCHAR(20),
    course_name VARCHAR(100),
    faculty VARCHAR(100),
    section VARCHAR(20),
    room VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

ALTER TABLE notifications ADD COLUMN read_at DATETIME DEFAULT NULL;

-- 5. Groups and group_members
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT,
    user_id INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);

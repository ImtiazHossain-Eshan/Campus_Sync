CREATE DATABASE IF NOT EXISTS campussync;
USE campussync;

-- 1. Users table
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

ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;


-- 2. Course sections table (cache for scraped data)
CREATE TABLE IF NOT EXISTS course_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    faculty_initials VARCHAR(20),
    room VARCHAR(20),
    start_time TIME,
    end_time TIME,
    raw_time VARCHAR(255),
    last_updated DATETIME NULL
);

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

ALTER TABLE routines ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ;

-- 4. Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

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

-- 6. Friends table with anti-duplicate logic
CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    friendship_key VARCHAR(255) GENERATED ALWAYS AS (
        IF(user_id < friend_id, CONCAT(user_id, '_', friend_id), CONCAT(friend_id, '_', user_id))
    ) STORED,
    UNIQUE KEY unique_friendship_pair (friendship_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE DATABASE IF NOT EXISTS campussync;
USE campussync;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    social VARCHAR(100)
);

CREATE TABLE routines (
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

CREATE TABLE `course_sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_code` VARCHAR(20) NOT NULL,         -- e.g. CSE440-02
  `course_name` VARCHAR(255) NOT NULL,         -- e.g. Artificial Intelligence
  `faculty_initials` VARCHAR(20) NOT NULL,     -- e.g. FYS
  `room` VARCHAR(20) NOT NULL,                 -- e.g. 09C or Online
  `time` VARCHAR(50) NOT NULL,                 -- e.g. SunTue 2:00–3:20
  `seat_info` VARCHAR(100) DEFAULT NULL        -- optional (e.g. "30/45 taken")
);

ALTER TABLE course_sections
ADD COLUMN day VARCHAR(15),
ADD COLUMN start_time TIME,
ADD COLUMN end_time TIME;


CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT,
    user_id INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE course_sections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_code VARCHAR(20) NOT NULL UNIQUE,
  course_name VARCHAR(100) NOT NULL,
  faculty_initials VARCHAR(20),
  room VARCHAR(20),
  start_time TIME,
  end_time TIME
);
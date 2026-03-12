-- SQL schema for PLRS application
-- Run this in phpMyAdmin or MySQL CLI to create the database and tables used by the project.

CREATE DATABASE IF NOT EXISTS `plrs` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `plrs`;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student','faculty') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB;

CREATE TABLE `student_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `weak_topic` VARCHAR(255),
    `learning_goal` TEXT,
    `study_hours` INT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `resources` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT UNSIGNED,
    `faculty_id` INT UNSIGNED,
    `faculty_name` VARCHAR(255),
    `subject` VARCHAR(255),
    `sub_topic` VARCHAR(255),
    `title` VARCHAR(255),
    `resource_type` ENUM('pdf','youtube'),
    `resource_link` TEXT,
    `file_path` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `student_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`faculty_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `request_id` INT UNSIGNED NOT NULL,
    `faculty_name` VARCHAR(255),
    `feedback` TEXT,
    `faculty_reply` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`request_id`) REFERENCES `student_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `performance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `final_score` INT,
    `study_hours` INT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

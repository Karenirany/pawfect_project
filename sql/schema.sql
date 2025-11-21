-- =======================================================
-- Dog Adoption Website - Database Schema
-- Database name: dog_adoption
-- =======================================================

-- Create the database if not exists
CREATE DATABASE IF NOT EXISTS  if0_39877333_dog_adoption CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE  if0_39877333_dog_adoption;


CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50),
  email VARCHAR(100) UNIQUE,
  password_hash VARCHAR(255),
  phone_number VARCHAR(20),
  role ENUM('admin','user') DEFAULT 'user',
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dogs (
  dog_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50),
  breed VARCHAR(50),
  age INT,
  gender ENUM('male','female'),
  size ENUM('small','medium','large'),
  description TEXT,
  image_path VARCHAR(255),
  status ENUM('available','pending','adopted') DEFAULT 'available'
);

CREATE TABLE adoption_requests (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  dog_id INT,
  request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_comment TEXT,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (dog_id) REFERENCES dogs(dog_id)
);

CREATE TABLE dog_photo (
  photo_id INT AUTO_INCREMENT PRIMARY KEY,
  dog_id INT,
  photo_path VARCHAR(255),
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dog_id) REFERENCES dogs(dog_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);
CREATE TABLE activity_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(255),
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);
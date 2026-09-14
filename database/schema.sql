-- Service Center Job Card & Repair Tracking System
-- Simple MySQL schema: exactly 3 tables (admins, customers, jobs)
-- No created_at/updated_at fields, per project rules.

CREATE DATABASE IF NOT EXISTS service_center_db;
USE service_center_db;

-- 1. ADMINS
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Sample admin login -> username: admin | password: admin123
INSERT INTO admins (username, password) VALUES
('admin', '$2b$10$00.4fE4Z5.Dy5QdpMk5jreIaJ5FG0OLuX3au/19PVJvc8dT9AUtLu');

-- 2. CUSTOMERS
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    address VARCHAR(255) NULL
);

-- 3. JOBS
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_no VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    device_name VARCHAR(100) NOT NULL,
    model VARCHAR(100) NULL,
    complaint TEXT NULL,
    technician VARCHAR(100) NULL,
    estimate DECIMAL(10,2) NOT NULL DEFAULT 0,
    final_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('Received','Checking','Repairing','Ready','Delivered') NOT NULL DEFAULT 'Received',
    CONSTRAINT fk_jobs_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
);

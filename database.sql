-- =============================================================
-- database.sql : creates the 2 tables and adds the 5 mechanics
-- CSE 391 / Assignment 3 / Car Workshop Appointment System
--
-- How to use: open phpMyAdmin -> choose your database ->
-- "Import" tab -> pick this file -> Go.
-- (On XAMPP you can first create a database named "car_workshop".)
-- =============================================================

-- Start clean if the tables already exist
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS mechanics;

-- ---------- Table 1: mechanics ----------
-- One row per senior mechanic.
CREATE TABLE mechanics (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- ---------- Table 2: appointments ----------
-- One row per booking. mechanic_id links to mechanics.id
CREATE TABLE appointments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    client_name      VARCHAR(100) NOT NULL,
    address          VARCHAR(255) NOT NULL,
    phone            VARCHAR(20)  NOT NULL,
    car_license      VARCHAR(50)  NOT NULL,
    car_engine       VARCHAR(50)  NOT NULL,
    appointment_date DATE         NOT NULL,
    mechanic_id      INT          NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(id)
);

-- ---------- The 5 senior mechanics ----------
INSERT INTO mechanics (name) VALUES
    ('Rahim Uddin'),
    ('Karim Hossain'),
    ('Jamal Ahmed'),
    ('Sohel Rana'),
    ('Tanvir Islam');

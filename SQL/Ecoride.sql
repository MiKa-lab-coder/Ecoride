-- Ecoride Database Creation & Seeding Script
-- This single script creates the schema and populates it with a comprehensive set of sample data.
-- It is designed to be idempotent and can be re-run safely.

CREATE DATABASE IF NOT EXISTS Ecoride;
USE Ecoride;

-- Table: ROLES
CREATE TABLE IF NOT EXISTS ROLES (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Table: USERS
CREATE TABLE IF NOT EXISTS USERS (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    firstname      VARCHAR(100) NOT NULL,
    birth_date     DATE         NOT NULL,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    photo          VARCHAR(255),
    email          VARCHAR(100) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    total_trips    INT DEFAULT 0,
    account_status ENUM ('active', 'suspended') DEFAULT 'active',
    role_id        INT,
    FOREIGN KEY (role_id) REFERENCES ROLES (role_id)
);

-- Table: VEHICLES
CREATE TABLE IF NOT EXISTS VEHICLES (
    vehicle_id          INT AUTO_INCREMENT PRIMARY KEY,
    first_service       DATE NOT NULL,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    energy_type         ENUM ('electric', 'hybrid', 'combustion') NOT NULL,
    brand               VARCHAR(50) NOT NULL,
    model               VARCHAR(50) NOT NULL,
    color               VARCHAR(30),
    seating_capacity    INT NOT NULL,
    user_id             INT,
    FOREIGN KEY (user_id) REFERENCES USERS (user_id) ON DELETE CASCADE
);

-- Table: TRIPS
CREATE TABLE IF NOT EXISTS TRIPS (
    trip_id            INT AUTO_INCREMENT PRIMARY KEY,
    departure_day      DATE NOT NULL,
    arrival_day        DATE NOT NULL,
    departure_location VARCHAR(255) NOT NULL,
    arrival_location   VARCHAR(255) NOT NULL,
    departure_time     TIME NOT NULL,
    arrival_time       TIME NOT NULL,
    trip_time          INT NOT NULL,
    trip_price         INT NOT NULL,
    trip_nature        ENUM ('ecologic', 'standard'),
    animal_pref        BOOLEAN DEFAULT FALSE,
    smoking_pref       BOOLEAN DEFAULT FALSE,
    seating            INT NOT NULL,
    status             ENUM ('pending', 'ongoing', 'completed', 'approved') NOT NULL DEFAULT 'pending',
    driver_id          INT,
    vehicle_id         INT,
    FOREIGN KEY (driver_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES VEHICLES (vehicle_id)
);

-- Table: RATINGS
CREATE TABLE IF NOT EXISTS RATINGS (
    id            INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rated_user_id INT(11) NOT NULL,
    passenger_id  INT(11) NOT NULL,
    trip_id       INT(11) NOT NULL,
    rating_value  INT(11) NOT NULL,
    FOREIGN KEY (rated_user_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES TRIPS (trip_id) ON DELETE CASCADE
);

-- Table: TRANSACTIONS
CREATE TABLE IF NOT EXISTS TRANSACTIONS (
    transaction_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    amount           INT NOT NULL,
    transaction_type ENUM ('payment', 'cancellation','service_fee','welcome_bonus') NOT NULL DEFAULT 'payment',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reference        INT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (reference) REFERENCES TRIPS (trip_id) ON DELETE CASCADE
);

-- Table: BOOKINGS
CREATE TABLE IF NOT EXISTS BOOKINGS (
    booking_id   INT AUTO_INCREMENT PRIMARY KEY,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id      INT,
    trip_id      INT,
    FOREIGN KEY (user_id) REFERENCES USERS (user_id),
    FOREIGN KEY (trip_id) REFERENCES TRIPS (trip_id),
    CONSTRAINT `unique_booking_user_trip` UNIQUE (`trip_id`, `user_id`)
);

-- Table: ISSUES
CREATE TABLE IF NOT EXISTS ISSUES (
    issue_id    INT AUTO_INCREMENT PRIMARY KEY,
    status      ENUM ('open', 'resolved') DEFAULT 'open',
    date_open   DATE NOT NULL,
    description TEXT NOT NULL,
    user_id     INT,
    trip_id     INT,
    FOREIGN KEY (user_id) REFERENCES USERS (user_id),
    FOREIGN KEY (trip_id) REFERENCES TRIPS (trip_id)
);

-- Seeding Section --

-- Use INSERT IGNORE for static data to prevent errors on re-run.
INSERT IGNORE INTO ROLES (role_id, role_name) VALUES (1, 'admin'), (2, 'moderator'), (3, 'user');

-- Clear existing data to make the script idempotent
DELETE FROM RATINGS;
DELETE FROM ISSUES;
DELETE FROM TRANSACTIONS;
DELETE FROM BOOKINGS;
DELETE FROM TRIPS;
DELETE FROM VEHICLES;
DELETE FROM USERS;

-- Seed Users with correct passwords and default photo
INSERT INTO USERS (user_id, name, firstname, birth_date, username, email, password, role_id, photo) VALUES
(1, 'Admin', 'Ecoride', '2025-01-01', 'adminEcoride', 'admin@ecoride-project.ovh', '$2y$12$zAahKmf91EWD.FeuCgxlYugtUONgtJmOp3..31NnCiRWQV6sw/9QC', 1, 'uploads/default.png'),
(2, 'Moderator', 'Ecoride', '2025-01-01', 'moderatorEcoride', 'moderator@ecoride-project.ovh', '$2y$12$.U6lrGE4IztOO5sjnq2zWOFrh/mQinZpIRN.ZOzxu6GCltvivdya2', 2, 'uploads/default.png'),
(3, 'User', 'Ecoride', '2025-01-01', 'userEcoride', 'userecoride-project@gmail.com', '$2y$12$JFE3YBn6JaB/UUpH0qTeXeJFDAq5RrLb7.TJnEAuX3czurCYirAwO', 3, 'uploads/default.png'),
(4, 'Dupont', 'Marie', '1990-05-15', 'mariedupont', 'marie.dupont@example.com', '$2y$10$dummyPassword1', 3, 'uploads/default.png'),
(5, 'Martin', 'Paul', '1988-11-20', 'paulmartin', 'paul.martin@example.com', '$2y$10$dummyPassword2', 3, 'uploads/default.png'),
(6, 'Dubois', 'Chloé', '1995-02-10', 'chloedubois', 'chloe.dubois@example.com', '$2y$10$dummyPassword3', 3, 'uploads/default.png'),
(7, 'Lefebvre', 'Lucas', '1992-09-30', 'lucaslefebvre', 'lucas.lefebvre@example.com', '$2y$10$dummyPassword4', 3, 'uploads/default.png'),
(8, 'Garcia', 'Emma', '1998-07-22', 'emmaga', 'emma.garcia@example.com', '$2y$10$dummyPassword5', 3, 'uploads/default.png'),
(9, 'Martinez', 'Hugo', '1985-03-12', 'hugomar', 'hugo.martinez@example.com', '$2y$10$dummyPassword6', 3, 'uploads/default.png'),
(10, 'Roux', 'Léa', '2000-01-05', 'learoux', 'lea.roux@example.com', '$2y$10$dummyPassword7', 3, 'uploads/default.png'),
(11, 'Fournier', 'Louis', '1993-12-18', 'louisfour', 'louis.fournier@example.com', '$2y$10$dummyPassword8', 3, 'uploads/default.png'),
(12, 'Moreau', 'Manon', '1997-08-08', 'manonmo', 'manon.moreau@example.com', '$2y$10$dummyPassword9', 3, 'uploads/default.png'),
(13, 'Girard', 'Adam', '1991-06-25', 'adamgir', 'adam.girard@example.com', '$2y$10$dummyPassword10', 3, 'uploads/default.png');

-- Seed Vehicles
INSERT INTO VEHICLES (vehicle_id, first_service, registration_number, energy_type, brand, model, color, seating_capacity, user_id) VALUES
(1, '2020-01-15', 'AA-123-BB', 'electric', 'Renault', 'Zoe', 'Blue', 4, 3),
(2, '2021-06-01', 'CC-456-DD', 'hybrid', 'Toyota', 'Yaris', 'Grey', 4, 4),
(3, '2019-03-20', 'EE-789-FF', 'combustion', 'Peugeot', '308', 'Black', 4, 5),
(4, '2022-08-10', 'GG-234-HH', 'electric', 'Tesla', 'Model 3', 'White', 4, 9),
(5, '2018-11-05', 'II-567-JJ', 'combustion', 'Volkswagen', 'Golf', 'Silver', 4, 11),
(6, '2023-01-30', 'KK-890-LL', 'hybrid', 'Hyundai', 'Ioniq', 'Red', 4, 13),
-- Additional 10 vehicles
(7, '2023-03-01', 'MM-111-NN', 'electric', 'Nissan', 'Leaf', 'White', 4, 3),
(8, '2022-05-10', 'PP-222-QQ', 'hybrid', 'Kia', 'Niro', 'Black', 5, 4),
(9, '2021-07-20', 'RR-333-SS', 'combustion', 'Ford', 'Focus', 'Blue', 5, 5),
(10, '2024-01-15', 'TT-444-UU', 'electric', 'BMW', 'i3', 'Grey', 4, 6),
(11, '2023-09-01', 'VV-555-WW', 'hybrid', 'Honda', 'CR-V', 'Red', 5, 6),
(12, '2022-11-20', 'XX-666-YY', 'combustion', 'Audi', 'A3', 'Silver', 5, 7),
(13, '2024-02-28', 'ZZ-777-AA', 'electric', 'Hyundai', 'Kona', 'Green', 4, 7),
(14, '2023-04-05', 'BB-888-CC', 'hybrid', 'Subaru', 'XV', 'Orange', 5, 8),
(15, '2022-06-12', 'DD-999-EE', 'combustion', 'Mazda', '3', 'White', 5, 9),
(16, '2024-03-10', 'FF-000-GG', 'electric', 'Peugeot', 'e-208', 'Yellow', 4, 10);

-- Seed Trips
INSERT INTO TRIPS (trip_id, departure_day, arrival_day, departure_location, arrival_location, departure_time, arrival_time, trip_time, trip_price, trip_nature, seating, status, driver_id, vehicle_id) VALUES
(1, '2025-12-20', '2025-12-20', 'Rennes', 'Nantes', '17:00:00', '18:30:00', 90, 5, 'standard', 3, 'approved', 13, 6),
(2, '2025-12-24', '2025-12-24', 'Paris', 'Orléans', '07:30:00', '09:00:00', 90, 4, 'ecologic', 3, 'approved', 3, 1),
(3, '2026-01-05', '2026-01-05', 'Lille', 'Brussels', '10:00:00', '11:30:00', 90, 5, 'standard', 3, 'approved', 4, 2),
(4, '2026-01-10', '2026-01-10', 'Marseille', 'Montpellier', '18:00:00', '20:00:00', 120, 4, 'standard', 3, 'approved', 5, 3),
(5, '2026-02-15', '2026-02-15', 'Toulouse', 'Biarritz', '08:00:00', '11:00:00', 180, 5, 'ecologic', 2, 'approved', 9, 4),
(6, '2026-03-01', '2026-03-01', 'Clermont-Ferrand', 'Lyon', '16:00:00', '18:00:00', 120, 3, 'standard', 3, 'pending', 11, 5),
-- 2024
(7, '2024-11-15', '2024-11-15', 'Bordeaux', 'Toulouse', '09:00:00', '11:30:00', 150, 4, 'standard', 3, 'approved', 4, 2),
(8, '2024-11-20', '2024-11-20', 'Strasbourg', 'Paris', '14:00:00', '18:00:00', 240, 2, 'ecologic', 2, 'approved', 9, 4),
(9, '2024-12-05', '2024-12-05', 'Nice', 'Marseille', '11:00:00', '13:00:00', 120, 3, 'standard', 3, 'approved', 5, 3),
(10, '2024-12-15', '2024-12-15', 'Lyon', 'Geneva', '08:30:00', '10:30:00', 120, 3, 'standard', 3, 'pending', 11, 5),
-- 2025
(11, '2025-01-20', '2025-01-20', 'Paris', 'Lille', '19:00:00', '21:30:00', 150, 4, 'ecologic', 3, 'approved', 3, 1),
(12, '2025-02-10', '2025-02-10', 'Nantes', 'Brest', '07:00:00', '10:00:00', 180, 5, 'standard', 2, 'approved', 13, 6),
(13, '2025-03-05', '2025-03-05', 'Toulouse', 'Montpellier', '15:00:00', '17:30:00', 150, 3, 'standard', 3, 'approved', 4, 2),
(14, '2025-04-01', '2025-04-01', 'Marseille', 'Lyon', '10:00:00', '13:00:00', 180, 4, 'ecologic', 3, 'approved', 9, 4),
(15, '2025-05-18', '2025-05-18', 'Bordeaux', 'Nantes', '16:30:00', '19:00:00', 150, 4, 'standard', 3, 'pending', 5, 3),
(16, '2025-06-22', '2025-06-22', 'Paris', 'Strasbourg', '06:00:00', '10:00:00', 240, 3, 'standard', 3, 'approved', 11, 5),
(17, '2025-07-14', '2025-07-14', 'Lyon', 'Paris', '17:00:00', '21:00:00', 240, 5, 'ecologic', 2, 'approved', 3, 1),
(18, '2025-08-05', '2025-08-05', 'Nice', 'Cannes', '10:30:00', '11:15:00', 45, 3, 'standard', 3, 'approved', 13, 6),
(19, '2025-09-10', '2025-09-10', 'Rennes', 'Caen', '09:00:00', '11:00:00', 120, 3, 'standard', 3, 'approved', 4, 2),
(20, '2025-10-25', '2025-10-25', 'Lille', 'Amiens', '14:00:00', '15:30:00', 90, 5, 'pending', 3, 'approved', 9, 4),
(21, '2025-11-11', '2025-11-11', 'Metz', 'Nancy', '18:00:00', '19:00:00', 60, 4, 'ecologic', 3, 'approved', 5, 3),
(22, '2025-12-01', '2025-12-01', 'Dijon', 'Besançon', '13:00:00', '14:30:00', 90, 5, 'standard', 2, 'approved', 11, 5),
(23, '2025-12-28', '2025-12-28', 'Paris', 'Reims', '11:00:00', '13:00:00', 120, 3, 'standard', 3, 'approved', 3, 1),
-- 2026
(24, '2026-01-15', '2026-01-15', 'Grenoble', 'Lyon', '07:45:00', '09:00:00', 75, 2, 'ecologic', 3, 'approved', 13, 6),
(25, '2026-02-20', '2026-02-20', 'Avignon', 'Marseille', '16:00:00', '17:30:00', 90, 4, 'standard', 3, 'pending', 4, 2),
(26, '2026-03-10', '2026-03-10', 'Le Havre', 'Rouen', '09:30:00', '10:45:00', 75, 3, 'standard', 3, 'approved', 9, 4);

-- Seed Bookings
INSERT INTO BOOKINGS (user_id, trip_id) VALUES
(7, 1), (8, 1), (10, 2), (11, 2), (6, 3), (4, 4), (6, 4), (8, 5), (5, 7), (6, 8), (7, 9), (8, 11), (10, 12), (12, 13), (3, 14), (4, 16), (13, 17), (6, 24), (7, 26), (10, 25), (12, 23), (12, 21), (12, 20);

-- Seed Ratings
INSERT INTO RATINGS (rated_user_id, passenger_id, trip_id, rating_value) VALUES
(13, 7, 1, 5), (13, 8, 1, 4), (7, 13, 1, 3), (8, 13, 1, 5), (8, 7, 1, 2), (7, 8, 1, 4),
(3, 10, 2, 3), (3, 11, 2, 5), (10, 3, 2, 4), (11, 3, 2, 3), (11, 10, 2, 5), (10, 11, 2, 2),
(4, 6, 3, 4), (6, 4, 3, 5),
(5, 4, 4, 3), (5, 6, 4, 4), (4, 5, 4, 5), (6, 5, 4, 3), (6, 4, 4, 2), (4, 6, 4, 5),
(9, 8, 5, 5), (8, 9, 5, 4),
(4, 5, 7, 3), (5, 4, 7, 5),
(9, 6, 8, 4), (6, 9, 8, 3),
(5, 7, 9, 5), (7, 5, 9, 4),
(3, 8, 11, 3), (8, 3, 11, 5),
(13, 10, 12, 4), (10, 13, 12, 3),
(4, 12, 13, 5), (12, 4, 13, 4),
(9, 3, 14, 3), (3, 9, 14, 5),
(11, 4, 16, 4), (4, 11, 16, 3),
(3, 13, 17, 5), (13, 3, 17, 4),
(13, 6, 24, 3), (6, 13, 24, 5),
(9, 7, 26, 4), (7, 9, 26, 3),
(4, 10, 25, 5), (10, 4, 25, 4),
(3, 12, 23, 3), (12, 3, 23, 5),
(5, 12, 21, 4), (12, 5, 21, 3),
(9, 12, 20, 5), (12, 9, 20, 4);

-- Seed Transactions
INSERT INTO TRANSACTIONS (user_id, amount, transaction_type, reference) VALUES
-- Welcome Bonuses
(4, 20, 'welcome_bonus', NULL),
(5, 20, 'welcome_bonus', NULL),
(6, 20, 'welcome_bonus', NULL),
(7, 20, 'welcome_bonus', NULL),
(8, 20, 'welcome_bonus', NULL),
(9, 20, 'welcome_bonus', NULL),
(10, 20, 'welcome_bonus', NULL),
(11, 20, 'welcome_bonus', NULL),
(12, 20, 'welcome_bonus', NULL),
(13, 20, 'welcome_bonus', NULL),
-- Trip 1 (Price 5, Driver 13)
(7, -5, 'payment', 1),
(13, 3, 'payment', 1),
(1, 2, 'service_fee', 1),
(8, -5, 'payment', 1),
(13, 3, 'payment', 1),
(1, 2, 'service_fee', 1),
-- Trip 2 (Price 4, Driver 3)
(10, -4, 'payment', 2),
(3, 2, 'payment', 2),
(1, 2, 'service_fee', 2),
(11, -4, 'payment', 2),
(3, 2, 'payment', 2),
(1, 2, 'service_fee', 2),
-- Trip 3 (Price 5, Driver 4)
(6, -5, 'payment', 3),
(4, 3, 'payment', 3),
(1, 2, 'service_fee', 3),
-- Trip 4 (Price 4, Driver 5)
(4, -4, 'payment', 4),
(5, 2, 'payment', 4),
(1, 2, 'service_fee', 4),
(6, -4, 'payment', 4),
(5, 2, 'payment', 4),
(1, 2, 'service_fee', 4),
-- Trip 5 (Price 5, Driver 9)
(8, -5, 'payment', 5),
(9, 3, 'payment', 5),
(1, 2, 'service_fee', 5);



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

-- Seed Users with correct passwords
INSERT INTO USERS (user_id, name, firstname, birth_date, username, email, password, role_id) VALUES
(1, 'Admin', 'Ecoride', '2025-01-01', 'adminEcoride', 'admin@ecoride-project.ovh', '$2y$12$zAahKmf91EWD.FeuCgxlYugtUONgtJmOp3..31NnCiRWQV6sw/9QC', 1),
(2, 'Moderator', 'Ecoride', '2025-01-01', 'moderatorEcoride', 'moderator@ecoride-project.ovh', '$2y$12$.U6lrGE4IztOO5sjnq2zWOFrh/mQinZpIRN.ZOzxu6GCltvivdya2', 2),
(3, 'User', 'Ecoride', '2025-01-01', 'userEcoride', 'userecoride-project@gmail.com', '$2y$12$JFE3YBn6JaB/UUpH0qTeXeJFDAq5RrLb7.TJnEAuX3czurCYirAwO', 3),
(4, 'Dupont', 'Marie', '1990-05-15', 'mariedupont', 'marie.dupont@example.com', '$2y$10$dummyPassword1', 3),
(5, 'Martin', 'Paul', '1988-11-20', 'paulmartin', 'paul.martin@example.com', '$2y$10$dummyPassword2', 3),
(6, 'Dubois', 'Chloé', '1995-02-10', 'chloedubois', 'chloe.dubois@example.com', '$2y$10$dummyPassword3', 3),
(7, 'Lefebvre', 'Lucas', '1992-09-30', 'lucaslefebvre', 'lucas.lefebvre@example.com', '$2y$10$dummyPassword4', 3),
(8, 'Garcia', 'Emma', '1998-07-22', 'emmaga', 'emma.garcia@example.com', '$2y$10$dummyPassword5', 3),
(9, 'Martinez', 'Hugo', '1985-03-12', 'hugomar', 'hugo.martinez@example.com', '$2y$10$dummyPassword6', 3),
(10, 'Roux', 'Léa', '2000-01-05', 'learoux', 'lea.roux@example.com', '$2y$10$dummyPassword7', 3),
(11, 'Fournier', 'Louis', '1993-12-18', 'louisfour', 'louis.fournier@example.com', '$2y$10$dummyPassword8', 3),
(12, 'Moreau', 'Manon', '1997-08-08', 'manonmo', 'manon.moreau@example.com', '$2y$10$dummyPassword9', 3),
(13, 'Girard', 'Adam', '1991-06-25', 'adamgir', 'adam.girard@example.com', '$2y$10$dummyPassword10', 3);

-- Seed Vehicles
INSERT INTO VEHICLES (vehicle_id, first_service, registration_number, energy_type, brand, model, color, seating_capacity, user_id) VALUES
(1, '2020-01-15', 'AA-123-BB', 'electric', 'Renault', 'Zoe', 'Blue', 4, 3),
(2, '2021-06-01', 'CC-456-DD', 'hybrid', 'Toyota', 'Yaris', 'Grey', 4, 4),
(3, '2019-03-20', 'EE-789-FF', 'combustion', 'Peugeot', '308', 'Black', 4, 5),
(4, '2022-08-10', 'GG-234-HH', 'electric', 'Tesla', 'Model 3', 'White', 4, 9),
(5, '2018-11-05', 'II-567-JJ', 'combustion', 'Volkswagen', 'Golf', 'Silver', 4, 11),
(6, '2023-01-30', 'KK-890-LL', 'hybrid', 'Hyundai', 'Ioniq', 'Red', 4, 13);

-- Seed Trips
INSERT INTO TRIPS (trip_id, departure_day, arrival_day, departure_location, arrival_location, departure_time, arrival_time, trip_time, trip_price, trip_nature, seating, status, driver_id, vehicle_id) VALUES
(1, '2025-12-20', '2025-12-20', 'Rennes', 'Nantes', '17:00:00', '18:30:00', 90, 5, 'standard', 3, 'approved', 13, 6),
(2, '2025-12-24', '2025-12-24', 'Paris', 'Orléans', '07:30:00', '09:00:00', 90, 4, 'ecologic', 3, 'approved', 3, 1),
(3, '2026-01-05', '2026-01-05', 'Lille', 'Brussels', '10:00:00', '11:30:00', 90, 5, 'standard', 3, 'approved', 4, 2),
(4, '2026-01-10', '2026-01-10', 'Marseille', 'Montpellier', '18:00:00', '20:00:00', 120, 4, 'standard', 3, 'approved', 5, 3),
(5, '2026-02-15', '2026-02-15', 'Toulouse', 'Biarritz', '08:00:00', '11:00:00', 180, 5, 'ecologic', 2, 'approved', 9, 4),
(6, '2026-03-01', '2026-03-01', 'Clermont-Ferrand', 'Lyon', '16:00:00', '18:00:00', 120, 3, 'standard', 3, 'pending', 11, 5);

-- Seed Bookings
INSERT INTO BOOKINGS (user_id, trip_id) VALUES
(7, 1), (8, 1),
(10, 2), (11, 2),
(6, 3),
(4, 4), (6, 4),
(8, 5);

-- Seed Transactions
INSERT INTO TRANSACTIONS (user_id, amount, transaction_type, reference) VALUES
-- Welcome Bonuses
(4, 20, 'welcome_bonus', NULL), (5, 20, 'welcome_bonus', NULL), (6, 20, 'welcome_bonus', NULL), (7, 20, 'welcome_bonus', NULL), (8, 20, 'welcome_bonus', NULL), (9, 20, 'welcome_bonus', NULL), (10, 20, 'welcome_bonus', NULL), (11, 20, 'welcome_bonus', NULL), (12, 20, 'welcome_bonus', NULL), (13, 20, 'welcome_bonus', NULL),
-- Trip 1 (Price 5, Driver 13)
(7, -5, 'payment', 1), (13, 3, 'payment', 1), (1, 2, 'service_fee', 1),
(8, -5, 'payment', 1), (13, 3, 'payment', 1), (1, 2, 'service_fee', 1),
-- Trip 2 (Price 4, Driver 3)
(10, -4, 'payment', 2), (3, 2, 'payment', 2), (1, 2, 'service_fee', 2),
(11, -4, 'payment', 2), (3, 2, 'payment', 2), (1, 2, 'service_fee', 2),
-- Trip 3 (Price 5, Driver 4)
(6, -5, 'payment', 3), (4, 3, 'payment', 3), (1, 2, 'service_fee', 3),
-- Trip 4 (Price 4, Driver 5)
(4, -4, 'payment', 4), (5, 2, 'payment', 4), (1, 2, 'service_fee', 4),
(6, -4, 'payment', 4), (5, 2, 'payment', 4), (1, 2, 'service_fee', 4),
-- Trip 5 (Price 5, Driver 9)
(8, -5, 'payment', 5), (9, 3, 'payment', 5), (1, 2, 'service_fee', 5);

SELECT 'Ecoride database created and seeded successfully!' AS status;

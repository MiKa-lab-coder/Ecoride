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

-- Seed Users
INSERT INTO USERS (user_id, name, firstname, birth_date, username, email, password, role_id, photo) VALUES
(1, 'Admin', 'Ecoride', '2025-01-01', 'adminEcoride',
 'admin@ecoride-project.ovh', '$2y$12$zAahKmf91EWD.FeuCgxlYugtUONgtJmOp3..31NnCiRWQV6sw/9QC', 1, 'uploads/default.png'),
(2, 'Moderator', 'Ecoride', '2025-01-01', 'moderatorEcoride',
 'moderator@ecoride-project.ovh', '$2y$12$.U6lrGE4IztOO5sjnq2zWOFrh/mQinZpIRN.ZOzxu6GCltvivdya2', 2, 'uploads/default.png'),
(3, 'User', 'Ecoride', '2025-01-01', 'userEcoride',
 'userecoride-project@gmail.com', '$2y$12$JFE3YBn6JaB/UUpH0qTeXeJFDAq5RrLb7.TJnEAuX3czurCYirAwO', 3, 'uploads/default.png'),
(4, 'Dupont', 'Marie', '1990-05-15', 'mariedupont',
 'marie.dupont@example.com', '$2y$10$dummyPassword1', 3, 'uploads/default.png'),
(5, 'Martin', 'Paul', '1988-11-20', 'paulmartin',
 'paul.martin@example.com', '$2y$10$dummyPassword2', 3, 'uploads/default.png'),
(6, 'Dubois', 'Chloé', '1995-02-10', 'chloedubois',
 'chloe.dubois@example.com', '$2y$10$dummyPassword3', 3, 'uploads/default.png'),
(7, 'Lefebvre', 'Lucas', '1992-09-30', 'lucaslefebvre'
, 'lucas.lefebvre@example.com', '$2y$10$dummyPassword4', 3, 'uploads/default.png'),
(8, 'Garcia', 'Emma', '1998-07-22', 'emmaga',
 'emma.garcia@example.com', '$2y$10$dummyPassword5', 3, 'uploads/default.png'),
(9, 'Martinez', 'Hugo', '1985-03-12', 'hugomar',
 'hugo.martinez@example.com', '$2y$10$dummyPassword6', 3, 'uploads/default.png'),
(10, 'Roux', 'Léa', '2000-01-05', 'learoux',
 'lea.roux@example.com', '$2y$10$dummyPassword7', 3, 'uploads/default.png'),
(11, 'Fournier', 'Louis', '1993-12-18', 'louisfour',
 'louis.fournier@example.com', '$2y$10$dummyPassword8', 3, 'uploads/default.png'),
(12, 'Moreau', 'Manon', '1997-08-08', 'manonmo',
 'manon.moreau@example.com', '$2y$10$dummyPassword9', 3, 'uploads/default.png'),
(13, 'Girard', 'Adam', '1991-06-25', 'adamgir',
 'adam.girard@example.com', '$2y$10$dummyPassword10', 3, 'uploads/default.png');

-- Seed Vehicles (Pas de véhicules pour Admin/Modérateur)
INSERT INTO VEHICLES (vehicle_id, first_service, registration_number, energy_type, brand, model, color, seating_capacity, user_id) VALUES
(1, '2020-01-15', 'AA-123-BB', 'electric',
 'Renault', 'Zoe', 'Blue', 4, 3),
(2, '2021-06-01', 'CC-456-DD', 'hybrid',
 'Toyota', 'Yaris', 'Grey', 4, 4),
(3, '2019-03-20', 'EE-789-FF', 'combustion',
 'Peugeot', '308', 'Black', 4, 5),
(4, '2022-08-10', 'GG-234-HH', 'electric',
 'Tesla', 'Model 3', 'White', 4, 9),
(5, '2018-11-05', 'II-567-JJ', 'combustion',
 'Volkswagen', 'Golf', 'Silver', 4, 11),
(6, '2023-01-30', 'KK-890-LL', 'hybrid',
 'Hyundai', 'Ioniq', 'Red', 4, 13),
(7, '2023-03-01', 'MM-111-NN', 'electric',
 'Nissan', 'Leaf', 'White', 4, 3);

-- Seed Trips
INSERT INTO TRIPS (trip_id, departure_day, arrival_day, departure_location, arrival_location, departure_time,
                   arrival_time, trip_time, trip_price, trip_nature, seating, status, driver_id, vehicle_id)
VALUES
-- 1. Trajets COMPLETED (Passés)
-- User 3 conducteur, 2 passagers (Marie, Paul)
(1, '2024-12-20', '2024-12-20', 'Paris', 'Lyon',
 '08:00:00', '13:00:00', 300, 5, 'ecologic', 3, 'completed', 3, 1),
-- User 13 conducteur, User 3 passager
(2, '2024-12-22', '2024-12-22', 'Rennes', 'Nantes',
 '17:00:00', '18:30:00', 90, 4, 'standard', 3, 'completed', 13, 6),

-- 2. Trajets PENDING (En attente de validation) - User 3 conducteur
-- Dates entre 25/12/2025 et 01/03/2026
(10, '2025-12-28', '2025-12-28', 'Paris', 'Rouen',
 '09:00:00', '10:30:00', 90, 5, 'ecologic', 3, 'pending', 3, 1),
(11, '2026-01-15', '2026-01-15', 'Lille', 'Calais',
 '14:00:00', '15:30:00', 90, 4, 'ecologic', 3, 'pending', 3, 7),
(12, '2026-02-20', '2026-02-20', 'Lyon', 'Grenoble',
 '10:00:00', '11:30:00', 90, 5, 'ecologic', 3, 'pending', 3, 1),

-- 3. Grappes de trajets APPROVED (Futurs) - Pour tester la recherche
-- Groupe 1 : Paris -> Marseille, 10/06/2026
(20, '2026-06-10', '2026-06-10', 'Paris', 'Marseille',
 '08:00:00', '16:00:00', 480, 5, 'ecologic', 3, 'approved', 3, 1), -- User 3 (Eco)
(21, '2026-06-10', '2026-06-10', 'Paris', 'Marseille',
 '08:30:00', '17:00:00', 510, 4, 'standard', 2, 'approved', 4, 2), -- Marie (Standard, moins cher)
(22, '2026-06-10', '2026-06-10', 'Paris', 'Marseille',
 '09:00:00', '17:30:00', 510, 5, 'standard', 3, 'approved', 5, 3), -- Paul (Standard, même prix)

-- Groupe 2 : Lyon -> Bordeaux, 20/07/2026
(30, '2026-07-20', '2026-07-20', 'Lyon', 'Bordeaux',
 '07:00:00', '13:00:00', 360, 5, 'ecologic', 2, 'approved', 9, 4), -- Hugo (Eco, animaux ok)
(31, '2026-07-20', '2026-07-20', 'Lyon', 'Bordeaux',
 '07:30:00', '14:00:00', 390, 4, 'standard', 3, 'approved', 11, 5), -- Louis (Standard, fumeur ok)

-- Trajet pour tester la réservation par userEcoride
(40, '2026-08-01', '2026-08-01', 'Nantes', 'Brest',
 '10:00:00', '13:00:00', 180, 15, 'standard', 2, 'approved', 13, 6),

-- 4. Nouveaux trajets APPROVED (Période 03/02/2026 - 01/03/2026)
-- 03/02/2026
(50, '2026-02-03', '2026-02-03', 'Paris', 'Lille',
 '08:00:00', '11:00:00', 180, 4, 'standard', 3, 'approved', 4, 2),
(51, '2026-02-03', '2026-02-03', 'Paris', 'Lille',
 '09:00:00', '12:00:00', 180, 5, 'ecologic', 2, 'approved', 9, 4),

-- 05/02/2026
(52, '2026-02-05', '2026-02-05', 'Lyon', 'Marseille',
 '14:00:00', '16:00:00', 120, 3, 'standard', 3, 'approved', 5, 3),
(53, '2026-02-05', '2026-02-05', 'Lyon', 'Marseille',
 '15:00:00', '17:00:00', 120, 4, 'ecologic', 3, 'approved', 13, 6),

-- 08/02/2026
(54, '2026-02-08', '2026-02-08', 'Bordeaux', 'Toulouse',
 '10:00:00', '12:30:00', 150, 4, 'standard', 3, 'approved', 11, 5),
(55, '2026-02-08', '2026-02-08', 'Bordeaux', 'Toulouse',
 '11:00:00', '13:30:00', 150, 5, 'ecologic', 2, 'approved', 3, 1),

-- 10/02/2026
(56, '2026-02-10', '2026-02-10', 'Nantes', 'Rennes',
 '07:00:00', '08:30:00', 90, 3, 'standard', 3, 'approved', 4, 2),
(57, '2026-02-10', '2026-02-10', 'Nantes', 'Rennes',
 '08:00:00', '09:30:00', 90, 4, 'ecologic', 3, 'approved', 9, 4),

-- 12/02/2026
(58, '2026-02-12', '2026-02-12', 'Strasbourg', 'Metz',
 '16:00:00', '17:30:00', 90, 4, 'standard', 2, 'approved', 5, 3),
(59, '2026-02-12', '2026-02-12', 'Strasbourg', 'Metz',
 '17:00:00', '18:30:00', 90, 5, 'ecologic', 3, 'approved', 13, 6),

-- 15/02/2026
(60, '2026-02-15', '2026-02-15', 'Paris', 'Bordeaux',
 '09:00:00', '14:00:00', 300, 5, 'standard', 3, 'approved', 11, 5),
(61, '2026-02-15', '2026-02-15', 'Paris', 'Bordeaux',
 '10:00:00', '15:00:00', 300, 5, 'ecologic', 2, 'approved', 3, 7),

-- 18/02/2026
(62, '2026-02-18', '2026-02-18', 'Marseille', 'Nice',
 '08:00:00', '10:00:00', 120, 4, 'standard', 3, 'approved', 4, 2),
(63, '2026-02-18', '2026-02-18', 'Marseille', 'Nice',
 '09:00:00', '11:00:00', 120, 5, 'ecologic', 3, 'approved', 9, 4),

-- 20/02/2026
(64, '2026-02-20', '2026-02-20', 'Lille', 'Bruxelles',
 '13:00:00', '14:30:00', 90, 3, 'standard', 3, 'approved', 5, 3),
(65, '2026-02-20', '2026-02-20', 'Lille', 'Bruxelles',
 '14:00:00', '15:30:00', 90, 4, 'ecologic', 2, 'approved', 13, 6),

-- 25/02/2026
(66, '2026-02-25', '2026-02-25', 'Toulouse', 'Montpellier',
 '15:00:00', '17:30:00', 150, 4, 'standard', 3, 'approved', 11, 5),
(67, '2026-02-25', '2026-02-25', 'Toulouse', 'Montpellier',
 '16:00:00', '18:30:00', 150, 5, 'ecologic', 3, 'approved', 3, 1),

-- 28/02/2026
(68, '2026-02-28', '2026-02-28', 'Lyon', 'Genève',
 '08:00:00', '10:00:00', 120, 4, 'standard', 3, 'approved', 4, 2),
(69, '2026-02-28', '2026-02-28', 'Lyon', 'Genève',
 '09:00:00', '11:00:00', 120, 5, 'ecologic', 2, 'approved', 9, 4);


-- Update preferences for specific trips
UPDATE TRIPS SET animal_pref = 1 WHERE trip_id = 30;
UPDATE TRIPS SET smoking_pref = 1 WHERE trip_id = 31;


-- Seed Bookings
INSERT INTO BOOKINGS (user_id, trip_id) VALUES
-- Réservations pour les trajets COMPLETED
(4, 1), (5, 1), -- Marie et Paul sont passagers du trajet 1 (User 3 conducteur)
(3, 2),       -- userEcoride est passager du trajet 2 (User 13 conducteur)
-- Réservations pour les trajets APPROVED
(6, 21),      -- Chloé est passagère du trajet 21
(7, 30),      -- Lucas est passager du trajet 30
-- Réservations pour les nouveaux trajets (pour montrer de l'activité)
(6, 50), (7, 50), -- 2 passagers sur Paris-Lille (Trajet 50, Prix 4, Conducteur 4)
(8, 52),          -- 1 passager sur Lyon-Marseille (Trajet 52, Prix 3, Conducteur 5)
(10, 55),         -- 1 passager sur Bordeaux-Toulouse (Trajet 55, Prix 5, Conducteur 3)
(12, 60), (13, 60); -- 2 passagers sur Paris-Bordeaux (Trajet 60, Prix 5, Conducteur 11)

-- Seed Ratings (uniquement pour les trajets COMPLETED)
INSERT INTO RATINGS (rated_user_id, passenger_id, trip_id, rating_value) VALUES
-- Trajet 1 : User 3 (conducteur) note ses passagers
(4, 3, 1, 5),  -- Note pour Marie
(5, 3, 1, 4),  -- Note pour Paul
-- Trajet 1 : Les passagers notent User 3
(3, 4, 1, 5),
(3, 5, 1, 4),

-- Trajet 2 : User 3 (passager) note le conducteur (Adam)
(13, 3, 2, 4),
-- Trajet 2 : Le conducteur note User 3
(3, 13, 2, 5);

-- Seed Transactions
-- Logique : Conducteur reçoit (Prix - 2), Admin reçoit 2 par passager.
INSERT INTO TRANSACTIONS (user_id, amount, transaction_type, reference) VALUES
-- Bonus de bienvenue
(3, 20, 'welcome_bonus', NULL),
(4, 20, 'welcome_bonus', NULL),
(5, 20, 'welcome_bonus', NULL),
(6, 20, 'welcome_bonus', NULL),
(7, 20, 'welcome_bonus', NULL),
(13, 20, 'welcome_bonus', NULL),

-- Transactions pour le trajet 1 (Prix 5, 2 passagers)
-- Passager Marie
(4, -5, 'payment', 1),
(3, 3, 'payment', 1), -- Conducteur reçoit 5-2 = 3
(1, 2, 'service_fee', 1), -- Admin reçoit 2
-- Passager Paul
(5, -5, 'payment', 1),
(3, 3, 'payment', 1), -- Conducteur reçoit 5-2 = 3
(1, 2, 'service_fee', 1), -- Admin reçoit 2

-- Transactions pour le trajet 2 (Prix 4, 1 passager User 3)
(3, -4, 'payment', 2),
(13, 2, 'payment', 2), -- Conducteur reçoit 4-2 = 2
(1, 2, 'service_fee', 2), -- Admin reçoit 2

-- Transactions pour les NOUVEAUX trajets (pour les stats)
-- Trajet 50 (Prix 4, Conducteur 4, Passagers 6 et 7)
(6, -4, 'payment', 50),
(4, 2, 'payment', 50),
(1, 2, 'service_fee', 50),
(7, -4, 'payment', 50),
(4, 2, 'payment', 50),
(1, 2, 'service_fee', 50),

-- Trajet 52 (Prix 3, Conducteur 5, Passager 8)
(8, -3, 'payment', 52),
(5, 1, 'payment', 52),
(1, 2, 'service_fee', 52),

-- Trajet 55 (Prix 5, Conducteur 3, Passager 10)
(10, -5, 'payment', 55),
(3, 3, 'payment', 55),
(1, 2, 'service_fee', 55),

-- Trajet 60 (Prix 5, Conducteur 11, Passagers 12 et 13)
(12, -5, 'payment', 60),
(11, 3, 'payment', 60),
(1, 2, 'service_fee', 60),
(13, -5, 'payment', 60),
(11, 3, 'payment', 60),
(1, 2, 'service_fee', 60);

-- Ecoride Database Creation
CREATE DATABASE IF NOT EXISTS Ecoride;
USE Ecoride;

-- Table: ROLES
CREATE TABLE IF NOT EXISTS ROLES (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
    );

-- Table: USERS
CREATE TABLE IF NOT EXISTS USERS (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    birth_date DATE NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    photo VARCHAR(255),
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    total_trips INT DEFAULT 0,
    account_status ENUM('active', 'suspended') DEFAULT 'active',
    role_id INT,
    FOREIGN KEY (role_id) REFERENCES ROLES(role_id)
    );

-- Table: VEHICLES
CREATE TABLE IF NOT EXISTS VEHICLES (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    first_service DATE NOT NULL,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    energy_type ENUM('electric', 'hybrid', 'combustion') NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30),
    seating_capacity INT NOT NULL,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
    );

-- Table: TRIPS
CREATE TABLE IF NOT EXISTS TRIPS (
    trip_id INT AUTO_INCREMENT PRIMARY KEY,
    departure_day DATE NOT NULL,
    arrival_day DATE NOT NULL,
    departure_location VARCHAR(255) NOT NULL,
    arrival_location VARCHAR(255) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    trip_time INT NOT NULL,
    trip_price INT NOT NULL,
    trip_nature VARCHAR(255) NOT NULL,
    animal_pref BOOLEAN DEFAULT FALSE,
    smoking_pref BOOLEAN DEFAULT FALSE,
    seating INT NOT NULL,
    status ENUM ('pending', 'completed', 'approved') NOT NULL DEFAULT 'pending',
    driver_id INT,
    vehicle_id INT,
    FOREIGN KEY (driver_id) REFERENCES USERS(user_id),
    FOREIGN KEY (vehicle_id) REFERENCES VEHICLES(vehicle_id)
    );

-- Table: RATINGS
CREATE TABLE IF NOT EXISTS RATINGS
(
    id            INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rated_user_id INT(11) NOT NULL, -- ID du conducteur noté
    passenger_id  INT(11) NOT NULL, -- ID du passager qui a noté
    trip_id       INT(11) NOT NULL, -- ID du trajet associé à la note
    rating_value  INT(11) NOT NULL, -- La note
    FOREIGN KEY (rated_user_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES TRIPS (trip_id) ON DELETE CASCADE
);

-- Table: TRANSACTIONS
CREATE TABLE IF NOT EXISTS TRANSACTIONS (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    transaction_type ENUM('payment', 'cancellation','service_fee','welcome_bonus') NOT NULL DEFAULT 'payment',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reference INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES USERS (user_id) ON DELETE CASCADE,
    FOREIGN KEY (reference) REFERENCES TRIPS (trip_id) ON DELETE CASCADE
    );

-- Table: RESERVATIONS
CREATE TABLE IF NOT EXISTS RESERVATIONS (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_date DATE NOT NULL,
    seat_reserved INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    user_id INT,
    trip_id INT,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id),
    FOREIGN KEY (trip_id) REFERENCES TRIPS(trip_id)
    );

-- Table: ISSUES
CREATE TABLE IF NOT EXISTS ISSUES (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL,
    date_open DATE NOT NULL,
    description TEXT NOT NULL,
    response TEXT, -- Ce champ peut être NULL avant qu'un modérateur ne réponde
    user_id INT,
    trip_id INT,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id),
    FOREIGN KEY (trip_id) REFERENCES TRIPS(trip_id)
    );

-- Table pour archiver les trajets terminés avec cron
CREATE TABLE IF NOT EXISTS ARCHIVED_TRIPS (
    trip_id INT AUTO_INCREMENT PRIMARY KEY,
    departure_day VARCHAR(255) NOT NULL,
    arrival_day VARCHAR(255) NOT NULL,
    departure_location VARCHAR(255) NOT NULL,
    arrival_location VARCHAR(255) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    trip_time INT NOT NULL,
    trip_price INT NOT NULL,
    trip_nature VARCHAR(255) NOT NULL,
    animal_pref BOOLEAN DEFAULT FALSE,
    smoking_pref BOOLEAN DEFAULT FALSE,
    seating INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    user_id INT,
    vehicle_id INT,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id),
    FOREIGN KEY (vehicle_id) REFERENCES VEHICLES(vehicle_id)
    );

-- Table pour archiver les litiges résolus avec cron
CREATE TABLE IF NOT EXISTS ARCHIVED_ISSUES (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL,
    date_open DATE NOT NULL,
    description TEXT NOT NULL,
    response TEXT,
    user_id INT,
    trip_id INT,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id),
    FOREIGN KEY (trip_id) REFERENCES TRIPS(trip_id)
    );

-- Insertion des rôles par défaut
INSERT INTO ROLES (role_name) VALUES
('admin'),
('moderator'),
('user');

-- Insertion d'un utilisateur admin par défaut
INSERT INTO USERS (name, firstname, birth_date, username, photo, email, password, role_id)
VALUES ('Admin', 'Ecoride', '2025-01-01', 'admin_ecoride',
        NULL, 'admin@ecoride.com', '', (SELECT role_id FROM ROLES WHERE role_name = 'admin'));

-- note : le mot de passe doit être mis à jour après le hashage via l'application
-- insertion ultérieurement via une requête UPDATE
-- UPDATE USERS SET password = 'hashed_password' WHERE username = 'admin_ecoride';

-- Insertion d'un utilisateur modérateur par défaut
INSERT INTO USERS (name, firstname, birth_date, username, photo, email, password, role_id)
VALUES ('Modérateur', 'Ecoride', '2025-01-01', 'moderator1_ecoride',
        NULL, 'moderator1@ecoride.com', '', (SELECT role_id FROM ROLES WHERE role_name = 'moderator'));

-- note : le mot de passe doit être mis à jour après le hashage via l'application
-- insertion ultérieurement via une requête UPDATE
-- UPDATE USERS SET password = 'hashed_password' WHERE username = 'moderator1_ecoride';

-- Insertion d'un utilisateur standard par défaut pour les tests
INSERT INTO USERS (name, firstname, birth_date, username, photo, email, password, role_id)
VALUES ('User', 'Ecoride', '2025-01-01', 'user1_ecoride',
        NULL, 'user1@gmail.com', '', (SELECT role_id FROM ROLES WHERE role_name = 'user'));
-- note : le mot de passe doit être mis à jour après le hashage via l'application
-- insertion ultérieurement via une requête UPDATE
-- UPDATE USERS SET password = 'hashed_password' WHERE username = 'user1_ecoride';
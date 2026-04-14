-- Database: dilan_airlines
CREATE DATABASE IF NOT EXISTS dilan_airlines;
USE dilan_airlines;

-- Roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (name) VALUES ('admin'), ('user');

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    nik VARCHAR(16) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Airports table
CREATE TABLE airports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    country VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Airlines table
CREATE TABLE airlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(2) NOT NULL UNIQUE,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flights table
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(10) NOT NULL UNIQUE,
    airline_id INT NOT NULL,
    departure_airport_id INT NOT NULL,
    arrival_airport_id INT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT NOT NULL,
    total_seats INT NOT NULL,
    status ENUM('scheduled', 'delayed', 'cancelled', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id),
    FOREIGN KEY (departure_airport_id) REFERENCES airports(id),
    FOREIGN KEY (arrival_airport_id) REFERENCES airports(id)
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(10) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    total_passengers INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'paid', 'rejected', 'refunded') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (flight_id) REFERENCES flights(id)
);

-- Booking passengers table
CREATE TABLE booking_passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    id_number VARCHAR(20) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    seat_number VARCHAR(5),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_date TIMESTAMP NULL,
    status ENUM('pending', 'confirmed', 'rejected', 'refunded') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Payment proofs table
CREATE TABLE payment_proofs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

-- Tickets table
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    qr_code VARCHAR(255),
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'used', 'expired', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Insert sample data
INSERT INTO airports (code, name, city, country) VALUES
('CGK', 'Soekarno-Hatta International Airport', 'Jakarta', 'Indonesia'),
('DPS', 'Ngurah Rai International Airport', 'Denpasar', 'Indonesia'),
('SUB', 'Juanda International Airport', 'Surabaya', 'Indonesia'),
('KNO', 'Kualanamu International Airport', 'Medan', 'Indonesia'),
('UPG', 'Sultan Hasanuddin International Airport', 'Makassar', 'Indonesia'),
('BDJ', 'Syamsudin Noor Airport', 'Banjarmasin', 'Indonesia'),
('BTH', 'Hang Nadim Airport', 'Batam', 'Indonesia'),
('LOP', 'Lombok International Airport', 'Praya', 'Indonesia'),
('JOG', 'Adisutjipto International Airport', 'Yogyakarta', 'Indonesia'),
('BDG', 'Husein Sastranegara International Airport', 'Bandung', 'Indonesia'),
('PKU', 'Sultan Syarif Kasim II International Airport', 'Pekanbaru', 'Indonesia'),
('PDG', 'Minangkabau International Airport', 'Padang', 'Indonesia'),
('MDN', 'Sam Ratulangi International Airport', 'Manado', 'Indonesia'),
('DJJ', 'Sentani International Airport', 'Jayapura', 'Indonesia'),
('BTJ', 'Sultan Iskandar Muda International Airport', 'Banda Aceh', 'Indonesia');

INSERT INTO airlines (name, code, logo) VALUES
('Garuda Indonesia', 'GA', 'garuda.png'),
('Lion Air', 'JT', 'lion.png'),
('Citilink', 'QG', 'citilink.png'),
('Batik Air', 'ID', 'batik.png'),
('Sriwijaya Air', 'SJ', 'sriwijaya.png');

-- Insert sample flights with comprehensive schedule
INSERT INTO flights (flight_number, airline_id, departure_airport_id, arrival_airport_id, departure_time, arrival_time, price, available_seats, total_seats) VALUES
-- Jakarta routes
('GA123', 1, 1, 2, '2024-12-25 08:00:00', '2024-12-25 10:30:00', 1500000.00, 150, 180),
('GA124', 1, 1, 2, '2024-12-25 14:00:00', '2024-12-25 16:30:00', 1500000.00, 120, 180),
('GA125', 1, 1, 3, '2024-12-25 09:00:00', '2024-12-25 10:45:00', 950000.00, 140, 180),
('GA126', 1, 1, 3, '2024-12-25 15:00:00', '2024-12-25 16:45:00', 950000.00, 100, 180),
('GA127', 1, 1, 4, '2024-12-25 07:00:00', '2024-12-25 09:15:00', 1800000.00, 90, 120),
('GA128', 1, 1, 5, '2024-12-25 10:00:00', '2024-12-25 13:30:00', 2100000.00, 110, 150),
('GA129', 1, 1, 9, '2024-12-25 11:00:00', '2024-12-25 12:30:00', 750000.00, 130, 150),
('GA130', 1, 1, 10, '2024-12-25 08:30:00', '2024-12-25 10:00:00', 650000.00, 120, 150),
('GA131', 1, 1, 11, '2024-12-25 06:00:00', '2024-12-25 07:45:00', 1200000.00, 80, 120),
('GA132', 1, 1, 12, '2024-12-25 09:30:00', '2024-12-25 11:15:00', 1100000.00, 100, 140),
('GA133', 1, 1, 13, '2024-12-25 13:00:00', '2024-12-25 15:30:00', 1600000.00, 70, 100),
('GA134', 1, 1, 14, '2024-12-25 08:00:00', '2024-12-25 12:00:00', 2500000.00, 60, 80),
('GA135', 1, 1, 15, '2024-12-25 07:30:00', '2024-12-25 09:45:00', 2000000.00, 85, 120),

-- Lion Air routes
('JT456', 2, 1, 2, '2024-12-25 09:00:00', '2024-12-25 11:30:00', 1200000.00, 160, 200),
('JT457', 2, 1, 2, '2024-12-25 16:00:00', '2024-12-25 18:30:00', 1200000.00, 140, 200),
('JT458', 2, 1, 3, '2024-12-25 08:00:00', '2024-12-25 09:45:00', 750000.00, 180, 220),
('JT459', 2, 1, 3, '2024-12-25 14:00:00', '2024-12-25 15:45:00', 750000.00, 150, 220),
('JT460', 2, 1, 4, '2024-12-25 06:30:00', '2024-12-25 08:45:00', 1500000.00, 120, 160),
('JT461', 2, 1, 5, '2024-12-25 11:00:00', '2024-12-25 14:30:00', 1800000.00, 130, 180),
('JT462', 2, 1, 9, '2024-12-25 10:00:00', '2024-12-25 11:30:00', 600000.00, 170, 200),
('JT463', 2, 1, 10, '2024-12-25 07:30:00', '2024-12-25 09:00:00', 550000.00, 160, 200),
('JT464', 2, 1, 11, '2024-12-25 08:30:00', '2024-12-25 10:15:00', 950000.00, 110, 150),
('JT465', 2, 1, 12, '2024-12-25 09:00:00', '2024-12-25 10:45:00', 900000.00, 140, 180),

-- Citilink routes
('QG789', 3, 2, 1, '2024-12-25 11:00:00', '2024-12-25 13:30:00', 1400000.00, 100, 120),
('QG790', 3, 2, 1, '2024-12-25 17:00:00', '2024-12-25 19:30:00', 1400000.00, 80, 120),
('QG791', 3, 2, 3, '2024-12-25 08:30:00', '2024-12-25 10:15:00', 1100000.00, 90, 120),
('QG792', 3, 2, 8, '2024-12-25 10:00:00', '2024-12-25 11:00:00', 650000.00, 110, 140),
('QG793', 3, 2, 9, '2024-12-25 12:00:00', '2024-12-25 13:30:00', 850000.00, 120, 150),

-- Batik Air routes
('ID234', 4, 1, 4, '2024-12-26 06:00:00', '2024-12-26 08:15:00', 2200000.00, 80, 100),
('ID235', 4, 1, 4, '2024-12-26 14:00:00', '2024-12-26 16:15:00', 2200000.00, 70, 100),
('ID236', 4, 1, 6, '2024-12-26 09:00:00', '2024-12-26 10:30:00', 1300000.00, 90, 120),
('ID237', 4, 1, 7, '2024-12-26 08:00:00', '2024-12-26 09:45:00', 1100000.00, 100, 140),
('ID238', 4, 1, 13, '2024-12-26 10:00:00', '2024-12-26 12:30:00', 1700000.00, 60, 80),

-- Sriwijaya Air routes
('SJ567', 5, 3, 1, '2024-12-26 14:00:00', '2024-12-26 16:00:00', 950000.00, 110, 140),
('SJ568', 5, 3, 2, '2024-12-26 08:00:00', '2024-12-26 10:30:00', 1250000.00, 90, 120),
('SJ569', 5, 3, 4, '2024-12-26 07:00:00', '2024-12-26 09:30:00', 1600000.00, 70, 100),
('SJ570', 5, 3, 9, '2024-12-26 11:00:00', '2024-12-26 12:30:00', 700000.00, 120, 150),
('SJ571', 5, 3, 10, '2024-12-26 09:30:00', '2024-12-26 11:00:00', 600000.00, 130, 160),

-- Return flights and additional routes
('GA201', 1, 2, 1, '2024-12-25 11:00:00', '2024-12-25 13:30:00', 1450000.00, 140, 180),
('GA202', 1, 3, 1, '2024-12-25 11:15:00', '2024-12-25 13:00:00', 950000.00, 130, 180),
('GA203', 1, 4, 1, '2024-12-25 09:30:00', '2024-12-25 11:45:00', 1750000.00, 85, 120),
('GA204', 1, 5, 1, '2024-12-25 14:00:00', '2024-12-25 17:30:00', 2050000.00, 100, 150),

('JT801', 2, 2, 1, '2024-12-25 12:00:00', '2024-12-25 14:30:00', 1150000.00, 150, 200),
('JT802', 2, 3, 1, '2024-12-25 10:15:00', '2024-12-25 12:00:00', 750000.00, 170, 220),
('JT803', 2, 4, 1, '2024-12-25 09:00:00', '2024-12-25 11:15:00', 1450000.00, 110, 160),

('QG901', 3, 1, 2, '2024-12-26 08:00:00', '2024-12-26 10:30:00', 1350000.00, 110, 120),
('QG902', 3, 1, 3, '2024-12-26 09:00:00', '2024-12-26 10:45:00', 900000.00, 130, 150),
('QG903', 3, 1, 8, '2024-12-26 10:00:00', '2024-12-26 11:00:00', 600000.00, 120, 140),

('ID301', 4, 4, 1, '2024-12-26 17:00:00', '2024-12-26 19:15:00', 2150000.00, 75, 100),
('ID302', 4, 6, 1, '2024-12-26 11:00:00', '2024-12-26 12:30:00', 1250000.00, 85, 120),
('ID303', 4, 7, 1, '2024-12-26 10:00:00', '2024-12-26 11:45:00', 1050000.00, 95, 140),

('SJ601', 5, 1, 3, '2024-12-26 17:00:00', '2024-12-26 19:00:00', 950000.00, 100, 140),
('SJ602', 5, 1, 2, '2024-12-26 13:30:00', '2024-12-26 16:00:00', 1200000.00, 80, 120),
('SJ603', 5, 1, 4, '2024-12-26 11:00:00', '2024-12-26 13:30:00', 1550000.00, 65, 100);

-- Insert admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, nik, phone, role_id) VALUES
('admin', 'admin@dilanairlines.com', '$2y$10$8KxO8O8O8O8O8O8O8O8O8e8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O', 'Administrator', '3173000000000001', '+62812345678', 1);

-- Insert sample user (password: user123)
INSERT INTO users (username, email, password, full_name, nik, phone, role_id) VALUES
('user', 'user@example.com', '$2y$10$8KxO8O8O8O8O8O8O8O8O8e8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O8O', 'John Doe', '3173000000000002', '+628987654321', 2);

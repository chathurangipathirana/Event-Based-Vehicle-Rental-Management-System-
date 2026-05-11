-- Create database
CREATE DATABASE IF NOT EXISTS fleetelite_admin;
USE fleetelite_admin;

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'manager') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    license_plate VARCHAR(20) UNIQUE,
    vin_number VARCHAR(50),
    price_per_day DECIMAL(10,2),
    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(20) UNIQUE,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    vehicle_id INT,
    event_type VARCHAR(50),
    event_date DATE,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Event packages table
CREATE TABLE event_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    included_services TEXT,
    vehicle_types VARCHAR(255),
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Drivers table
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    rating DECIMAL(3,2) DEFAULT 5.00,
    rating_level INT DEFAULT 5,
    status ENUM('available', 'on_duty', 'off_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin user (password: Admin123!)
INSERT INTO admin_users (username, email, password, full_name, role) VALUES
('admin', 'admin@fleetelite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert sample vehicles
INSERT INTO vehicles (name, model, license_plate, vin_number, price_per_day, status, category) VALUES
('Porsche 911 GT3', '992', 'FLT-001', 'FLT-8829-PX', 850, 'available', 'Sports'),
('Range Rover SV', 'L405', 'FLT-002', 'FLT-1142-RR', 680, 'booked', 'Luxury SUV'),
('BMW 7 Series', 'G70', 'FLT-003', 'FLT-5510-BM', 450, 'maintenance', 'Executive'),
('Mercedes S-Class', 'W223', 'FLT-004', 'FLT-4432-MB', 520, 'available', 'Luxury'),
('Tesla Model S', 'Plaid', 'FLT-005', 'FLT-9912-TS', 380, 'available', 'Electric');

-- Insert sample drivers
INSERT INTO drivers (name, email, phone, rating, rating_level, status) VALUES
('Marcus Vance', 'marcus@fleetelite.com', '+1 (555) 123-4567', 4.98, 5, 'available'),
('Sarah Jennings', 'sarah@fleetelite.com', '+1 (555) 234-5678', 4.95, 4, 'available'),
('David Chen', 'david@fleetelite.com', '+1 (555) 345-6789', 4.92, 4, 'available');

-- Insert sample packages
INSERT INTO event_packages (name, description, base_price, included_services, vehicle_types, status) VALUES
('Wedding Premium', 'The ultimate luxury experience for high-profile weddings.', 2500, '3 Luxury Sedans + 1 Stretch Limousine,8-Hour Chauffeur Service,Champagne & Concierge', 'Sedan,Limousine', 'active'),
('Business Pro', 'Optimized logistics for corporate summits.', 1800, '5 Executive SUVs,Airport Transfer Coordination,Real-time Fleet Tracking', 'SUV', 'active'),
('Gala Elite', 'Premium gala night package with multiple arrival points.', 3200, 'Red Carpet Service,Multiple Arrival Points,VIP Coordination', 'Sedan,Limo,Bus', 'active');
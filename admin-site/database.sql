-- ======================================================
-- COMPLETE DATABASE FOR FLEETELITE ADMIN PANEL
-- ======================================================

-- Create database
CREATE DATABASE IF NOT EXISTS fleetelite_db;
USE fleetelite_db;

-- ======================================================
-- 1. ADMIN USERS TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'manager') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 2. VEHICLES TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    year INT,
    license_plate VARCHAR(20) UNIQUE,
    vin_number VARCHAR(50),
    price_per_day DECIMAL(15,2)  DEFAULT 0,
    price_per_hour DECIMAL(15,2) DEFAULT 0,
    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    category VARCHAR(50),
    capacity INT DEFAULT 4,
    transmission ENUM('Manual', 'Automatic') DEFAULT 'Automatic',
    fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid') DEFAULT 'Petrol',
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 3. EVENT TYPES TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS event_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    icon_class VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- ======================================================
-- 4. USERS TABLE (Customers)
-- ======================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    company_name VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 5. BOOKINGS TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    vehicle_id INT,
    event_type_id INT,
    event_name VARCHAR(100),
    event_date DATE,
    start_time TIME,
    end_time TIME,
    pickup_location TEXT,
    dropoff_location TEXT,
    total_hours INT,
    total_days INT,
    subtotal DECIMAL(10,2),
    tax DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    admin_notes TEXT,
    invoice_generated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE SET NULL
);

-- ======================================================
-- 6. EVENT PACKAGES TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS event_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(15,2) NOT NULL,
    included_services TEXT,
    vehicle_types VARCHAR(255),
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 7. DRIVERS TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    rating DECIMAL(3,2) DEFAULT 5.00,
    rating_level INT DEFAULT 5,
    status ENUM('available', 'on_duty', 'off_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 8. INVOICES TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    booking_id INT,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    tax DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('paid', 'pending', 'overdue', 'sent', 'cancelled') DEFAULT 'pending',
    issue_date DATE,
    due_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- ======================================================
-- 9. INVOICE ITEMS TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- ======================================================
-- 10. PAYMENTS TABLE
-- ======================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('credit_card', 'bank_transfer', 'cash') DEFAULT 'credit_card',
    transaction_id VARCHAR(100),
    status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ======================================================
-- SAMPLE DATA
-- ======================================================

-- Insert Admin (password: Admin123!)
INSERT INTO admin_users (username, email, password, full_name, role) VALUES
('admin', 'admin@fleetelite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert Sri Lankan customer users
INSERT INTO users (full_name, email, password, phone, company_name, address, city, state, zip_code, role) VALUES
('Nimal Perera', 'nimal.perera@example.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+94 77 111 2233', 'Ceylon Luxury Events', 'No. 45, Galle Road', 'Colombo', 'Western', '00300', 'customer'),
('Anjali Fernando', 'anjali.fernando@example.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+94 71 222 3344', 'Kandy Heritage Weddings', 'No. 12, Peradeniya Road', 'Kandy', 'Central', '20000', 'customer'),
('Sameera Jayawardena', 'sameera.jayawardena@example.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+94 70 333 5566', 'Southern Event Rentals', 'No. 9, Galle Road', 'Galle', 'Southern', '80000', 'customer'),
('Priya Senanayake', 'priya.senanayake@example.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+94 78 444 7788', NULL, 'No. 77, Lotus Road', 'Colombo', 'Western', '00500', 'customer'),
('Sanduni Kumar', 'sanduni.kumar@example.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+94 76 555 8899', 'Colombo Gala Planners', 'No. 123, Park Street', 'Colombo', 'Western', '00700', 'customer');

-- Insert Event Types
INSERT INTO event_types (name, slug, description, sort_order) VALUES
('Kandyan Wedding', 'kandyan-wedding', 'Elegant transport for traditional Sri Lankan weddings', 1),
('Colombo Business', 'colombo-business', 'Executive logistics for corporate events in Sri Lanka', 2),
('Island Tours', 'island-tours', 'Comfortable travel for Sri Lankan sightseeing routes', 3);

-- Insert Vehicles
INSERT INTO vehicles (name, model, license_plate, vin_number, price_per_day, price_per_hour, status, category, capacity, transmission, fuel_type, description, image_url) VALUES
('Colombo Toyota Axio', 'Toyota Corolla Axio', 'WP CAA-2345', 'LK-1001-AXIO', 18500, 2400, 'available', 'Luxury', 4, 'Automatic', 'Petrol', 'Popular Sri Lankan sedan for weddings, business transfers, and city events.', 'assets/vehicles/toyota-axio.png'),
('Kandy Toyota Premio', 'Toyota Premio', 'CP CAD-9876', 'LK-1002-PREMIO', 22000, 2800, 'available', 'Luxury', 4, 'Automatic', 'Petrol', 'Comfortable executive sedan commonly used for Sri Lankan wedding hires.', 'assets/vehicles/toyota-premio.png'),
('Galle Honda Vezel', 'Honda Vezel', 'SP CBA-4456', 'LK-1003-VEZEL', 24500, 3200, 'available', 'Luxury SUV', 5, 'Automatic', 'Hybrid', 'Compact hybrid SUV suited for coastal trips and event guest transport.', 'assets/vehicles/honda-vezel.png'),
('Negombo Toyota HiAce', 'Toyota HiAce', 'WP NB-1123', 'LK-1004-HIACE', 30000, 3800, 'maintenance', 'Executive', 10, 'Automatic', 'Diesel', 'Reliable van for airport pickups, group travel, and family events.', 'assets/vehicles/toyota-hiace.png'),
('Colombo Nissan Sunny', 'Nissan Sunny', 'WP CAS-7788', 'LK-1005-SUNNY', 16000, 2000, 'available', 'Executive', 5, 'Automatic', 'Petrol', 'Compact city sedan ideal for Colombo airport transfers and corporate bookings.', 'assets/vehicles/nissan-sunny.png'),
('Matara Suzuki Wagon R', 'Suzuki Wagon R', 'SP CBA-4456', 'LK-1006-WAGON', 14000, 1800, 'available', 'Sedan', 4, 'Automatic', 'Hybrid', 'Efficient city car for compact event errands and short hires.', 'assets/vehicles/suzuki-wagonr.png'),
('Nuwara Eliya Toyota Corolla', 'Toyota Corolla', 'KN CDL-3344', 'LK-1007-COROLLA', 16500, 2100, 'available', 'Sedan', 5, 'Automatic', 'Petrol', 'Reliable sedan for hill country tours and wedding guest transfers.', 'assets/vehicles/toyota-axio.png'),
('Matara Suzuki Wagon R', 'Suzuki Wagon R', 'SP CBA-5567', 'LK-1006-WAGON', 14000, 1800, 'available', 'Economy', 4, 'Automatic', 'Hybrid', 'Efficient city car for compact event errands and short hires.', 'assets/vehicles/suzuki-wagonr.png'),
('Jaffna Nissan Sunny', 'Nissan Sunny', 'NP JAF-5566', 'LK-1007-SUNNY', 16000, 2000, 'available', 'Economy', 5, 'Automatic', 'Petrol', 'Popular sedan for everyday Sri Lankan bookings and airport transfers.', 'assets/vehicles/nissan-sunny.png'),
('Batticaloa Honda City', 'Honda City', 'EP BAC-2233', 'LK-1008-HCITY', 21000, 2600, 'available', 'Premium', 5, 'Automatic', 'Petrol', 'Comfortable compact sedan with premium features for city events.', 'assets/vehicles/toyota-premio.png');

-- Insert Drivers
INSERT INTO drivers (name, email, phone, rating, status) VALUES
('Sunil Perera', 'sunil@fleetelite.com', '+94 77 123 4567', 4.98, 'available'),
('Kumari Silva', 'kumari@fleetelite.com', '+94 71 234 5678', 4.95, 'available'),
('Chaminda Bandara', 'chaminda@fleetelite.com', '+94 76 345 6789', 4.92, 'available');

-- Insert Event Packages
INSERT INTO event_packages (name, description, base_price, included_services, vehicle_types, status) VALUES
('Kandyan Wedding Premium', 'Premium transport for Sri Lankan weddings', 737500, '3 Sedans + Decorated Wedding Car,8-Hour Service', 'Toyota Premio,Toyota Axio', 'active'),
('Colombo Business Pro', 'Corporate logistics for Colombo events', 531000, '3 SUVs,Airport Transfer', 'Honda Vezel,Toyota Premio', 'active'),
('Galle Island Tour Elite', 'Premium coastal tour package',  944000, 'VIP Coordination,Group Van Service', 'Toyota HiAce,Honda Vezel', 'active');

-- Insert Sample Invoices
INSERT INTO invoices (invoice_number, client_name, client_email, amount, tax, total_amount, status, issue_date, due_date, description) VALUES
('INV-8842', 'Ceylon Luxury Events', 'billing@ceylonevents.lk', 1135750, 113575, 1249325, 'paid', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Kandyan wedding transport services'),
('INV-8843', 'Lanka Corporate Logistics', 'accounts@lankalogistics.lk', 506863, 50686, 557549, 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Colombo corporate transport'),
('INV-8844', 'Southern Event Rentals', 'finance@southernevents.lk', 226748, 22675, 249423, 'overdue', DATE_SUB(CURDATE(), INTERVAL 20 DAY), CURDATE(), 'Galle event transport');

-- Insert Invoice Items
INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES
(1, 'Luxury Sedan Rental', 8, 103250, 826000),
(1, 'Stretch Limousine', 2, 132750, 265500),
(1, 'Chauffeur Service', 12, 14750, 177000),
(2, 'Executive SUV Rental', 5, 88500, 442500),
(2, 'Airport Transfer Fee', 5, 12873, 64365);

-- Insert sample bookings
INSERT INTO bookings (booking_number, user_id, vehicle_id, event_type_id, event_name, event_date, start_time, end_time, total_amount, status) VALUES
('BK-9021', 1, 3, 2, 'Colombo Corporate Gala', '2024-10-24', '18:00:00', '23:30:00', 368750, 'in_progress'),
('BK-8842', 2, 1, 1, 'Kandy Royal Wedding', '2024-10-26', '14:00:00', '20:00:00', 826000, 'confirmed'),
('BK-9105', 1, 5, 2, 'BIA Airport Transfer', '2024-10-24', '06:30:00', '08:00:00', 132750, 'pending'),
('BK-7721', 2, 2, 2, 'Colombo Tech Summit Logistics', '2024-10-22', '09:00:00', '17:00:00', 944000, 'completed'),
('BK-9110', 3, 4, 3, 'Galle Beach Wedding Transport', '2024-10-20', '10:00:00', '15:00:00', 560000, 'confirmed'),
('BK-9111', 3, 2, 3, 'Island Tour VIP Service', '2024-10-26', '08:00:00', '18:00:00', 725000, 'completed'),
('BK-9112', 4, 1, 2, 'Colombo Airport Transfer', '2024-10-28', '09:30:00', '11:00:00', 132750, 'pending'),
('BK-9113', 5, 3, 1, 'Luxury Wedding Shuttle', '2024-10-29', '16:00:00', '22:00:00', 980000, 'confirmed');

SELECT 'Database setup completed successfully! (Prices in Sri Lankan Rupees - LKR)' as Status;

-- Insert default settings (Sri Lankan Rupees)
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'FleetElite Logistics (Pvt) Ltd'),
('company_email', 'info@fleetelite.com'),
('company_phone', '+94 11 234 5678'),
('company_address', 'No. 123, Galle Road, Colombo 03, Sri Lanka'),
('currency', 'LKR'),
('currency_symbol', 'LKR'),
('timezone', 'Asia/Colombo'),
('date_format', 'Y-m-d'),
('tax_rate', '10.00'),
('mon_fri_start', '08:00'),
('mon_fri_end', '18:00'),
('sat_start', '10:00'),
('sat_end', '15:00'),
('sun_status', 'closed')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

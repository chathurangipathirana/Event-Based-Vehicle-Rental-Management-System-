CREATE DATABASE IF NOT EXISTS fleetelite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fleetelite_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  model VARCHAR(150) NOT NULL,
  year YEAR DEFAULT NULL,
  capacity TINYINT UNSIGNED DEFAULT 4,
  transmission VARCHAR(50) DEFAULT 'Automatic',
  fuel_type VARCHAR(50) DEFAULT 'Gasoline',
  status VARCHAR(50) NOT NULL DEFAULT 'available',
  image_url VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  icon_class VARCHAR(50) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_number VARCHAR(50) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  vehicle_id INT UNSIGNED NOT NULL,
  event_type_id INT UNSIGNED NOT NULL,
  event_name VARCHAR(150) DEFAULT NULL,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  pickup_location VARCHAR(255) NOT NULL,
  dropoff_location VARCHAR(255) NOT NULL,
  total_hours INT UNSIGNED NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  special_requests TEXT DEFAULT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO event_types (name, slug, description, icon_class, is_active, sort_order) VALUES
  ('Wedding', 'wedding', 'Elegant luxury vehicles perfect for your special day. Make your wedding transportation unforgettable with our premium fleet.', 'wedding', 1, 1),
  ('Corporate', 'corporate', 'Professional executive vehicles for business meetings, airport transfers, and corporate events. Impress your clients and colleagues.', 'business', 1, 2),
  ('Birthday', 'birthday', 'Celebrate in style with our luxury vehicles. Perfect for birthday parties, anniversaries, and special celebrations.', 'celebration', 1, 3);

INSERT INTO vehicles (name, model, year, capacity, transmission, fuel_type, status, image_url, description, price_per_hour, price_per_day) VALUES
  ('Luxury Sedan', 'Mercedes S-Class', 2024, 4, 'Automatic', 'Gasoline', 'available', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=400&h=300&fit=crop', 'Premium luxury sedan ideal for weddings and corporate events.', 120.00, 800.00),
  ('Executive SUV', 'BMW X7', 2024, 7, 'Automatic', 'Gasoline', 'available', 'https://images.unsplash.com/photo-1549399735-cef2e2c3f638?w=400&h=300&fit=crop', 'Spacious SUV with premium comfort and styling.', 150.00, 950.00);

-- To create a first user, either register through the site or insert a hashed password manually.

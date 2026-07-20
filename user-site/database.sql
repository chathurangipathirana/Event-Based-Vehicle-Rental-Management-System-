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
  ('Kandyan Wedding', 'kandyan-wedding', 'Elegant Sri Lankan vehicles for traditional weddings and family celebrations.', 'wedding', 1, 1),
  ('Colombo Corporate', 'colombo-corporate', 'Professional vehicles for business meetings, airport transfers, and corporate events in Sri Lanka.', 'business', 1, 2),
  ('Island Tour', 'island-tour', 'Comfortable vehicles for coastal trips, hill-country travel, and special celebrations.', 'celebration', 1, 3);

INSERT INTO vehicles (name, model, year, capacity, transmission, fuel_type, status, image_url, description, price_per_hour, price_per_day) VALUES
  ('Colombo Toyota Axio', 'Toyota Corolla Axio', 2020, 4, 'Automatic', 'Petrol', 'available', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=900&q=80', 'Popular Sri Lankan sedan for weddings, business transfers, and city events.', 2400.00, 18500.00),
  ('Kandy Toyota Premio', 'Toyota Premio', 2019, 4, 'Automatic', 'Petrol', 'available', 'https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=900&q=80', 'Comfortable executive sedan commonly used for Sri Lankan wedding hires.', 2800.00, 22000.00),
  ('Galle Honda Vezel', 'Honda Vezel', 2021, 5, 'Automatic', 'Hybrid', 'available', 'https://images.unsplash.com/photo-1549399735-cef2e2c3f638?auto=format&fit=crop&w=900&q=80', 'Compact hybrid SUV suited for coastal trips and event guest transport.', 3200.00, 24500.00),
  ('Negombo Toyota HiAce', 'Toyota HiAce', 2018, 10, 'Automatic', 'Diesel', 'available', 'https://images.unsplash.com/photo-1610647752706-3bb12232b3b1?auto=format&fit=crop&w=900&q=80', 'Reliable van for airport pickups, group travel, and family events.', 3800.00, 30000.00),
  ('Matara Suzuki Wagon R', 'Suzuki Wagon R', 2022, 4, 'Automatic', 'Hybrid', 'available', 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&w=900&q=80', 'Efficient city car for compact event errands and short hires.', 1800.00, 14000.00),
  ('Jaffna Nissan Sunny', 'Nissan Sunny', 2020, 5, 'Automatic', 'Petrol', 'available', 'https://images.unsplash.com/photo-1549921296-3fb9a5c61336?auto=format&fit=crop&w=900&q=80', 'Popular sedan commonly used across Sri Lanka for airport transfers and city travel.', 2000.00, 16000.00),
  ('Batticaloa Honda City', 'Honda City', 2021, 5, 'Automatic', 'Petrol', 'available', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=900&q=80', 'Comfortable compact sedan with premium amenities for executive bookings.', 2600.00, 21000.00);

-- To create a first user, either register through the site or insert a hashed password manually.

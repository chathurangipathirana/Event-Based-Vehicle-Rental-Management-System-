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
  ('Colombo Toyota Axio', 'Toyota Corolla Axio', 2020, 4, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-axio.png', 'Popular Sri Lankan sedan for weddings, business transfers, and city events.', 2400.00, 18500.00),
  ('Kandy Toyota Premio', 'Toyota Premio', 2019, 4, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-premio.png', 'Comfortable executive sedan commonly used for Sri Lankan wedding hires.', 2800.00, 22000.00),
  ('Galle Honda Vezel', 'Honda Vezel', 2021, 5, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/honda-vezel.png', 'Compact hybrid SUV suited for coastal trips and event guest transport.', 3200.00, 24500.00),
  ('Negombo Toyota HiAce', 'Toyota HiAce', 2018, 10, 'Automatic', 'Diesel', 'available', 'assets/vehicles/toyota-hiace.png', 'Reliable van for airport pickups, group travel, and family events.', 3800.00, 30000.00),
  ('Colombo Nissan Sunny', 'Nissan Sunny', 2020, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/nissan-sunny.png', 'Compact city sedan ideal for Colombo airport transfers and corporate bookings.', 2000.00, 16000.00),
  ('Matara Suzuki Wagon R', 'Suzuki Wagon R', 2022, 4, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/suzuki-wagonr.png', 'Efficient city car for compact event errands and short hires.', 1800.00, 14000.00),
  ('Nuwara Eliya Toyota Corolla', 'Toyota Corolla', 2019, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-axio.png', 'Reliable sedan for hill country tours and wedding guest transfers.', 2100.00, 16500.00),
  ('Jaffna Toyota Axio', 'Toyota Corolla Axio', 2021, 4, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-axio.png', 'Comfortable sedan perfect for northern Sri Lankan events and guest transport.', 2350.00, 18000.00),
  ('Trincomalee Suzuki Swift', 'Suzuki Swift', 2021, 4, 'Automatic', 'Petrol', 'available', 'assets/vehicles/suzuki-wagonr.png', 'Compact hatchback ideal for coastal city routes and short event hires.', 1700.00, 13500.00),
  ('Jaffna Nissan Sunny', 'Nissan Sunny', 2020, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/nissan-sunny.png', 'Popular sedan commonly used across Sri Lanka for airport transfers and city travel.', 2000.00, 16000.00),
  ('Batticaloa Honda City', 'Honda City', 2021, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-premio.png', 'Comfortable compact sedan with premium amenities for executive bookings.', 2600.00, 21000.00);

CREATE TABLE IF NOT EXISTS event_packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  included_services TEXT DEFAULT NULL,
  vehicle_types VARCHAR(255) DEFAULT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'active',
  image_url VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO event_packages (name, description, base_price, included_services, vehicle_types, status) VALUES
('Kandyan Wedding Premium', 'Complete luxury transport package for Sri Lankan traditional Kandyan weddings with decorated bridal vehicle.', 737500, '3 Sedans + Decorated Wedding Car, 8-Hour Service, Professional Chauffeur, Red Carpet & Floral Setup', 'Toyota Premio, Toyota Axio', 'active'),
('Royal Presidential Wedding', 'Ultra-luxury presidential wedding fleet service with dedicated convoy management and VIP coordination.', 1250000, 'Luxury SUV Convoy, 12-Hour Full Service, VIP Chauffeur Team, Champagne Toast Setup, Event Logistics Coordinator', 'Honda Vezel, Toyota Premio', 'active'),
('Coastal Beach Wedding Deluxe', 'Tailored for beachside and destination weddings in Bentota, Galle, and Mirissa with guest shuttle service.', 650000, 'Decorated Convertible/Sedan, Guest Transport Van, Beach Location Logistics, Driver Accommodation', 'Toyota Axio, Toyota HiAce', 'active'),
('Colombo Business Pro', 'Corporate logistics for Colombo conferences, trade summits, and high-profile business meetings.', 531000, '3 SUVs, Airport Transfer, 24/7 Dedicated Driver, On-board Wi-Fi', 'Honda Vezel, Toyota Premio', 'active'),
('Executive VIP Fleet Shuttle', 'Premium corporate delegation transport with real-time GPS tracking and airport arrival handling.', 820000, '5 Executive Sedans, BIA Airport Meet & Greet, Full Day Standby, Multi-lingual Chauffeur', 'Toyota Premio, Honda Vezel', 'active'),
('Diplomatic Summit Transport', 'State-level and diplomatic conference transport package designed for high-security international envoys.', 1450000, 'Armored/Luxury SUV Fleet, Police Escort Coordination, 24/7 Security Dispatch, Priority Fuel Pass', 'Honda Vezel, Toyota HiAce', 'active'),
('Galle Island Tour Elite', 'Premium coastal tour package covering Southern Expressway, Galle Fort, and luxury beach resorts.', 944000, 'VIP Coordination, Group Van Service, English Speaking Guide, Fuel & Toll Charges Included', 'Toyota HiAce, Honda Vezel', 'active'),
('Hill Country Scenic Expedition', 'Multi-day hill country travel bundle with experienced mountain terrain chauffeurs and itinerary assistance.', 680000, 'Luxury Van/SUV, Kandy & Nuwara Eliya Circuit, Tea Estate Access, All-Weather Driver', 'Toyota HiAce, Toyota Premio', 'active'),
('Cultural Triangle Grand Tour', 'Comprehensive heritage tour transport covering Sri Lanka\'s ancient cities with round-the-clock support.', 890000, 'Group Transport Bus/Van, Sigiriya & Polonnaruwa Excursion, Hotel Transfers, Unlimited Mileage', 'Toyota HiAce, Nissan Sunny', 'active'),
('Gala & Red Carpet Night', 'Glamorous transportation for award ceremonies, gala dinners, and high-profile red carpet events.', 480000, 'Executive Sedan Escort, Red Carpet Drop-off, Hourly Standby Service, Uniformed Driver', 'Toyota Premio, Suzuki WagonR', 'active'),
('VIP Party Shuttle Express', 'Safe, stylish, and synchronized night event transport for private parties, concerts, and nightlife outings.', 390000, 'Luxury Passenger Van, Multi-stop Nightclub & Concert Transfer, Safe Night Chauffeur, Refreshments', 'Toyota HiAce, Honda Vezel', 'active'),
('Milestone Celebration Package', 'Specialized transport bundle for milestone birthdays, anniversaries, graduation galas, and family reunions.', 550000, 'Decorated Luxury Vehicle, Photo Shoot Transport, Complimentary Refreshment Basket, Flexible Timings', 'Toyota Axio, Honda Vezel', 'active');

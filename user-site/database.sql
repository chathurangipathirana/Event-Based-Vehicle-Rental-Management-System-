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

CREATE TABLE IF NOT EXISTS password_reset_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_reset_email (email),
  INDEX idx_password_reset_expiry (expires_at)
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
  category VARCHAR(50) DEFAULT 'Uncategorized',
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
  user_id INT UNSIGNED DEFAULT NULL,
  vehicle_id INT UNSIGNED NOT NULL,
  driver_id INT UNSIGNED DEFAULT NULL,
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
  ('Colombo Toyota Premio F-Ex', 'Toyota Premio', 2021, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-premio.png', 'Luxury Sri Lankan executive sedan for weddings, VIP arrivals, and corporate transport in Colombo.', 2800.00, 22000.00),
  ('Kandy Toyota Axio Hybrid', 'Toyota Corolla Axio', 2020, 5, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/toyota-axio.png', 'Popular, comfortable Sri Lankan sedan for city events, wedding escorts, and airport transfers.', 2400.00, 18500.00),
  ('Galle Honda Vezel RS', 'Honda Vezel RS', 2021, 5, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/honda-vezel.png', 'Stylish hybrid SUV ideal for southern beach weddings, luxury island tours, and corporate delegations.', 3200.00, 24500.00),
  ('Negombo Toyota HiAce KDH Super GL', 'Toyota HiAce KDH 200', 2019, 10, 'Automatic', 'Diesel', 'available', 'assets/vehicles/toyota-hiace.png', 'Premium high-roof Sri Lankan passenger van for airport pickups, group tours, and wedding guest shuttles.', 3800.00, 30000.00),
  ('Colombo Nissan Sunny Super Saloon', 'Nissan Sunny N17', 2020, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/nissan-sunny.png', 'Reliable Sri Lankan sedan for Colombo city business meetings, airport runs, and daily hires.', 2000.00, 16000.00),
  ('Matara Suzuki Wagon R Stingray', 'Suzuki Wagon R Stingray', 2022, 4, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/suzuki-wagonr.png', 'Highly fuel-efficient Sri Lankan city hatchback for short event hires and coastal errands.', 1800.00, 14000.00),
  ('Bentota Toyota Premio Luxury Bridal Edition', 'Toyota Premio G Superior', 2022, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-premio.png', 'Decorated luxury Sri Lankan wedding car with professional uniformed chauffeur service.', 3500.00, 26000.00),
  ('Nuwara Eliya Honda Vezel Z Sensing', 'Honda Vezel Hybrid', 2021, 5, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/honda-vezel.png', 'Smooth, powerful hybrid SUV for Sri Lankan hill-country expeditions and Nuwara Eliya tours.', 3300.00, 25000.00),
  ('Jaffna Toyota Axio EX', 'Toyota Corolla Axio', 2021, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/toyota-axio.png', 'Spacious, reliable Sri Lankan sedan for northern peninsula travel and event guest transport.', 2350.00, 18000.00),
  ('Trincomalee Suzuki Swift RS', 'Suzuki Swift Turbo', 2021, 5, 'Automatic', 'Petrol', 'available', 'assets/vehicles/suzuki-wagonr.png', 'Agile hatchback ideal for eastern coast trips, city tours, and fast event errands.', 1900.00, 15000.00),
  ('Kurunegala Toyota HiAce VIP Commuter', 'Toyota HiAce KDH 222', 2020, 12, 'Automatic', 'Diesel', 'available', 'assets/vehicles/toyota-hiace.png', 'Large luxury van for Sri Lankan wedding entourages, corporate delegates, and family trips.', 4200.00, 32000.00),
  ('Colombo Toyota Corolla Grace Hybrid', 'Honda Grace / Toyota Corolla', 2021, 5, 'Automatic', 'Hybrid', 'available', 'assets/vehicles/toyota-axio.png', 'Premium hybrid sedan with leather interior for diplomatic and corporate transfers.', 2600.00, 20000.00);

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

# VehicleRental - FleetElite Vehicle Rental System

A modern PHP-based vehicle rental application for managing high-profile event transportation with a professional dashboard and booking system.

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Project Structure](#project-structure)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Building CSS](#building-css)
- [First-Time Setup](#first-time-setup)

## Requirements

- **XAMPP** (or Apache + PHP 7.4+ + MySQL 5.7+)
- **PHP** with PDO and PDO MySQL extensions enabled
- **MySQL** database server
- **Node.js** and **npm** (for Tailwind CSS compilation)
- Modern web browser

## Installation

### Step 1: Clone or Copy Project
Copy the project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\VehicleRental
```

### Step 2: Install Dependencies
Open a terminal/PowerShell in the project root and install npm dependencies:
```bash
npm install
```

### Step 3: Build Tailwind CSS
Compile the Tailwind CSS stylesheet for production:
```bash
npm run build:css
```

This generates `public/assets/css/styles.css` from `src/styles.css`.

### Step 4: Set Up Database
1. Start Apache and MySQL in XAMPP
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Import `database.sql`:
   - Click **Import** tab
   - Select the `database.sql` file from the project root
   - Click **Go**

**Alternative (if import fails):**
1. Create database manually:
   ```sql
   CREATE DATABASE fleetelite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. In phpMyAdmin, select the new database and use the **SQL** tab to run `database.sql`

## Running the Application

### Option 1: Via Root Redirect (Recommended)
```
http://localhost/VehicleRental/
```
This automatically redirects to `public/index.php`

### Option 2: Direct Public Folder Access
```
http://localhost/VehicleRental/public/
```

## Project Structure

```
VehicleRental/
├── public/                              # Web-accessible directory
│   ├── index.php                        # Home page
│   ├── login.php                        # User login
│   ├── register.php                     # New user registration
│   ├── dashboard.php                    # User dashboard
│   ├── vehicles.php                     # Vehicle gallery & search
│   ├── vehicle-details.php              # Single vehicle details
│   ├── booking-process.php              # Multi-step booking (3 steps)
│   ├── booking-success.php              # Booking confirmation
│   ├── my-bookings.php                  # User's booking history
│   ├── logout.php                       # Logout handler
│   └── assets/
│       ├── css/
│       │   └── styles.css               # Compiled Tailwind CSS
│       └── images/                      # Vehicle images directory
│
├── includes/                            # Shared PHP components
│   ├── header.php                       # HTML head, navigation, styling
│   ├── footer.php                       # Footer component
│   └── auth.php                         # Authentication functions
│
├── config/                              # Configuration
│   └── database.php                     # Database connection & helper functions
│
├── src/                                 # Source files
│   └── styles.css                       # Tailwind CSS source (input)
│
├── Build & Config Files
│   ├── package.json                     # npm dependencies & scripts
│   ├── tailwind.config.js               # Tailwind CSS configuration
│   ├── postcss.config.js                # PostCSS configuration
│   ├── database.sql                     # Database schema & sample data
│   ├── .gitignore                       # Git ignore file
│   ├── index.php                        # Root redirect to public/
│   └── README.md                        # This file
│
└── node_modules/                        # npm packages (auto-generated)
```

## Database Setup

### Default Connection Settings
Configure in `config/database.php`:
- **Host:** localhost
- **Database:** fleetelite_db
- **Username:** root
- **Password:** (empty)

**If your MySQL credentials are different:**
Edit `config/database.php` and update the connection details.

### Database Tables
- `users` - User accounts (customers)
- `vehicles` - Available rental vehicles
- `event_types` - Event categories (Wedding, Corporate, Birthday)
- `bookings` - Booking records with rental details

## Configuration

### Database Connection
Edit `config/database.php` if needed:
```php
$host = 'localhost';
$dbname = 'fleetelite_db';
$username = 'root';
$password = '';
```

### Tailwind CSS
To modify styles, edit `src/styles.css` and rebuild:
```bash
npm run build:css
```

The compiled output is saved to `public/assets/css/styles.css`.

## Building CSS

### Development
Whenever you modify `src/styles.css`, rebuild the CSS:
```bash
npm run build:css
```

### Production
The build process automatically minifies the CSS. The generated `public/assets/css/styles.css` is production-ready.

### Troubleshooting CSS Build
If you see warnings about outdated browserslist, run:
```bash
npx update-browserslist-db@latest
```

## First-Time Setup

### Create Your First Account
1. Navigate to `http://localhost/VehicleRental/public/register.php`
2. Fill in your details:
   - Full Name
   - Email Address
   - Password (min. 6 characters)
   - Phone (optional)
   - Company Name (optional)
3. Click **Create Account**
4. You'll be redirected to login after 2 seconds
5. Log in with your email and password

### Browse Vehicles
1. Log in to your account
2. Go to **Vehicles** page to view the rental fleet
3. Filter by event type, vehicle type, or search by name
4. Click **View Details** on any vehicle

### Make a Booking
1. Select a vehicle and click **View Details**
2. Follow the 3-step booking process:
   - **Step 1:** Select event type, date, time, pickup/dropoff locations
   - **Step 2:** Add optional extras (driver, decorations, extra hours)
   - **Step 3:** Review and confirm your booking
3. Your booking confirmation appears with booking number

### View Bookings
1. Log in and go to **My Bookings**
2. See all past and upcoming bookings
3. Cancel pending bookings if needed

## Features

- **User Authentication:** Secure registration and login with session management
- **Vehicle Gallery:** Browse vehicles with advanced filtering by event type and vehicle type
- **Detailed Vehicle Info:** View specifications, pricing, and availability
- **Multi-Step Booking:** Intuitive 3-step booking process with confirmation
- **Booking Management:** View, track, and cancel bookings
- **User Dashboard:** Centralized portal for account management
- **Responsive Design:** Mobile-friendly interface using Tailwind CSS
- **Event-Type Filtering:** Organize vehicles for different event needs (Wedding, Corporate, Birthday)

## Troubleshooting

### "Cannot access http://localhost/VehicleRental"
- Ensure XAMPP Apache is running
- Verify the project folder is in `C:\xampp\htdocs\VehicleRental`
- Restart Apache

### "Database connection failed"
- Check MySQL is running in XAMPP
- Verify credentials in `config/database.php`
- Ensure `fleetelite_db` database exists in phpMyAdmin

### "Stylesheet not loading (missing styles)"
- Run `npm install` to install Tailwind dependencies
- Run `npm run build:css` to generate CSS
- Verify `public/assets/css/styles.css` exists

### "Can't log in"
- Verify your account was created successfully
- Check database has users table with correct data
- Clear browser cookies and try again

### "Booking not saving"
- Verify all form fields are filled
- Check browser console for JavaScript errors
- Confirm database connection is working

## Support

For issues or questions:
1. Check the **Troubleshooting** section above
2. Verify all setup steps were completed
3. Review browser console for error messages
4. Check `config/database.php` settings

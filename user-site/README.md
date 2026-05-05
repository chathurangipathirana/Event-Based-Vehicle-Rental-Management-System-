# VehicleRental Local Setup

## Requirements
- XAMPP or similar local PHP/MySQL environment
- PHP with PDO and PDO MySQL enabled
- Browser pointing to `http://localhost/VehicleRental`

## Local setup steps
1. Copy this project folder into your XAMPP `htdocs` folder.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Import `database.sql` to create the `fleetelite_db` database and initial data.
5. If `phpMyAdmin` import fails, create the database manually and then run the SQL script.
6. Access the app via `http://localhost/VehicleRental`.

## Default database connection
The project connects using `config/database.php`:
- host: `localhost`
- dbname: `fleetelite_db`
- username: `root`
- password: empty

If your local MySQL uses different credentials, update `config/database.php` accordingly.

## Notes
- The `booking.php` route was added so booking links in the UI work correctly.
- Use the registration form to create your first user account.
- If you want a test account, insert a bcrypt-hashed password into the `users` table manually.

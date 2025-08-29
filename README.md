# Mobile Todo (PHP + MySQL)

A mobile-first, white/blue themed team todo app with bottom nav and simple CRUD.

## Requirements
- PHP 8+
- MySQL 5.7+/MariaDB 10.4+
- XAMPP or Laragon

## Setup (XAMPP on Windows)
1. Copy the `mobile_todo_app` folder to: `C:/xampp/htdocs/mobile_todo_app`.
2. Create database and tables:
   - Open `http://localhost/phpmyadmin`.
   - Create DB: `mobile_todo` (utf8mb4).
   - Import `sql/schema.sql`.
3. Configure DB credentials:
   - Copy `config/config.sample.php` to `config/config.php`.
   - Edit db user/password if needed.
4. Visit the app:
   - Go to `http://localhost/mobile_todo_app/login.php`.

## Setup (Laragon)
1. Copy the folder to `C:/laragon/www/mobile_todo_app`.
2. Start Apache & MySQL.
3. Create DB `mobile_todo` and import `sql/schema.sql` via HeidiSQL or phpMyAdmin.
4. Copy `config/config.sample.php` → `config/config.php` and adjust.
5. Open `http://mobile_todo_app.test/login.php` (or `http://localhost/mobile_todo_app/login.php`).

## Multi-user (same network)
- Ensure Apache is accessible on LAN:
  - Find your PC LAN IP (e.g., `192.168.1.10`).
  - Other devices on the same Wi‑Fi can visit `http://192.168.1.10/mobile_todo_app/login.php`.
- On Windows Firewall, allow Apache (httpd.exe) for Private networks.
- Optional: set up a VirtualHost (Laragon auto virtual hosts) and share the `.test` domain via hosts override, but IP is simpler.

## Default Flow
- Register users.
- Create a team in **Team**.
- Add members by their email (they need to register first).
- Create categories (optional) and tasks in **Tasks**.
- Assign tasks, set priorities (high/medium/low), due dates, and mark complete.

## Notes
- This is a simple educational scaffold using PDO prepared statements.
- For production, consider CSRF protection, role checks beyond membership, and email invites.


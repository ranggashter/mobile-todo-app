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


# Mobile Todo (PHP + MySQL)
aplikasi todo list sederhana, tampilan mobile (putih + biru), bisa dipake bareng tim, ada bottom nav, dan CRUD basic.

## yang dibutuhin
- PHP 8+
- MySQL 5.7+ / MariaDB 10.4+
- XAMPP atau Laragon

## cara install (pakai XAMPP di Windows)

1. copy folder mobile_todo_app ke: C:/xampp/htdocs/mobile_todo_app.
2. bikin database & tabel:
3. buka http://localhost/phpmyadmin.

bikin database baru: mobile_todo (pakai utf8mb4).

import file sql/schema.sql.

setting koneksi database:

copy config/config.sample.php → jadi config/config.php.

edit username/password db kalau perlu.

jalanin aplikasinya:

buka http://localhost/mobile_todo_app/login.php.

cara install (pakai Laragon)

copy folder ke: C:/laragon/www/mobile_todo_app.

nyalain Apache & MySQL.

bikin database mobile_todo, import sql/schema.sql lewat HeidiSQL / phpMyAdmin.

copy config/config.sample.php → config/config.php, terus edit sesuai kebutuhan.

buka http://mobile_todo_app.test/login.php (atau http://localhost/mobile_todo_app/login.php).

biar bisa dipake rame-rame (satu jaringan)

pastiin Apache bisa diakses lewat LAN:

cek alamat IP laptop/PC kamu di jaringan (misal: 192.168.1.10).

hp/pc lain di wifi yang sama bisa akses ke http://192.168.1.10/mobile_todo_app/login.php.

kalau Windows firewall ngeblokir, izinin Apache (httpd.exe) buat jaringan Private.

kalau mau lebih rapih, bisa bikin VirtualHost (Laragon biasanya otomatis bikin .test domain), tapi cara paling gampang ya pake IP aja.

alur aplikasi

daftar akun user dulu.

bikin Team.

tambah member pake email mereka (syarat: mereka udah daftar juga).

bikin kategori (opsional) & bikin task di menu Tasks.

task bisa di-assign ke member, kasih prioritas (tinggi / sedang / rendah), deadline, terus ditandai selesai kalau udah beres.

catatan

ini cuma project belajar, pake PDO & prepared statement biar aman dari SQL injection.

kalau buat produksi beneran, perlu ditambahin:

proteksi CSRF,

role/akses user yang lebih jelas,

sistem invite lewat email.
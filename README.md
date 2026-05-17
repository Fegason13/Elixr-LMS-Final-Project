<<<<<<< HEAD
# Elixr LMS (PHP + MySQL)

A simple Learning Management System similar to Google Classroom, built with **basic PHP** (no frameworks) and **MySQL**.

## Features

- **Register / Login** as Teacher or Student
- **Teachers** create classes with unique join codes
- **Students** join classes using the code
- **Class stream** with announcements and assignments
- **Students** submit written work and file attachments
- **Teachers** view submissions and assign grades with feedback
- **Student roster** per class for teachers

## Requirements

- PHP 7.4+ (with mysqli extension)
- MySQL or MariaDB
- XAMPP, WAMP, Laragon, or similar (recommended on Windows)

## Installation

### 1. Copy project

Place this folder in your web server directory, for example:

- XAMPP: `C:\xampp\htdocs\php-lms`
- WAMP: `C:\wamp64\www\php-lms`

### 2. Create the database

1. Start Apache and MySQL in XAMPP/WAMP.
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Import or run the SQL file: `database/schema.sql`

Or from command line:

```bash
mysql -u root -p < database/schema.sql
```

### 3. Configure database

Edit `pages/config.php` if your MySQL username/password differs:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'classroom_lms');
```

### 4. Run the app

Open in browser:

```
http://localhost/php-lms/
```

### 5. Create accounts

Use **Register** to create a teacher and student account, or use demo accounts from `schema.sql` (password: `password` — see note in SQL file).

## Project structure

```
php-lms/
├── assets/css/style.css   # Styles
├── database/schema.sql    # Database tables
├── pages/
│   ├── config.php         # App & DB settings
│   ├── bootstrap.php
│   ├── includes/
│   │   ├── header.php
│   │   └── footer.php
│   ├── register.php
│   ├── dashboard.php      # Class list
│   ├── create_class.php   # Teacher only
│   ├── join_class.php     # Student only
│   ├── class.php          # Class stream
│   ├── assignment.php     # Submit / grade
│   ├── download.php
│   └── logout.php
├── uploads/               # Student file uploads
├── index.php              # Login
├── composer.json
└── src/                   # Application classes
```

## How to use (quick guide)

### Teacher

1. Register as **Teacher**
2. **Create Class** → share the **join code** with students
3. Open a class → post **announcements** or **assignments**
4. Click an assignment → view submissions and **grade** students

### Student

1. Register as **Student**
2. **Join Class** with the teacher's code
3. Open a class → click an assignment → **Turn in** work

## Security note

This project uses basic PHP for learning purposes. For production, use prepared statements (PDO/MySQLi), CSRF tokens, stricter validation, and HTTPS.


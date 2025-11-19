# Three X Quote Form

This project is a multilingual (English / Arabic) landing page with a PHP backend that stores quote requests in MySQL. The instructions below target a typical WampServer installation on Windows.

## Requirements
- WampServer 3.3 (or newer) with Apache + MySQL + PHP 8.0+
- MySQL account that can create databases/tables (the default `root` user on WAMP works)

## Setup
1. Copy this folder into `C:\wamp64\www\three_x` (or any folder under `www`) and make sure WampServer is running (green tray icon).
2. Edit `config.php` if you use non-default credentials. The default assumes:
   - host: `localhost`
   - username: `root`
   - password: *(empty)*
   - database: `three_x`
3. Visit `http://localhost/three_x/index.html` (or `index-ar.html` for Arabic) from a browser served by Apache, **not** via `file://`.

The first form submission will automatically:
- Create the `three_x` database (if it does not exist).
- Create the `quote_requests` table with UTF-8 encoding.

You can also create the table manually if you prefer:

```sql
CREATE DATABASE IF NOT EXISTS three_x CHARACTER SET utf8mb4;
USE three_x;

CREATE TABLE IF NOT EXISTS quote_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    area VARCHAR(255) NOT NULL,
    service VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Quick Test
Submit the HTML form or run:

```powershell
curl.exe -X POST "http://localhost/three_x/submit_quote.php" `
  -F "language=en" `
  -F "name=John Doe" `
  -F "phone=+1555123456" `
  -F "area=Riyadh" `
  -F "service=Design" `
  -F "message=Please contact me about a new project."
```

A JSON response of `{"success":true,...}` indicates that the backend, database connection, and table creation all succeeded.

## Troubleshooting
- Ensure Apache, MySQL, and PHP services in WampServer are green.
- phpMyAdmin (`http://localhost/phpmyadmin`) lets you inspect the `quote_requests` table.
- Check `C:\wamp64\logs\apache_error.log` if you receive a `500` response.
"# companywebsite" 

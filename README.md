# Appliances Inventory — POS System

A full-stack PHP + MySQL POS-style inventory management system for appliances.

## Tech Stack
- **Backend:** PHP 8+, MySQLi
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, Vanilla JS
- **Storage:** MySQL database + local file uploads

## File Structure
```
appliances_inventory/
├── config.php          ← DB credentials (edit this)
├── index.php           ← Main POS interface
├── products.php        ← Product management (CRUD)
├── schema.sql          ← Database setup
├── assets/
│   ├── css/style.css   ← All styles
│   └── js/app.js       ← Cart logic & filters
└── uploads/            ← Uploaded product images
```

## Setup

### 1. Configure database
Edit `config.php` and set your MySQL credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');         // your password
define('DB_NAME', 'appliances_inventory');
```

### 2. Import the schema
```bash
mysql -u root -p < schema.sql
```
Or paste the contents of `schema.sql` into phpMyAdmin.

### 3. Set uploads permissions
```bash
chmod 755 uploads/
```

### 4. Run with PHP
```bash
cd appliances_inventory
php -S localhost:8000
```
Then visit: http://localhost:8000

Or place the folder in your XAMPP/WAMP `htdocs` and visit:
http://localhost/appliances_inventory/

## Features
- **POS Interface** — product grid with category pills + live search
- **Shopping Cart** — add/remove items, qty controls, no page reload
- **VAT Toggle** — optional 12% VAT calculation
- **Payment Flow** — cash input, change calculation, receipt modal, print
- **Product Management** — add, edit, delete with image upload
- **Responsive** — works on tablet and mobile

## Categories
Air Conditioner · Dishwasher · Microwave · Oven · Refrigerator · Washer

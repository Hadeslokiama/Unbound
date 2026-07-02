# SoleStride 

SoleStride is a dedicated footwear e-commerce platform built natively using **PHP** and **MySQL**. This project fulfills an academic requirement, demonstrating robust backend form validation, database management, user authentication, and secure session handling without the use of monolithic PHP frameworks. 

The application is split into two core modules: a **Buyer Storefront** (complete with registration, email confirmation, categorized shopping, and checkout) and a secure **Seller/Admin Dashboard** (featuring inventory controls, role management, and system audit logs).

---

## Academic Disclaimer
> **Notice:** This website is for educational purposes only and serves as a requirement for a final project. A disclaimer stating this is embedded into the footer of every webpage across the site, alongside our official group name and logo.

---

## Features

### Buyer Part (Storefront)
* **User Registration & Validation:** Comprehensive sign-up process capturing complete name, valid email, password (with confirmation validation), complete address, and contact numbers.
* **Email Confirmation:** Automation system that dispatches a confirmation email to the user upon registration to verify their account.
* **Categorized Store Page:** Clean, intuitive catalog browsing specifically tailored for different footwear categories (e.g., Sneakers, Boots, Formal, Athletic) with seamless "Add to Cart" actions.
* **Shopping Cart & Checkout:** Persistent cart management tracking selected items, quantities, and a multi-step checkout/mock-payment pipeline (no live payment APIs integrated).
* **About Page:** Dedicated space showcasing our company vision, mission, and the contributing group members.

### Seller Part (Admin Dashboard)
* **System Admin Controls:** A secure portal allowing main administrators to add, modify, and manage user roles authorized to perform admin actions.
* **Stock & Price Management:** Dynamic CRUD operations for footwear inventory, enabling admins to easily restock items, add new products, or adjust pricing.
* **Inventory Reports:** Real-time visibility into stock levels, highlighting low-stock items or remaining quantities.
* **System Audit Log:** A security-focused ledger tracking all actions and operational activities performed by the currently authenticated admin user.

---

## Tech Stack & Constraints

* **Backend:** Native PHP (Strictly no frameworks like Laravel or CodeIgniter)
* **Database:** MySQL
* **Frontend:** Custom CSS / Tailwind CSS (Responsive and modern UI)
* **Hosting:** Fully hosted online via free/academic cloud hosting (InfinityFree / AwardSpace)

---

## Database Schema Overview

The MySQL database consists of the following foundational tables managed entirely through standard SQL queries:
* `users` - Stores buyer credentials, addresses, verification status, and contact details.
* `admins` - Manages administrative accounts and role permissions.
* `products` - Houses footwear items, images, descriptions, pricing, and category keys.
* `cart` / `order_items` - Manages session-based or account-tied buyer selections.
* `audit_logs` - Records timestamps, admin IDs, and specific actions performed inside the dashboard.

---

## Installation & Local Setup

### Prerequisites
* PHP (v8.0 or higher recommended)
* MySQL Server
* Apache Server (XAMPP / MAMP / WampServer)
* Composer (Optional, if using PHPMailer for the email confirmation requirement)

### Steps
1. **Clone the repository:**
   ```bash
   git clone [https://github.com/Hadeslokiama/SoleStride.git]
   cd SoleStride

2. **Database Setup:**
   * Open phpMyAdmin or your preferred MySQL client.
   * Create a new database named `solestride_db`.
   * Import the provided SQL dump file found in the `/database` directory:
     ```bash
     # Or import via phpMyAdmin GUI
     source database/schema.sql;
     ```

3. **Configure Environment:**
   * Open your database connection file (e.g., `config/db.php` or `includes/connection.php`).
   * Update the credentials to match your local or hosted environment:
     ```php
     <?php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $dbname = "solestride_db";

     $conn = mysqli_connect($host, $user, $pass, $dbname);

     if (!$conn) {
         die("Connection failed: " . mysqli_connect_error());
     }
     ?>
     ```

4. **Run the Project Locally:**
   * Move the project folder into your local server's root directory (e.g., `htdocs` for XAMPP, `www` for WampServer).
   * Start your Apache and MySQL modules via your control panel.
   * Navigate to `http://localhost/SoleStride` in your web browser.

---

## Security & Data Validation Implementation

As per the academic requirements, this project strictly uses native PHP functions to handle security without external frameworks:
* **Form Validation:** Server-side checks using `filter_var()` for email verification, and regex for contact numbers.
* **SQL Injection Prevention:** Implementation of Prepared Statements (`mysqli_prepare` or PDO) for all database operations.
* **Password Hashing:** Utilizing PHP's native `password_hash()` and `password_verify()` for secure credentials storage.
* **Session Management:** Secure admin and buyer sessions to prevent unauthorized page access (e.g., protecting the admin dashboard from unauthenticated users).

---

## Group Members 

* **Daradar, Colin James (Hadeslokiama)** 
* **Cruz, Aryanne Chelsea (seaariii)**
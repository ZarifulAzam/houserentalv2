 House Rental System – Complete Technical Documentation


## 1. Project Overview

A comprehensive **web-based house rental management system** built using **PHP, MySQL**, and modern **security practices**.
The system supports **Admin, Owner, and Tenant roles**, enabling house listings, rental requests, and system administration.

---

## 2. Project Structure

### Directory Layout

```plaintext
house_rental/
├── config/           # Core configuration files
│   ├── auth.php      # Authentication utilities
│   ├── db.php        # Database connection
│   ├── security.php  # Security utilities
│   └── session.php   # Session management
├── modules/          # Role-specific features
│   ├── admin/        # Admin functionality
│   ├── owner/        # Property owner features
│   └── tenant/       # Tenant features
├── includes/         # Shared components
│   ├── header.php    # Common header
│   └── footer.php    # Common footer
├── assets/           # Static resources
│   ├── css/          # Stylesheets
│   └── js/           # JavaScript files
└── logs/             # System logs
```

---

## 3. Configuration Files

### 3.1 Database Configuration (`db.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'houserentalv2DB');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
```

* Establishes connection
* Uses UTF-8 encoding
* Handles connection errors

---

### 3.2 Session Management (`session.php`)

```php
if (session_status() === PHP_SESSION_NONE) {
    $secure = false;    
    $httponly = true;   
    $samesite = 'Lax';  
    session_start();
}
```

* Secure session handling
* CSRF protection via `SameSite`
* Prevents JavaScript access to cookies

---

### 3.3 Authentication (`auth.php`)

```php
class Auth {
    public static function login($user_data) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user_data;
    }
    
    public static function redirectUser($role) {
        $routes = [
            'admin' => 'modules/admin/dashboard.php',
            'owner' => 'modules/owner/manage.php',
            'tenant' => 'modules/tenant/browse.php'
        ];
    }
}
```

* Login session handling
* Role-based redirection
* Rate limiting & security

---

### 3.4 Security Utilities (`security.php`)

```php
class SecurityUtils {
    public static function validateCSRFToken($token) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
}
```

Features:

* CSRF protection
* Input validation
* Password hashing (Argon2id)
* Rate limiting
* Security logging

---

## 4. Database Schema

### 4.1 Tables

#### Users

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(120),
    email VARCHAR(160) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    role ENUM('tenant','owner','admin')
);
```

#### House Information

```sql
CREATE TABLE house_information (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT,
    title VARCHAR(160),
    location TEXT,
    facilities TEXT,
    rent_price DECIMAL(10,2),
    status ENUM('available','rented'),
    FOREIGN KEY (owner_id) REFERENCES users(id)
);
```

#### Rental Requests

```sql
CREATE TABLE rental_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    house_id INT,
    user_id INT,
    status ENUM('pending','approved','rejected'),
    created_at DATETIME,
    FOREIGN KEY (house_id) REFERENCES house_information(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

📌 Relationships:

* **Users → Houses** (1\:N)
* **Houses → Requests** (1\:N)
* **Users → Requests** (1\:N)

---

## 5. Modules

### 5.1 Tenant Module

* **Browse Houses (browse.php)**

  ```php
  SELECT h.*, u.name as owner_name 
  FROM house_information h 
  LEFT JOIN users u ON u.id = h.owner_id 
  WHERE h.status = "available";
  ```

  * Search & filter by location, price, status

* **Request System (request.php)**

  * Submit rental request
  * Validate ownership (`cannot rent own house`)
  * Track request status

* **My Requests (my\_requests.php)**

  * View history of submitted requests
  * Status updates: pending/approved/rejected

---

### 5.2 Owner Module

* **Property Management (manage.php)**

  * CRUD operations for houses
  * Toggle availability
  * Manage images & facilities

* **Request Handling (requests.php)**

  * View incoming requests
  * Approve/reject requests
  * Update tenant status

---

### 5.3 Admin Module

* **Dashboard (dashboard.php)**

  ```php
  $stats = [
      'users' => $mysqli->query('SELECT COUNT(*) FROM users'),
      'houses' => $mysqli->query('SELECT COUNT(*) FROM house_information')
  ];
  ```

  * System statistics
  * Overview panels
  * Quick actions

* **User Management (users.php)**

  * Role management
  * Account activation/deactivation
  * Activity monitoring

---

## 6. Security Features

* **Password Hashing:** Argon2id
* **CSRF Protection:** Session tokens in forms
* **Rate Limiting:** Blocks excessive login attempts
* **Input Validation:** Email, phone, name, etc.
* **Session Security:** Secure cookie params + fixation prevention
* **XSS Prevention:** `htmlspecialchars()` and sanitization

---

## 7. Error Handling

* **Error Display**

```php
<?php if ($error): ?>
  <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
```

* **Success Messages**

```php
<?php if ($success): ?>
  <div class="flash success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
```

* Database errors logged in `/logs/`

---

## 8. Frontend & UI

* **Header (header.php)**

  * Role-based navigation
  * Login/logout links

* **Footer (footer.php)**

  * Common scripts
  * Copyright

* **Assets**

  ```plaintext
  assets/
  ├── css/style.css
  ├── css/landing.css
  └── js/script.js
  ```

---

## 9. Best Practices

1. **Security**

   * Use Argon2id hashing
   * Validate all inputs
   * CSRF protection
   * Rate limiting
   * Prevent XSS

2. **Code Organization**

   * Modular structure
   * Separation of concerns
   * Clear naming conventions

3. **Error Handling**

   * Graceful UI messages
   * Centralized logging

4. **Performance**

   * Prepared statements
   * Session optimization
   * Efficient queries

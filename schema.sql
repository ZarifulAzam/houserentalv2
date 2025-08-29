-- Minimal schema for the mini House Rental project
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('tenant','owner','admin') NOT NULL DEFAULT 'tenant'
);

CREATE TABLE IF NOT EXISTS house_information (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  title VARCHAR(160) NOT NULL,
  location VARCHAR(160) NOT NULL,
  facilities TEXT,
  rent_price INT NOT NULL DEFAULT 0,
  status ENUM('available','rented') NOT NULL DEFAULT 'available',
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rental_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (house_id) REFERENCES house_information(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed admin and owner
INSERT INTO users(name,email,password,role) VALUES
('Admin','admin@example.com', 'admin@123', 'admin')
ON DUPLICATE KEY UPDATE name=VALUES(name);
-- Password hash corresponds to: Admin@123



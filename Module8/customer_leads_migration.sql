-- Customer Leads Migration
-- Run this script to create the necessary tables and relationships for Customer Leads management

-- Create users table if not exists
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  role ENUM('admin','sales','support') DEFAULT 'sales',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample users for testing
INSERT IGNORE INTO users (name, email, role) VALUES
('Nathaniel Himarangan', 'natnatmo@gmail.com', 'sales'),
('Kyle Cabal', 'kylecabal@gmail.com', 'support'),
('Mark Anthony Dano', 'danyomark@gmail.com', 'admin'),
('Renoel Jersean Ocampo', 'bossrenoelmapagmahal@gmail.com', 'sales'),
('Kyle Raven Mabignay', 'mabignay@example.com', 'support');


-- Create customer_leads table
CREATE TABLE IF NOT EXISTS customer_leads (
  lead_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(100) NOT NULL,
  company_name VARCHAR(100) NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contact_number VARCHAR(20) NOT NULL,
  lead_source ENUM('Website', 'Referral', 'Email', 'Call', 'Walk-in', 'Other') DEFAULT 'Website',
  interest_level ENUM('Hot', 'Warm', 'Cold') DEFAULT 'Warm',
  status ENUM('New', 'Contacted', 'Qualified', 'Converted', 'Lost') DEFAULT 'New',
  assigned_to INT NOT NULL,
  remarks TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

-- Add lead_id to sales_orders for tracking conversions
ALTER TABLE sales_orders ADD COLUMN IF NOT EXISTS lead_id INT NULL;
ALTER TABLE sales_orders ADD CONSTRAINT IF NOT EXISTS fk_sales_orders_lead_id FOREIGN KEY (lead_id) REFERENCES customer_leads(lead_id) ON DELETE SET NULL;

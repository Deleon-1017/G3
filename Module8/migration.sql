-- Module8 migration: Sales & Support
-- Run this file in MySQL to create Module8 tables
--
-- Compatibility notes / pre-conditions
-- 1) This file uses CREATE TABLE IF NOT EXISTS to avoid errors when tables already exist.
-- 2) It does not create the foreign key from sales_order_items.location_id -> locations.id
--    because some environments may not have a `locations` table yet. After importing this
--    file, you can add the FK using the supplied helper script `Module8/run_add_fk.php`
--    or by executing `Module8/add_fk.sql` in phpMyAdmin.
-- 3) If you need to create indexes or FKs conditionally during import, you can run the
--    example conditional SQL shown below (commented) using the SQL tab in phpMyAdmin.
--
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(50) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  credit_limit DECIMAL(12,2) DEFAULT 10000,
  outstanding_balance DECIMAL(12,2) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Locations table (added to make this migration self-contained)
-- (locations table removed from this migration; locations are part of Inventory module)

CREATE TABLE IF NOT EXISTS customer_segments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS quotations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_number VARCHAR(50) NOT NULL,
  customer_id INT NOT NULL,
  status ENUM('draft','sent','accepted','rejected') DEFAULT 'draft',
  subtotal DECIMAL(12,2) DEFAULT 0,
  discount DECIMAL(12,2) DEFAULT 0,
  tax DECIMAL(12,2) DEFAULT 0,
  total DECIMAL(12,2) DEFAULT 0,
  expiry_date DATE DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  terms TEXT DEFAULT NULL,
  created_by VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quotation_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT NOT NULL,
  product_id INT NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  qty INT DEFAULT 1,
  unit_price DECIMAL(12,2) DEFAULT 0,
  line_total DECIMAL(12,2) DEFAULT 0,
  FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sales_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(50) NOT NULL,
  customer_id INT NOT NULL,
  status ENUM('pending','processed','shipped','delivered','cancelled') DEFAULT 'pending',
  subtotal DECIMAL(12,2) DEFAULT 0,
  discount DECIMAL(12,2) DEFAULT 0,
  tax DECIMAL(12,2) DEFAULT 0,
  total DECIMAL(12,2) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  confirmed_at DATETIME DEFAULT NULL,
  -- Finance sync tracking
  finance_transaction_id VARCHAR(100) DEFAULT NULL,
  finance_sync_status ENUM('pending','synced','failed') DEFAULT 'pending',
  finance_sync_error TEXT DEFAULT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sales_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_order_id INT NOT NULL,
  product_id INT NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  qty INT DEFAULT 1,
  unit_price DECIMAL(12,2) DEFAULT 0,
  line_total DECIMAL(12,2) DEFAULT 0,
  -- Note: location-specific allocation is handled by Inventory module; do not store location here
  FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
);

-- Add foreign key from sales_order_items.location_id to locations.id
-- (FK removed; locations managed in Inventory module)

CREATE TABLE IF NOT EXISTS support_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(50) NOT NULL,
  customer_id INT DEFAULT NULL,
  subject VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
  priority ENUM('low','medium','high') DEFAULT 'medium',
  assigned_to VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME DEFAULT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS communications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT DEFAULT NULL,
  customer_id INT DEFAULT NULL,
  message TEXT NOT NULL,
  sender VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS customer_finance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  credit_limit DECIMAL(12,2) DEFAULT 10000,
  outstanding_balance DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (customer_id)
);

CREATE TABLE IF NOT EXISTS invoice_sync_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_order_id INT NOT NULL,
  attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  http_code INT DEFAULT NULL,
  response TEXT DEFAULT NULL,
  success TINYINT(1) DEFAULT 0,
  FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS invoice_sync_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_order_id INT NOT NULL,
  attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  http_code INT DEFAULT NULL,
  response TEXT DEFAULT NULL,
  success TINYINT(1) DEFAULT 0,
  FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
);

-- Indexes for reporting
-- Indexes for reporting
-- Note: MySQL/MariaDB does not support CREATE INDEX IF NOT EXISTS. If you want to
-- create these indexes only when missing, run the following conditional statements
-- in the SQL tab (they check information_schema first):
--
-- -- create idx_sales_orders_customer if missing
-- SET @exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_orders' AND INDEX_NAME = 'idx_sales_orders_customer');
-- SET @sql = IF(@exists = 0, 'CREATE INDEX idx_sales_orders_customer ON sales_orders(customer_id);', 'SELECT "idx_sales_orders_customer already exists";');
-- PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
--
-- -- create idx_quotations_customer if missing
-- SET @exists2 = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotations' AND INDEX_NAME = 'idx_quotations_customer');
-- SET @sql2 = IF(@exists2 = 0, 'CREATE INDEX idx_quotations_customer ON quotations(customer_id);', 'SELECT "idx_quotations_customer already exists";');
-- PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Alternatively, simply run these if you know they don't exist:
-- CREATE INDEX idx_sales_orders_customer ON sales_orders(customer_id);
-- CREATE INDEX idx_quotations_customer ON quotations(customer_id);

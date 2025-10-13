-- Migration: Connect Customer Leads to Customers
-- Adds a foreign key column to link converted leads to customers

ALTER TABLE customer_leads
ADD COLUMN converted_customer_id INT DEFAULT NULL AFTER remarks,
ADD CONSTRAINT fk_lead_customer FOREIGN KEY (converted_customer_id) REFERENCES customers(id) ON DELETE SET NULL;

-- Optional: Add index for better query performance
CREATE INDEX idx_customer_leads_converted_customer ON customer_leads(converted_customer_id);

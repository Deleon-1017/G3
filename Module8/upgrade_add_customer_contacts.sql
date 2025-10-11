-- Add contact fields to customers table for Module8
-- Run this on existing installations if your customers table lacks email/phone/address

ALTER TABLE customers
  ADD COLUMN email VARCHAR(255) DEFAULT NULL,
  ADD COLUMN phone VARCHAR(50) DEFAULT NULL,
  ADD COLUMN address TEXT DEFAULT NULL;

-- Optionally backfill data from customer_contacts table if you imported that earlier.
-- Example: Set first contact email/phone into customers (run with care):
-- UPDATE customers c JOIN (
--   SELECT customer_id, GROUP_CONCAT(email SEPARATOR ';') as emails, GROUP_CONCAT(phone SEPARATOR ';') as phones
--   FROM customer_contacts GROUP BY customer_id
-- ) cc ON cc.customer_id = c.id SET c.email = SUBSTRING_INDEX(cc.emails,';',1), c.phone = SUBSTRING_INDEX(cc.phones,';',1);

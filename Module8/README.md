# Module 8 — Sales & Customer Support

This module provides Sales Order Management, CRM, After-Sales Support, and Reporting.

Key files:
- `migration.sql` — SQL to create the required tables (customers, quotations, sales_orders, support_tickets, communications, etc.). Run this against your `coffee` database.
- `api/` — API endpoints: `customers.php`, `quotes.php`, `sales_orders.php`, `support_tickets.php`.
- `index.php`, `customers.php`, `quotes.php`, `sales_orders.php`, `support_tickets.php`, `support_view.php`, `reports.php` — frontend pages.

Integration notes:
- Inventory: When confirming an order, the module checks `product_locations` and `products.total_quantity` and deducts stock. It also inserts a `stock_transactions` record.
- Finance: On order confirmation, a request is posted to `Module5/api/receive_sales_invoice.php` to create an invoice in the Finance module. The sales order row now contains `finance_transaction_id`, `finance_sync_status` (`pending|synced|failed`), and `finance_sync_error` to track invoice sync.
- Invoice sync attempts are logged in `invoice_sync_logs`.
- The module uses existing `db.php` and `shared/config.php` for DB connection and `BASE_URL`.

Invoice sync & retry:
- A retry endpoint is available at `Module8/api/invoice_sync_retry.php`. It will attempt to resend recent pending/failed invoices to Finance and update `sales_orders` and `invoice_sync_logs` accordingly.

Manual retry (curl):

curl -X GET http://localhost/Group3/Module8/api/invoice_sync_retry.php

Cron example (every 5 minutes):

php -f C:\xampp\htdocs\Group3\Module8\api\invoice_sync_retry.php

How to install:
1. Copy `Module8` folder into your project root (already added here).
2. Import `Module8/migration.sql` into your MySQL database (same DB used by the app).
3. Ensure `BASE_URL` in `shared/config.php` is correct.
4. Start your local server (e.g., Apache via XAMPP) and open `Module8/index.php`.

Security and TODOs:
- Add proper authentication checks and CSRF protection for production.
- Improve UI forms and product selection when creating orders/quotes.
- Implement more robust Finance & Inventory API contracts (endpoints and payloads).

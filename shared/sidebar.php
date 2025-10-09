<?php
// shared/sidebar.php
require_once __DIR__ . '/config.php';

// --- Ensure $current_page exists ---
if (!isset($current_page) || empty($current_page)) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_page = basename($path);
    if (empty($current_page)) {
        $current_page = basename($_SERVER['SCRIPT_NAME']);
    }
}
 $current_page = strtolower($current_page);

// helper: check if any of the names matches current page
function is_active($names) {
    global $current_page;
    foreach ((array)$names as $n) {
        if ($current_page === strtolower(basename($n))) return true;
    }
    return false;
}
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">

<aside class="fixed top-0 left-0 w-60 h-screen bg-gray-100 text-gray-800 flex flex-col shadow-md z-50">
  <!-- Title -->
  <div class="p-4 text-xl font-semibold flex items-center gap-2 border-b border-gray-300">
    <span>Coffee Business</span>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
    <!-- Dashboard -->
    <a href="<?php echo BASE_URL; ?>dashboard.php"
       class="flex items-center gap-2 px-3 py-2 rounded-md transition <?php echo is_active('dashboard.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
       <span>Dashboard</span>
    </a>

    <!-- Inventory Dropdown -->
    <details class="group" <?php if (is_active(['index.php','locations.php','transactions.php','alerts.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Inventory</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module1/index.php" class="block px-2 py-1 rounded <?php echo is_active('index.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Product Items</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module1/locations.php" class="block px-2 py-1 rounded <?php echo is_active('locations.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Manage Warehouse</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module1/Alerts.php" class="block px-2 py-1 rounded <?php echo is_active('alerts.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Alerts & Reports</span>
        </a>
      </div>
    </details>
    
    <!-- Procurement Dropdown -->
    <details class="group" <?php if (is_active(['purchase_requisitions.php','purchase_orders.php','invoices.php', 'suppliers.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Procurement</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module3/purchase_requisitions.php" class="block px-2 py-1 rounded <?php echo is_active('purchase_requisitions.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Purchase Requisitions</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>Module3/purchase_orders.php" class="block px-2 py-1 rounded <?php echo is_active('purchase_orders.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Purchase Orders</span>
        </a>
        <a href="<?php echo BASE_URL; ?>Module3/invoices.php" class="block px-2 py-1 rounded <?php echo is_active('invoices.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Invoice</span>
        </a>
        <a href="<?php echo BASE_URL; ?>Module3/suppliers.php" class="block px-2 py-1 rounded <?php echo is_active('suppliers.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Suppliers</span>
        </a>
      </div>
    </details>

    <!-- Finance Dropdown -->
    <details class="group" <?php if (is_active(['general_ledger.php','finance_dashboard.php', 'warehouse_stock.php', 'accounts_receivable.php','payroll_to_finance.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Finance & Accounting</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module5/general_ledger.php" class="block px-2 py-1 rounded <?php echo is_active('general_ledger.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>General Ledger</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/finance_dashboard.php" class="block px-2 py-1 rounded <?php echo is_active('finance_dashboard.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Accounts Payable</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/warehouse_stock.php" class="block px-2 py-1 rounded <?php echo is_active('warehouse_stock.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Asset Ledger</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/accounts_receivable.php" class="block px-2 py-1 rounded <?php echo is_active('accounts_receivable.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Accounts Receivable</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/payroll_to_finance.php" class="block px-2 py-1 rounded <?php echo is_active('payroll_to_finance.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Financial Reports and Compliance</span>
        </a>
      </div>
    </details>

    <!-- Human Resources Dropdown -->
    <details class="group" <?php if (is_active(['index.php','employees.php','payroll.php','attendance.php','leave.php','employee_documents.php','payslip_generator.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Human Resources</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module10/index.php" class="block px-2 py-1 rounded <?php echo is_active('index.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>HR Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/employees.php" class="block px-2 py-1 rounded <?php echo is_active('employees.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Employee Management</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/employee_documents.php" class="block px-2 py-1 rounded <?php echo is_active('employee_documents.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Employee Documents</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/payroll.php" class="block px-2 py-1 rounded <?php echo is_active('payroll.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Payroll Management</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/attendance.php" class="block px-2 py-1 rounded <?php echo is_active('attendance.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Attendance Management</span>
        </a>
 
        <a href="<?php echo BASE_URL; ?>Module10/leave.php" class="block px-2 py-1 rounded <?php echo is_active('leave.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Leave Management</span>
        </a>

      </div>
    </details>
  </nav>
</aside>
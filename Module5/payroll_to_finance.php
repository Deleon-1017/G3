<?php
require '../db.php';
require '../shared/config.php';

// Security: Add CSRF token generation and validation
function generateCSRFToken() {
    return bin2hex(random_bytes(32));
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
}

// Handle actions
 $action = $_GET['action'] ?? '';
 $id = $_GET['id'] ?? null;
 $period = $_GET['period'] ?? null;

// Process payroll approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_payroll'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    $payPeriod = $_POST['pay_period'];
    $selectedIds = $_POST['selected_payrolls'] ?? [];
    
    if (empty($selectedIds)) {
        $_SESSION['message'] = [
            'type' => 'warning',
            'title' => 'No Payrolls Selected',
            'content' => 'Please select at least one payroll record to approve.'
        ];
        header("Location: payroll_to_finance.php?period=" . urlencode($payPeriod));
        exit;
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        $approvedCount = 0;
        $errorCount = 0;
        
        foreach ($selectedIds as $payrollId) {
            try {
                // Get payroll details
                $stmt = $pdo->prepare("
                    SELECT p.*, e.employee_id, e.first_name, e.last_name, d.name as department
                    FROM hr_payroll p
                    JOIN hr_employees e ON p.employee_id = e.id
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    WHERE p.id = ? AND p.status = 'pending'
                ");
                $stmt->execute([$payrollId]);
                $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payroll) {
                    $errorCount++;
                    continue;
                }
                
                // Update payroll status to 'processed'
                $stmt = $pdo->prepare("
                    UPDATE hr_payroll 
                    SET status = 'processed', finance_sync_status = 'synced'
                    WHERE id = ?
                ");
                $stmt->execute([$payrollId]);
                
                // Send approval confirmation to Finance Module (Module 5)
                approvePayrollInFinance($payrollId, $payPeriod, $pdo);
                $approvedCount++;
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        $pdo->commit();
        
        // Set session message
        $messageType = $errorCount > 0 ? 'warning' : 'success';
        $messageTitle = 'Payroll Approval Processed';
        
        $_SESSION['message'] = [
            'type' => $messageType,
            'title' => $messageTitle,
            'content' => "Approved: {$approvedCount}, Errors: {$errorCount}"
        ];
        
        header("Location: payroll_to_finance.php?period=" . urlencode($payPeriod));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = [
            'type' => 'danger',
            'title' => 'Error Approving Payroll',
            'content' => $e->getMessage()
        ];
        header("Location: payroll_to_finance.php?period=" . urlencode($payPeriod));
        exit;
    }
}

// Get payroll periods with pending records
 $payPeriods = $pdo->query("
    SELECT DISTINCT p.pay_period, 
           COUNT(*) as total_count,
           SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
           SUM(CASE WHEN p.status = 'processed' THEN 1 ELSE 0 END) as processed_count,
           SUM(CASE WHEN p.status = 'released' THEN 1 ELSE 0 END) as released_count
    FROM hr_payroll p
    GROUP BY p.pay_period 
    HAVING pending_count > 0
    ORDER BY p.pay_period DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get current month payroll if exists
 $currentMonth = date('Y-m');
 $selectedPeriod = $period ?? $currentMonth;

// Get pending payroll records for the selected period
 $pendingPayrollStmt = $pdo->prepare("
    SELECT p.*, e.employee_id, e.first_name, e.last_name, d.name as department
    FROM hr_payroll p
    JOIN hr_employees e ON p.employee_id = e.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE p.pay_period = ? AND p.status = 'pending'
    ORDER BY e.last_name, e.first_name
");
 $pendingPayrollStmt->execute([$selectedPeriod]);
 $pendingPayroll = $pendingPayrollStmt->fetchAll(PDO::FETCH_ASSOC);

// Get processed payroll records for the selected period
 $processedPayrollStmt = $pdo->prepare("
    SELECT p.*, e.employee_id, e.first_name, e.last_name, d.name as department
    FROM hr_payroll p
    JOIN hr_employees e ON p.employee_id = e.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE p.pay_period = ? AND p.status = 'processed'
    ORDER BY e.last_name, e.first_name
");
 $processedPayrollStmt->execute([$selectedPeriod]);
 $processedPayroll = $processedPayrollStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate payroll statistics
 $pendingStats = [
    'total_basic' => array_sum(array_column($pendingPayroll, 'basic_salary')),
    'total_overtime' => array_sum(array_column($pendingPayroll, 'overtime_pay')),
    'total_allowances' => array_sum(array_column($pendingPayroll, 'allowances')),
    'total_deductions' => array_sum(array_column($pendingPayroll, 'deductions')),
    'total_tax' => array_sum(array_column($pendingPayroll, 'tax')),
    'total_net' => array_sum(array_column($pendingPayroll, 'net_pay')),
    'employee_count' => count($pendingPayroll)
];

 $processedStats = [
    'total_basic' => array_sum(array_column($processedPayroll, 'basic_salary')),
    'total_overtime' => array_sum(array_column($processedPayroll, 'overtime_pay')),
    'total_allowances' => array_sum(array_column($processedPayroll, 'allowances')),
    'total_deductions' => array_sum(array_column($processedPayroll, 'deductions')),
    'total_tax' => array_sum(array_column($processedPayroll, 'tax')),
    'total_net' => array_sum(array_column($processedPayroll, 'net_pay')),
    'employee_count' => count($processedPayroll)
];

// Function to approve payroll in Finance Module
function approvePayrollInFinance($payrollId, $payPeriod, $pdo) {
    try {
        // Get payroll details
        $stmt = $pdo->prepare("
            SELECT p.*, e.employee_id, e.first_name, e.last_name, d.name as department
            FROM hr_payroll p
            JOIN hr_employees e ON p.employee_id = e.id
            LEFT JOIN hr_departments d ON e.department_id = d.id
            WHERE p.id = ?
        ");
        $stmt->execute([$payrollId]);
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payroll) {
            return false;
        }
        
        // Prepare data for Finance Module
        $financeData = [
            'payroll_id' => $payrollId,
            'employee_id' => $payroll['employee_id'],
            'employee_name' => $payroll['first_name'] . ' ' . $payroll['last_name'],
            'department' => $payroll['department'],
            'pay_period' => $payPeriod,
            'amount' => $payroll['net_pay'],
            'transaction_type' => 'payroll_approval',
            'description' => 'Payroll approval for ' . $payPeriod,
            'date' => date('Y-m-d'),
            'status' => 'approved'
        ];
        
        // Make API call to Module 5
        $ch = curl_init(BASE_URL . 'Module5/api/approve_transaction.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($financeData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL Error approving payroll in Finance Module: " . $curlError);
            return false;
        }
        
        if ($httpCode != 200) {
            error_log("HTTP Error approving payroll in Finance Module: Code {$httpCode}, Response: {$response}");
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error approving payroll in Finance Module: " . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll to Finance Approval</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --white: #ffffff;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            position: fixed;
            width: 18rem;
            z-index: 100;
            top: 0;
            left: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: 18rem;
            padding: 1.5rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            border-radius: 0.35rem;
            background-color: var(--white);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .btn {
            display: inline-block;
            font-weight: 400;
            line-height: 1.5;
            color: var(--white);
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            background-color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .btn:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #17a673;
            border-color: #169b6b;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
        
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: var(--dark-color);
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #e3e6f0;
            text-align: center;
        }
        
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 700;
            color: var(--dark-color);
            text-align: center;
        }
        
        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processed {
            background-color: #d1f2eb;
            color: #0e5a46;
        }
        
        .status-released {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stats-card {
            border-left: 0.25rem solid;
            margin-bottom: 1rem;
        }
        
        .stats-card.primary {
            border-left-color: var(--primary-color);
        }
        
        .stats-card.success {
            border-left-color: var(--success-color);
        }
        
        .stats-card.info {
            border-left-color: var(--info-color);
        }
        
        .stats-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .stats-card.danger {
            border-left-color: var(--danger-color);
        }
        
        .stats-card .stats-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0;
            color: #5a5c69;
        }
        
        .stats-card .stats-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--secondary-color);
            text-transform: uppercase;
        }
        
        .stats-card i {
            font-size: 2rem;
            opacity: 0.4;
        }
        
        .alert {
            position: relative;
            padding: 1rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-dismissible {
            padding-right: 3rem;
        }
        
        .alert-dismissible .btn-close {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 2;
            padding: 1rem 1rem;
            color: inherit;
        }
        
        .btn-close {
            box-sizing: content-box;
            width: 1em;
            height: 1em;
            padding: 0.25em 0.25em;
            color: #000;
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0;
            border-radius: 0.25rem;
            opacity: 0.5;
            cursor: pointer;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        .form-check {
            display: block;
            min-height: 1.5rem;
            padding-left: 1.5em;
            margin-bottom: 0.125rem;
        }
        
        .form-check-input {
            width: 1em;
            height: 1em;
            margin-top: 0.25em;
            vertical-align: top;
            background-color: #fff;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            border: 1px solid rgba(0,0,0,.25);
            appearance: none;
            color-adjust: exact;
            cursor: pointer;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-input:checked[type=checkbox] {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
        }
        
        .form-check-label {
            margin-left: -1.5em;
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .me-2 {
            margin-right: 0.5rem !important;
        }
        
        .me-3 {
            margin-right: 1rem !important;
        }
        
        .ms-2 {
            margin-left: 0.5rem !important;
        }
        
        .ms-3 {
            margin-left: 1rem !important;
        }
        
        .mt-3 {
            margin-top: 1rem !important;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
        
        .mb-0 {
            margin-bottom: 0 !important;
        }
        
        .py-4 {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }
        
        .text-center {
            text-align: center !important;
        }
        
        .d-flex {
            display: flex !important;
        }
        
        .justify-content-between {
            justify-content: space-between !important;
        }
        
        .align-items-center {
            align-items: center !important;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #5a5c69;
            margin-bottom: 0;
        }
        
        .period-selector {
            max-width: 200px;
        }
        
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .text-gray-300 {
            color: #dddfeb !important;
        }
        
        .text-gray-500 {
            color: #858796 !important;
        }
        
        .w-auto {
            width: auto !important;
        }
        
        .d-inline-block {
            display: inline-block !important;
        }
        
        .form-select {
            display: block;
            width: 100%;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--dark-color);
            background-color: var(--white);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            border: 1px solid #d1d3e2;
            border-radius: 0.25rem;
            appearance: none;
        }
        
        .form-select:focus {
            border-color: #bac8f3;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .form-select-sm {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            padding-left: 0.5rem;
            font-size: 0.875rem;
        }
        
        .m-0 {
            margin: 0 !important;
        }
        
        .summary-card {
            background-color: #f8f9fc;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .summary-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }
        
        .summary-label {
            font-weight: 500;
        }
        
        .summary-value {
            font-weight: 600;
        }
        
        .approval-form {
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 6.5rem;
            }
            
            .main-content {
                margin-left: 6.5rem;
            }
            
            .stats-card .stats-value {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../shared/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">Payroll to Finance Approval</h1>
            <div>
                <a href="payroll.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Payroll
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                <strong><?php echo htmlspecialchars($_SESSION['message']['title']); ?></strong>
                <?php echo htmlspecialchars($_SESSION['message']['content']); ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Payroll Periods -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Payroll Periods with Pending Approvals</h3>
            </div>
            <div class="card-body">
                <?php if (count($payPeriods) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Pay Period</th>
                                    <th>Total Employees</th>
                                    <th>Pending</th>
                                    <th>Processed</th>
                                    <th>Released</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payPeriods as $period): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($period['pay_period'] . '-01')); ?></td>
                                        <td><?php echo $period['total_count']; ?></td>
                                        <td><?php echo $period['pending_count']; ?></td>
                                        <td><?php echo $period['processed_count']; ?></td>
                                        <td><?php echo $period['released_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-gray-500">All payrolls have been processed. No pending approvals.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Payroll Summary -->
        <?php if (!empty($pendingPayroll)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">
                    Pending Payroll Approval - 
                    <select class="form-select form-select-sm period-selector d-inline-block w-auto ms-2" onchange="location.href='?period='+this.value">
                        <?php foreach ($payPeriods as $period): ?>
                            <option value="<?php echo $period['pay_period']; ?>" <?php echo $period['pay_period'] == $selectedPeriod ? 'selected' : ''; ?>>
                                <?php echo date('F Y', strtotime($period['pay_period'] . '-01')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </h3>
            </div>
            <div class="card-body">
                <div class="summary-card">
                    <div class="summary-title">Pending Payroll Summary</div>
                    <div class="summary-row">
                        <span class="summary-label">Total Employees:</span>
                        <span class="summary-value"><?php echo $pendingStats['employee_count']; ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Basic Salary:</span>
                        <span class="summary-value">₱<?php echo number_format($pendingStats['total_basic'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Overtime:</span>
                        <span class="summary-value">₱<?php echo number_format($pendingStats['total_overtime'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Allowances:</span>
                        <span class="summary-value">₱<?php echo number_format($pendingStats['total_allowances'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Deductions:</span>
                        <span class="summary-value">₱<?php echo number_format($pendingStats['total_deductions'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Tax:</span>
                        <span class="summary-value">₱<?php echo number_format($pendingStats['total_tax'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Net Pay:</span>
                        <span class="summary-value">₱<?php echo number_format($pendingStats['total_net'], 2); ?></span>
                    </div>
                </div>

                <form method="post" class="approval-form">
                    <input type="hidden" name="approve_payroll" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="pay_period" value="<?php echo $selectedPeriod; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Basic Salary</th>
                                    <th>Overtime</th>
                                    <th>Allowances</th>
                                    <th>Deductions</th>
                                    <th>Tax</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayroll as $payroll): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input payroll-checkbox" type="checkbox" name="selected_payrolls[]" value="<?php echo $payroll['id']; ?>" id="payroll-<?php echo $payroll['id']; ?>">
                                                <label class="form-check-label" for="payroll-<?php echo $payroll['id']; ?>"></label>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($payroll['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payroll['department']); ?></td>
                                        <td>₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                        <td>₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                                        <td>₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                                        <td>₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                                        <td>₱<?php echo number_format($payroll['tax'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($payroll['net_pay'], 2); ?></strong></td>
                                        <td>
                                            <span class="status-badge status-pending">
                                                Pending
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i>Approve Selected Payrolls
                            </button>
                        </div>
                        <div>
                            <span id="selectedCount" class="me-3">0 selected</span>
                            <button type="button" class="btn btn-primary" onclick="selectAll()">
                                <i class="fas fa-check-square me-2"></i>Select All
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Processed Payroll -->
        <?php if (!empty($processedPayroll)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">
                    Processed Payroll - 
                    <select class="form-select form-select-sm period-selector d-inline-block w-auto ms-2" onchange="location.href='?period='+this.value">
                        <?php foreach ($payPeriods as $period): ?>
                            <option value="<?php echo $period['pay_period']; ?>" <?php echo $period['pay_period'] == $selectedPeriod ? 'selected' : ''; ?>>
                                <?php echo date('F Y', strtotime($period['pay_period'] . '-01')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </h3>
            </div>
            <div class="card-body">
                <div class="summary-card">
                    <div class="summary-title">Processed Payroll Summary</div>
                    <div class="summary-row">
                        <span class="summary-label">Total Employees:</span>
                        <span class="summary-value"><?php echo $processedStats['employee_count']; ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Net Pay:</span>
                        <span class="summary-value">₱<?php echo number_format($processedStats['total_net'], 2); ?></span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Basic Salary</th>
                                <th>Overtime</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Tax</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processedPayroll as $payroll): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payroll['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payroll['department']); ?></td>
                                    <td>₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                    <td>₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                                    <td>₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                                    <td>₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                                    <td>₱<?php echo number_format($payroll['tax'], 2); ?></td>
                                    <td><strong>₱<?php echo number_format($payroll['net_pay'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-processed">
                                            Processed
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Select all checkboxes functionality
        function selectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.payroll-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateSelectedCount();
        }
        
        // Update selected count
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.payroll-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = `${count} selected`;
        }
        
        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Select all checkbox functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', selectAll);
            }
            
            // Individual checkbox change
            const checkboxes = document.querySelectorAll('.payroll-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
            
            // Initialize selected count
            updateSelectedCount();
        });
    </script>
</body>
</html>
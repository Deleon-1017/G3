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

// Process payroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payroll'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    $payPeriod = $_POST['pay_period'];
    $payDate = $_POST['pay_date'];
    $reprocess = isset($_POST['reprocess']) && $_POST['reprocess'] == 1;
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // If reprocessing, delete existing payroll records for this period
        if ($reprocess) {
            $stmt = $pdo->prepare("DELETE FROM hr_payroll WHERE pay_period = ?");
            $stmt->execute([$payPeriod]);
        }
        
        // Get all active employees
        $employees = $pdo->query("SELECT * FROM hr_employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        
        $processedCount = 0;
        
        foreach ($employees as $employee) {
            try {
                // Calculate payroll for each employee
                $basicSalary = $employee['salary'];
                
                // Get attendance data for the pay period
                $stmt = $pdo->prepare("
                    SELECT SUM(hours_worked) as total_hours, COUNT(*) as days_present
                    FROM hr_attendance 
                    WHERE employee_id = ? AND attendance_date LIKE ? AND status = 'present'
                ");
                $stmt->execute([$employee['id'], $payPeriod . '%']);
                $attendanceData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculate overtime
                $overtimePay = 0;
                if ($attendanceData && $attendanceData['total_hours'] > 160) { // Assuming 8 hours/day * 20 days = 160 hours
                    $overtimeHours = $attendanceData['total_hours'] - 160;
                    $overtimePay = $overtimeHours * ($basicSalary / 160 / 8 * 1.5); // 1.5x rate
                }
                
                // Calculate allowances
                $allowances = $basicSalary * 0.1; // 10% of basic salary
                
                // Calculate deductions
                $deductions = $basicSalary * 0.05; // 5% of basic salary
                
                // Calculate tax
                $tax = ($basicSalary + $overtimePay + $allowances) * 0.1; // 10% tax
                
                // Calculate net pay
                $netPay = $basicSalary + $overtimePay + $allowances - $deductions - $tax;
                
                // Insert payroll record with status as 'pending'
                $stmt = $pdo->prepare("
                    INSERT INTO hr_payroll 
                    (employee_id, pay_period, basic_salary, overtime_pay, bonus, allowances, deductions, tax, net_pay, pay_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $employee['id'],
                    $payPeriod,
                    $basicSalary,
                    $overtimePay,
                    0, // Bonus
                    $allowances,
                    $deductions,
                    $tax,
                    $netPay,
                    $payDate,
                    'pending' // Changed from 'processed' to 'pending'
                ]);
                
                // Send payroll data to Finance Module (Module 5)
                sendPayrollToFinance($employee['id'], $payPeriod, $netPay, $pdo);
                $processedCount++;
            } catch (Exception $e) {
                // Silently continue processing other employees
            }
        }
        
        $pdo->commit();
        
        // Set session message
        $messageType = 'success';
        $messageTitle = $reprocess ? 'Payroll Reprocessed' : 'Payroll Processed';
        
        $_SESSION['message'] = [
            'type' => $messageType,
            'title' => $messageTitle,
            'content' => "Processed: {$processedCount} employees. Payroll status is pending until Finance Module approval."
        ];
        
        header("Location: payroll.php?period=" . urlencode($payPeriod));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = [
            'type' => 'danger',
            'title' => 'Error Processing Payroll',
            'content' => $e->getMessage()
        ];
        header("Location: payroll.php");
        exit;
    }
}

// Generate payslip for a single employee
if ($action === 'generate_payslip' && $id) {
    try {
        // Get payroll details
        $stmt = $pdo->prepare("
            SELECT p.*, e.employee_id, e.first_name, e.last_name, e.email, d.name as department, pos.title as position
            FROM hr_payroll p
            JOIN hr_employees e ON p.employee_id = e.id
            LEFT JOIN hr_departments d ON e.department_id = d.id
            LEFT JOIN hr_positions pos ON e.position_id = pos.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payroll) {
            throw new Exception("Payroll record not found");
        }
        
        // Generate payslip HTML
        $payslipHtml = generatePayslipHtml($payroll);
        
        // Save HTML file
        $fileName = 'payslip_' . $payroll['employee_id'] . '_' . $payroll['pay_period'] . '.html';
        $filePath = '../uploads/payslips/' . $fileName;
        
        if (!file_exists('../uploads/payslips/')) {
            mkdir('../uploads/payslips/', 0777, true);
        }
        
        file_put_contents($filePath, $payslipHtml);
        
        // Update payroll record with payslip path
        $stmt = $pdo->prepare("UPDATE hr_payroll SET payslip_path = ? WHERE id = ?");
        $stmt->execute([$filePath, $id]);
        
        // Send email with payslip link
        if ($payroll['email']) {
            sendPayslipEmail($payroll['email'], $filePath, $fileName, $payroll);
        }
        
        $_SESSION['message'] = [
            'type' => 'success',
            'title' => 'Payslip Generated',
            'content' => "Payslip for {$payroll['first_name']} {$payroll['last_name']} has been generated and sent."
        ];
        
        header("Location: payroll.php?period=" . urlencode($payroll['pay_period']));
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'title' => 'Error Generating Payslip',
            'content' => $e->getMessage()
        ];
        header("Location: payroll.php?period=" . urlencode($period));
        exit;
    }
}

// Generate payslips for all employees in a period
if ($action === 'generate_all_payslips' && $period) {
    try {
        // Get all payroll records for the period
        $stmt = $pdo->prepare("
            SELECT p.*, e.employee_id, e.first_name, e.last_name, e.email, d.name as department, pos.title as position
            FROM hr_payroll p
            JOIN hr_employees e ON p.employee_id = e.id
            LEFT JOIN hr_departments d ON e.department_id = d.id
            LEFT JOIN hr_positions pos ON e.position_id = pos.id
            WHERE p.pay_period = ?
        ");
        $stmt->execute([$period]);
        $payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($payrollRecords)) {
            throw new Exception("No payroll records found for the selected period");
        }
        
        $generatedCount = 0;
        
        foreach ($payrollRecords as $payroll) {
            try {
                // Skip if payslip already exists
                if (!empty($payroll['payslip_path'])) {
                    $generatedCount++;
                    continue;
                }
                
                // Generate payslip HTML
                $payslipHtml = generatePayslipHtml($payroll);
                
                // Save HTML file
                $fileName = 'payslip_' . $payroll['employee_id'] . '_' . $payroll['pay_period'] . '.html';
                $filePath = '../uploads/payslips/' . $fileName;
                
                if (!file_exists('../uploads/payslips/')) {
                    mkdir('../uploads/payslips/', 0777, true);
                }
                
                file_put_contents($filePath, $payslipHtml);
                
                // Update payroll record with payslip path
                $stmt = $pdo->prepare("UPDATE hr_payroll SET payslip_path = ? WHERE id = ?");
                $stmt->execute([$filePath, $payroll['id']]);
                
                // Send email with payslip link
                if ($payroll['email']) {
                    sendPayslipEmail($payroll['email'], $filePath, $fileName, $payroll);
                }
                
                $generatedCount++;
            } catch (Exception $e) {
                // Silently continue processing other employees
            }
        }
        
        $_SESSION['message'] = [
            'type' => 'success',
            'title' => 'Payslips Generated',
            'content' => "Generated: {$generatedCount} payslips"
        ];
        
        header("Location: payroll.php?period=" . urlencode($period));
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'title' => 'Error Generating Payslips',
            'content' => $e->getMessage()
        ];
        header("Location: payroll.php?period=" . urlencode($period));
        exit;
    }
}

// View payslip for printing
if ($action === 'view_payslip' && $id) {
    try {
        // Get payroll details
        $stmt = $pdo->prepare("
            SELECT p.*, e.employee_id, e.first_name, e.last_name, e.email, d.name as department, pos.title as position
            FROM hr_payroll p
            JOIN hr_employees e ON p.employee_id = e.id
            LEFT JOIN hr_departments d ON e.department_id = d.id
            LEFT JOIN hr_positions pos ON e.position_id = pos.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payroll) {
            throw new Exception("Payroll record not found");
        }
        
        // Generate payslip HTML
        $payslipHtml = generatePayslipHtml($payroll, true);
        
        // Output the HTML for printing
        echo $payslipHtml;
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'title' => 'Error Viewing Payslip',
            'content' => $e->getMessage()
        ];
        header("Location: payroll.php?period=" . urlencode($period));
        exit;
    }
}

// Function to generate payslip HTML
function generatePayslipHtml($payroll, $forPrint = false) {
    $printStyles = $forPrint ? '
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        @page {
            size: auto;
            margin: 10mm;
        }
    ' : '';
    
    $printButton = $forPrint ? '
        <div class="no-print" style="margin-bottom: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Payslip</button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Close</button>
        </div>
    ' : '';
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Payslip - ' . $payroll['employee_id'] . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px;
                color: #333;
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                border-bottom: 2px solid #3498db;
                padding-bottom: 20px;
            }
            .company-info { 
                margin-bottom: 20px; 
            }
            .payslip-title { 
                font-size: 24px; 
                font-weight: bold; 
                margin-bottom: 20px;
                color: #2c3e50;
            }
            .employee-info { 
                margin-bottom: 20px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .pay-details { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 20px; 
            }
            .pay-details th, .pay-details td { 
                padding: 8px; 
                border: 1px solid #ddd; 
                text-align: left; 
            }
            .pay-details th { 
                background-color: #f2f2f2; 
            }
            .earnings-deductions { 
                display: flex; 
                justify-content: space-between; 
                margin-bottom: 20px; 
            }
            .earnings, .deductions { 
                width: 48%; 
            }
            .table { 
                width: 100%; 
                border-collapse: collapse; 
            }
            .table th, .table td { 
                padding: 8px; 
                border: 1px solid #ddd; 
                text-align: left; 
            }
            .table th { 
                background-color: #f2f2f2; 
            }
            .net-pay { 
                font-size: 18px; 
                font-weight: bold; 
                text-align: right; 
                margin-top: 20px;
                padding: 10px;
                background-color: #f8f9fa;
                border-radius: 4px;
            }
            .footer { 
                margin-top: 50px; 
                text-align: center; 
                font-size: 12px; 
                color: #666; 
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            ' . $printStyles . '
        </style>
    </head>
    <body>
        ' . $printButton . '
        
        <div class="header">
            <div class="company-info">
                <h2>Coffee Business</h2>
                <p>123 Business Street, City, Country</p>
                <p>Phone: (123) 456-7890 | Email: info@coffeebusiness.com</p>
            </div>
            <div class="payslip-title">PAYSLIP</div>
        </div>
        
        <div class="employee-info">
            <p><strong>Employee ID:</strong> ' . $payroll['employee_id'] . '</p>
            <p><strong>Name:</strong> ' . $payroll['first_name'] . ' ' . $payroll['last_name'] . '</p>
            <p><strong>Department:</strong> ' . $payroll['department'] . '</p>
            <p><strong>Position:</strong> ' . $payroll['position'] . '</p>
            <p><strong>Pay Period:</strong> ' . date('F Y', strtotime($payroll['pay_period'] . '-01')) . '</p>
            <p><strong>Pay Date:</strong> ' . date('F d, Y', strtotime($payroll['pay_date'])) . '</p>
        </div>
        
        <div class="earnings-deductions">
            <div class="earnings">
                <h3>Earnings</h3>
                <table class="table">
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                    <tr>
                        <td>Basic Salary</td>
                        <td>$' . number_format($payroll['basic_salary'], 2) . '</td>
                    </tr>
                    <tr>
                        <td>Overtime Pay</td>
                        <td>$' . number_format($payroll['overtime_pay'], 2) . '</td>
                    </tr>
                    <tr>
                        <td>Bonus</td>
                        <td>$' . number_format($payroll['bonus'], 2) . '</td>
                    </tr>
                    <tr>
                        <td>Allowances</td>
                        <td>$' . number_format($payroll['allowances'], 2) . '</td>
                    </tr>
                </table>
            </div>
            
            <div class="deductions">
                <h3>Deductions</h3>
                <table class="table">
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                    <tr>
                        <td>Tax</td>
                        <td>$' . number_format($payroll['tax'], 2) . '</td>
                    </tr>
                    <tr>
                        <td>Other Deductions</td>
                        <td>$' . number_format($payroll['deductions'], 2) . '</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="net-pay">
            Net Pay: $' . number_format($payroll['net_pay'], 2) . '
        </div>
        
        <div class="footer">
            <p>This is a computer-generated payslip and does not require a signature.</p>
            <p>Generated on: ' . date('F d, Y') . '</p>
        </div>
    </body>
    </html>
    ';
}

// Function to send payslip email
function sendPayslipEmail($email, $filePath, $fileName, $payroll) {
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../';
    $payslipUrl = $baseUrl . $filePath;
    
    $subject = "Your Payslip is Ready";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .content { margin-bottom: 30px; }
            .footer { text-align: center; font-size: 12px; color: #666; }
            .btn { 
                display: inline-block; 
                padding: 10px 20px; 
                background: #3498db; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <div class=\"header\">
                <h2>Coffee Business</h2>
            </div>
            <div class=\"content\">
                <p>Dear " . htmlspecialchars($payroll['first_name']) . ",</p>
                <p>Your payslip for " . date('F Y', strtotime($payroll['pay_period'] . '-01')) . " is now available.</p>
                <p>You can view and print your payslip by clicking the button below:</p>
                <a href=\"" . $payslipUrl . "\" class=\"btn\">View Payslip</a>
                <p>Once opened, you can use your browser's print function to save it as a PDF.</p>
                <p>If you have any questions about your payslip, please contact the HR department.</p>
                <p>Thank you,</p>
                <p>HR Department<br>Coffee Business</p>
            </div>
            <div class=\"footer\">
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: <hr@coffeebusiness.com>' . "\r\n";
    
    mail($email, $subject, $message, $headers);
}

// Function to send payroll data to Finance Module
function sendPayrollToFinance($employeeId, $payPeriod, $netPay, $pdo) {
    try {
        // Get employee details
        $stmt = $pdo->prepare("
            SELECT e.employee_id, e.first_name, e.last_name, d.name as department
            FROM hr_employees e
            LEFT JOIN hr_departments d ON e.department_id = d.id
            WHERE e.id = ?
        ");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            return false;
        }
        
        // Prepare data for Finance Module
        $financeData = [
            'employee_id' => $employee['employee_id'],
            'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
            'department' => $employee['department'],
            'pay_period' => $payPeriod,
            'amount' => $netPay,
            'transaction_type' => 'payroll',
            'description' => 'Payroll for ' . $payPeriod,
            'date' => date('Y-m-d')
        ];
        
        // Make API call to Module 5
        $ch = curl_init(BASE_URL . 'Module5/api/receive_transaction.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($financeData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL Error sending payroll to Finance Module: " . $curlError);
            return false;
        }
        
        if ($httpCode != 200) {
            error_log("HTTP Error sending payroll to Finance Module: Code {$httpCode}, Response: {$response}");
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error sending payroll to Finance Module: " . $e->getMessage());
        return false;
    }
}

// Get payroll periods
 $payPeriods = $pdo->query("
    SELECT DISTINCT pay_period, 
           COUNT(*) as employee_count,
           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
           SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed_count,
           SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as released_count
    FROM hr_payroll 
    GROUP BY pay_period 
    ORDER BY pay_period DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get current month payroll if exists
 $currentMonth = date('Y-m');
 $selectedPeriod = $period ?? $currentMonth;

 $currentPayrollStmt = $pdo->prepare("
    SELECT p.*, e.employee_id, e.first_name, e.last_name, d.name as department
    FROM hr_payroll p
    JOIN hr_employees e ON p.employee_id = e.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE p.pay_period = ?
    ORDER BY e.last_name, e.first_name
");
 $currentPayrollStmt->execute([$selectedPeriod]);
 $currentPayroll = $currentPayrollStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate payroll statistics
 $payrollStats = [
    'total_basic' => array_sum(array_column($currentPayroll, 'basic_salary')),
    'total_overtime' => array_sum(array_column($currentPayroll, 'overtime_pay')),
    'total_allowances' => array_sum(array_column($currentPayroll, 'allowances')),
    'total_deductions' => array_sum(array_column($currentPayroll, 'deductions')),
    'total_tax' => array_sum(array_column($currentPayroll, 'tax')),
    'total_net' => array_sum(array_column($currentPayroll, 'net_pay')),
    'employee_count' => count($currentPayroll)
];

// Check if payroll has been processed for the selected period
 $payrollProcessed = !empty($currentPayroll);

// Get count of active employees
 $activeEmployeesCount = $pdo->query("SELECT COUNT(*) FROM hr_employees WHERE status = 'active'")->fetchColumn();

// Check if there are new employees that need to be processed
 $needsReprocessing = false;
if ($payrollProcessed) {
    $processedEmployeesCount = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM hr_payroll WHERE pay_period = ?");
    $processedEmployeesCount->execute([$selectedPeriod]);
    $processedCount = $processedEmployeesCount->fetchColumn();
    
    if ($processedCount < $activeEmployeesCount) {
        $needsReprocessing = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management</title>
    
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
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #fff;
        }
        
        .btn-warning:hover {
            background-color: #dda20a;
            border-color: #d6a006;
            color: #fff;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
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
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            display: none;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-dialog {
            position: relative;
            width: auto;
            max-width: 500px;
            margin: 1.75rem;
        }
        
        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            background-color: var(--white);
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.3rem;
            outline: 0;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1rem;
            border-bottom: 1px solid #e3e6f0;
            background-color: #f8f9fc;
        }
        
        .modal-title {
            margin-bottom: 0;
            line-height: 1.5;
        }
        
        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }
        
        .modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            padding: 0.75rem;
            border-top: 1px solid #e3e6f0;
            background-color: #f8f9fc;
        }
        
        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--dark-color);
            background-color: var(--white);
            background-clip: padding-box;
            border: 1px solid #d1d3e2;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            color: var(--dark-color);
            background-color: var(--white);
            border-color: #bac8f3;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
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
        
        .reprocess-alert {
            background-color: #fff3cd;
            border-left: 4px solid var(--warning-color);
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
            <h1 class="page-title">Payroll Management</h1>
            <div>
                <?php if (!$payrollProcessed): ?>
                <button class="btn btn-primary" onclick="openModal('processPayrollModal')">
                    <i class="fas fa-calculator me-2"></i>Process Payroll
                </button>
                <?php else: ?>
                <button class="btn btn-warning" onclick="openModal('reprocessPayrollModal')">
                    <i class="fas fa-redo me-2"></i>Reprocess Payroll
                </button>
                <?php endif; ?>
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

        <!-- New Employees Alert -->
        <?php if ($needsReprocessing): ?>
            <div class="alert reprocess-alert d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3 text-warning"></i>
                <div>
                    <strong>New Employees Detected!</strong> 
                    There are <?php echo $activeEmployeesCount - count($currentPayroll); ?> new employees that haven't been processed for this period.
                    <button class="btn btn-warning btn-sm ms-3" onclick="openModal('reprocessPayrollModal')">
                        <i class="fas fa-redo me-1"></i>Reprocess Now
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payroll Periods -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Payroll Periods</h3>
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
                                        <td><?php echo $period['employee_count']; ?></td>
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
                        <i class="fas fa-calendar-times fa-3x text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No payroll periods found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Month Payroll -->
        <?php if (!empty($currentPayroll)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">
                    Payroll Details - 
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
                <div class="table-responsive">
                    <table class="table table-hover" id="payrollTable">
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
                                <th>Payslip</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentPayroll as $payroll): ?>
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
                                        <span class="status-badge status-<?php echo $payroll['status']; ?>">
                                            <?php echo ucfirst($payroll['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($payroll['payslip_path'])): ?>
                                            <a href="?action=view_payslip&id=<?php echo $payroll['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=generate_payslip&id=<?php echo $payroll['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-file-invoice-dollar me-1"></i>Generate
                                            </a>
                                        <?php endif; ?>
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

    <!-- Process Payroll Modal -->
    <div class="modal" id="processPayrollModal" tabindex="-1" aria-labelledby="processPayrollModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processPayrollModalLabel">Process Payroll</h5>
                    <button type="button" class="btn-close" onclick="closeModal('processPayrollModal')"></button>
                </div>
                <div class="modal-body">
                    <form id="processPayrollForm" method="post">
                        <input type="hidden" name="process_payroll" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="pay_period" class="form-label">Pay Period</label>
                            <input type="month" class="form-control" id="pay_period" name="pay_period" value="<?php echo date('Y-m'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pay_date" class="form-label">Pay Date</label>
                            <input type="date" class="form-control" id="pay_date" name="pay_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This will process payroll for all active employees and send data to the Finance Module. The initial status will be "pending" until the Finance Module approves it. Make sure attendance data is up to date.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('processPayrollModal')">Cancel</button>
                    <button type="submit" form="processPayrollForm" class="btn btn-primary">
                        <i class="fas fa-calculator me-2"></i>Process Payroll
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reprocess Payroll Modal -->
    <div class="modal" id="reprocessPayrollModal" tabindex="-1" aria-labelledby="reprocessPayrollModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reprocessPayrollModalLabel">Reprocess Payroll</h5>
                    <button type="button" class="btn-close" onclick="closeModal('reprocessPayrollModal')"></button>
                </div>
                <div class="modal-body">
                    <form id="reprocessPayrollForm" method="post">
                        <input type="hidden" name="process_payroll" value="1">
                        <input type="hidden" name="reprocess" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="reprocess_pay_period" class="form-label">Pay Period</label>
                            <input type="month" class="form-control" id="reprocess_pay_period" name="pay_period" value="<?php echo $selectedPeriod; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reprocess_pay_date" class="form-label">Pay Date</label>
                            <input type="date" class="form-control" id="reprocess_pay_date" name="pay_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will delete existing payroll records for this period and process payroll again for all active employees. This action cannot be undone.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('reprocessPayrollModal')">Cancel</button>
                    <button type="submit" form="reprocessPayrollForm" class="btn btn-warning">
                        <i class="fas fa-redo me-2"></i>Reprocess Payroll
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        // Initialize DataTable for payroll table
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable if table exists
            const payrollTable = document.getElementById('payrollTable');
            if (payrollTable) {
                // Simple table sorting and pagination functionality
                const table = payrollTable;
                const headers = table.querySelectorAll('thead th');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                // Add sorting functionality
                headers.forEach((header, index) => {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        const isNumeric = index >= 3 && index <= 8; // Numeric columns
                        
                        // Sort rows
                        rows.sort((a, b) => {
                            const aValue = a.cells[index].textContent.trim();
                            const bValue = b.cells[index].textContent.trim();
                            
                            if (isNumeric) {
                                // Remove currency symbol and commas for numeric comparison
                                const aNum = parseFloat(aValue.replace(/[₱,]/g, ''));
                                const bNum = parseFloat(bValue.replace(/[₱,]/g, ''));
                                return aNum - bNum;
                            } else {
                                return aValue.localeCompare(bValue);
                            }
                        });
                        
                        // Clear tbody and append sorted rows
                        tbody.innerHTML = '';
                        rows.forEach(row => tbody.appendChild(row));
                    });
                });
            }
        });
    </script>
</body>
</html>
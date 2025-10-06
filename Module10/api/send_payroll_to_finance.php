<?php
require '../db.php';
require '../shared/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON data from request body
 $jsonData = file_get_contents('php://input');
 $data = json_decode($jsonData, true);

// Validate required fields
 $requiredFields = ['employee_id', 'pay_period', 'amount', 'transaction_type'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Get employee details
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, department_id 
        FROM hr_employees 
        WHERE employee_id = ?
    ");
    $stmt->execute([$data['employee_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['error' => 'Employee not found']);
        exit;
    }
    
    // Get department name
    $stmt = $pdo->prepare("SELECT name FROM hr_departments WHERE id = ?");
    $stmt->execute([$employee['department_id']]);
    $department = $stmt->fetchColumn();
    
    // Prepare data for Finance Module
    $financeData = [
        'source_module' => 'HR',
        'employee_id' => $data['employee_id'],
        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'department' => $department,
        'pay_period' => $data['pay_period'],
        'amount' => $data['amount'],
        'transaction_type' => $data['transaction_type'],
        'description' => $data['description'] ?? 'Payroll transaction',
        'transaction_date' => $data['date'] ?? date('Y-m-d'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Make API call to Module 5
    $ch = curl_init(BASE_URL . 'Module5/api/receive_transaction.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($financeData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Return response
    http_response_code($httpCode);
    echo $response;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
<?php
require '../db.php';
require '../shared/config.php';

// Handle actions
 $action = $_GET['action'] ?? '';
 $id = $_GET['id'] ?? null;

// Add/Edit employee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'] ?? '';
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $middleName = $_POST['middle_name'] ?? '';
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $birthDate = $_POST['birth_date'] ?? null;
    $hireDate = $_POST['hire_date'];
    $employmentType = $_POST['employment_type'];
    $salary = $_POST['salary'];
    $status = $_POST['status'] ?? 'active';
    
    // Handle department
    $departmentName = $_POST['department_name'] ?? '';
    $departmentId = null;
    
    if (!empty($departmentName)) {
        // Check if department exists
        $stmt = $pdo->prepare("SELECT id FROM hr_departments WHERE name = ?");
        $stmt->execute([$departmentName]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dept) {
            $departmentId = $dept['id'];
        } else {
            // Create new department
            $stmt = $pdo->prepare("INSERT INTO hr_departments (name) VALUES (?)");
            $stmt->execute([$departmentName]);
            $departmentId = $pdo->lastInsertId();
        }
    }
    
    // Handle position
    $positionName = $_POST['position_name'] ?? '';
    $positionId = null;
    
    if (!empty($positionName) && $departmentId) {
        // Check if position exists in this department
        $stmt = $pdo->prepare("SELECT id FROM hr_positions WHERE title = ? AND department_id = ?");
        $stmt->execute([$positionName, $departmentId]);
        $pos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pos) {
            $positionId = $pos['id'];
        } else {
            // Create new position
            $stmt = $pdo->prepare("INSERT INTO hr_positions (title, department_id) VALUES (?, ?)");
            $stmt->execute([$positionName, $departmentId]);
            $positionId = $pdo->lastInsertId();
        }
    }
    
    try {
        if ($id) {
            // Update employee
            $stmt = $pdo->prepare("
                UPDATE hr_employees 
                SET employee_id = ?, first_name = ?, last_name = ?, middle_name = ?, email = ?, 
                    phone = ?, address = ?, birth_date = ?, hire_date = ?, position_id = ?, 
                    department_id = ?, employment_type = ?, salary = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $employeeId, $firstName, $lastName, $middleName, $email, $phone, $address,
                $birthDate, $hireDate, $positionId, $departmentId, $employmentType, $salary, $status, $id
            ]);
            
            // Preserve filter parameters when redirecting
            $search = $_POST['search'] ?? '';
            $department = $_POST['department'] ?? '';
            $statusFilter = $_POST['status_filter'] ?? '';
            
            $redirectUrl = "employees.php?status=updated";
            if (!empty($search)) $redirectUrl .= "&search=" . urlencode($search);
            if (!empty($department)) $redirectUrl .= "&department=" . urlencode($department);
            if (!empty($statusFilter)) $redirectUrl .= "&status_filter=" . urlencode($statusFilter);
            
            header("Location: " . BASE_URL . "Module10/" . $redirectUrl);
        } else {
            // Add new employee
            $stmt = $pdo->prepare("
                INSERT INTO hr_employees 
                (employee_id, first_name, last_name, middle_name, email, phone, address, 
                 birth_date, hire_date, position_id, department_id, employment_type, salary, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $employeeId, $firstName, $lastName, $middleName, $email, $phone, $address,
                $birthDate, $hireDate, $positionId, $departmentId, $employmentType, $salary, $status
            ]);
            
            $employeeId = $pdo->lastInsertId();
            
            // Initialize leave balances for the new employee
            $currentYear = date('Y');
            $leaveTypes = $pdo->query("SELECT * FROM hr_leave_types")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($leaveTypes as $leaveType) {
                $stmt = $pdo->prepare("
                    INSERT INTO hr_leave_balances (employee_id, leave_type_id, year, total_days)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$employeeId, $leaveType['id'], $currentYear, $leaveType['days_allowed']]);
            }
            
            // Create onboarding checklist
            $checklistItems = [
                'Complete employment paperwork',
                'Set up email account',
                'Provide company ID and access cards',
                'Equipment setup (computer, phone, etc.)',
                'Orientation session',
                'Benefits enrollment',
                'IT security training'
            ];
            
            foreach ($checklistItems as $index => $task) {
                $dueDate = date('Y-m-d', strtotime("+$index days", strtotime($hireDate)));
                $stmt = $pdo->prepare("
                    INSERT INTO hr_onboarding_checklist (employee_id, task_name, due_date)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$employeeId, $task, $dueDate]);
            }
            
            // Preserve filter parameters when redirecting
            $search = $_POST['search'] ?? '';
            $department = $_POST['department'] ?? '';
            $statusFilter = $_POST['status_filter'] ?? '';
            
            $redirectUrl = "employees.php?status=added";
            if (!empty($search)) $redirectUrl .= "&search=" . urlencode($search);
            if (!empty($department)) $redirectUrl .= "&department=" . urlencode($department);
            if (!empty($statusFilter)) $redirectUrl .= "&status_filter=" . urlencode($statusFilter);
            
            header("Location: " . BASE_URL . "Module10/" . $redirectUrl);
        }
        exit;
    } catch (Exception $e) {
        $error = "Error saving employee: " . $e->getMessage();
    }
}

// Delete employee
if ($action === 'delete' && $id) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete related records
        $pdo->prepare("DELETE FROM hr_onboarding_checklist WHERE employee_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM hr_leave_balances WHERE employee_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM hr_employee_documents WHERE employee_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM hr_attendance WHERE employee_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM hr_leave_applications WHERE employee_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM hr_payroll WHERE employee_id = ?")->execute([$id]);
        
        // Delete employee
        $pdo->prepare("DELETE FROM hr_employees WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        
        // Preserve filter parameters when redirecting
        $search = $_GET['search'] ?? '';
        $department = $_GET['department'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';
        
        $redirectUrl = "employees.php?status=deleted";
        if (!empty($search)) $redirectUrl .= "&search=" . urlencode($search);
        if (!empty($department)) $redirectUrl .= "&department=" . urlencode($department);
        if (!empty($statusFilter)) $redirectUrl .= "&status_filter=" . urlencode($statusFilter);
        
        header("Location: " . BASE_URL . "Module10/" . $redirectUrl);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting employee: " . $e->getMessage();
    }
}

// Get employee data for editing
 $employee = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("
        SELECT e.*, p.title as position_name, d.name as department_name
        FROM hr_employees e
        LEFT JOIN hr_positions p ON e.position_id = p.id
        LEFT JOIN hr_departments d ON e.department_id = d.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get departments and positions for dropdowns
 $departments = $pdo->query("SELECT * FROM hr_departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
 $positions = $pdo->query("SELECT * FROM hr_positions ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Get all employees
 $search = $_GET['search'] ?? '';
 $departmentFilter = $_GET['department'] ?? '';
 $statusFilter = $_GET['status_filter'] ?? ''; // Changed from 'status' to 'status_filter'

 $sql = "
    SELECT e.*, p.title as position_name, d.name as department_name
    FROM hr_employees e
    LEFT JOIN hr_positions p ON e.position_id = p.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE 1
";

 $params = [];

if ($search) {
    // FIXED: Now searches by full name (first name + last name) as well as individual fields
    $sql .= " AND (
        e.first_name LIKE ? OR 
        e.last_name LIKE ? OR 
        CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR 
        e.employee_id LIKE ? OR 
        e.email LIKE ?
    )";
    $searchParam = "%$search%";
    // Push the same search parameter for each condition
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
}

if ($departmentFilter) {
    $sql .= " AND e.department_id = ?";
    $params[] = $departmentFilter;
}

if ($statusFilter) {
    $sql .= " AND e.status = ?";
    $params[] = $statusFilter;
}

 $sql .= " ORDER BY e.last_name, e.first_name";

 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
    <!-- Using official Font Awesome CDN instead of kit to avoid CORS issues -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        /* Main container compatible with sidebar */
        .main-container {
            margin-left: 18rem;
            padding: 2rem;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        /* Header styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.75rem;
        }
        
        /* Alert styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: opacity 0.5s ease;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        /* Card styles */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .card-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.25rem;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 2rem; /* Increased margin for better spacing */
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.75rem; /* Increased margin for better spacing */
            font-weight: 500;
            color: #495057;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1.25rem; /* Increased padding for better appearance */
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            /* Ensure placeholder text is fully visible */
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2c5aa0;
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
        }
        
        .form-row {
            display: flex;
            gap: 2.5rem; /* Significantly increased gap for better spacing */
            margin-bottom: 2rem; /* Increased margin for better spacing */
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        /* Button styles */
        .btn {
            padding: 0.875rem 1.5rem; /* Increased padding for better appearance */
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #2c5aa0;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e3d6f;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 0.6rem 1.2rem; /* Increased padding for better appearance */
            font-size: 0.8rem;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #ced4da;
            color: #495057;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
        }
        
        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 1rem;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5rem;
        }
        
        .close {
            font-size: 1.5rem;
            text-decoration: none;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .close:hover {
            color: #495057;
            background: #e9ecef;
        }
        
        .modal-body {
            padding: 2rem; /* Increased padding for better spacing */
        }
        
        /* Employee card styles */
        .employee-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .employee-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 1.5rem;
            display: flex;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #f0f0f0;
        }
        
        .employee-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .employee-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #2c5aa0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            margin-right: 1.25rem;
            flex-shrink: 0;
        }
        
        .employee-details {
            flex: 1;
        }
        
        .employee-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            color: #2c3e50;
        }
        
        .employee-position {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .employee-department {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .employee-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .employee-id {
            font-size: 0.8rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-on-leave {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-terminated {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .employee-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-left: 1rem;
        }
        
        /* Filter form styles */
        .filter-form {
            display: flex;
            gap: 2rem; /* Increased gap for better spacing between filter fields and buttons */
            align-items: flex-end;
        }
        
        .filter-form .form-row {
            display: flex;
            gap: 2rem; /* Increased gap for better spacing between filter fields */
            width: 100%;
        }
        
        .filter-form .form-group {
            flex: 1;
            margin-bottom: 0;
            padding-right: 1rem; /* Added right padding for extra space */
        }
        
        .filter-actions {
            display: flex;
            gap: 0.75rem; /* Increased gap between buttons */
            padding-left: 0.5rem; /* Added left padding for extra space */
        }
        
        /* No data message */
        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
            font-style: italic;
        }
        
        /* Form actions */
        .form-actions {
            margin-top: 2.5rem; /* Increased margin for better spacing */
            display: flex;
            gap: 1.5rem; /* Increased gap for better spacing */
            justify-content: flex-end;
            padding-top: 1rem; /* Added top padding for visual separation */
            border-top: 1px solid #e9ecef; /* Added border for visual separation */
        }
        
        /* Improved Modal Form Styles */
        .form-section {
            margin-bottom: 2.5rem; /* Increased margin for better spacing */
            padding: 2rem; /* Increased padding for better spacing */
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.75rem; /* Increased margin for better spacing */
            padding-bottom: 0.75rem; /* Increased padding for better spacing */
            border-bottom: 2px solid #2c5aa0;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 0.75rem; /* Increased margin for better spacing */
            color: #2c5aa0;
        }
        
        .form-group.required label:after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control.error {
            border-color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        /* Form field icons */
        .form-group-with-icon {
            position: relative;
        }
        
        .form-group-with-icon .form-control {
            padding-left: 2.75rem; /* Increased left padding for better spacing with icon */
        }
        
        .form-group-with-icon .form-icon {
            position: absolute;
            left: 1rem; /* Adjusted position for better spacing */
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Department and position field improvements */
        .department-position-container {
            display: flex;
            gap: 2.5rem; /* Increased gap for better spacing */
            margin-bottom: 1.75rem; /* Added margin for better spacing */
        }
        
        .department-field, .position-field {
            flex: 1;
        }
        
        /* Textarea styling */
        textarea.form-control {
            min-height: 80px; /* Increased minimum height for better appearance */
            resize: vertical; /* Allow vertical resizing */
            /* Ensure placeholder text is fully visible */
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }
        
        /* Search field styling */
        .search-field {
            position: relative;
        }
        
        .search-field .form-control {
            padding-left: 2.75rem; /* Increased left padding for icon */
        }
        
        .search-field .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .employee-list {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .employee-list {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form .form-row {
                flex-direction: column;
                gap: 1.5rem; /* Increased gap for mobile */
            }
            
            .form-row {
                flex-direction: column;
                gap: 1.5rem; /* Increased gap for mobile */
            }
            
            .department-position-container {
                flex-direction: column;
                gap: 1.5rem; /* Increased gap for mobile */
            }
            
            .modal-content {
                max-width: 95%;
            }
            
            .modal-body {
                padding: 1.5rem; /* Reduced padding for mobile */
            }
            
            .form-section {
                padding: 1.5rem; /* Reduced padding for mobile */
            }
        }
        
        @media (max-width: 576px) {
            .employee-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .employee-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .employee-actions {
                flex-direction: row;
                margin-left: 0;
                margin-top: 1rem;
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-actions .btn {
                width: 100%;
            }
        }
        
        /* Additional styles for better placeholder visibility */
        input::placeholder, textarea::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }
        
        /* Ensure input fields don't overflow their containers */
        .form-control {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Add proper spacing for department and position fields */
        .department-field .form-control,
        .position-field .form-control {
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <h1>Employee Management</h1>
            <div>
                <button class="btn btn-primary" onclick="window.location.href='<?php echo BASE_URL; ?>Module10/employees.php?action=add'">
                    <i class="fas fa-user-plus"></i> Add Employee
                </button>
            </div>
        </div>

        <!-- Only show status alerts if there's no error and status is explicitly set -->
        <?php if (isset($_GET['status']) && !isset($error) && in_array($_GET['status'], ['added', 'updated', 'deleted'])): ?>
            <div class="alert alert-<?php echo $_GET['status'] === 'added' || $_GET['status'] === 'updated' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $_GET['status'] === 'added' || $_GET['status'] === 'updated' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php 
                if ($_GET['status'] === 'added') echo "Employee added successfully!";
                elseif ($_GET['status'] === 'updated') echo "Employee updated successfully!";
                elseif ($_GET['status'] === 'deleted') echo "Employee deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h2>Filter Employees</h2>
            </div>
            <div class="card-content">
                <form method="get" class="filter-form">
                    <div class="form-row">
                        <div class="form-group search-field">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, ID, or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="department" class="form-control">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $departmentFilter == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="status_filter" class="form-control"> <!-- Changed from 'status' to 'status_filter' -->
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="on-leave" <?php echo $statusFilter === 'on-leave' ? 'selected' : ''; ?>>On Leave</option>
                                <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="terminated" <?php echo $statusFilter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="<?php echo BASE_URL; ?>Module10/employees.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employee List -->
        <div class="card">
            <div class="card-header">
                <h2>Employee List</h2>
                <span><?php echo count($employees); ?> employees found</span>
            </div>
            <div class="card-content">
                <?php if (count($employees) > 0): ?>
                    <div class="employee-list">
                        <?php foreach ($employees as $emp): ?>
                            <div class="employee-card">
                                <div class="employee-avatar">
                                    <?php echo substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1); ?>
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                    <div class="employee-position"><?php echo htmlspecialchars($emp['position_name']); ?></div>
                                    <div class="employee-department"><?php echo htmlspecialchars($emp['department_name']); ?></div>
                                    <div class="employee-meta">
                                        <span class="employee-id"><?php echo htmlspecialchars($emp['employee_id']); ?></span>
                                        <span class="status-badge status-<?php echo $emp['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $emp['status'])); ?></span>
                                    </div>
                                </div>
                                <div class="employee-actions">
                                    <a href="<?php echo BASE_URL; ?>Module10/employee_view.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>Module10/employees.php?action=edit&id=<?php echo $emp['id']; ?>" class="btn btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>Module10/employees.php?action=delete&id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this employee?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No employees found matching your criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <?php if ($action === 'add' || ($action === 'edit' && $employee)): ?>
    <div class="modal active" id="employeeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $action === 'add' ? 'Add New Employee' : 'Edit Employee'; ?></h2>
                <a href="<?php echo BASE_URL; ?>Module10/employees.php" class="close">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            <div class="modal-body">
                <form method="post" action="<?php echo BASE_URL; ?>Module10/employees.php<?php echo $action === 'edit' ? '?id=' . $id : ''; ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <!-- Hidden fields to preserve filter parameters -->
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="department" value="<?php echo htmlspecialchars($departmentFilter); ?>">
                    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($statusFilter); ?>"> <!-- Changed from 'status_filter' -->
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user"></i> Basic Information
                        </div>
                        <div class="form-row">
                            <div class="form-group required">
                                <label>Employee ID</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-id-badge form-icon"></i>
                                    <input type="text" name="employee_id" class="form-control" placeholder="Enter employee ID" value="<?php echo $employee['employee_id'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label>First Name</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-user form-icon"></i>
                                    <input type="text" name="first_name" class="form-control" placeholder="Enter first name" value="<?php echo $employee['first_name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label>Last Name</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-user form-icon"></i>
                                    <input type="text" name="last_name" class="form-control" placeholder="Enter last name" value="<?php echo $employee['last_name'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Middle Name</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-user form-icon"></i>
                                    <input type="text" name="middle_name" class="form-control" placeholder="Enter middle name (optional)" value="<?php echo $employee['middle_name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="form-group required">
                                <label>Email</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-envelope form-icon"></i>
                                    <input type="email" name="email" class="form-control" placeholder="Enter email address" value="<?php echo $employee['email'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-phone form-icon"></i>
                                    <input type="text" name="phone" class="form-control" placeholder="Enter phone number (optional)" value="<?php echo $employee['phone'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i> Additional Information
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <div class="form-group-with-icon">
                                <i class="fas fa-map-marker-alt form-icon"></i>
                                <textarea name="address" class="form-control" placeholder="Enter complete address" rows="2"><?php echo $employee['address'] ?? ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Birth Date</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-birthday-cake form-icon"></i>
                                    <input type="date" name="birth_date" class="form-control" placeholder="Select birth date" value="<?php echo $employee['birth_date'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="form-group required">
                                <label>Hire Date</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-calendar-alt form-icon"></i>
                                    <input type="date" name="hire_date" class="form-control" placeholder="Select hire date" value="<?php echo $employee['hire_date'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-briefcase"></i> Employment Details
                        </div>
                        <div class="department-position-container">
                            <div class="form-group department-field required">
                                <label>Department</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-building form-icon"></i>
                                    <input type="text" name="department_name" class="form-control" placeholder="Enter or select department" value="<?php echo $employee['department_name'] ?? ''; ?>" required list="departmentList">
                                    <datalist id="departmentList">
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['name']); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>
                            <div class="form-group position-field required">
                                <label>Position</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-user-tie form-icon"></i>
                                    <input type="text" name="position_name" class="form-control" placeholder="Enter or select position" value="<?php echo $employee['position_name'] ?? ''; ?>" required list="positionList">
                                    <datalist id="positionList">
                                        <?php foreach ($positions as $pos): ?>
                                            <option value="<?php echo htmlspecialchars($pos['title']); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group required">
                                <label>Employment Type</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-file-contract form-icon"></i>
                                    <select name="employment_type" class="form-control" required>
                                        <option value="">Select employment type</option>
                                        <option value="full-time" <?php echo (isset($employee) && $employee['employment_type'] === 'full-time') ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="part-time" <?php echo (isset($employee) && $employee['employment_type'] === 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="contract" <?php echo (isset($employee) && $employee['employment_type'] === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                        <option value="probationary" <?php echo (isset($employee) && $employee['employment_type'] === 'probationary') ? 'selected' : ''; ?>>Probationary</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label>Salary</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-dollar-sign form-icon"></i>
                                    <input type="number" name="salary" class="form-control" placeholder="Enter salary amount" step="0.01" value="<?php echo $employee['salary'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <div class="form-group-with-icon">
                                <i class="fas fa-info-circle form-icon"></i>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo (!isset($employee) || $employee['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="on-leave" <?php echo (isset($employee) && $employee['status'] === 'on-leave') ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="suspended" <?php echo (isset($employee) && $employee['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="terminated" <?php echo (isset($employee) && $employee['status'] === 'terminated') ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $action === 'add' ? 'Add Employee' : 'Update Employee'; ?>
                        </button>
                        <a href="<?php echo BASE_URL; ?>Module10/employees.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    // Fade out effect
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    
                    // Remove from DOM after fade out
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                });
            }, 3000); // 5 seconds
        });
    </script>
</body>
</html>
<?php
require '../db.php';
require '../shared/config.php';

// Get employee ID from URL
 $id = $_GET['id'] ?? null;

// FIXED: Check if ID is provided and redirect only if necessary
if (!$id) {
    header("Location: " . BASE_URL . "Module10/employees.php");
    exit;
}

// Get employee data
 $stmt = $pdo->prepare("
    SELECT e.*, p.title as position_name, d.name as department_name
    FROM hr_employees e
    LEFT JOIN hr_positions p ON e.position_id = p.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE e.id = ?
");
 $stmt->execute([$id]);
 $employee = $stmt->fetch(PDO::FETCH_ASSOC);

// FIXED: Check if employee exists and redirect only if necessary
if (!$employee) {
    header("Location: " . BASE_URL . "Module10/employees.php");
    exit;
}

// Get employee documents
 $stmt = $pdo->prepare("
    SELECT * FROM hr_employee_documents 
    WHERE employee_id = ? 
    ORDER BY uploaded_at DESC
");
 $stmt->execute([$id]);
 $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get leave balances
 $currentYear = date('Y');
 $stmt = $pdo->prepare("
    SELECT lb.*, lt.name as leave_type_name 
    FROM hr_leave_balances lb
    JOIN hr_leave_types lt ON lb.leave_type_id = lt.id
    WHERE lb.employee_id = ? AND lb.year = ?
");
 $stmt->execute([$id, $currentYear]);
 $leaveBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get onboarding checklist
 $stmt = $pdo->prepare("
    SELECT * FROM hr_onboarding_checklist 
    WHERE employee_id = ? 
    ORDER BY due_date
");
 $stmt->execute([$id]);
 $checklist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent attendance
 $stmt = $pdo->prepare("
    SELECT * FROM hr_attendance 
    WHERE employee_id = ? 
    ORDER BY attendance_date DESC 
    LIMIT 10
");
 $stmt->execute([$id]);
 $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Details - <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
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
        
        /* Profile header */
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #2c5aa0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 3rem;
            margin-right: 2rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .profile-position {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .profile-department {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .profile-meta {
            display: flex;
            gap: 1rem;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .profile-meta-item i {
            color: #2c5aa0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
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
        
        /* Info section */
        .info-section {
            margin-bottom: 1.5rem;
        }
        
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 500;
            color: #2c3e50;
        }
        
        /* Button styles */
        .btn {
            padding: 0.75rem 1.25rem;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #ced4da;
            color: #495057;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 1.5rem;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #2c5aa0;
        }
        
        .tab.active {
            color: #2c5aa0;
            border-bottom-color: #2c5aa0;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Table styles */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table th {
            font-weight: 600;
            color: #2c3e50;
            background-color: #f8f9fa;
        }
        
        .table tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Checklist */
        .checklist-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .checklist-checkbox {
            margin-right: 1rem;
        }
        
        .checklist-text {
            flex: 1;
        }
        
        .checklist-date {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* Document list */
        .document-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .document-icon {
            margin-right: 1rem;
            color: #2c5aa0;
            font-size: 1.2rem;
        }
        
        .document-info {
            flex: 1;
        }
        
        .document-name {
            font-weight: 500;
        }
        
        .document-date {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .profile-meta {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <h1>Employee Details</h1>
            <div>
                <a href="<?php echo BASE_URL; ?>Module10/employees.php?action=edit&id=<?php echo $employee['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Employee
                </a>
                <a href="<?php echo BASE_URL; ?>Module10/employees.php" class="btn btn-outline" style="margin-left: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Profile Header -->
        <div class="card">
            <div class="card-content">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1); ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></div>
                        <div class="profile-position"><?php echo htmlspecialchars($employee['position_name']); ?></div>
                        <div class="profile-department"><?php echo htmlspecialchars($employee['department_name']); ?></div>
                        <div class="profile-meta">
                            <div class="profile-meta-item">
                                <i class="fas fa-id-badge"></i>
                                <span><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Hired: <?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fas fa-briefcase"></i>
                                <span><?php echo ucfirst(str_replace('-', ' ', $employee['employment_type'])); ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <span class="status-badge status-<?php echo $employee['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $employee['status'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('personal')">Personal Information</div>
            <div class="tab" onclick="showTab('employment')">Employment Details</div>
            <div class="tab" onclick="showTab('leave')">Leave Balances</div>
            <div class="tab" onclick="showTab('documents')">Documents</div>
            <div class="tab" onclick="showTab('attendance')">Attendance</div>
            <div class="tab" onclick="showTab('onboarding')">Onboarding</div>
        </div>

        <!-- Personal Information Tab -->
        <div id="personal" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h2>Personal Information</h2>
                </div>
                <div class="card-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['middle_name'] . ' ' . $employee['last_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Employee ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['phone'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['address'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Birth Date</div>
                            <div class="info-value"><?php echo $employee['birth_date'] ? date('M d, Y', strtotime($employee['birth_date'])) : 'Not provided'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment Details Tab -->
        <div id="employment" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Employment Details</h2>
                </div>
                <div class="card-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Position</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['position_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['department_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Employment Type</div>
                            <div class="info-value"><?php echo ucfirst(str_replace('-', ' ', $employee['employment_type'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Hire Date</div>
                            <div class="info-value"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Salary</div>
                            <div class="info-value">$<?php echo number_format($employee['salary'], 2); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo $employee['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $employee['status'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Balances Tab -->
        <div id="leave" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Leave Balances (<?php echo $currentYear; ?>)</h2>
                </div>
                <div class="card-content">
                    <?php if (count($leaveBalances) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Total Days</th>
                                    <th>Used Days</th>
                                    <th>Remaining Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaveBalances as $balance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($balance['leave_type_name']); ?></td>
                                        <td><?php echo $balance['total_days']; ?></td>
                                        <td><?php echo $balance['used_days']; ?></td>
                                        <td><?php echo $balance['total_days'] - $balance['used_days']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No leave balance information available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents Tab -->
        <div id="documents" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Employee Documents</h2>
                </div>
                <div class="card-content">
                    <?php if (count($documents) > 0): ?>
                        <?php foreach ($documents as $document): ?>
                            <div class="document-item">
                                <div class="document-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="document-info">
                                    <div class="document-name"><?php echo htmlspecialchars($document['document_name']); ?></div>
                                    <div class="document-date">Uploaded: <?php echo date('M d, Y', strtotime($document['uploaded_at'])); ?></div>
                                    <?php if ($document['expiry_date']): ?>
                                        <div class="document-date">Expires: <?php echo date('M d, Y', strtotime($document['expiry_date'])); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>Module10/download_document.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No documents available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Attendance Tab -->
        <div id="attendance" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Recent Attendance</h2>
                </div>
                <div class="card-content">
                    <?php if (count($attendance) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : 'N/A'; ?></td>
                                        <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : 'N/A'; ?></td>
                                        <td><?php echo $record['hours_worked']; ?></td>
                                        <td><?php echo ucfirst($record['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No attendance records available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onboarding Tab -->
        <div id="onboarding" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Onboarding Checklist</h2>
                </div>
                <div class="card-content">
                    <?php if (count($checklist) > 0): ?>
                        <?php foreach ($checklist as $item): ?>
                            <div class="checklist-item">
                                <div class="checklist-checkbox">
                                    <?php if ($item['completed']): ?>
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                    <?php else: ?>
                                        <i class="far fa-circle" style="color: #6c757d;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="checklist-text">
                                    <?php echo htmlspecialchars($item['task_name']); ?>
                                </div>
                                <div class="checklist-date">
                                    Due: <?php echo date('M d, Y', strtotime($item['due_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No onboarding checklist available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
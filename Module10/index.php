<?php
require '../db.php';
require '../shared/config.php';

// Get HR statistics
 $employeeCount = $pdo->query("SELECT COUNT(*) FROM hr_employees WHERE status = 'active'")->fetchColumn();
 $pendingLeaves = $pdo->query("SELECT COUNT(*) FROM hr_leave_applications WHERE status = 'pending'")->fetchColumn();

// Get recent hires
 $recentHires = $pdo->query("
    SELECT e.employee_id, e.first_name, e.last_name, p.title, d.name as department, e.hire_date
    FROM hr_employees e
    JOIN hr_positions p ON e.position_id = p.id
    JOIN hr_departments d ON e.department_id = d.id
    WHERE e.status = 'active'
    ORDER BY e.hire_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get pending leave applications
 $pendingLeavesList = $pdo->query("
    SELECT la.id, e.employee_id, e.first_name, e.last_name, lt.name as leave_type, 
           la.start_date, la.end_date, la.total_days
    FROM hr_leave_applications la
    JOIN hr_employees e ON la.employee_id = e.id
    JOIN hr_leave_types lt ON la.leave_type_id = lt.id
    WHERE la.status = 'pending'
    ORDER BY la.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get payroll status for current month
 $currentMonth = date('Y-m');
 $payrollStatus = $pdo->prepare("
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
        SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as released
    FROM hr_payroll 
    WHERE pay_period = ?
");
 $payrollStatus->execute([$currentMonth]);
 $payrollStatus = $payrollStatus->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Human Resources Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        /* Dashboard styles compatible with sidebar */
        .main-container {
            margin-left: 18rem;
            padding: 2rem;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.75rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            background: #f0f7ff;
            color: #2c5aa0;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            font-size: 1.5rem;
        }
        
        .stat-info h3 {
            font-size: 2rem;
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
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
        }
        
        .card-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.25rem;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
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
        
        .btn {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #1e3d6f;
        }
        
        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .btn-primary {
            background: #2c5aa0;
        }
        
        .btn-primary:hover {
            background: #1e3d6f;
        }
        
        .payroll-status {
            margin-bottom: 1.5rem;
        }
        
        .payroll-progress {
            margin-top: 1rem;
        }
        
        .progress-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }
        
        .progress-fill {
            height: 100%;
            background: #2c5aa0;
            border-radius: 5px;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .quick-action {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #2c3e50;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
        }
        
        .quick-action:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }
        
        .quick-action i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #2c5aa0;
        }
        
        .quick-action span {
            font-size: 0.9rem;
        }
        
        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 1.5rem;
            margin: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="main-container">
        <div class="dashboard-header">
            <h1>Human Resources Management</h1>
            <div>
                <button class="btn btn-primary" onclick="window.location.href='<?php echo BASE_URL; ?>Module10/employees.php'">Manage Employees</button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $employeeCount; ?></h3>
                    <p>Total Employees</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pendingLeaves; ?></h3>
                    <p>Pending Leaves</p>
                </div>
            </div>
        </div>

        <!-- Payroll Status Card -->
        <div class="card payroll-status">
            <div class="card-header">
                <h2>Payroll Status - <?php echo date('F Y'); ?></h2>
                <a href="<?php echo BASE_URL; ?>Module10/payroll.php" class="btn btn-sm">Manage Payroll</a>
            </div>
            <div class="card-content">
                <div class="payroll-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($payrollStatus['total_employees'] > 0) ? ($payrollStatus['released'] / $payrollStatus['total_employees'] * 100) : 0; ?>%"></div>
                    </div>
                    <div class="progress-stats">
                        <span>Processed: <?php echo $payrollStatus['processed']; ?></span>
                        <span>Released: <?php echo $payrollStatus['released']; ?></span>
                        <span>Total: <?php echo $payrollStatus['total_employees']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Recent Hires -->
            <div class="card">
                <div class="card-header">
                    <h2>Recent Hires</h2>
                    <a href="<?php echo BASE_URL; ?>Module10/employees.php" class="btn btn-sm">View All</a>
                </div>
                <div class="card-content">
                    <?php if (count($recentHires) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Hire Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentHires as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['title']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No recent hires found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Leave Applications -->
            <div class="card">
                <div class="card-header">
                    <h2>Pending Leave Applications</h2>
                    <a href="<?php echo BASE_URL; ?>Module10/leave.php" class="btn btn-sm">View All</a>
                </div>
                <div class="card-content">
                    <?php if (count($pendingLeavesList) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingLeavesList as $leave): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                        <td><?php echo $leave['total_days']; ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>Module10/leave.php?action=view&id=<?php echo $leave['id']; ?>" class="btn btn-sm">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No pending leave applications.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="<?php echo BASE_URL; ?>Module10/employees.php?action=add" class="quick-action">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Employee</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>Module10/recruitment.php" class="quick-action">
                            <i class="fas fa-briefcase"></i>
                            <span>Post Job</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>Module10/payroll.php" class="quick-action">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Process Payroll</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>Module10/attendance.php" class="quick-action">
                            <i class="fas fa-clock"></i>
                            <span>Mark Attendance</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
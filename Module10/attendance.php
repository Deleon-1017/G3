<?php
// attendance.php
require '../db.php';
require '../shared/config.php';

// Handle actions
 $action = $_GET['action'] ?? '';
 $id = $_GET['id'] ?? null;

// Mark attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $employeeId = $_POST['employee_id'];
    $attendanceDate = $_POST['attendance_date'];
    $timeIn = $_POST['time_in'] ?? null;
    $timeOut = $_POST['time_out'] ?? null;
    $breakIn = $_POST['break_in'] ?? null;
    $breakOut = $_POST['break_out'] ?? null;
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        // Calculate hours worked
        $hoursWorked = 0;
        if ($timeIn && $timeOut && $status === 'present') {
            $timeInObj = new DateTime($timeIn);
            $timeOutObj = new DateTime($timeOut);
            $interval = $timeInObj->diff($timeOutObj);
            $hoursWorked = $interval->h + ($interval->i / 60);
            
            // Subtract break time if provided
            if ($breakIn && $breakOut) {
                $breakInObj = new DateTime($breakIn);
                $breakOutObj = new DateTime($breakOut);
                $breakInterval = $breakInObj->diff($breakOutObj);
                $breakHours = $breakInterval->h + ($breakInterval->i / 60);
                $hoursWorked -= $breakHours;
            }
        }
        
        // Check if attendance record already exists
        $stmt = $pdo->prepare("
            SELECT id FROM hr_attendance 
            WHERE employee_id = ? AND attendance_date = ?
        ");
        $stmt->execute([$employeeId, $attendanceDate]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE hr_attendance 
                SET time_in = ?, time_out = ?, break_in = ?, break_out = ?, status = ?, notes = ?, hours_worked = ?
                WHERE id = ?
            ");
            $stmt->execute([$timeIn, $timeOut, $breakIn, $breakOut, $status, $notes, $hoursWorked, $existing['id']]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO hr_attendance 
                (employee_id, attendance_date, time_in, time_out, break_in, break_out, status, notes, hours_worked)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$employeeId, $attendanceDate, $timeIn, $timeOut, $breakIn, $breakOut, $status, $notes, $hoursWorked]);
        }
        
        header("Location: attendance.php?status=marked");
        exit;
    } catch (Exception $e) {
        $error = "Error marking attendance: " . $e->getMessage();
    }
}

// Get all employees
 $employees = $pdo->query("
    SELECT e.id, e.employee_id, e.first_name, e.last_name, d.name as department
    FROM hr_employees e
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE e.status = 'active'
    ORDER BY e.last_name, e.first_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get today's attendance
 $today = date('Y-m-d');
 $todayAttendance = $pdo->prepare("
    SELECT a.*, e.employee_id, e.first_name, e.last_name, d.name as department
    FROM hr_attendance a
    JOIN hr_employees e ON a.employee_id = e.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE a.attendance_date = ?
    ORDER BY e.last_name, e.first_name
");
 $todayAttendance->execute([$today]);
 $todayAttendance = $todayAttendance->fetchAll(PDO::FETCH_ASSOC);

// Get attendance summary for the current month
 $currentMonth = date('Y-m');
 $attendanceSummary = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
    FROM hr_attendance
    WHERE attendance_date LIKE ?
");
 $attendanceSummary->execute([$currentMonth . '%']);
 $attendanceSummary = $attendanceSummary->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --danger-color: #b5179e;
            --light-color: #f8f9fa;
            --dark-color: #2d3748;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            margin-left: 18rem;
            padding: 2rem;
            max-width: 1400px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: var(--dark-color);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--dark-color);
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #f8fafc, #f1f5f9);
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-content {
            padding: 1.5rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .summary-item {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid #e2e8f0;
        }

        .summary-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .summary-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .summary-item:nth-child(1) .summary-icon {
            color: var(--primary-color);
        }

        .summary-item:nth-child(2) .summary-icon {
            color: var(--success-color);
        }

        .summary-item:nth-child(3) .summary-icon {
            color: var(--warning-color);
        }

        .summary-item:nth-child(4) .summary-icon {
            color: var(--danger-color);
        }

        .summary-item:nth-child(5) .summary-icon {
            color: #8b5cf6;
        }

        .summary-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #64748b;
            font-size: 1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1rem;
        }

        /* Fix for form controls to prevent overflow */
        .form-control {
            width: 100%;
            padding: 0.875rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f8fafc;
            letter-spacing: 0.5px;
            box-sizing: border-box; /* Ensures padding doesn't affect width */
            max-width: 100%; /* Prevents overflow */
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .form-control::placeholder {
            color: #a0aec0;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        /* Increase gap between form elements */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem; /* Increased from 1.5rem to 2rem */
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            padding: 1rem;
            text-align: center; /* Center all table data */
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--dark-color);
            position: sticky;
            top: 0;
        }

        .table tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .status-present {
            background: #d1fae5;
            color: #065f46;
        }

        .status-absent {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-late {
            background: #fef3c7;
            color: #92400e;
        }

        .status-leave {
            background: #dbeafe;
            color: #1e40af;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #f8fafc, #f1f5f9);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.75rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Ensure modal content doesn't overflow */
        .modal-body {
            padding: 2rem;
            max-width: 100%;
            overflow-x: hidden; /* Prevent horizontal overflow */
        }

        .close {
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            transition: var(--transition);
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            background: #f1f5f9;
            color: var(--dark-color);
        }

        /* Form sections like employee modal */
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            overflow: hidden; /* Prevent content overflow */
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
        }

        .form-section-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        /* Fix for form group with icon */
        .form-group-with-icon {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group-with-icon .form-control {
            padding-left: 2.75rem;
        }

        /* Fix for textarea spacing */
        .form-group-with-icon textarea.form-control {
            padding-top: 1rem;
            padding-bottom: 1rem;
            min-height: 100px;
            resize: vertical;
            margin-top: 0.5rem; /* Add space above textarea */
        }

        .form-group-with-icon .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 1; /* Ensure icon stays above form control */
        }

        /* Special positioning for textarea icon */
        .form-group-with-icon textarea.form-control ~ .form-icon {
            top: 1.2rem; /* Adjust for textarea */
            transform: none;
        }

        /* Fix for time inputs specifically */
        input[type="time"] {
            height: 3rem; /* Consistent height */
        }

        /* Ensure select elements have consistent height */
        select.form-control {
            height: 3rem; /* Consistent height */
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .summary-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .table {
                font-size: 0.875rem;
            }
            
            .table th, .table td {
                padding: 0.75rem 0.5rem;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .modal-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-check"></i> Attendance Management</h1>
            <div>
                <button class="btn btn-primary" onclick="document.getElementById('markAttendanceModal').classList.add('active')">
                    <i class="fas fa-plus"></i> Mark Attendance
                </button>
                <a href="attendance_logs.php" class="btn btn-secondary">
                    <i class="fas fa-history"></i> View Logs
                </a>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo $_GET['status'] === 'marked' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $_GET['status'] === 'marked' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php 
                if ($_GET['status'] === 'marked') echo "Attendance marked successfully!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Attendance Summary -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> Monthly Summary - <?php echo date('F Y'); ?></h2>
            </div>
            <div class="card-content">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-calendar-days"></i></div>
                        <div class="summary-value"><?php echo $attendanceSummary['total_days']; ?></div>
                        <div class="summary-label">Total Days</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-user-check"></i></div>
                        <div class="summary-value"><?php echo $attendanceSummary['present_days']; ?></div>
                        <div class="summary-label">Present</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-user-times"></i></div>
                        <div class="summary-value"><?php echo $attendanceSummary['absent_days']; ?></div>
                        <div class="summary-label">Absent</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-clock"></i></div>
                        <div class="summary-value"><?php echo $attendanceSummary['late_days']; ?></div>
                        <div class="summary-label">Late</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-plane"></i></div>
                        <div class="summary-value"><?php echo $attendanceSummary['leave_days']; ?></div>
                        <div class="summary-label">On Leave</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-calendar-day"></i> Today's Attendance - <?php echo date('F d, Y'); ?></h2>
            </div>
            <div class="card-content">
                <?php if (count($todayAttendance) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Time In</th>
                                    <th>Break In</th>
                                    <th>Break Out</th>
                                    <th>Time Out</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAttendance as $attendance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attendance['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attendance['department']); ?></td>
                                        <td><?php echo $attendance['time_in'] ? date('h:i A', strtotime($attendance['time_in'])) : '-'; ?></td>
                                        <td><?php echo $attendance['break_in'] ? date('h:i A', strtotime($attendance['break_in'])) : '-'; ?></td>
                                        <td><?php echo $attendance['break_out'] ? date('h:i A', strtotime($attendance['break_out'])) : '-'; ?></td>
                                        <td><?php echo $attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : '-'; ?></td>
                                        <td><?php echo $attendance['hours_worked'] ? round($attendance['hours_worked'], 2) . ' hrs' : '-'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $attendance['status']; ?>">
                                                <?php echo ucfirst($attendance['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>No attendance records for today.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mark Attendance Modal -->
    <div id="markAttendanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Mark Attendance</h2>
                <div class="close" onclick="document.getElementById('markAttendanceModal').classList.remove('active')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="mark_attendance" value="1">
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user-clock"></i> Attendance Information
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="employee_id">Employee</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-user form-icon"></i>
                                    <select name="employee_id" id="employee_id" class="form-control" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> (<?php echo htmlspecialchars($emp['employee_id']); ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="attendance_date">Date</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-calendar form-icon"></i>
                                    <input type="date" name="attendance_date" id="attendance_date" class="form-control" value="<?php echo $today; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-clock"></i> Time Details
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="time_in">Time In</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-sign-in-alt form-icon"></i>
                                    <input type="time" name="time_in" id="time_in" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="break_in">Break In</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-coffee form-icon"></i>
                                    <input type="time" name="break_in" id="break_in" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="break_out">Break Out</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-coffee form-icon"></i>
                                    <input type="time" name="break_out" id="break_out" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="time_out">Time Out</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-sign-out-alt form-icon"></i>
                                    <input type="time" name="time_out" id="time_out" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <div class="form-group-with-icon">
                                    <i class="fas fa-info-circle form-icon"></i>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                        <option value="leave">On Leave</option>
                                        <option value="holiday">Holiday</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-sticky-note"></i> Additional Information
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <div class="form-group-with-icon">
                                <textarea name="notes" id="notes" class="form-control" placeholder="Add any additional notes here..."></textarea>
                                <i class="fas fa-edit form-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Mark Attendance
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('markAttendanceModal').classList.remove('active')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>
<?php
// attendance_logs.php
require '../db.php';
require '../shared/config.php';

// Get filter parameters
 $employeeId = $_GET['employee_id'] ?? '';
 $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to first day of current month
 $endDate = $_GET['end_date'] ?? date('Y-m-d'); // Default to today

// Build the query for daily attendance summary with break times
 $sql = "
    SELECT 
        e.employee_id,
        CONCAT(e.first_name, ' ', e.last_name) AS name,
        d.name AS department,
        a.attendance_date AS date,
        a.time_in,
        a.break_in,
        a.break_out,
        a.time_out,
        a.hours_worked,
        a.status
    FROM hr_attendance a
    JOIN hr_employees e ON a.employee_id = e.id
    LEFT JOIN hr_departments d ON e.department_id = d.id
    WHERE 1
";

 $params = [];

if ($employeeId) {
    $sql .= " AND a.employee_id = ?";
    $params[] = $employeeId;
}

if ($startDate) {
    $sql .= " AND a.attendance_date >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND a.attendance_date <= ?";
    $params[] = $endDate;
}

 $sql .= " ORDER BY a.attendance_date DESC, name";

 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $attendanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all employees for dropdown
 $employees = $pdo->query("
    SELECT id, employee_id, first_name, last_name 
    FROM hr_employees 
    WHERE status = 'active'
    ORDER BY last_name, first_name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Logs</title>
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

        .filter-form {
            display: flex;
            gap: 3rem; /* Increased gap for more spacing */
            align-items: flex-end;
            margin-bottom: 1.5rem;
        }

        .filter-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-left: 1rem;
            padding-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-item {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid #e2e8f0;
        }

        .stats-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .stats-item:nth-child(1) .stats-icon {
            color: var(--primary-color);
        }

        .stats-item:nth-child(2) .stats-icon {
            color: var(--success-color);
        }

        .stats-item:nth-child(3) .stats-icon {
            color: var(--warning-color);
        }

        .stats-item:nth-child(4) .stats-icon {
            color: var(--danger-color);
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 1.5rem;
            }
            
            .filter-actions {
                margin-left: 0;
                margin-top: 0;
                padding-top: 0;
                justify-content: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-history"></i> Attendance Logs</h1>
            <div>
                <a href="attendance.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Attendance
                </a>
            </div>
        </div>

        <!-- Statistics Summary -->
        <div class="stats-grid">
            <div class="stats-item">
                <div class="stats-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stats-value"><?php echo count(array_unique(array_column($attendanceLogs, 'date'))); ?></div>
                <div class="stats-label">Total Days</div>
            </div>
            <div class="stats-item">
                <div class="stats-icon"><i class="fas fa-user-check"></i></div>
                <div class="stats-value"><?php echo count(array_filter($attendanceLogs, function($log) { return $log['status'] === 'present'; })); ?></div>
                <div class="stats-label">Present Days</div>
            </div>
            <div class="stats-item">
                <div class="stats-icon"><i class="fas fa-user-times"></i></div>
                <div class="stats-value"><?php echo count(array_filter($attendanceLogs, function($log) { return $log['status'] === 'absent'; })); ?></div>
                <div class="stats-label">Absent Days</div>
            </div>
            <div class="stats-item">
                <div class="stats-icon"><i class="fas fa-clock"></i></div>
                <div class="stats-value"><?php echo count(array_filter($attendanceLogs, function($log) { return $log['status'] === 'late'; })); ?></div>
                <div class="stats-label">Late Days</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filter Logs</h2>
            </div>
            <div class="card-content">
                <form method="get" class="filter-form">
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control">
                            <option value="">All Employees</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo ($employeeId == $emp['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="attendance_logs.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Logs -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list-alt"></i> Attendance History</h2>
            </div>
            <div class="card-content">
                <?php if (count($attendanceLogs) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Break In</th>
                                    <th>Break Out</th>
                                    <th>Time Out</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($log['name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['department']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($log['date'])); ?></td>
                                        <td><?php echo $log['time_in'] ? date('h:i A', strtotime($log['time_in'])) : '-'; ?></td>
                                        <td><?php echo $log['break_in'] ? date('h:i A', strtotime($log['break_in'])) : '-'; ?></td>
                                        <td><?php echo $log['break_out'] ? date('h:i A', strtotime($log['break_out'])) : '-'; ?></td>
                                        <td><?php echo $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '-'; ?></td>
                                        <td><?php echo $log['hours_worked'] ? round($log['hours_worked'], 2) . ' hrs' : '-'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $log['status']; ?>">
                                                <?php echo ucfirst($log['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No attendance records found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
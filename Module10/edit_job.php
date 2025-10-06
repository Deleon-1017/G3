<?php
require '../db.php';
require '../shared/config.php';

 $jobId = $_GET['id'] ?? null;

if (!$jobId) {
    header("Location: recruitment.php");
    exit;
}

// Get job details
 $stmt = $pdo->prepare("SELECT * FROM hr_job_postings WHERE id = ?");
 $stmt->execute([$jobId]);
 $job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header("Location: recruitment.php");
    exit;
}

// Handle job update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $departmentId = $_POST['department_id'];
    $positionId = $_POST['position_id'];
    $requirements = $_POST['requirements'];
    $salaryRange = $_POST['salary_range'];
    $employmentType = $_POST['employment_type'];
    $closingDate = $_POST['closing_date'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE hr_job_postings 
            SET title = ?, description = ?, department_id = ?, position_id = ?, 
                requirements = ?, salary_range = ?, employment_type = ?, 
                closing_date = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $title, $description, $departmentId, $positionId, 
            $requirements, $salaryRange, $employmentType, 
            $closingDate, $status, $jobId
        ]);
        
        header("Location: recruitment.php?status=updated");
        exit;
    } catch (Exception $e) {
        $error = "Error updating job: " . $e->getMessage();
    }
}

// Get departments and positions for dropdowns
 $departments = $pdo->query("SELECT * FROM hr_departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
 $positions = $pdo->query("SELECT * FROM hr_positions ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Job Posting</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .main-content {
            margin-left: 18rem;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #2c3e50;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Edit Job Posting</h1>
            <div>
                <a href="<?php echo BASE_URL; ?>Module10/recruitment.php" class="btn btn-primary">Back to Recruitment</a>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success">
                Job updated successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Edit Job Posting</h2>
            </div>
            <div class="card-content">
                <form method="post">
                    <input type="hidden" name="update_job" value="1">
                    
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $job['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Position</label>
                            <select name="position_id" class="form-control" required>
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo $pos['id']; ?>" <?php echo $job['position_id'] == $pos['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pos['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Employment Type</label>
                            <select name="employment_type" class="form-control" required>
                                <option value="full-time" <?php echo $job['employment_type'] === 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                                <option value="part-time" <?php echo $job['employment_type'] === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                                <option value="contract" <?php echo $job['employment_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="open" <?php echo $job['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                <option value="on-hold" <?php echo $job['status'] === 'on-hold' ? 'selected' : ''; ?>>On Hold</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Salary Range</label>
                            <input type="text" name="salary_range" class="form-control" value="<?php echo htmlspecialchars($job['salary_range']); ?>" placeholder="e.g., 30000 - 50000">
                        </div>
                        <div class="form-group">
                            <label>Closing Date</label>
                            <input type="date" name="closing_date" class="form-control" value="<?php echo $job['closing_date']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Job Description</label>
                        <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Requirements</label>
                        <textarea name="requirements" class="form-control" rows="3"><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Job</button>
                        <a href="<?php echo BASE_URL; ?>Module10/recruitment.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
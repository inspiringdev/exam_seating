<?php
require_once 'config.php';
requireLogin();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id > 0) {
    $exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
    
    if ($exam) {
        // Get students with exact matching
        $exact_match = $conn->query("
            SELECT * FROM students 
            WHERE department = '{$exam['department']}' 
            AND semester = {$exam['semester']}
        ");
        
        // Get students with trimmed/case-insensitive matching
        $loose_match = $conn->query("
            SELECT * FROM students 
            WHERE TRIM(LOWER(department)) = TRIM(LOWER('{$exam['department']}'))
            AND semester = {$exam['semester']}
        ");
        
        // Get all students for comparison
        $all_students = $conn->query("SELECT DISTINCT department, semester FROM students ORDER BY department, semester");
    }
}

// Get all exams for dropdown
$all_exams = $conn->query("SELECT * FROM exams ORDER BY exam_date DESC, exam_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Tool - Department/Semester Matching</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .diagnostic-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #6366f1;
        }
        .diagnostic-box.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .diagnostic-box.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .diagnostic-box.success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        .comparison-table {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>🔍 Diagnostic Tool</h1>
                <p>Check Department/Semester Matching Issues</p>
            </div>
            <a href="exams.php" class="btn btn-secondary">← Back to Exams</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Select Exam to Diagnose</h2>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-group">
                        <label>Choose Exam:</label>
                        <select name="exam_id" onchange="this.form.submit()" required>
                            <option value="">-- Select an exam --</option>
                            <?php while ($e = $all_exams->fetch_assoc()): ?>
                                <option value="<?php echo $e['id']; ?>" <?php echo ($exam_id == $e['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['exam_name']); ?> - 
                                    <?php echo htmlspecialchars($e['department']); ?> 
                                    (Sem <?php echo $e['semester']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($exam_id > 0 && isset($exam)): ?>
            
            <div class="diagnostic-box">
                <h3>📋 Exam Details</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 16px;">
                    <div>
                        <strong>Exam Name:</strong><br>
                        <?php echo htmlspecialchars($exam['exam_name']); ?>
                    </div>
                    <div>
                        <strong>Course Code:</strong><br>
                        <?php echo htmlspecialchars($exam['course_code']); ?>
                    </div>
                    <div>
                        <strong>Department (Raw):</strong><br>
                        <div class="code-block">'<?php echo htmlspecialchars($exam['department']); ?>'</div>
                        <small>Length: <?php echo strlen($exam['department']); ?> chars</small>
                    </div>
                    <div>
                        <strong>Semester:</strong><br>
                        <div class="code-block"><?php echo $exam['semester']; ?></div>
                        <small>Type: <?php echo gettype($exam['semester']); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="comparison-table">
                <div class="diagnostic-box <?php echo $exact_match->num_rows > 0 ? 'success' : 'error'; ?>">
                    <h3>🔍 Exact Match Query</h3>
                    <p><strong>Students Found: <?php echo $exact_match->num_rows; ?></strong></p>
                    <div class="code-block">
SELECT * FROM students<br>
WHERE department = '<?php echo htmlspecialchars($exam['department']); ?>'<br>
AND semester = <?php echo $exam['semester']; ?>
                    </div>
                    
                    <?php if ($exact_match->num_rows > 0): ?>
                        <div style="margin-top: 12px;">
                            <strong>Matched Students:</strong>
                            <ul style="margin-top: 8px;">
                                <?php while ($s = $exact_match->fetch_assoc()): ?>
                                    <li><?php echo htmlspecialchars($s['roll_number']); ?> - <?php echo htmlspecialchars($s['name']); ?></li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p style="color: #ef4444; margin-top: 12px;"> No students found with exact match!</p>
                    <?php endif; ?>
                </div>
                
                <div class="diagnostic-box <?php echo $loose_match->num_rows > 0 ? 'success' : 'warning'; ?>">
                    <h3>🔍 Loose Match Query (Recommended)</h3>
                    <p><strong>Students Found: <?php echo $loose_match->num_rows; ?></strong></p>
                    <div class="code-block">
SELECT * FROM students<br>
WHERE TRIM(LOWER(department)) = <br>
&nbsp;&nbsp;TRIM(LOWER('<?php echo htmlspecialchars($exam['department']); ?>'))<br>
AND semester = <?php echo $exam['semester']; ?>
                    </div>
                    
                    <?php if ($loose_match->num_rows > 0): ?>
                        <div style="margin-top: 12px;">
                            <strong>Matched Students:</strong>
                            <ul style="margin-top: 8px;">
                                <?php while ($s = $loose_match->fetch_assoc()): ?>
                                    <li>
                                        <?php echo htmlspecialchars($s['roll_number']); ?> - <?php echo htmlspecialchars($s['name']); ?>
                                        <br><small style="color: #64748b;">Dept in DB: '<?php echo htmlspecialchars($s['department']); ?>'</small>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p style="color: #f59e0b; margin-top: 12px;">No students found even with loose matching!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="diagnostic-box warning">
                <h3>📊 All Department/Semester Combinations in Database</h3>
                <p>Compare these with your exam settings to find mismatches:</p>
                <div style="margin-top: 16px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Department (Raw)</th>
                                <th>Semester</th>
                                <th>Student Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($combo = $all_students->fetch_assoc()): ?>
                                <?php 
                                $count = $conn->query("
                                    SELECT COUNT(*) as cnt FROM students 
                                    WHERE department = '{$combo['department']}' 
                                    AND semester = {$combo['semester']}
                                ")->fetch_assoc()['cnt'];
                                
                                $is_match = (trim(strtolower($combo['department'])) === trim(strtolower($exam['department'])) && 
                                           $combo['semester'] == $exam['semester']);
                                ?>
                                <tr style="<?php echo $is_match ? 'background: #d1fae5;' : ''; ?>">
                                    <td><?php echo htmlspecialchars($combo['department']); ?></td>
                                    <td><code>'<?php echo htmlspecialchars($combo['department']); ?>'</code></td>
                                    <td><?php echo $combo['semester']; ?></td>
                                    <td><strong><?php echo $count; ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="diagnostic-box">
                <h3>💡 Recommendations</h3>
                <ol style="line-height: 2; margin-top: 12px;">
                    <li>
                        <strong>If Exact Match = 0 but Loose Match > 0:</strong><br>
                        There are whitespace or case differences. The updated seating.php file handles this automatically.
                    </li>
                    <li>
                        <strong>If Both Matches = 0:</strong><br>
                        The department name in the exam doesn't match any students in the database. Check for typos:
                        <ul style="margin-top: 8px;">
                            <li>Exam Department: "<?php echo htmlspecialchars($exam['department']); ?>"</li>
                            <li>Should probably be one of: 
                                <?php
                                $distinct_depts = $conn->query("SELECT DISTINCT department FROM students");
                                $dept_list = [];
                                while ($d = $distinct_depts->fetch_assoc()) {
                                    $dept_list[] = '"' . htmlspecialchars($d['department']) . '"';
                                }
                                echo implode(', ', $dept_list);
                                ?>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <strong>Quick Fix:</strong><br>
                        Go to <a href="exams.php" style="color: #6366f1; font-weight: 600;">Exams page</a> and edit this exam's department/semester to match exactly what's in the students table.
                    </li>
                </ol>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script src="script.js"></script>
</body>
</html>

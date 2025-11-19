<?php
require_once 'config.php';
requireAdmin();

$dept_stats = $conn->query("SELECT department, COUNT(*) as count FROM students GROUP BY department ORDER BY count DESC");
$room_util = $conn->query("SELECT r.room_number, r.building, r.capacity, COUNT(DISTINCT sa.exam_id) as usage_count FROM rooms r LEFT JOIN seating_arrangements sa ON r.id = sa.room_id GROUP BY r.id ORDER BY usage_count DESC");
$upcoming = $conn->query("SELECT e.*, COUNT(sa.id) as assigned FROM exams e LEFT JOIN seating_arrangements sa ON e.id = sa.exam_id WHERE e.exam_date >= CURDATE() GROUP BY e.id ORDER BY e.exam_date LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Reports & Analytics</h1>
                <p>System insights and statistics</p>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print Report
            </button>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Students by Department</h2>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <?php while ($stat = $dept_stats->fetch_assoc()): ?>
                            <?php $max = $conn->query("SELECT MAX(cnt) as max FROM (SELECT COUNT(*) as cnt FROM students GROUP BY department) as t")->fetch_assoc()['max']; ?>
                            <div class="chart-row">
                                <span class="chart-label"><?php echo htmlspecialchars($stat['department']); ?></span>
                                <div class="chart-bar">
                                    <div class="chart-fill" style="width: <?php echo ($stat['count'] / $max * 100); ?>%">
                                        <span class="chart-value"><?php echo $stat['count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Room Utilization</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Building</th>
                                    <th>Capacity</th>
                                    <th>Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($util = $room_util->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($util['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($util['building']); ?></td>
                                    <td><?php echo $util['capacity']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $util['usage_count']; ?> exams</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Upcoming Examinations</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Course</th>
                                <th>Date</th>
                                <th>Department</th>
                                <th>Assigned</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($exam = $upcoming->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['course_code']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
                                <td><?php echo htmlspecialchars($exam['department']); ?></td>
                                <td><?php echo $exam['assigned']; ?> students</td>
                                <td><span class="badge badge-<?php echo $exam['status']; ?>"><?php echo ucfirst($exam['status']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    </div>
    
    <script src="script.js"></script>
</body>
</html>
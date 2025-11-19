<?php
require_once 'config.php';
requireLogin();

// Check for seat conflicts between exams
$conflicts = $conn->query("
    SELECT 
        sa1.exam_id as exam1_id,
        sa2.exam_id as exam2_id,
        sa1.room_id,
        r.room_number,
        r.building,
        sa1.seat_number,
        e1.exam_name as exam1_name,
        e2.exam_name as exam2_name,
        e1.exam_date,
        e1.start_time as exam1_start,
        e2.start_time as exam2_start,
        e1.duration as exam1_duration,
        e2.duration as exam2_duration,
        s1.roll_number as student1_roll,
        s1.name as student1_name,
        s2.roll_number as student2_roll,
        s2.name as student2_name
    FROM seating_arrangements sa1
    INNER JOIN seating_arrangements sa2 
        ON sa1.room_id = sa2.room_id 
        AND sa1.row_position = sa2.row_position 
        AND sa1.column_position = sa2.column_position
        AND sa1.exam_id < sa2.exam_id
    INNER JOIN exams e1 ON sa1.exam_id = e1.id
    INNER JOIN exams e2 ON sa2.exam_id = e2.id
    INNER JOIN rooms r ON sa1.room_id = r.id
    INNER JOIN students s1 ON sa1.student_id = s1.id
    INNER JOIN students s2 ON sa2.student_id = s2.id
    WHERE e1.exam_date = e2.exam_date
      AND e1.status = 'scheduled'
      AND e2.status = 'scheduled'
      AND (
        (e1.start_time <= e2.start_time AND DATE_ADD(e1.start_time, INTERVAL e1.duration MINUTE) > e2.start_time)
        OR
        (e2.start_time <= e1.start_time AND DATE_ADD(e2.start_time, INTERVAL e2.duration MINUTE) > e1.start_time)
      )
    ORDER BY e1.exam_date, sa1.room_id, sa1.seat_number
");

// Check for duplicate seat usage (any exams, even non-conflicting times)
$duplicates = $conn->query("
    SELECT 
        sa.room_id,
        r.room_number,
        r.building,
        sa.seat_number,
        sa.row_position,
        sa.column_position,
        COUNT(DISTINCT sa.exam_id) as exam_count,
        GROUP_CONCAT(CONCAT(e.exam_name, ' (ID: ', e.id, ')') SEPARATOR '\n') as exams
    FROM seating_arrangements sa
    INNER JOIN exams e ON sa.exam_id = e.id
    INNER JOIN rooms r ON sa.room_id = r.id
    WHERE e.status = 'scheduled'
    GROUP BY sa.room_id, sa.row_position, sa.column_position
    HAVING COUNT(DISTINCT sa.exam_id) > 1
    ORDER BY exam_count DESC, r.room_number, sa.seat_number
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conflict Checker - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .conflict-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid #ef4444;
        }
        .conflict-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        .conflict-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }
        .conflict-section {
            background: #fef2f2;
            padding: 12px;
            border-radius: 8px;
        }
        .duplicate-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>🔍 Seating Conflict Checker</h1>
                <p>Detect scheduling conflicts and seat overlaps</p>
            </div>
            <a href="seating.php" class="btn btn-secondary">← Back to Seating</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>⚠️ Time Conflicts (Same Seat, Same Time)</h2>
                <span class="badge badge-danger"><?php echo $conflicts->num_rows; ?> conflicts</span>
            </div>
            <div class="card-body">
                <?php if ($conflicts->num_rows === 0): ?>
                    <div style="text-align: center; padding: 40px; color: #10b981;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 16px;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <h3>✅ No Time Conflicts Found!</h3>
                        <p>All scheduled exams have proper seat separation.</p>
                    </div>
                <?php else: ?>
                    <p style="color: #ef4444; margin-bottom: 20px;">
                        <strong>Critical Issue:</strong> The following seats are assigned to multiple students for exams happening at the same time.
                    </p>
                    
                    <?php while ($conflict = $conflicts->fetch_assoc()): ?>
                        <div class="conflict-card">
                            <div class="conflict-header">
                                <div>
                                    <h3 style="color: #ef4444; margin-bottom: 4px;">
                                        🚨 Room <?php echo htmlspecialchars($conflict['room_number']); ?> - Seat <?php echo htmlspecialchars($conflict['seat_number']); ?>
                                    </h3>
                                    <p style="color: #64748b; font-size: 14px;">
                                        <?php echo htmlspecialchars($conflict['building']); ?> | 
                                        <?php echo date('M d, Y', strtotime($conflict['exam_date'])); ?>
                                    </p>
                                </div>
                                <span class="badge badge-danger">CONFLICT</span>
                            </div>
                            
                            <div class="conflict-details">
                                <div class="conflict-section">
                                    <strong style="color: #ef4444;">Exam 1:</strong><br>
                                    <?php echo htmlspecialchars($conflict['exam1_name']); ?><br>
                                    <small><?php echo date('h:i A', strtotime($conflict['exam1_start'])); ?> (<?php echo $conflict['exam1_duration']; ?> min)</small><br>
                                    <strong>Student:</strong> <?php echo htmlspecialchars($conflict['student1_roll']); ?> - <?php echo htmlspecialchars($conflict['student1_name']); ?>
                                </div>
                                
                                <div class="conflict-section">
                                    <strong style="color: #ef4444;">Exam 2:</strong><br>
                                    <?php echo htmlspecialchars($conflict['exam2_name']); ?><br>
                                    <small><?php echo date('h:i A', strtotime($conflict['exam2_start'])); ?> (<?php echo $conflict['exam2_duration']; ?> min)</small><br>
                                    <strong>Student:</strong> <?php echo htmlspecialchars($conflict['student2_roll']); ?> - <?php echo htmlspecialchars($conflict['student2_name']); ?>
                                </div>
                            </div>
                            
                            <div style="margin-top: 12px; display: flex; gap: 12px;">
                                <a href="manual_seating.php?exam_id=<?php echo $conflict['exam1_id']; ?>" class="btn btn-sm">
                                    Fix Exam 1
                                </a>
                                <a href="manual_seating.php?exam_id=<?php echo $conflict['exam2_id']; ?>" class="btn btn-sm">
                                    Fix Exam 2
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>📊 Seat Usage Overview</h2>
                <span class="badge" style="background: #f59e0b;"><?php echo $duplicates->num_rows; ?> seats used by multiple exams</span>
            </div>
            <div class="card-body">
                <?php if ($duplicates->num_rows === 0): ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <p>No seats are being used by multiple exams. Each exam has unique seating.</p>
                    </div>
                <?php else: ?>
                    <p style="color: #f59e0b; margin-bottom: 20px;">
                        <strong>Note:</strong> These seats are used by multiple exams. This is OK if the exams are at different times.
                    </p>
                    
                    <?php while ($dup = $duplicates->fetch_assoc()): ?>
                        <div class="duplicate-card">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3 style="margin-bottom: 4px;">
                                        Room <?php echo htmlspecialchars($dup['room_number']); ?> - Seat <?php echo htmlspecialchars($dup['seat_number']); ?>
                                    </h3>
                                    <p style="color: #64748b; font-size: 14px;">
                                        <?php echo htmlspecialchars($dup['building']); ?> | 
                                        Position: Row <?php echo chr(65 + $dup['row_position']); ?>, Col <?php echo $dup['column_position'] + 1; ?>
                                    </p>
                                </div>
                                <span class="badge" style="background: #f59e0b; color: white;">
                                    Used by <?php echo $dup['exam_count']; ?> exams
                                </span>
                            </div>
                            <div style="margin-top: 12px; background: #fef3c7; padding: 12px; border-radius: 8px;">
                                <strong>Exams using this seat:</strong><br>
                                <?php 
                                $exams_list = explode("\n", $dup['exams']);
                                foreach ($exams_list as $exam) {
                                    echo "• " . htmlspecialchars($exam) . "<br>";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>💡 How to Fix Conflicts</h2>
            </div>
            <div class="card-body">
                <ol style="line-height: 2;">
                    <li><strong>Time Conflicts:</strong> These MUST be fixed. Two students can't sit in the same seat at the same time. Use manual seating to reassign one of the students.</li>
                    <li><strong>Duplicate Usage (No Time Conflict):</strong> This is fine. The same physical seat can be used by different exams if they're at different times.</li>
                    <li><strong>Prevention:</strong> When manually assigning seats, the system now checks for time conflicts automatically.</li>
                    <li><strong>Auto-Assignment:</strong> The auto-assign feature now automatically avoids seats that are blocked by other exams at the same time.</li>
                </ol>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
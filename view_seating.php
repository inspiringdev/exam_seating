<?php
require_once 'config.php';
requireLogin();

function hasConflict($seat_details, $r, $c, $current_dept, $current_sem, $max_rows, $max_cols) {
    $positions = [
        [$r, $c-1], [$r, $c+1], [$r-1, $c], [$r+1, $c]
    ];
    foreach ($positions as $pos) {
        list($pr, $pc) = $pos;
        if ($pc < 0 || $pc >= $max_cols || $pr < 0 || $pr >= $max_rows) continue;
        
        $key = $pr . '_' . $pc;
        if (isset($seat_details[$key])) {
            $neighbor = $seat_details[$key];
            if ($neighbor['department'] === $current_dept && 
                intval($neighbor['semester']) === intval($current_sem)) {
                return true;
            }
        }
    }
    return false;
}

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

$exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
if (!$exam) {
    redirect('seating.php');
}

// ===== FIX: Calculate total and assigned students BEFORE using them =====
$total_students = $conn->query("
    SELECT COUNT(*) as cnt 
    FROM students 
    WHERE TRIM(LOWER(department)) = TRIM(LOWER('{$exam['department']}'))
    AND semester = {$exam['semester']}
")->fetch_assoc()['cnt'];

$assigned_count = $conn->query("
    SELECT COUNT(*) as cnt 
    FROM seating_arrangements 
    WHERE exam_id = $exam_id
")->fetch_assoc()['cnt'];

$all_assigned = ($assigned_count >= $total_students);
// ===== END FIX =====

// Get this exam's stats
$exam_stats = $conn->query("
    SELECT 
        COUNT(*) as total_assigned,
        COUNT(DISTINCT room_id) as rooms_used
    FROM seating_arrangements 
    WHERE exam_id = $exam_id
")->fetch_assoc();

// Get total capacity available across ALL rooms
$total_capacity = $conn->query("
    SELECT SUM(total_rows * total_columns) as total
    FROM rooms 
    WHERE available = 1
")->fetch_assoc()['total'];

// Get all system-wide assignments
$total_assigned_system = $conn->query("
    SELECT COUNT(*) as cnt FROM seating_arrangements
")->fetch_assoc()['cnt'];

// Get rooms for this exam
$rooms_data = $conn->query("
    SELECT DISTINCT r.* 
    FROM rooms r 
    INNER JOIN seating_arrangements sa ON r.id = sa.room_id 
    WHERE sa.exam_id = $exam_id
    ORDER BY r.room_number
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seating Plan - <?php echo htmlspecialchars($exam['exam_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .seating-stats {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-box {
            text-align: center;
            padding: 16px;
            background: linear-gradient(135deg, #f0f4ff, #e3f2fd);
            border-radius: 12px;
        }
        .stat-box h3 {
            font-size: 32px;
            color: #6366f1;
            margin-bottom: 8px;
        }
        .stat-box p {
            color: #64748b;
            font-size: 14px;
        }
        .validation-indicator {
            position: absolute;
            top: 5px;
            left: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .validation-ok {
            background: #10b981;
        }
        .validation-warning {
            background: #f59e0b;
            animation: pulse-warning 2s infinite;
        }
        @keyframes pulse-warning {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @media print {
            .btn, .page-header a, .no-print {
                display: none !important;
            }
            .container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header no-print">
            <div>
                <h1><?php echo htmlspecialchars($exam['exam_name']); ?></h1>
                <p><?php echo htmlspecialchars($exam['course_code']); ?> - <?php echo date('l, M d, Y', strtotime($exam['exam_date'])); ?> at <?php echo date('h:i A', strtotime($exam['start_time'])); ?></p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="seating.php" class="btn btn-secondary">← Back</a>
                <button onclick="window.print()" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Print
                </button>
            </div>
        </div>
        
        <div class="seating-stats">
            <div class="stat-box">
                <h3><?php echo $exam_stats['total_assigned']; ?> / <?php echo $total_students; ?></h3>
                <p>Students Assigned (This Exam)</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $exam_stats['rooms_used']; ?></h3>
                <p>Rooms Used</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $total_assigned_system; ?></h3>
                <p>Total Students Assigned (All Exams)</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $total_capacity; ?></h3>
                <p>Available Capacity (System)</p>
            </div>
        </div>
        
        <div style="background: <?php echo $all_assigned ? '#d1fae5' : '#fef3c7'; ?>; 
             padding: 16px; border-radius: 12px; margin: 20px 0; border-left: 6px solid <?php echo $all_assigned ? '#10b981' : '#f59e0b'; ?>; font-weight: 600;">
            <?php if ($all_assigned): ?>
                ✅ All <?php echo $total_students; ?> students have been assigned!
            <?php else: ?>
                ⚠️ Warning: Only <?php echo $assigned_count; ?> of <?php echo $total_students; ?> students assigned 
                (<?php echo $total_students - $assigned_count; ?> still missing)
            <?php endif; ?>
        </div>
        
        <?php if ($rooms_data->num_rows === 0): ?>
            <div class="alert alert-error">
                No seating arrangements found for this exam. 
                <a href="seating.php" style="color: #ef4444; font-weight: 600;">Go back and assign seats →</a>
            </div>
        <?php endif; ?>
        
        <?php while ($room = $rooms_data->fetch_assoc()): ?>
            <?php
            $seats_result = $conn->query("
                SELECT sa.*, s.name, s.roll_number, s.department, s.semester
                FROM seating_arrangements sa 
                INNER JOIN students s ON sa.student_id = s.id 
                WHERE sa.exam_id = $exam_id AND sa.room_id = {$room['id']} 
                ORDER BY sa.row_position, sa.column_position
            ");
            
            $seating_map = [];
            $seat_details = [];
            $conflict_count = 0;
            
            while ($seat = $seats_result->fetch_assoc()) {
                $seating_map[$seat['row_position']][$seat['column_position']] = $seat;
                $seat_details[$seat['row_position'] . '_' . $seat['column_position']] = $seat;
            }
            
            $room_occupied = count($seat_details);
            $room_capacity = $room['total_rows'] * $room['total_columns'];
            ?>
            
            <div class="card room-card">
                <div class="card-header">
                    <h2>🏛️ Room <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['building']); ?></h2>
                    <div style="display: flex; gap: 12px;">
                        <span class="badge">Capacity: <?php echo $room_capacity; ?></span>
                        <span class="badge" style="background: <?php echo $room_occupied < $room_capacity ? '#f59e0b' : '#10b981'; ?>">
                            Occupied: <?php echo $room_occupied; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="seating-layout">
                        <div class="board-label">👨‍🏫 Supervisor's Desk</div>
                        <div class="seating-grid-view" style="display: grid; grid-template-columns: repeat(<?php echo $room['total_columns']; ?>, 1fr); gap: 8px;">
                            <?php for ($row = 0; $row < $room['total_rows']; $row++): ?>
                                <?php for ($col = 0; $col < $room['total_columns']; $col++): ?>
                                    <?php if (isset($seating_map[$row][$col])): ?>
                                        <?php 
                                        $seat = $seating_map[$row][$col]; 
                                        $has_conflict = hasConflict($seat_details, $row, $col, $seat['department'], $seat['semester'], $room['total_rows'], $room['total_columns']);
                                        if ($has_conflict) $conflict_count++;
                                        ?>
                                        <div class="seat occupied" style="position: relative;">
                                            <?php if ($has_conflict): ?>
                                                <div class="validation-warning" title="Warning: Same department/semester student nearby"></div>
                                            <?php else: ?>
                                                <div class="validation-ok" title="Properly spaced"></div>
                                            <?php endif; ?>
                                            <div class="seat-number"><?php echo htmlspecialchars($seat['seat_number']); ?></div>
                                            <div class="seat-roll"><?php echo htmlspecialchars($seat['roll_number']); ?></div>
                                            <div class="seat-name"><?php echo htmlspecialchars($seat['name']); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="seat empty">
                                            <div class="seat-number"><?php echo chr(65 + $row) . ($col + 1); ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </div>
                        
                        <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; display: flex; gap: 24px; justify-content: center; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></div>
                                <span style="font-size: 14px; color: #64748b;">Properly Spaced</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 12px; height: 12px; background: #f59e0b; border-radius: 50%;"></div>
                                <span style="font-size: 14px; color: #64748b;">Same Dept/Sem Nearby (<?php echo $conflict_count; ?>)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 12px; height: 12px; background: #e2e8f0; border: 2px solid #cbd5e1; border-radius: 50%;"></div>
                                <span style="font-size: 14px; color: #64748b;">Empty Seat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
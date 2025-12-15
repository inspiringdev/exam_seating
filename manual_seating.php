<?php
require_once 'config.php';
requireLogin();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();

if (!$exam) {
    redirect('seating.php');
}

$success = '';
$error = '';

//Clear all manual assignments for this exam when page loads 
if (!isset($_SESSION['manual_cleared_' . $exam_id])) {
    $conn->query("DELETE FROM seating_arrangements WHERE exam_id = $exam_id");
    $_SESSION['manual_cleared_' . $exam_id] = true;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_seat'])) {
    $student_id = intval($_POST['student_id']);
    $room_id = intval($_POST['room_id']);
    $row = intval($_POST['row_position']);
    $col = intval($_POST['column_position']);
    
    //Validate room is assigned to this exam
    if($exam['room_id'] != $room_id){
        $error = "This room is not assigned to this exam!";
    } 
    else 
        {
        $student = $conn->query("SELECT * FROM students WHERE id = $student_id")->fetch_assoc();
        $room = $conn->query("SELECT * FROM rooms WHERE id = $room_id")->fetch_assoc();
        
        //Check if student already assigned for this exam
        $already_assigned = $conn->query("
            SELECT id, seat_number FROM seating_arrangements 
            WHERE exam_id = $exam_id AND student_id = $student_id
        ");
        
        if ($already_assigned->num_rows > 0) {
            $existing = $already_assigned->fetch_assoc();
            $error = " This student is already assigned to seat {$existing['seat_number']}!";
        } 
        else{
            //Check room boundaries
            if($row < 0 || $row >= $room['total_rows'] || $col < 0 || $col >= $room['total_columns']) {
                $error = "Invalid seat position!";
            } 
            else{
                //CHECK 1 that Is this physical seat occupied by ANY exam?
                $check_any_exam = $conn->query("
                    SELECT e.exam_name, s.name, s.roll_number 
                    FROM seating_arrangements sa
                    INNER JOIN exams e ON sa.exam_id = e.id
                    INNER JOIN students s ON sa.student_id = s.id
                    WHERE sa.room_id = $room_id 
                    AND sa.row_position = $row 
                    AND sa.column_position = $col
                ");
                
                if ($check_any_exam->num_rows > 0) {
                    $occupant = $check_any_exam->fetch_assoc();
                    $error = " This seat is already occupied by {$occupant['roll_number']} - {$occupant['name']} (Exam: {$occupant['exam_name']})!";
                } else {
                    // CHECK 2: Are adjacent seats occupied by THIS SAME EXAM?
                    $adjacent_positions = [
                        [$row-1, $col],     // Above
                        [$row+1, $col],     // Below
                        [$row, $col-1],     // Left
                        [$row, $col+1]      // Right
                    ];
                    
                    $conflict = false;
                    $conflict_msg = '';
                    
                    foreach ($adjacent_positions as $pos) {
                        list($cr, $cc) = $pos;
                        
                        // Skip if out of bounds
                        if ($cc < 0 || $cc >= $room['total_columns'] || 
                            $cr < 0 || $cr >= $room['total_rows']) {
                            continue;
                        }
                        
                        // Check if adjacent seat has a student from THIS exam
                        $check = $conn->query("
                            SELECT s.name, s.roll_number 
                            FROM seating_arrangements sa
                            INNER JOIN students s ON sa.student_id = s.id
                            WHERE sa.exam_id = $exam_id 
                            AND sa.room_id = $room_id 
                            AND sa.row_position = $cr 
                            AND sa.column_position = $cc
                        ");
                        
                        if ($check->num_rows > 0) {
                            $neighbor = $check->fetch_assoc();
                            $conflict = true;
                            $conflict_msg = " Cannot assign: {$neighbor['roll_number']} ({$neighbor['name']}) from the SAME EXAM is at adjacent seat " . chr(65 + $cr) . ($cc + 1);
                            break;
                        }
                    }
                    
                    if ($conflict) {
                        $error = $conflict_msg;
                    } else {
                        // All checks passed - assign student
                        $seat_number = chr(65 + $row) . ($col + 1);
                        $stmt = $conn->prepare("
                            INSERT INTO seating_arrangements 
                            (exam_id, student_id, room_id, seat_number, row_position, column_position) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param("iiisii", $exam_id, $student_id, $room_id, $seat_number, $row, $col);
                        
                        if ($stmt->execute()) {
                            $success = " {$student['roll_number']} assigned to seat $seat_number successfully!";
                        } else {
                            $error = "Database error: " . $conn->error;
                        }
                    }
                }
            }
        }
    }
}

if (isset($_POST['remove_seat'])) {
    $sa_id = intval($_POST['sa_id']);
    $conn->query("DELETE FROM seating_arrangements WHERE id = $sa_id AND exam_id = $exam_id");
    $success = " Student removed from seat";
}

// Get students for this exam
$students = $conn->query("
    SELECT s.*, 
    (SELECT COUNT(*) FROM seating_arrangements WHERE student_id = s.id AND exam_id = $exam_id) as is_assigned,
    (SELECT seat_number FROM seating_arrangements WHERE student_id = s.id AND exam_id = $exam_id LIMIT 1) as assigned_seat
    FROM students s
    WHERE s.department = '{$exam['department']}' AND s.semester = {$exam['semester']}
    ORDER BY s.roll_number
");

// === ADD THIS BLOCK AFTER $students = $conn->query(...) ===
$total_students = $conn->query("
    SELECT COUNT(*) FROM students 
    WHERE TRIM(LOWER(department)) = TRIM(LOWER('{$exam['department']}'))
    AND semester = {$exam['semester']}
")->fetch_row()[0];

$assigned_count = $conn->query("
    SELECT COUNT(*) FROM seating_arrangements 
    WHERE exam_id = $exam_id
")->fetch_row()[0];

$all_assigned = ($assigned_count >= $total_students);

// Get only the room assigned to this exam
$room = null;
if ($exam['room_id']) {
    $room = $conn->query("SELECT * FROM rooms WHERE id = {$exam['room_id']}")->fetch_assoc();
}

// Check for other exams in this room at the same time
$other_exams_warning = '';
if ($room) {
    $other_exams = $conn->query("
        SELECT COUNT(*) as cnt 
        FROM exams e
        WHERE e.room_id = {$room['id']}
        AND e.id != $exam_id
        AND e.exam_date = '{$exam['exam_date']}'
        AND e.status = 'scheduled'
        AND (
            (e.start_time <= '{$exam['start_time']}' AND DATE_ADD(e.start_time, INTERVAL e.duration MINUTE) > '{$exam['start_time']}')
            OR
            (e.start_time < DATE_ADD('{$exam['start_time']}', INTERVAL {$exam['duration']} MINUTE) AND e.start_time >= '{$exam['start_time']}')
        )
    ")->fetch_assoc()['cnt'];
    
    if ($other_exams > 0) {
        $other_exams_warning = " Warning: $other_exams other exam(s) scheduled in this room at overlapping times!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Seating - <?php echo htmlspecialchars($exam['exam_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .manual-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
            margin-top: 24px;
        }
        .student-list {
            background: white;
            border-radius: 16px;
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .student-item {
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .student-item:hover {
            border-color: #6366f1;
            background: #f0f4ff;
            transform: translateX(4px);
        }
        .student-item.assigned {
            background: #d1fae5;
            border-color: #10b981;
            opacity: 0.7;
        }
        .student-item.selected {
            border-color: #6366f1;
            background: #e0e7ff;
            border-width: 3px;
        }
        .room-selector {
            background: white;
            border-radius: 16px;
            padding: 20px;
        }
        .seat-grid {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }
        .seat-btn {
            min-height: 90px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            padding: 8px;
            font-size: 12px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .seat-btn:not(.occupied):not(.blocked):hover {
            border-color: #6366f1;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .seat-btn.occupied {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
            cursor: default;
        }
        .seat-btn.blocked {
            background: repeating-linear-gradient(
                45deg,
                #fee2e2,
                #fee2e2 10px,
                #fecaca 10px,
                #fecaca 20px
            );
            border-color: #ef4444;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .seat-btn.selected {
            background: #fef3c7;
            border-color: #f59e0b;
            border-width: 3px;
            transform: scale(1.05);
        }
        .seat-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .seat-actions button {
            font-size: 11px;
            padding: 4px 8px;
        }
        @media (max-width: 968px) {
            .manual-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Manual Seating Assignment</h1>
                <p><?php echo htmlspecialchars($exam['exam_name']); ?> - <?php echo htmlspecialchars($exam['course_code']); ?></p>
                <p style="color: black; font-size: 14px;">
                    📅 <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?> 
                    at <?php echo date('h:i A', strtotime($exam['start_time'])); ?>
                </p>
                <?php if ($room): ?>
                    <p style="color: white; font-weight: 600;">📍 Room: <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['building']); ?></p>
                <?php else: ?>
                    <p style="color: #ef4444; font-weight: 600;"> No room assigned to this exam! Please assign a room first.</p>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="seating.php" class="btn btn-secondary">← Back to Seating</a>
                <?php if (isset($_SESSION['manual_cleared_' . $exam_id])): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="reset_manual" onclick="return confirm('Clear all manual assignments and start fresh?')" class="btn" style="background: #ef4444; color: white;">
                            🔄 Reset All
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($other_exams_warning): ?>
            <div class="alert alert-warning">
                <?php echo $other_exams_warning; ?>
                <br><small>Seats occupied by other exams will be marked in red stripes.</small>
            </div>
        <?php endif; ?>
        
        <?php if (!$room): ?>
            <div class="alert alert-error">
                Please assign a room to this exam from the Exams page before using manual seating.
            </div>
        <?php else: ?>
        
        <div class="manual-grid">
            <div class="student-list">
                <h3 style="margin-bottom: 16px;">📋 Select Student</h3>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <div class="student-item <?php echo $student['is_assigned'] ? 'assigned' : ''; ?>" 
                         id="student_<?php echo $student['id']; ?>"
                         onclick="selectStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['roll_number'], ENT_QUOTES); ?>')">
                        <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong><br>
                        <?php echo htmlspecialchars($student['name']); ?>
                        <?php if ($student['is_assigned']): ?>
                            <br><span style="color: #059669; font-size: 11px;">✓ Assigned to <?php echo $student['assigned_seat']; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="room-selector">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3>🏛️ Room <?php echo htmlspecialchars($room['room_number']); ?> Layout</h3>
                    <div style="color: #64748b; font-size: 14px;">
                        <?php 
                        $occupied = $conn->query("SELECT COUNT(*) as cnt FROM seating_arrangements WHERE exam_id = $exam_id AND room_id = {$room['id']}")->fetch_assoc()['cnt'];
                        $total = $room['total_rows'] * $room['total_columns'];
                        ?>
                        Occupied: <?php echo $occupied; ?> / <?php echo $total; ?>
                    </div>
                </div>
                
                <div id="selectedInfo" style="background: #f0f4ff; padding: 16px; border-radius: 12px; margin-bottom: 16px; display: none; border-left: 4px solid #6366f1;">
                    <strong>👤 Selected Student:</strong> <span id="selectedStudent">None</span><br>
                    <strong>💺 Selected Seat:</strong> <span id="selectedSeat">None</span>
                </div>
                
                <div id="seatGrid" class="seat-grid" style="grid-template-columns: repeat(<?php echo $room['total_columns']; ?>, 1fr);">
                    <div style="grid-column: 1 / -1; text-align: center; padding: 16px; background: #1e293b; color: white; border-radius: 12px; font-weight: 600; font-size: 16px;">
                        👨‍🏫 Supervisor Desk
                    </div>
                    <?php
                    // Load current seating FOR THIS EXAM
                    $seats_data = $conn->query("
                        SELECT sa.*, s.name, s.roll_number
                        FROM seating_arrangements sa
                        INNER JOIN students s ON sa.student_id = s.id
                        WHERE sa.exam_id = $exam_id AND sa.room_id = {$room['id']}
                    ");
                    $seat_map = [];
                    while ($s = $seats_data->fetch_assoc()) {
                        $seat_map[$s['row_position'] . '_' . $s['column_position']] = $s;
                    }
                    
                    // Load seats blocked by OTHER exams at the same time
                    $blocked_seats_data = $conn->query("
                        SELECT DISTINCT sa.row_position, sa.column_position, e.exam_name, s.roll_number
                        FROM seating_arrangements sa
                        INNER JOIN exams e ON sa.exam_id = e.id
                        INNER JOIN students s ON sa.student_id = s.id
                        WHERE sa.room_id = {$room['id']}
                        AND sa.exam_id != $exam_id
                        AND e.exam_date = '{$exam['exam_date']}'
                        AND e.status = 'scheduled'
                        AND (
                            (e.start_time <= '{$exam['start_time']}' AND DATE_ADD(e.start_time, INTERVAL e.duration MINUTE) > '{$exam['start_time']}')
                            OR
                            (e.start_time < DATE_ADD('{$exam['start_time']}', INTERVAL {$exam['duration']} MINUTE) AND e.start_time >= '{$exam['start_time']}')
                        )
                    ");
                    $blocked_seats = [];
                    while ($bs = $blocked_seats_data->fetch_assoc()) {
                        $blocked_seats[$bs['row_position'] . '_' . $bs['column_position']] = $bs;
                    }
                    
                    for ($r = 0; $r < $room['total_rows']; $r++):
                        for ($c = 0; $c < $room['total_columns']; $c++):
                            $seatLabel = chr(65 + $r) . ($c + 1);
                            $key = $r . '_' . $c;
                            $seat = isset($seat_map[$key]) ? $seat_map[$key] : null;
                            $blocked = isset($blocked_seats[$key]) ? $blocked_seats[$key] : null;
                    ?>
                        <?php if ($seat): ?>
                            <div class="seat-btn occupied" data-sa-id="<?php echo $seat['id']; ?>">
                                <strong style="font-size: 14px;"><?php echo $seatLabel; ?></strong>
                                <span style="font-size: 11px;"><?php echo htmlspecialchars($seat['roll_number']); ?></span>
                                <span style="font-size: 10px;"><?php echo htmlspecialchars($seat['name']); ?></span>
                                <form method="POST" class="seat-actions" onclick="event.stopPropagation();">
                                    <input type="hidden" name="sa_id" value="<?php echo $seat['id']; ?>">
                                    <button type="submit" name="remove_seat" class="btn btn-sm" style="background: #ef4444; color: white;" onclick="return confirm('Remove student from this seat?')">Remove</button>
                                </form>
                            </div>
                        <?php elseif ($blocked): ?>
                            <div class="seat-btn blocked" title="Blocked by: <?php echo htmlspecialchars($blocked['exam_name']); ?> - <?php echo htmlspecialchars($blocked['roll_number']); ?>">
                                <strong style="font-size: 14px; color: #ef4444;"><?php echo $seatLabel; ?></strong>
                                <span style="font-size: 10px; color: #991b1b;">🚫 Other Exam</span>
                            </div>
                        <?php else: ?>
                            <button type="button" class="seat-btn" onclick="selectSeat(<?php echo $r; ?>, <?php echo $c; ?>, '<?php echo $seatLabel; ?>', this)">
                                <strong style="font-size: 16px;"><?php echo $seatLabel; ?></strong>
                                <span style="color: #94a3b8; font-size: 11px;">Empty</span>
                            </button>
                        <?php endif; ?>
                    <?php 
                        endfor;
                    endfor;
                    ?>
                </div>
                
                <form method="POST" id="assignForm" style="display: none;">
                    <input type="hidden" name="student_id" id="formStudentId">
                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                    <input type="hidden" name="row_position" id="formRow">
                    <input type="hidden" name="column_position" id="formCol">
                    <button type="submit" name="assign_seat" class="btn btn-primary btn-full" style="margin-top: 16px;">
                        ✓ Assign to Selected Seat
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        let selectedStudentId = null;
        let selectedStudentName = '';
        let selectedRollNumber = '';
        let selectedRow = null;
        let selectedCol = null;
        
        function selectStudent(id, name, roll) {
            document.querySelectorAll('.student-item').forEach(el => el.classList.remove('selected'));
            const studentEl = document.getElementById('student_' + id);
            if (studentEl) {
                studentEl.classList.add('selected');
            }
            
            selectedStudentId = id;
            selectedStudentName = name;
            selectedRollNumber = roll;
            document.getElementById('selectedStudent').textContent = roll + ' - ' + name;
            document.getElementById('selectedInfo').style.display = 'block';
            document.getElementById('formStudentId').value = id;
            checkAssignReady();
        }
        
        function selectSeat(row, col, label, btnElement) {
            document.querySelectorAll('.seat-btn:not(.occupied):not(.blocked)').forEach(btn => btn.classList.remove('selected'));
            btnElement.classList.add('selected');
            
            selectedRow = row;
            selectedCol = col;
            document.getElementById('selectedSeat').textContent = label;
            document.getElementById('formRow').value = row;
            document.getElementById('formCol').value = col;
            
            checkAssignReady();
        }
        
        function checkAssignReady() {
            if (selectedStudentId && selectedRow !== null && selectedCol !== null) {
                document.getElementById('assignForm').style.display = 'block';
            }
        }
    </script>
</body>
</html>

<?php
// Handle reset
if (isset($_POST['reset_manual'])) {
    $conn->query("DELETE FROM seating_arrangements WHERE exam_id = $exam_id");
    unset($_SESSION['manual_cleared_' . $exam_id]);
    redirect("manual_seating.php?exam_id=$exam_id");
}
?>

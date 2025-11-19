<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// =====================================================
// BULK AUTO-ASSIGN ALL EXAMS (Improved Version)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign_all'])) {
    beginTransaction();
    
    try {
        // Step 1: Clear ALL existing seat assignments
        $conn->query("DELETE FROM seating_arrangements");
        
        // Step 2: Get all scheduled exams ordered by date/time
        $all_exams_result = $conn->query("
            SELECT * FROM exams 
            WHERE status = 'scheduled'
            ORDER BY exam_date, start_time
        ");
        
        $all_exams = [];
        while ($e = $all_exams_result->fetch_assoc()) {
            $all_exams[] = $e;
        }
        
        if (count($all_exams) === 0) {
            throw new Exception("No scheduled exams found");
        }
        
        // Step 3: Group exams by same date/time/room (can share seats)
        $exam_groups = [];
        foreach ($all_exams as $exam) {
            $key = $exam['exam_date'] . '_' . $exam['start_time'] . '_' . $exam['room_id'];
            if (!isset($exam_groups[$key])) {
                $exam_groups[$key] = [];
            }
            $exam_groups[$key][] = $exam;
        }
        
        // Step 4: Get all available rooms
        $rooms_result = $conn->query("
            SELECT * FROM rooms 
            WHERE available = 1 
            ORDER BY (total_rows * total_columns) DESC
        ");
        
        $rooms = [];
        while ($r = $rooms_result->fetch_assoc()) {
            $rooms[] = $r;
        }
        
        if (count($rooms) === 0) {
            throw new Exception("No rooms available");
        }
        
        // Prepare insert statement
        $insert_stmt = $conn->prepare("
            INSERT INTO seating_arrangements 
            (exam_id, student_id, room_id, seat_number, row_position, column_position) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $total_assigned = 0;
        $total_students = 0;
        $assignment_results = [];
        $warning_messages = [];
        
        // Step 5: Process each exam group
        foreach ($exam_groups as $group_key => $exams_in_group) {
            // Get all students for all exams in this group
            $all_group_students = [];
            foreach ($exams_in_group as $exam) {
                $students_result = $conn->query("
                    SELECT s.*, '{$exam['id']}' as exam_id, '{$exam['exam_name']}' as exam_name
                    FROM students s
                    WHERE TRIM(LOWER(s.department)) = TRIM(LOWER('{$exam['department']}'))
                    AND s.semester = {$exam['semester']}
                    ORDER BY s.roll_number
                ");
                
                while ($s = $students_result->fetch_assoc()) {
                    $all_group_students[] = $s;
                    $total_students++;
                }
            }
            
            if (count($all_group_students) === 0) continue;
            
            // Assign students from this group to available rooms
            $student_index = 0;
            $assigned_in_group = 0;
            
            foreach ($rooms as $room) {
                if ($student_index >= count($all_group_students)) break;
                
                $room_occupied = []; // Track occupied seats in this room for this group
                
                for ($r = 0; $r < $room['total_rows']; $r++) {
                    for ($c = 0; $c < $room['total_columns']; $c++) {
                        if ($student_index >= count($all_group_students)) break 2;
                        
                        $seat_key = $r . '_' . $c;
                        $student = $all_group_students[$student_index];
                        
                        // Check adjacency conflict (same exam only)
                        $has_conflict = false;
                        $adjacent = [[$r,$c-1],[$r,$c+1],[$r-1,$c],[$r+1,$c]];
                        
                        foreach ($adjacent as $pos) {
                            list($ar, $ac) = $pos;
                            if ($ar < 0 || $ar >= $room['total_rows'] || 
                                $ac < 0 || $ac >= $room['total_columns']) continue;
                            
                            $adj_key = $ar . '_' . $ac;
                            if (isset($room_occupied[$adj_key])) {
                                $neighbor = $room_occupied[$adj_key];
                                // Only conflict if same exam (dept + semester)
                                if ($neighbor['department'] === $student['department'] && 
                                    intval($neighbor['semester']) === intval($student['semester'])) {
                                    $has_conflict = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($has_conflict) continue;
                        
                        // Assign seat
                        $seat_number = chr(65 + $r) . ($c + 1);
                        
                        $insert_stmt->bind_param("iiisii", 
                            $student['exam_id'], 
                            $student['id'], 
                            $room['id'], 
                            $seat_number, 
                            $r, 
                            $c
                        );
                        
                        if ($insert_stmt->execute()) {
                            $room_occupied[$seat_key] = $student;
                            $student_index++;
                            $assigned_in_group++;
                            $total_assigned++;
                        }
                    }
                }
            }
            
            // Check if all students in group were assigned
            if ($assigned_in_group < count($all_group_students)) {
                $unassigned = count($all_group_students) - $assigned_in_group;
                $warning_messages[] = "⚠️ Warning: {$unassigned} student(s) from exams at " . 
                    date('M d, h:i A', strtotime($exams_in_group[0]['exam_date'] . ' ' . $exams_in_group[0]['start_time'])) . 
                    " could not be assigned (insufficient space with spacing rules)";
            }
        }
        
        // Step 6: Collect assignment results per exam
        foreach ($all_exams as $exam) {
            $exam_id = $exam['id'];
            $assigned = $conn->query("
                SELECT COUNT(*) as cnt FROM seating_arrangements WHERE exam_id = $exam_id
            ")->fetch_assoc()['cnt'];
            
            $total_for_exam = $conn->query("
                SELECT COUNT(*) as cnt FROM students 
                WHERE TRIM(LOWER(department)) = TRIM(LOWER('{$exam['department']}'))
                AND semester = {$exam['semester']}
            ")->fetch_assoc()['cnt'];
            
            $assignment_results[] = [
                'exam' => $exam['exam_name'],
                'assigned' => $assigned,
                'total' => $total_for_exam
            ];
        }
        
        $insert_stmt->close();
        commitTransaction();
        
        $success = "✅ Successfully assigned $total_assigned of $total_students students across " . count($all_exams) . " exams!<br><br>";
        $success .= "<strong>Details:</strong><ul style='margin-top: 8px; padding-left: 15px; padding-top: 5px;'>";
        foreach ($assignment_results as $result) {
            $icon = ($result['assigned'] >= $result['total']) ? '✅' : '⚠️';
            $success .= "<li>$icon {$result['exam']}: {$result['assigned']}/{$result['total']} students</li>";
        }
        $success .= "</ul>";
        
        if (count($warning_messages) > 0) {
            $success .= "<br><strong>Warnings:</strong><ul style='margin-top: 8px; padding-left: 15px;'>";
            foreach ($warning_messages as $msg) {
                $success .= "<li>$msg</li>";
            }
            $success .= "</ul>";
        }
        
        $_SESSION['bulk_assign_success'] = $success;
        redirect("seating.php");
        
    } catch (Exception $e) {
        rollbackTransaction();
        $error = "Bulk Assignment Error: " . $e->getMessage();
    }
}

// =====================================================
// INDIVIDUAL AUTO-ASSIGN (Respects Existing Assignments)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign'])) {
    $exam_id = intval($_POST['exam_id']);
    
    beginTransaction();
    
    try {
        $exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
        if (!$exam) {
            throw new Exception("Exam not found");
        }
        
        // Check existing assignments
        $existing_count = $conn->query("
            SELECT COUNT(*) as cnt FROM seating_arrangements WHERE exam_id = $exam_id
        ")->fetch_assoc()['cnt'];
        
        if ($existing_count > 0) {
            throw new Exception("This exam already has seat assignments! Use manual assignment to add more, or use Bulk Assignment to reset all.");
        }
        
        // Get students for this exam
        $students_result = $conn->query("
            SELECT s.* 
            FROM students s
            WHERE TRIM(LOWER(s.department)) = TRIM(LOWER('{$exam['department']}'))
            AND s.semester = {$exam['semester']}
            ORDER BY s.roll_number
        ");
        
        $students = [];
        while ($s = $students_result->fetch_assoc()) {
            $students[] = $s;
        }
        
        if (count($students) === 0) {
            throw new Exception("No students found for this department/semester");
        }
        
        // Get available rooms
        $rooms_result = $conn->query("
            SELECT * FROM rooms 
            WHERE available = 1 
            ORDER BY (total_rows * total_columns) DESC
        ");
        
        $rooms = [];
        while ($r = $rooms_result->fetch_assoc()) {
            $rooms[] = $r;
        }
        
        if (count($rooms) === 0) {
            throw new Exception("No rooms available");
        }
        
        // Get seats blocked by OTHER exams at overlapping times
        $exam_start = strtotime($exam['exam_date'] . ' ' . $exam['start_time']);
        $exam_end = $exam_start + ($exam['duration'] ?? 180) * 60;
        
        $blocked_query = $conn->query("
            SELECT DISTINCT sa.room_id, sa.row_position, sa.column_position,
                   e.exam_name, s.roll_number
            FROM seating_arrangements sa
            INNER JOIN exams e ON sa.exam_id = e.id
            INNER JOIN students s ON sa.student_id = s.id
            WHERE e.status = 'scheduled'
              AND e.id != $exam_id
              AND e.exam_date = '{$exam['exam_date']}'
              AND (
                STR_TO_DATE(CONCAT(e.exam_date, ' ', e.start_time), '%Y-%m-%d %H:%i:%s') < FROM_UNIXTIME($exam_end)
                AND 
                STR_TO_DATE(CONCAT(e.exam_date, ' ', e.start_time), '%Y-%m-%d %H:%i:%s') + INTERVAL (e.duration) MINUTE > FROM_UNIXTIME($exam_start)
              )
        ");
        
        $blocked_seats = [];
        $rooms_with_conflicts = [];
        while ($b = $blocked_query->fetch_assoc()) {
            $key = $b['room_id'] . '_' . $b['row_position'] . '_' . $b['column_position'];
            $blocked_seats[$key] = $b;
            $rooms_with_conflicts[$b['room_id']] = true;
        }
        
        $insert_stmt = $conn->prepare("
            INSERT INTO seating_arrangements 
            (exam_id, student_id, room_id, seat_number, row_position, column_position) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $assigned_count = 0;
        $student_index = 0;
        $rooms_used = [];
        
        foreach ($rooms as $room) {
            if ($student_index >= count($students)) break;
            
            $room_occupied = [];
            
            // Load existing assignments in this room for THIS exam (should be empty)
            $existing_in_room = $conn->query("
                SELECT row_position, column_position, student_id
                FROM seating_arrangements
                WHERE exam_id = $exam_id AND room_id = {$room['id']}
            ");
            while ($ex = $existing_in_room->fetch_assoc()) {
                $key = $ex['row_position'] . '_' . $ex['column_position'];
                $room_occupied[$key] = $ex['student_id'];
            }
            
            for ($r = 0; $r < $room['total_rows']; $r++) {
                for ($c = 0; $c < $room['total_columns']; $c++) {
                    if ($student_index >= count($students)) break 2;
                    
                    $seat_key = $r . '_' . $c;
                    $global_key = $room['id'] . '_' . $r . '_' . $c;
                    
                    // Skip if blocked by time-overlapping exam
                    if (isset($blocked_seats[$global_key])) {
                        continue;
                    }
                    
                    // Skip if already occupied by this exam
                    if (isset($room_occupied[$seat_key])) {
                        continue;
                    }
                    
                    $student = $students[$student_index];
                    
                    // Check adjacency for same exam students only
                    $has_conflict = false;
                    $adjacent = [[$r,$c-1],[$r,$c+1],[$r-1,$c],[$r+1,$c]];
                    
                    foreach ($adjacent as $pos) {
                        list($ar, $ac) = $pos;
                        if ($ar < 0 || $ar >= $room['total_rows'] || 
                            $ac < 0 || $ac >= $room['total_columns']) continue;
                        
                        $adj_key = $ar . '_' . $ac;
                        if (isset($room_occupied[$adj_key])) {
                            $neighbor_id = $room_occupied[$adj_key];
                            
                            // Find neighbor details
                            foreach ($students as $s) {
                                if ($s['id'] == $neighbor_id) {
                                    if (trim(strtolower($s['department'])) === trim(strtolower($student['department'])) && 
                                        intval($s['semester']) === intval($student['semester'])) {
                                        $has_conflict = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    
                    if (!$has_conflict) {
                        $seat_number = chr(65 + $r) . ($c + 1);
                        
                        $insert_stmt->bind_param("iiisii", 
                            $exam_id, 
                            $student['id'], 
                            $room['id'], 
                            $seat_number, 
                            $r, 
                            $c
                        );
                        
                        if ($insert_stmt->execute()) {
                            $room_occupied[$seat_key] = $student['id'];
                            $student_index++;
                            $assigned_count++;
                            $rooms_used[$room['id']] = $room['room_number'];
                        }
                    }
                }
            }
        }
        
        $insert_stmt->close();
        commitTransaction();
        
        $unassigned = count($students) - $assigned_count;
        
        if ($unassigned > 0) {
            $success = "⚠️ Assigned $assigned_count of " . count($students) . " students ($unassigned still unassigned due to spacing constraints).";
            if (count($rooms_with_conflicts) > 0) {
                $success .= "<br><small>Note: Some seats were unavailable due to time conflicts with other exams in rooms: " . 
                    implode(', ', array_keys($rooms_with_conflicts)) . "</small>";
            }
        } else {
            $success = "✅ Successfully assigned all $assigned_count students!";
            if (count($rooms_used) > 0) {
                $success .= "<br><small>Rooms used: " . implode(', ', $rooms_used) . "</small>";
            }
        }
        
        $_SESSION['seating_success'] = $success;
        redirect("view_seating.php?exam_id=$exam_id");
        
    } catch (Exception $e) {
        rollbackTransaction();
        $error = "Error: " . $e->getMessage();
    }
}

// Display success/error messages
if (isset($_SESSION['seating_success'])) {
    $success = $_SESSION['seating_success'];
    unset($_SESSION['seating_success']);
}

if (isset($_SESSION['bulk_assign_success'])) {
    $success = $_SESSION['bulk_assign_success'];
    unset($_SESSION['bulk_assign_success']);
}

// Get exams
$exams = $conn->query("
    SELECT 
        e.*, 
        r.room_number, 
        r.building,
        (SELECT COUNT(*) 
         FROM students 
         WHERE TRIM(LOWER(department)) = TRIM(LOWER(e.department)) 
         AND semester = e.semester) as total_students,
        (SELECT COUNT(*) 
         FROM seating_arrangements 
         WHERE exam_id = e.id) as assigned_students
    FROM exams e
    LEFT JOIN rooms r ON e.room_id = r.id
    WHERE e.status = 'scheduled'
    ORDER BY e.exam_date, e.start_time
");

$total_exams = $exams->num_rows;
$exams->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seating Arrangement - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Smart Seating Arrangement</h1>
                <p>Automatic allocation with multi-exam room sharing</p>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Bulk Assignment Section -->
        <?php if ($total_exams > 0): ?>
        <div class="card" style="border: 3px solid #6366f1; background: linear-gradient(135deg, #f0f4ff 0%, #e3f2fd 100%);">
            <div class="card-header" style="background: linear-gradient(135deg, #52c0ecff, #1726adff); color: white;">
                <h2>🚀 Bulk Assignment</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 8px; color: #1e293b;">Auto-Assign All Exams</h3>
                        <p style="color: #64748b; margin-bottom: 12px;">
                            Automatically assign ALL <?php echo $total_exams; ?> scheduled exam(s) at once with intelligent room sharing:
                        </p>
                        <ul style="color: #64748b; margin-left: 20px; line-height: 1.8;">
                            <li><strong>Reset all existing assignments</strong> and start fresh</li>
                            <li><strong>Share rooms between exams</strong> at different times</li>
                            <li><strong>Fill all available seats</strong> efficiently</li>
                            <li><strong>Prevent same dept/semester</strong> students from sitting adjacent</li>
                            <li><strong>Allow different exams</strong> to use adjacent seats</li>
                            <li><strong>Handle time conflicts</strong> automatically</li>
                        </ul>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="auto_assign_all" class="btn btn-primary" 
                                    style="padding: 20px 32px; font-size: 16px; font-weight: 700;"
                                    onclick="return confirm('⚠️ AUTO-ASSIGN ALL EXAMS?\n\nThis will:\n✓ CLEAR all existing seat assignments\n✓ Reassign ALL <?php echo $total_exams; ?> exams automatically\n✓ Optimize space across all rooms\n✓ Allow room sharing between exams\n\nContinue?')">
                                🚀 AUTO-ASSIGN ALL EXAMS
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card" style="margin-top: 16px;">
            <div class="card-header" style="background: linear-gradient(135deg, #57c799ff, #07a84ffc);">
                <h2>📝 Individual Exam Assignment</h2>
            </div>
            <div class="card-body">
                <div class="seating-grid">
                    <?php while ($exam = $exams->fetch_assoc()): ?>
                        <?php 
                        $is_assigned = $exam['assigned_students'] > 0;
                        $is_locked = $is_assigned;
                        ?>
                        <div class="seating-card" style="<?php echo $is_locked ? 'border: 2px solid #10b981; background: #f0fdf4;' : ''; ?>">
                            <?php if ($is_locked): ?>
                                <div style="background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-bottom: 12px; display: inline-block;">
                                    🔒 ASSIGNED
                                </div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($exam['exam_name']); ?></h3>
                            <p class="seating-info"><?php echo htmlspecialchars($exam['course_code']); ?></p>
                            <p class="seating-info"><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?> at <?php echo date('h:i A', strtotime($exam['start_time'])); ?></p>
                            <p class="seating-info">
                                <strong>Department:</strong> <?php echo htmlspecialchars($exam['department']); ?><br>
                                <strong>Semester:</strong> <?php echo $exam['semester']; ?>
                            </p>
                            
                            <div class="progress-info">
                                <span><?php echo $exam['assigned_students']; ?> / <?php echo $exam['total_students']; ?> assigned</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $exam['total_students'] > 0 ? ($exam['assigned_students'] / $exam['total_students'] * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="seating-actions">
                                <?php if ($is_locked): ?>
                                    <button class="btn btn-full" style="background: #94a3b8; cursor: not-allowed;" disabled>
                                        🔒 Use Manual or Bulk Assign
                                    </button>
                                <?php elseif ($exam['total_students'] > 0): ?>
                                    <form method="POST" style="display:inline; width: 100%;">
                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                        <button type="submit" name="auto_assign" class="btn btn-primary btn-full">
                                            ⚡ Auto Assign
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($exam['assigned_students'] > 0): ?>
                                    <a href="view_seating.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-secondary btn-full">
                                        👁️ View Plan
                                    </a>
                                <?php endif; ?>
                                
                                <a href="manual_seating.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-full" style="background: #10b981; color: white;">
                                    ✏️ Manual Assignment
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 16px;">
            <div class="card-header" style="background: linear-gradient(135deg, #ebd37cff, #fabf11ff);">
                <h2>ℹ️ Important Information</h2>
            </div>
            <div class="card-body">
                <div class="info-box" style="background: #f0f4ff; padding: 20px; border-radius: 8px; border-left: 4px solid #6366f1;">
                    <strong>Assignment Methods:</strong>
                    <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                        <li><strong>🚀 Bulk Assignment:</strong> Resets and assigns ALL exams optimally. Allows multiple exams to share rooms at different times.</li>
                        <li><strong>⚡ Individual Auto-Assign:</strong> Assigns one exam, respecting existing assignments from other exams. Will skip seats already taken.</li>
                        <li><strong>✏️ Manual Assignment:</strong> Fine-tune individual seats for any exam.</li>
                        <li><strong>🔒 Lock System:</strong> Once auto-assigned, exams are locked to prevent accidents.</li>
                        <li><strong>📐 Spacing Rules:</strong> Same dept/semester cannot sit adjacent, but DIFFERENT exams CAN share adjacent seats (at different times).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
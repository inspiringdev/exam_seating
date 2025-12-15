<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// =====================================================
// BULK AUTO-ASSIGN ALL EXAMS
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign_all'])) {
    beginTransaction();
    
    try {
        // Step 1: Clear ALL existing assignments
        $conn->query("DELETE FROM seating_arrangements");
        
        // Step 2: Get all scheduled exams
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
        
        // Step 3: Get all available rooms
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
        
        $insert_stmt = $conn->prepare("
            INSERT INTO seating_arrangements 
            (exam_id, student_id, room_id, seat_number, row_position, column_position) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Global tracking: Which physical seats are occupied (by ANY exam)
        // Format: "room_id_row_col" => true
        $globally_occupied_seats = [];
        
        // Track assignments per exam per room
        // Format: exam_id => room_id => "row_col" => true
        $exam_room_assignments = [];
        
        $total_assigned = 0;
        $total_students = 0;
        $assignment_results = [];
        
        // Step 4: Process each exam
        foreach ($all_exams as $exam) {
            $exam_id = $exam['id'];
            
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
                $total_students++;
            }
            
            if (count($students) === 0) continue;
            
            $student_index = 0;
            $assigned_in_exam = 0;
            
            // Try to assign all students for this exam
            foreach ($rooms as $room) {
                if ($student_index >= count($students)) break;
                
                $room_id = $room['id'];
                
                // Initialize tracking for this exam in this room
                if (!isset($exam_room_assignments[$exam_id])) {
                    $exam_room_assignments[$exam_id] = [];
                }
                if (!isset($exam_room_assignments[$exam_id][$room_id])) {
                    $exam_room_assignments[$exam_id][$room_id] = [];
                }
                
                // Go through each seat in the room
                for ($r = 0; $r < $room['total_rows']; $r++) {
                    for ($c = 0; $c < $room['total_columns']; $c++) {
                        if ($student_index >= count($students)) break 2;
                        
                        $global_seat_key = $room_id . '_' . $r . '_' . $c;
                        $local_seat_key = $r . '_' . $c;
                        
                        // CHECK 1: Is this physical seat already occupied by ANY exam?
                        if (isset($globally_occupied_seats[$global_seat_key])) {
                            continue; // Seat is taken, skip it
                        }
                        
                        // CHECK 2: Are adjacent seats occupied by THIS SAME EXAM?
                        $adjacent_positions = [
                            [$r-1, $c],     // Above
                            [$r+1, $c],     // Below
                            [$r, $c-1],     // Left
                            [$r, $c+1]      // Right
                        ];
                        
                        $has_same_exam_adjacent = false;
                        
                        foreach ($adjacent_positions as $pos) {
                            list($ar, $ac) = $pos;
                            
                            // Skip if out of bounds
                            if ($ar < 0 || $ar >= $room['total_rows'] || 
                                $ac < 0 || $ac >= $room['total_columns']) {
                                continue;
                            }
                            
                            $adj_key = $ar . '_' . $ac;
                            
                            // Check if adjacent seat has a student from THIS exam
                            if (isset($exam_room_assignments[$exam_id][$room_id][$adj_key])) {
                                $has_same_exam_adjacent = true;
                                break;
                            }
                        }
                        
                        // If adjacent seat has same exam student, skip
                        if ($has_same_exam_adjacent) {
                            continue;
                        }
                        
                        // All checks passed - assign student
                        $student = $students[$student_index];
                        $seat_number = chr(65 + $r) . ($c + 1);
                        
                        $insert_stmt->bind_param("iiisii", 
                            $exam_id, 
                            $student['id'], 
                            $room_id, 
                            $seat_number, 
                            $r, 
                            $c
                        );
                        
                        if ($insert_stmt->execute()) {
                            // Mark seat as occupied globally
                            $globally_occupied_seats[$global_seat_key] = true;
                            
                            // Mark seat as occupied by this exam in this room
                            $exam_room_assignments[$exam_id][$room_id][$local_seat_key] = true;
                            
                            $student_index++;
                            $assigned_in_exam++;
                            $total_assigned++;
                        }
                    }
                }
            }
            
            $assignment_results[] = [
                'exam' => $exam['exam_name'],
                'assigned' => $assigned_in_exam,
                'total' => count($students)
            ];
        }
        
        $insert_stmt->close();
        commitTransaction();
        
        $success = " Successfully assigned $total_assigned of $total_students students across " . count($all_exams) . " exams!<br><br>";
        $success .= "<strong>Details:</strong><ul style='margin-top: 8px; padding-left: 20px;'>";
        foreach ($assignment_results as $result) {
            $icon = ($result['assigned'] >= $result['total']) ? '' : '';
            $success .= "<li>$icon {$result['exam']}: {$result['assigned']}/{$result['total']} students</li>";
        }
        $success .= "</ul>";
        
        $_SESSION['bulk_assign_success'] = $success;
        redirect("seating.php");
        
    } catch (Exception $e) {
        rollbackTransaction();
        $error = "Bulk Assignment Error: " . $e->getMessage();
    }
}

// =====================================================
// INDIVIDUAL AUTO-ASSIGN - ONE EXAM
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
            throw new Exception("This exam already has assignments! Clear them first.");
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
            throw new Exception("No students found");
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
        
        // Get globally occupied seats (by other exams)
        $occupied_result = $conn->query("
            SELECT room_id, row_position, column_position
            FROM seating_arrangements
            WHERE exam_id != $exam_id
        ");
        
        $globally_occupied_seats = [];
        while ($occ = $occupied_result->fetch_assoc()) {
            $key = $occ['room_id'] . '_' . $occ['row_position'] . '_' . $occ['column_position'];
            $globally_occupied_seats[$key] = true;
        }
        
        $insert_stmt = $conn->prepare("
            INSERT INTO seating_arrangements 
            (exam_id, student_id, room_id, seat_number, row_position, column_position) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $assigned_count = 0;
        $student_index = 0;
        $rooms_used = [];
        
        // Assign students room by room
        foreach ($rooms as $room) {
            if ($student_index >= count($students)) break;
            
            $room_id = $room['id'];
            $this_room_assignments = []; // Track THIS exam's assignments in THIS room
            
            // Fill room sequentially
            for ($r = 0; $r < $room['total_rows']; $r++) {
                for ($c = 0; $c < $room['total_columns']; $c++) {
                    if ($student_index >= count($students)) break 2;
                    
                    $global_seat_key = $room_id . '_' . $r . '_' . $c;
                    $local_seat_key = $r . '_' . $c;
                    
                    // CHECK 1: Is seat occupied by another exam?
                    if (isset($globally_occupied_seats[$global_seat_key])) {
                        continue;
                    }
                    
                    // CHECK 2: Adjacent seats with THIS exam?
                    $adjacent_positions = [
                        [$r-1, $c], [$r+1, $c], [$r, $c-1], [$r, $c+1]
                    ];
                    
                    $has_same_exam_adjacent = false;
                    
                    foreach ($adjacent_positions as $pos) {
                        list($ar, $ac) = $pos;
                        
                        if ($ar < 0 || $ar >= $room['total_rows'] || 
                            $ac < 0 || $ac >= $room['total_columns']) {
                            continue;
                        }
                        
                        $adj_key = $ar . '_' . $ac;
                        
                        if (isset($this_room_assignments[$adj_key])) {
                            $has_same_exam_adjacent = true;
                            break;
                        }
                    }
                    
                    if ($has_same_exam_adjacent) {
                        continue;
                    }
                    
                    // Assign student
                    $student = $students[$student_index];
                    $seat_number = chr(65 + $r) . ($c + 1);
                    
                    $insert_stmt->bind_param("iiisii", 
                        $exam_id, 
                        $student['id'], 
                        $room_id, 
                        $seat_number, 
                        $r, 
                        $c
                    );
                    
                    if ($insert_stmt->execute()) {
                        $this_room_assignments[$local_seat_key] = true;
                        $student_index++;
                        $assigned_count++;
                        $rooms_used[$room_id] = $room['room_number'];
                    }
                }
            }
        }
        
        $insert_stmt->close();
        commitTransaction();
        
        $unassigned = count($students) - $assigned_count;
        
        if ($unassigned > 0) {
            $success = " Assigned $assigned_count of " . count($students) . " students ($unassigned unassigned due to spacing constraints).";
        } else {
            $success = " Successfully assigned all $assigned_count students!";
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

// Display messages
if (isset($_SESSION['seating_success'])) {
    $success = $_SESSION['seating_success'];
    unset($_SESSION['seating_success']);
}

if (isset($_SESSION['bulk_assign_success'])) {
    $success = $_SESSION['bulk_assign_success'];
    unset($_SESSION['bulk_assign_success']);
}

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
                <h2>Bulk Assignment</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 8px; color: #1e293b;">Auto-Assign All Exams</h3>
                        <p style="color: #64748b; margin-bottom: 12px;">
                            Automatically assign ALL <?php echo $total_exams; ?> scheduled exam(s) at once with intelligent room sharing:
                        </p>
                        <ul style="color: #64748b; margin-left: 20px; line-height: 1.8;">
                            <li>Reset all existing assignments and start fresh</li>
                            <li>Share rooms between exams at different times</li>
                            <li>Fill all available seats efficiently</li>
                            <li>Prevent same dept/semester students from sitting adjacent</li>
                            <li>Allow different exams to use adjacent seats</li>
                            <li>Handle time conflicts automatically</li>
                        </ul>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="auto_assign_all" class="btn btn-primary" 
                                    style="padding: 20px 32px; font-size: 16px; font-weight: 700;"
                                    onclick="return confirm(' AUTO-ASSIGN ALL EXAMS?\n\nThis will:\n✓ CLEAR all existing seat assignments\n✓ Reassign ALL <?php echo $total_exams; ?> exams automatically\n✓ Optimize space across all rooms\n✓ Allow room sharing between exams\n\nContinue?')">
                                AUTO-ASSIGN ALL EXAMS
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card" style="margin-top: 16px;">
            <div class="card-header" style="background: linear-gradient(135deg, #57c799ff, #07a84ffc);">
                <h2>Individual Exam Assignment</h2>
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
                                ASSIGNED
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
                                     Use Manual or Bulk Assign
                                    </button>
                                <?php elseif ($exam['total_students'] > 0): ?>
                                    <form method="POST" style="display:inline; width: 100%;">
                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                        <button type="submit" name="auto_assign" class="btn btn-primary btn-full">
                                     Auto Assign
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($exam['assigned_students'] > 0): ?>
                                    <a href="view_seating.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-secondary btn-full">
                                     View Plan
                                    </a>
                                <?php endif; ?>
                                
                                <a href="manual_seating.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-full" style="background: #10b981; color: white;">
                                     Manual Assignment
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        
    </div>
    
    <script src="script.js"></script>
</body>
</html>

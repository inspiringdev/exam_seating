<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exam'])) {
    $name     = sanitize($_POST['exam_name']);
    $code     = sanitize($_POST['course_code']);
    $date     = $_POST['exam_date'];
    $start    = $_POST['start_time'];
    $end      = $_POST['end_time'];
    $dept     = sanitize($_POST['department']);
    $sem      = intval($_POST['semester']);
    $room_id  = intval($_POST['room_id']);

    // ============================================================
    // STEP 1: Get Total Room Capacity
    // ============================================================
    $cap_query = $conn->query("SELECT capacity, room_number FROM rooms WHERE id = $room_id");
    $room_data = $cap_query->fetch_assoc();
    $room_capacity = $room_data['capacity'];
    $room_name = $room_data['room_number'];

    // ============================================================
    // STEP 2: Count Students in the NEW Exam (The one you are adding)
    // ============================================================
    $new_stu_query = $conn->prepare("SELECT COUNT(*) as cnt FROM students WHERE department = ? AND semester = ?");
    $new_stu_query->bind_param("si", $dept, $sem);
    $new_stu_query->execute();
    $new_exam_students = $new_stu_query->get_result()->fetch_assoc()['cnt'];

    // ============================================================
    // STEP 3: Calculate Occupied Seats by OVERLAPPING Exams
    // ============================================================
    // We find exams that clash with your time, but instead of stopping, 
    // we count their students to see if there is still space.
    
    $overlap_query = $conn->prepare("
        SELECT department, semester 
        FROM exams 
        WHERE room_id = ? 
        AND exam_date = ? 
        AND (
            (start_time < ? AND end_time > ?) -- Standard overlap logic
        )
    ");
    // Logic: An exam overlaps if it starts before you end, AND ends after you start
    $overlap_query->bind_param("isss", $room_id, $date, $end, $start);
    $overlap_query->execute();
    $result = $overlap_query->get_result();

    $current_occupied_seats = 0;

    while($row = $result->fetch_assoc()) {
        // Count students for this EXISTING overlapping exam
        $exist_stu_query = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE department = '{$row['department']}' AND semester = {$row['semester']}");
        $current_occupied_seats += $exist_stu_query->fetch_assoc()['cnt'];
    }

    $total_needed = $current_occupied_seats + $new_exam_students;

    // ============================================================
    // STEP 4: The Decision
    // ============================================================
    
    if ($total_needed > $room_capacity) {
        // FAIL: The room is actually full
        $error_msg = "Cannot schedule! Room $room_name Capacity: $room_capacity.<br>";
        $error_msg .= "Existing overlapping exams use: $current_occupied_seats seats.<br>";
        $error_msg .= "You are adding: $new_exam_students students.<br>";
        $error_msg .= "<strong>Total Required: $total_needed</strong>";
        
        echo "<div class='alert alert-danger' style='padding:15px; background:#f8d7da; color:#721c24; text-align:center; margin:10px;'>$error_msg</div>";
    } else {
        // SUCCESS: There is overlap, but there is enough space for both!
        $stmt = $conn->prepare("INSERT INTO exams 
            (exam_name, course_code, exam_date, start_time, end_time, department, semester, room_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssii", $name, $code, $date, $start, $end, $dept, $sem, $room_id);
        $stmt->execute();
        
        // Optional: Show a warning if it's getting tight
        if ($current_occupied_seats > 0) {
             echo "<script>alert('Exam added! Note: This room is shared with another exam at this time.'); window.location='exams.php';</script>";
        } else {
             redirect('exams.php');
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM exams WHERE id = $id");
    redirect('exams.php');
}

$exams = $conn->query("
    SELECT e.*, r.room_number, r.building, COUNT(sa.id) as assigned_count 
    FROM exams e 
    LEFT JOIN rooms r ON e.room_id = r.id
    LEFT JOIN seating_arrangements sa ON e.id = sa.exam_id 
    GROUP BY e.id 
    ORDER BY e.exam_date DESC
");

$departments = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Exams Management</h1>
            <button onclick="openModal('addExamModal')" class="btn btn-primary">Schedule Exam</button>
        </div>
        
        <div class="exams-grid">
            <?php while ($exam = $exams->fetch_assoc()): ?>
            <div class="exam-card">
                <div class="exam-header">
                    <h3><?php echo htmlspecialchars($exam['exam_name']); ?></h3>
                    <p class="exam-course"><?php echo htmlspecialchars($exam['course_code']); ?></p>
                </div>
                
                <div class="exam-details">
                    <div class="exam-info">Date: <?php echo date('D, M d', strtotime($exam['exam_date'])); ?></div>
                    <div class="exam-info">Time: <?php echo date('g:i A', strtotime($exam['start_time'])) . ' - ' . date('g:i A', strtotime($exam['end_time'])); ?></div>
                    <div class="exam-info">Class: <?php echo $exam['department']; ?> - Sem <?php echo $exam['semester']; ?></div>
                    <?php if ($exam['room_number']): ?>
                        <div class="exam-info">Room: <?php echo $exam['room_number']; ?>, <?php echo $exam['building']; ?></div>
                    <?php else: ?>
                        <div class="exam-info text-warning">No room assigned</div>
                    <?php endif; ?>
                    <div class="exam-info">Assigned: <?php echo $exam['assigned_count']; ?> students</div>
                </div>
                
                <div class="exam-actions">
                    <a href="seating.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary">
                        Arrange Seating
                    </a>

                    <a href="view_seating.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-secondary">View Plan</a>
                    <button onclick="if(confirm('Delete?')) location.href='?delete=<?php echo $exam['id']; ?>'" 
                            class="btn btn-sm btn-danger">Delete</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="addExamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Schedule New Exam</h2>
                <span onclick="closeModal('addExamModal')" class="modal-close">x</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Exam Name</label>
                            <input type="text" name="exam_name" required>
                        </div>
                        <div class="form-group">
                            <label>Course Code</label>
                            <input type="text" name="course_code" required>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="exam_date" required>
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department" required>
                                <option value="">Select department...</option>
                                <?php 
                                $dept_query = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
                                while($d = $dept_query->fetch_assoc()): ?>

                                <option value="<?=htmlspecialchars($d['department'])?>">
                                    <?=htmlspecialchars($d['department'])?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <input type="number" name="semester" min="1" max="8" required>
                        </div>
                        <div class="form-group">
                            <label>Room</label>
                            <select name="room_id" id="roomSelect" required>
                                <option value="">Select room...</option>
                                <?php
                                $rooms = $conn->query("SELECT id, room_number, building, capacity FROM rooms ORDER BY room_number");
                                while($r = $rooms->fetch_assoc()){
                                    echo "<option value='{$r['id']}'>{$r['room_number']} – {$r['building']} ({$r['capacity']} seats)</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('addExamModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="add_exam" class="btn btn-primary">Schedule Exam</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    
</body>
</html>
<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

header('Content-Type: application/json');

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'get_student_details') {
        $roll_number = isset($_GET['roll_number']) ? sanitize($_GET['roll_number']) : '';
        
        if (empty($roll_number)) {
            echo json_encode(['success' => false, 'message' => 'Roll number is required']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT * FROM students WHERE roll_number = ?");
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($student = $result->fetch_assoc()) {
            $student_id = $student['id'];
            
            $seating_query = $conn->prepare("
                SELECT 
                    sa.seat_number,
                    sa.row_position,
                    sa.column_position,
                    r.room_number,
                    r.building,
                    e.exam_name,
                    e.course_code,
                    e.exam_date,
                    e.start_time,
                    e.end_time
                FROM seating_arrangements sa
                INNER JOIN rooms r ON sa.room_id = r.id
                INNER JOIN exams e ON sa.exam_id = e.id
                WHERE sa.student_id = ?
                ORDER BY e.exam_date ASC
            ");
            $seating_query->bind_param("i", $student_id);
            $seating_query->execute();
            $seating_result = $seating_query->get_result();
            
            $exams = [];
            while ($seat = $seating_result->fetch_assoc()) {
                $exams[] = $seat;
            }
            
            echo json_encode([
                'success' => true,
                'student' => $student,
                'exams' => $exams
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found. Please check your roll number.']);
        }
        
    } elseif ($action === 'get_all_exams') {
        $roll_number = isset($_GET['roll_number']) ? sanitize($_GET['roll_number']) : '';
        
        if (empty($roll_number)) {
            echo json_encode(['success' => false, 'message' => 'Roll number is required']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT id, department, semester FROM students WHERE roll_number = ?");
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($student = $result->fetch_assoc()) {
            $exams_query = $conn->prepare("
                SELECT 
                    e.*,
                    sa.seat_number,
                    r.room_number,
                    r.building
                FROM exams e
                LEFT JOIN seating_arrangements sa ON e.id = sa.exam_id AND sa.student_id = ?
                LEFT JOIN rooms r ON sa.room_id = r.id
                WHERE e.department = ? AND e.semester = ?
                ORDER BY e.exam_date ASC
            ");
            $exams_query->bind_param("isi", $student['id'], $student['department'], $student['semester']);
            $exams_query->execute();
            $exams_result = $exams_query->get_result();
            
            $exams = [];
            while ($exam = $exams_result->fetch_assoc()) {
                $exams[] = $exam;
            }
            
            echo json_encode([
                'success' => true,
                'exams' => $exams
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
        
    } elseif ($action === 'get_seat_map') {
        $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
        $roll_number = isset($_GET['roll_number']) ? sanitize($_GET['roll_number']) : '';
        
        if (empty($roll_number) || $exam_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
            exit;
        }
        
        $student_query = $conn->prepare("SELECT id FROM students WHERE roll_number = ?");
        if (!$student_query) {
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            exit;
        }
        
        $student_query->bind_param("s", $roll_number);
        $student_query->execute();
        $student_result = $student_query->get_result();
        
        if (!$student_result || $student_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            exit;
        }
        
        $student = $student_result->fetch_assoc();
        $student_id = intval($student['id']);
        
        $seat_query = $conn->prepare("
            SELECT 
                sa.seat_number,
                sa.row_position,
                sa.column_position,
                sa.room_id,
                r.room_number,
                r.building,
                r.total_rows,
                r.total_columns,
                r.capacity
            FROM seating_arrangements sa
            INNER JOIN rooms r ON sa.room_id = r.id
            WHERE sa.exam_id = ? AND sa.student_id = ?
        ");
        
        if (!$seat_query) {
            echo json_encode(['success' => false, 'message' => 'Database query error']);
            exit;
        }
        
        $seat_query->bind_param("ii", $exam_id, $student_id);
        $seat_query->execute();
        $seat_result = $seat_query->get_result();
        
        if (!$seat_result || $seat_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Your seat has not been assigned yet for this exam']);
            exit;
        }
        
        $my_seat = $seat_result->fetch_assoc();
        $room_id = intval($my_seat['room_id']);
        
        $all_seats_query = $conn->prepare("
            SELECT 
                sa.seat_number,
                sa.row_position,
                sa.column_position,
                s.roll_number,
                s.name
            FROM seating_arrangements sa
            INNER JOIN students s ON sa.student_id = s.id
            WHERE sa.exam_id = ? AND sa.room_id = ?
            ORDER BY sa.row_position, sa.column_position
        ");
        
        if (!$all_seats_query) {
            echo json_encode(['success' => false, 'message' => 'Database query error']);
            exit;
        }
        
        $all_seats_query->bind_param("ii", $exam_id, $room_id);
        $all_seats_query->execute();
        $all_seats_result = $all_seats_query->get_result();
        
        $seat_map = [];
        if ($all_seats_result) {
            while ($s = $all_seats_result->fetch_assoc()) {
                $seat_map[] = $s;
            }
        }
        
        echo json_encode([
            'success' => true,
            'my_seat' => $my_seat,
            'seat_map' => $seat_map
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
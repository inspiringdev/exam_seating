<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if (!$exam_id || !$room_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Get all occupied seats for THIS exam in this room
    $seats_result = $conn->query("
        SELECT sa.*, s.name, s.roll_number, s.department, s.semester
        FROM seating_arrangements sa
        INNER JOIN students s ON sa.student_id = s.id
        WHERE sa.exam_id = $exam_id AND sa.room_id = $room_id
    ");
    
    $seats = [];
    while ($seat = $seats_result->fetch_assoc()) {
        $seats[] = [
            'id' => $seat['id'],
            'row_position' => intval($seat['row_position']),
            'column_position' => intval($seat['column_position']),
            'seat_number' => $seat['seat_number'],
            'name' => $seat['name'],
            'roll_number' => $seat['roll_number'],
            'department' => $seat['department'],
            'semester' => $seat['semester']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'seats' => $seats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
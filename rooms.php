<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_num = sanitize($_POST['room_number']);
    $building = sanitize($_POST['building']);
    $capacity = intval($_POST['capacity']);
    $rows = intval($_POST['rows']);
    $columns = intval($_POST['columns']);
    
    $stmt = $conn->prepare("INSERT INTO rooms (room_number, building, capacity, total_rows, total_columns) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiii", $room_num, $building, $capacity, $rows, $columns);
    $stmt->execute();
    redirect('rooms.php');
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM rooms WHERE id = $id");
    redirect('rooms.php');
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE rooms SET available = NOT available WHERE id = $id");
    redirect('rooms.php');
}

$rooms = $conn->query("
    SELECT r.*, 
    COUNT(DISTINCT sa.exam_id) as exam_count,
    (SELECT COUNT(*) FROM seating_arrangements WHERE room_id = r.id) as total_seats_used
    FROM rooms r 
    LEFT JOIN seating_arrangements sa ON r.id = sa.room_id 
    GROUP BY r.id 
    ORDER BY r.building, r.room_number
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .room-preview-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 100%;
        }
        .room-preview-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-2px);
        }
        .usage-indicator {
            margin-top: 12px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 6px;
            font-size: 13px;
        }
        .usage-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }
        .usage-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Examination Rooms</h1>
                <p>Manage exam venues and capacity</p>
            </div>
            <button onclick="openModal('addRoomModal')" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Room
            </button>
        </div>
        
        <div class="rooms-grid">
            <?php while ($room = $rooms->fetch_assoc()): ?>
            <?php
                $capacity = $room['total_rows'] * $room['total_columns'];
                $usage_percent = $capacity > 0 ? ($room['total_seats_used'] / $capacity) * 100 : 0;
            ?>
            <div class="room-item <?php echo !$room['available'] ? 'room-disabled' : ''; ?>">
                <div class="room-header">
                    <div class="room-title">
                        <h3>Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                        <p><?php echo htmlspecialchars($room['building']); ?></p>
                    </div>
                    <?php if ($room['available']): ?>
                        <span class="badge badge-success">Available</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Disabled</span>
                    <?php endif; ?>
                </div>
                
                <div class="room-details">
                    <div class="room-stat">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Capacity: <?php echo $capacity; ?> seats</span>
                    </div>
                    
                    <div class="room-stat">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M9 3v18"></path>
                        </svg>
                        <span>Layout: <?php echo $room['total_rows']; ?> × <?php echo $room['total_columns']; ?></span>
                    </div>
                    
                    <div class="room-stat">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span><?php echo $room['exam_count']; ?> exams scheduled</span>
                    </div>
                </div>
                
                <div class="usage-indicator">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Usage</span>
                        <span style="color: #64748b; font-weight: 600;">
                            <?php echo $room['total_seats_used']; ?> / <?php echo $capacity; ?>
                        </span>
                    </div>
                    <div class="usage-bar">
                        <div class="usage-fill" style="width: <?php echo min($usage_percent, 100); ?>%"></div>
                    </div>
                </div>
                
                <div class="room-actions" style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="room_visualization.php?room_id=<?php echo $room['id']; ?>" class="btn room-preview-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View Room Layout
                    </a>
                    
                    <div style="display: flex; gap: 8px;">
                        <a href="?toggle=<?php echo $room['id']; ?>" class="btn btn-sm" style="flex: 1;">
                            <?php echo $room['available'] ? 'Disable' : 'Enable'; ?>
                        </a>
                        <button onclick="if(confirm('Delete this room? This will also remove all seating assignments.')) location.href='?delete=<?php echo $room['id']; ?>'" class="btn btn-sm btn-danger" style="flex: 1;">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Room</h2>
                <button onclick="closeModal('addRoomModal')" class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Room Number</label>
                            <input type="text" name="room_number" required>
                        </div>
                        <div class="form-group">
                            <label>Building</label>
                            <input type="text" name="building" required>
                        </div>
                        <div class="form-group">
                            <label>Capacity</label>
                            <input type="number" name="capacity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Rows</label>
                            <input type="number" name="rows" min="1" max="20" required>
                        </div>
                        <div class="form-group">
                            <label>Columns</label>
                            <input type="number" name="columns" min="1" max="10" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('addRoomModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
<?php
require_once 'config.php';
requireLogin();

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

$room = $conn->query("SELECT * FROM rooms WHERE id = $room_id")->fetch_assoc();
if (!$room) {
    redirect('rooms.php');
}

$exams = $conn->query("
    SELECT DISTINCT e.* 
    FROM exams e
    INNER JOIN seating_arrangements sa ON e.id = sa.exam_id
    WHERE sa.room_id = $room_id AND e.status = 'scheduled'
    ORDER BY e.exam_date, e.start_time
");

$exam_colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f43f5e', '#84cc16'];
$exam_list = [];
$color_index = 0;
while ($exam = $exams->fetch_assoc()) {
    $exam['color'] = $exam_colors[$color_index % count($exam_colors)];
    $exam_list[] = $exam;
    $color_index++;
}

$has_exams = count($exam_list) > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room <?php echo htmlspecialchars($room['room_number']); ?> - Visualization</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .exam-selector {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .exam-tabs {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .exam-tab {
            padding: 14px 24px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }
        .exam-tab:hover {
            border-color: currentColor;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .exam-tab.active {
            border-width: 3px;
            font-weight: 600;
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .exam-tab.combined-tab {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f59e0b 100%);
            color: white;
            border: none;
            font-weight: 700;
            font-size: 16px;
        }
        .exam-tab.combined-tab:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .exam-tab.combined-tab.active {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
        }
        .exam-color-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        .combined-icon {
            display: flex;
            gap: 2px;
        }
        .combined-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .visualization-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
        }
        .seat-grid-viz {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }
        .seat-viz {
            min-height: 85px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            padding: 8px;
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }
        .seat-viz.occupied {
            color: white;
            border: none;
            font-weight: 600;
        }
        .seat-viz:hover {
            transform: scale(1.08);
            z-index: 10;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        .seat-viz.empty {
            background: #f8fafc;
            border-style: dashed;
        }
        .exam-indicator {
            position: absolute;
            top: 3px;
            right: 3px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid white;
        }
        .legend {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
            margin-top: 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .legend-box {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: linear-gradient(135deg, #f0f4ff, #e3f2fd);
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #6366f1;
        }
        .stat-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
            display: none;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>🏛️ Room <?php echo htmlspecialchars($room['room_number']); ?></h1>
                <p><?php echo htmlspecialchars($room['building']); ?> • Layout: <?php echo $room['total_rows']; ?>×<?php echo $room['total_columns']; ?> (<?php echo $room['total_rows'] * $room['total_columns']; ?> seats)</p>
            </div>
            <a href="rooms.php" class="btn btn-secondary"  style="background-color: #ffc71fff; color: black;">← Back to Rooms</a>
        </div>
        
        <div class="exam-selector">
            <h3 style="margin-bottom: 16px; color: #1e293b;">Select View Mode:</h3>
            <div class="exam-tabs" id="examTabs">
                <!-- Combined View Button -->
                <?php if ($has_exams): ?>
<div class="exam-tab combined-tab active" data-mode="combined" onclick="selectCombinedView()">
    <div class="combined-icon">
        <div class="combined-dot" style="background: #fff;"></div>
        <div class="combined-dot" style="background: #fff;"></div>
        <div class="combined-dot" style="background: #fff;"></div>
    </div>
    <div>
        <strong style="font-size: 15px;">COMBINED VIEW</strong><br>
        <small style="opacity: 0.9;">All Exams Together</small>
    </div>
</div>
<?php endif; ?>

<?php foreach ($exam_list as $exam): ?>
<div class="exam-tab" 
     data-exam-id="<?php echo $exam['id']; ?>" 
     data-color="<?php echo $exam['color']; ?>"
     onclick="selectExam(<?php echo $exam['id']; ?>, '<?php echo $exam['color']; ?>')">
    <div class="exam-color-dot" style="background: <?php echo $exam['color']; ?>;"></div>
    <div>
        <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong><br>
        <small style="color: #64748b;">
            <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?> • 
            <?php echo date('h:i A', strtotime($exam['start_time'])); ?>
        </small>
    </div>
</div>
<?php endforeach; ?>
            </div>
            
            <?php if (count($exam_list) === 0): ?>
                <div style="text-align: center; padding: 40px; color: #64748b;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 16px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <p>No exams have been assigned to this room yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="visualizationContainer" class="visualization-container" style="display: none;">
            <div class="stats-row" id="examStats"></div>
            
            <div style="text-align: center; padding: 16px; background: #1e293b; color: white; border-radius: 12px; font-weight: 600; margin-bottom: 20px;">
                👨‍🏫 Supervisor's Desk
            </div>
            
            <div id="seatGridViz" class="seat-grid-viz" style="grid-template-columns: repeat(<?php echo $room['total_columns']; ?>, 1fr);"></div>
            
            <div class="legend" id="legend"></div>
        </div>
    </div>
    
    <div id="tooltip" class="tooltip"></div>
    
    <script>
        const roomData = {
            id: <?php echo $room_id; ?>,
            rows: <?php echo $room['total_rows']; ?>,
            columns: <?php echo $room['total_columns']; ?>
        };
        
        const examData = <?php echo json_encode($exam_list); ?>;
        
        let currentMode = null;
        let currentExamId = null;
        let currentColor = null;
        let allSeatsData = {};
        
        // Auto-select combined view if multiple exams, otherwise first exam
        document.addEventListener('DOMContentLoaded', function() {
            if (examData.length > 1) {
                selectCombinedView();
            } else if (examData.length === 1) {
                selectExam(examData[0].id, examData[0].color);
            }
        });
        
        async function selectCombinedView() {
            currentMode = 'combined';
            
            // Update tab selection
            document.querySelectorAll('.exam-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.mode === 'combined') {
                    tab.classList.add('active');
                }
            });
            
            // Load all exam data
            try {
                allSeatsData = {};
                for (const exam of examData) {
                    const response = await fetch(`get_room_seats.php?exam_id=${exam.id}&room_id=${roomData.id}`);
                    const data = await response.json();
                    if (data.success) {
                        allSeatsData[exam.id] = {
                            seats: data.seats,
                            exam: exam
                        };
                    }
                }
                
                renderCombinedView();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load combined view');
            }
        }
        
        function renderCombinedView() {
            const container = document.getElementById('seatGridViz');
            const vizContainer = document.getElementById('visualizationContainer');
            
            vizContainer.style.display = 'block';
            container.innerHTML = '';
            
            // Create combined seat map
            const combinedMap = {};
            let totalOccupied = 0;
            const examCounts = {};
            
            Object.keys(allSeatsData).forEach(examId => {
                const { seats, exam } = allSeatsData[examId];
                examCounts[examId] = seats.length;
                
                seats.forEach(seat => {
                    const key = `${seat.row_position}_${seat.column_position}`;
                    if (!combinedMap[key]) {
                        combinedMap[key] = [];
                    }
                    combinedMap[key].push({
                        ...seat,
                        exam: exam
                    });
                    totalOccupied++;
                });
            });
            
            // Render grid
            for (let r = 0; r < roomData.rows; r++) {
                for (let c = 0; c < roomData.columns; c++) {
                    const key = `${r}_${c}`;
                    const seatData = combinedMap[key];
                    const seatLabel = String.fromCharCode(65 + r) + (c + 1);
                    
                    const seatDiv = document.createElement('div');
                    
                    if (seatData && seatData.length > 0) {
                        // Multiple exams might use this seat at different times
                        const mainSeat = seatData[0];
                        const exam = mainSeat.exam;
                        
                        seatDiv.className = 'seat-viz occupied';
                        seatDiv.style.background = `linear-gradient(135deg, ${exam.color}, ${adjustColor(exam.color, -20)})`;
                        
                        seatDiv.innerHTML = `
                            <div class="exam-indicator" style="background: ${exam.color};"></div>
                            <strong style="font-size: 13px;">${escapeHtml(seatLabel)}</strong>
                            <span style="font-size: 10px; margin-top: 2px;">${escapeHtml(mainSeat.roll_number)}</span>
                            <span style="font-size: 9px; opacity: 0.9;">${escapeHtml(mainSeat.name)}</span>
                        `;
                        
                        // Build tooltip
                        let tooltipText = '';
                        seatData.forEach((s, idx) => {
                            if (idx > 0) tooltipText += '\\n---\\n';
                            tooltipText += `${s.exam.exam_name}\\n${s.roll_number} - ${s.name}\\n${s.department} - Sem ${s.semester}`;
                        });
                        
                        seatDiv.onmouseenter = (e) => showTooltip(e, tooltipText);
                        seatDiv.onmouseleave = hideTooltip;
                    } else {
                        seatDiv.className = 'seat-viz empty';
                        seatDiv.innerHTML = `<strong style="color: #94a3b8; font-size: 14px;">${seatLabel}</strong>`;
                    }
                    
                    container.appendChild(seatDiv);
                }
            }
            
            updateCombinedStats(totalOccupied, examCounts);
            updateCombinedLegend();
        }
        
        async function selectExam(examId, color) {
            currentMode = 'single';
            currentExamId = examId;
            currentColor = color;
            
            // Update tab selection
            document.querySelectorAll('.exam-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.examId == examId) {
                    tab.classList.add('active');
                    tab.style.borderColor = color;
                    tab.style.background = color + '15';
                }
            });
            
            // Load seating data
            try {
                const response = await fetch(`get_room_seats.php?exam_id=${examId}&room_id=${roomData.id}`);
                const data = await response.json();
                
                if (data.success) {
                    renderSingleExamView(data.seats, color);
                    updateSingleStats(data.seats);
                } else {
                    alert('Error loading seating data: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load seating data');
            }
        }
        
        function renderSingleExamView(seats, color) {
            const container = document.getElementById('seatGridViz');
            const vizContainer = document.getElementById('visualizationContainer');
            
            vizContainer.style.display = 'block';
            container.innerHTML = '';
            
            // Create seat map
            const seatMap = {};
            seats.forEach(seat => {
                const key = `${seat.row_position}_${seat.column_position}`;
                seatMap[key] = seat;
            });
            
            // Render grid
            for (let r = 0; r < roomData.rows; r++) {
                for (let c = 0; c < roomData.columns; c++) {
                    const key = `${r}_${c}`;
                    const seat = seatMap[key];
                    const seatLabel = String.fromCharCode(65 + r) + (c + 1);
                    
                    const seatDiv = document.createElement('div');
                    
                    if (seat) {
                        seatDiv.className = 'seat-viz occupied';
                        seatDiv.style.background = `linear-gradient(135deg, ${color}, ${adjustColor(color, -20)})`;
                        seatDiv.innerHTML = `
                            <strong style="font-size: 13px;">${escapeHtml(seatLabel)}</strong>
                            <span style="font-size: 11px; margin-top: 2px;">${escapeHtml(seat.roll_number)}</span>
                            <span style="font-size: 10px; opacity: 0.9;">${escapeHtml(seat.name)}</span>
                        `;
                        seatDiv.title = `${seat.roll_number} - ${seat.name}\\n${seat.department} - Sem ${seat.semester}`;
                    } else {
                        seatDiv.className = 'seat-viz empty';
                        seatDiv.innerHTML = `<strong style="color: #94a3b8;">${seatLabel}</strong>`;
                    }
                    
                    container.appendChild(seatDiv);
                }
            }
            
            updateSingleLegend(color);
        }
        
        function updateSingleStats(seats) {
            const totalCapacity = roomData.rows * roomData.columns;
            const occupied = seats.length;
            const empty = totalCapacity - occupied;
            const percentage = ((occupied / totalCapacity) * 100).toFixed(1);
            
            document.getElementById('examStats').innerHTML = `
                <div class="stat-item">
                    <div class="stat-value">${occupied}</div>
                    <div class="stat-label">Students Assigned</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${empty}</div>
                    <div class="stat-label">Empty Seats</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${totalCapacity}</div>
                    <div class="stat-label">Total Capacity</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${percentage}%</div>
                    <div class="stat-label">Utilization</div>
                </div>
            `;
        }
        
        function updateCombinedStats(totalOccupied, examCounts) {
            const totalCapacity = roomData.rows * roomData.columns;
            const uniqueSeats = new Set();
            
            Object.keys(allSeatsData).forEach(examId => {
                allSeatsData[examId].seats.forEach(seat => {
                    uniqueSeats.add(`${seat.row_position}_${seat.column_position}`);
                });
            });
            
            const uniqueOccupied = uniqueSeats.size;
            const empty = totalCapacity - uniqueOccupied;
            const percentage = ((uniqueOccupied / totalCapacity) * 100).toFixed(1);
            
            let statsHTML = `
                <div class="stat-item">
                    <div class="stat-value">${examData.length}</div>
                    <div class="stat-label">Total Exams</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${uniqueOccupied}</div>
                    <div class="stat-label">Unique Seats Used</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${empty}</div>
                    <div class="stat-label">Available Seats</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${percentage}%</div>
                    <div class="stat-label">Overall Utilization</div>
                </div>
            `;
            
            document.getElementById('examStats').innerHTML = statsHTML;
        }
        
        function updateSingleLegend(color) {
            document.getElementById('legend').innerHTML = `
                <div class="legend-item">
                    <div class="legend-box" style="background: linear-gradient(135deg, ${color}, ${adjustColor(color, -20)});"></div>
                    <span><strong>Occupied</strong> (Current Exam)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background: #f8fafc; border: 2px dashed #cbd5e1;"></div>
                    <span><strong>Empty</strong> Seat</span>
                </div>
            `;
        }
        
        function updateCombinedLegend() {
            let legendHTML = '';
            examData.forEach(exam => {
                const count = allSeatsData[exam.id] ? allSeatsData[exam.id].seats.length : 0;
                legendHTML += `
                    <div class="legend-item">
                        <div class="legend-box" style="background: ${exam.color};"></div>
                        <span><strong>${escapeHtml(exam.exam_name)}</strong> (${count} students)</span>
                    </div>
                `;
            });
            
            legendHTML += `
                <div class="legend-item">
                    <div class="legend-box" style="background: #f8fafc; border: 2px dashed #cbd5e1;"></div>
                    <span><strong>Empty</strong> Seat</span>
                </div>
            `;
            
            document.getElementById('legend').innerHTML = legendHTML;
        }
        
        function showTooltip(e, text) {
            const tooltip = document.getElementById('tooltip');
            tooltip.innerHTML = text.replace(/\\n/g, '<br>');
            tooltip.style.display = 'block';
            tooltip.style.left = (e.pageX + 10) + 'px';
            tooltip.style.top = (e.pageY + 10) + 'px';
        }
        
        function hideTooltip() {
            document.getElementById('tooltip').style.display = 'none';
        }
        
        function adjustColor(color, amount) {
            return '#' + color.replace(/^#/, '').replace(/../g, color => 
                ('0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2)
            );
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($has_exams): ?>
        selectCombinedView();
    <?php endif; ?>
});
</script>
</body>
</html>
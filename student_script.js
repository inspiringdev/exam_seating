let currentRollNumber = '';

document.getElementById('studentLoginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const rollNumber = document.getElementById('roll_number').value.trim();
    const errorDiv = document.getElementById('loginError');
    
    if (!rollNumber) {
        showError('Please enter your roll number');
        return;
    }
    
    try {
        const response = await fetch(`student_api.php?action=get_student_details&roll_number=${encodeURIComponent(rollNumber)}`);
        const data = await response.json();
        
        if (data.success) {
            window.location.href = `student_dashboard.php?roll=${encodeURIComponent(rollNumber)}`;
        } else {
            showError(data.message || 'Student not found. Please check your roll number.');
        }
    } catch (error) {
        showError('An error occurred. Please try again.');
        console.error('Error:', error);
    }
});



function showError(message) {
    const errorDiv = document.getElementById('loginError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }
}

async function loadStudentData(rollNumber) {
    currentRollNumber = rollNumber;
    const loadingSpinner = document.getElementById('loadingSpinner');
    const examsContainer = document.getElementById('examsContainer');
    const noExamsMessage = document.getElementById('noExamsMessage');
    
    try {
        const response = await fetch(`student_api.php?action=get_student_details&roll_number=${encodeURIComponent(rollNumber)}`);
        const data = await response.json();
        
        if (data.success) {
    const student = data.student;
    document.getElementById('studentName').textContent = student.name;
    document.getElementById('studentInfo').textContent = 
        `${student.roll_number} • ${student.department} • Semester ${student.semester}`;
    
    // ADD THESE TWO LINES
    document.getElementById('profileLink').href = `student_profile.php?roll=${encodeURIComponent(rollNumber)}`;
    document.getElementById('profileLink').style.display = 'inline-flex';

    await loadAllExams(rollNumber);
}
    else {
            window.location.href = 'student_login.php';
        }
    } catch (error) {
        console.error('Error loading student data:', error);
        showNotification('Failed to load student data', 'error');
    }
}

async function loadAllExams(rollNumber) {
    const loadingSpinner = document.getElementById('loadingSpinner');
    const examsContainer = document.getElementById('examsContainer');
    const noExamsMessage = document.getElementById('noExamsMessage');
    
    try {
        const response = await fetch(`student_api.php?action=get_all_exams&roll_number=${encodeURIComponent(rollNumber)}`);
        const data = await response.json();
        
        loadingSpinner.style.display = 'none';
        
        if (data.success && data.exams.length > 0) {
            examsContainer.style.display = 'grid';
            examsContainer.innerHTML = '';
            
            data.exams.forEach(exam => {
                const examCard = createExamCard(exam);
                examsContainer.appendChild(examCard);
            });
        } else {
            noExamsMessage.style.display = 'flex';
        }
    } catch (error) {
        loadingSpinner.style.display = 'none';
        console.error('Error loading exams:', error);
        showNotification('Failed to load exams', 'error');
    }
}

function createExamCard(exam) {
    const card = document.createElement('div');
    card.className = 'exam-card student-exam-card';
    
    const isAssigned = exam.seat_number !== null;
    const examDate = new Date(exam.exam_date);
    const today = new Date();
    const isPast = examDate < today;
    
    let statusBadge = '';
    if (isPast) {
        statusBadge = '<span class="badge badge-completed">Completed</span>';
    } else if (isAssigned) {
        statusBadge = '<span class="badge badge-success">Seat Assigned</span>';
    } else {
        statusBadge = '<span class="badge badge-warning">Pending</span>';
    }
    
    card.innerHTML = `
        <div class="exam-header">
            <div>
                <h3>${escapeHtml(exam.exam_name)}</h3>
                <p class="exam-course">${escapeHtml(exam.course_code)}</p>
            </div>
            ${statusBadge}
        </div>
        
        <div class="exam-details">
            <div class="exam-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>${formatDate(exam.exam_date)}</span>
            </div>
            
            <div class="exam-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>${formatTime(exam.start_time)} - ${formatTime(exam.end_time)}</span>
            </div>
            
            ${isAssigned ? `
                <div class="exam-info seat-highlight">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <path d="M9 3v18"></path>
                    </svg>
                    <span><strong>Seat: ${escapeHtml(exam.seat_number)}</strong></span>
                </div>
                
                <div class="exam-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Room ${escapeHtml(exam.room_number)}, ${escapeHtml(exam.building)}</span>
                </div>
            ` : `
                <div class="exam-info pending-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>Seat allocation pending</span>
                </div>
            `}
        </div>
        
        ${isAssigned && !isPast ? `
            <div class="exam-actions">
                <button onclick="viewSeatMap(${exam.id})" class="btn btn-primary btn-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"></path>
                        <line x1="8" y1="2" x2="8" y2="18"></line>
                        <line x1="16" y1="6" x2="16" y2="22"></line>
                    </svg>
                    View Seating Plan
                </button>
            </div>
        ` : ''}
    `;
    
    return card;
}

async function viewSeatMap(examId) {
    const modal = document.getElementById('seatMapModal');
    const container = document.getElementById('seatMapContainer');
    
    container.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><p>Loading seating plan...</p></div>';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    try {
        const response = await fetch(`student_api.php?action=get_seat_map&exam_id=${examId}&roll_number=${encodeURIComponent(currentRollNumber)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            displaySeatMap(data.my_seat, data.seat_map);
        } else {
            container.innerHTML = `<div class="error-message">${escapeHtml(data.message || 'Unable to load seating plan')}</div>`;
        }
    } catch (error) {
        console.error('Error loading seat map:', error);
        container.innerHTML = `<div class="error-message">Failed to load seating plan. Please try again.<br><small>Error: ${escapeHtml(error.message)}</small></div>`;
    }
}

function displaySeatMap(mySeat, seatMap) {
    const container = document.getElementById('seatMapContainer');
    document.getElementById('modalTitle').textContent = 
        `Room ${mySeat.room_number}, ${mySeat.building} - Your Seat: ${mySeat.seat_number}`;

    const seatGrid = [];
    for (let r = 0; r < mySeat.total_rows; r++) {
        seatGrid[r] = [];
        
        for (let c = 0; c < mySeat.total_columns; c++) {
            seatGrid[r][c] = null;
        }
    }

    seatMap.forEach(seat => {
        seatGrid[seat.row_position][seat.column_position] = seat;
    });

    let html = `
        <div class="seating-layout">
            <div class="board-label">Supervisor's Desk</div>
            <div class="seating-grid-view" style="grid-template-columns: repeat(${mySeat.total_columns}, 1fr);">
    `;

    for (let r = 0; r < mySeat.total_rows; r++) {
        for (let c = 0; c < mySeat.total_columns; c++) {
            const seat = seatGrid[r][c];
            const seatNumber = String.fromCharCode(65 + r) + (c + 1); // A1, A2, ...

            if (seat) {
                const isMySeat = seat.row_position === mySeat.row_position && 
                                seat.column_position === mySeat.column_position;

                html += `
                    <div class="seat occupied ${isMySeat ? 'my-seat' : ''}">
                        <div class="seat-number">${escapeHtml(seat.seat_number)}</div>
                        <div class="seat-roll">${escapeHtml(seat.roll_number)}</div>
                        ${isMySeat ? '<div class="seat-label">YOU</div>' : ''}
                    </div>
                `;
            } else {
                html += `
                    <div class="seat empty">
                        <div class="seat-number">${seatNumber}</div>
                    </div>
                `;
            }
        }
    }

    html += `
            </div>
            <div class="seating-legend">
                <div class="legend-item">
                    <div class="legend-box my-seat-box"></div>
                    <span>Your Seat</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box occupied-box"></div>
                    <span>Occupied</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box empty-box"></div>
                    <span>Empty</span>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

function closeSeatMap() {
    const modal = document.getElementById('seatMapModal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'student_login.php';
    }
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('seatMapModal');
    if (e.target === modal) {
        closeSeatMap();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSeatMap();
    }
});
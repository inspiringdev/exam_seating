function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function toggleMobileMenu() {
    const nav = document.getElementById('headerNav');
    if (nav) {
        nav.classList.toggle('show');
    }
}

document.addEventListener('click', function(e) {
    const userMenu = document.querySelector('.user-menu');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenu && userDropdown && !userMenu.contains(e.target)) {
        userDropdown.classList.remove('show');
    }
    
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (e.target === modal) {
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        });
        
        const dropdown = document.getElementById('userDropdown');
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
});

function editStudent(student) {
    alert('Edit functionality would open a modal with student data:\n\n' + 
          'Name: ' + student.name + '\n' +
          'Roll: ' + student.roll_number + '\n' +
          'Email: ' + student.email);
}

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.stat-card, .card, .exam-card, .room-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.6s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    cards.forEach(card => observer.observe(card));
    
    const progressFills = document.querySelectorAll('.progress-fill, .chart-fill');
    progressFills.forEach(fill => {
        const width = fill.style.width;
        fill.style.width = '0';
        setTimeout(() => {
            fill.style.transition = 'width 1s ease';
            fill.style.width = width;
        }, 200);
    });
});

function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
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

window.openModal = openModal;
window.closeModal = closeModal;
window.toggleUserMenu = toggleUserMenu;
window.toggleMobileMenu = toggleMobileMenu;
window.editStudent = editStudent;
window.confirmDelete = confirmDelete;
window.showNotification = showNotification;
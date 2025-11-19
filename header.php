<header class="header">
    <div class="header-container">
        <div class="header-brand">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <span class="brand-name">Exam Seating</span>
        </div>
        
        <button class="mobile-toggle" onclick="toggleMobileMenu()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        
        <nav class="header-nav" id="headerNav">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                Students
            </a>
            <a href="exams.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : ''; ?>">
                Exams
            </a>
            <a href="seating.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'seating.php' ? 'active' : ''; ?>">
                Seating
            </a>
            <a href="rooms.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">
                Rooms
            </a>
            <?php if (isAdmin()): ?>
            <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                Reports
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="header-actions">
            <div class="user-menu">
                <button class="user-button" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                        <small><?php echo ucfirst($_SESSION['role']); ?></small>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Profile
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
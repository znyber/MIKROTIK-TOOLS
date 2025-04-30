<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <span data-feather="home"></span>
                    Dashboard <span class="sr-only">(current)</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'connections.php' ? 'active' : ''; ?>" href="connections.php">
                    <span data-feather="activity"></span>
                    Active Connections
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'firewall.php' ? 'active' : ''; ?>" href="firewall.php">
                    <span data-feather="shield"></span>
                    Firewall Rules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>" href="logs.php">
                    <span data-feather="file-text"></span>
                    System Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ai-analysis.php' ? 'active' : ''; ?>" href="ai-analysis.php">
                    <span data-feather="cpu"></span>
                    AI Analysis
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Security Management</span>
            <a class="d-flex align-items-center text-muted" href="#" aria-label="Add a new report">
                <span data-feather="plus-circle"></span>
            </a>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'threat-detection.php' ? 'active' : ''; ?>" href="threat-detection.php">
                    <span data-feather="alert-triangle"></span>
                    Threat Detection
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'mitigation.php' ? 'active' : ''; ?>" href="mitigation.php">
                    <span data-feather="shield"></span>
                    Mitigation Templates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <span data-feather="bar-chart-2"></span>
                    Security Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <span data-feather="settings"></span>
                    Settings
                </a>
            </li>
        </ul>
    </div>
</nav>
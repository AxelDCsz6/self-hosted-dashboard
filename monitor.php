<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Monitor Sistema</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/monitor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="brand"><i class="fas fa-cube"></i> AXELHOST</div>
        <nav class="top-nav">
            <a href="index.php" class="nav-link"><i class="fas fa-th"></i> Servicios</a>
            <a href="monitor.php" class="nav-link active"><i class="fas fa-chart-line"></i> Monitor</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="users.php" class="nav-link"><i class="fas fa-users-cog"></i> Admin</a>
            <?php endif; ?>
        </nav>
        <div class="user-controls">
            <span class="user-badge"><?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-power-off"></i></a>
        </div>
    </header>

    <main class="main-container monitor-layout-container">
        <div class="page-title"><i class="fas fa-microchip"></i> Estado del Servidor</div>

        <div class="monitor-grid">
            <div class="stat-box">
                <div class="stat-label">CPU</div>
                <div class="stat-number" id="cpu-val">--%</div>
                <div class="progress-bg"><div id="cpu-bar" class="progress-fill"></div></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">RAM</div>
                <div class="stat-number" id="ram-val">--%</div>
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:5px" id="ram-text">-- / --</div>
                <div class="progress-bg"><div id="ram-bar" class="progress-fill"></div></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">DISCO</div>
                <div class="stat-number" id="disk-val">--%</div>
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:5px" id="disk-text">-- / --</div>
                <div class="progress-bg"><div id="disk-bar" class="progress-fill"></div></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">UPTIME</div>
                <div class="stat-number" id="uptime-val" style="font-size:2rem">--</div>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-label" style="margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">PARTICIONES MONTADAS</div>
            <div id="partitions" class="disk-grid">
                <!-- Se llena vía JS -->
            </div>
        </div>
    </main>
    <script src="js/script.js"></script>
</body>
</html>

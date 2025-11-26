<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$services = get_my_services();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="brand"><i class="fas fa-cube"></i> AXELHOST</div>
        <nav class="top-nav">
            <a href="index.php" class="nav-link active"><i class="fas fa-th"></i> Servicios</a>
            <a href="monitor.php" class="nav-link"><i class="fas fa-chart-line"></i> Monitor</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="users.php" class="nav-link"><i class="fas fa-users-cog"></i> Admin</a>
            <?php endif; ?>
        </nav>
        <div class="user-controls">
            <span class="user-badge"><?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-power-off"></i></a>
        </div>
    </header>

    <main class="main-container">
        <div class="page-title"><i class="fas fa-layer-group"></i> Mis Servicios</div>

        <?php if(empty($services)): ?>
            <div style="text-align:center; padding:50px; color:var(--text-secondary);">
                <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:20px"></i>
                <p>No tienes servicios asignados.</p>
            </div>
        <?php else: ?>
            <div class="services-grid">
                <?php foreach($services as $s): $online = $s['status'] === 'online'; ?>
                <div class="service-card">
                    <div class="card-header">
                        <i class="<?php echo htmlspecialchars($s['icon']); ?> service-icon"></i>
                        <span class="status-dot <?php echo $online ? 'status-online' : 'status-offline'; ?>">
                            <?php echo strtoupper($s['status']); ?>
                        </span>
                    </div>
                    <h3 style="margin-bottom:5px"><?php echo htmlspecialchars($s['name']); ?></h3>
                    <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:25px; flex:1; line-height:1.5;">
                        <?php echo htmlspecialchars($s['description']); ?>
                    </p>
                    <?php if($online): ?>
                        <a href="<?php echo $s['url']; ?>" target="_blank" class="btn-access">ABRIR APLICACIÓN</a>
                    <?php else: ?>
                        <div class="btn-access" style="opacity:0.5; cursor:not-allowed">NO DISPONIBLE</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $err = "Invalid security token.";
    } else {
        // CSRF token is valid, process the form
        if (isset($_POST['delete'])) {
            $db->prepare("DELETE FROM services WHERE id=?")->execute([$_POST['id']]);
        } else {
            try {
                $n = $_POST['name']; $u = $_POST['url']; $d = $_POST['desc'];
                $i = $_POST['icon'] ?: 'fas fa-server'; 
                $s = $_POST['status']; $p = isset($_POST['public']) ? 1 : 0;
                
                $db->prepare("INSERT INTO services (name, url, description, icon, status, is_public) VALUES (?,?,?,?,?,?)")
                   ->execute([$n,$u,$d,$i,$s,$p]);
            } catch(PDOException $e) {
                // Error handling
            }
        }
        header("Location: services.php");
        exit;
    }       
}

$services = get_all_services();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios | Admin</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="brand"><i class="fas fa-cube"></i> AXELHOST</div>
        <nav class="top-nav">
            <a href="index.php" class="nav-link"><i class="fas fa-th"></i> Servicios</a>
            <a href="monitor.php" class="nav-link"><i class="fas fa-chart-line"></i> Monitor</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users-cog"></i> Usuarios</a>
            <a href="services.php" class="nav-link active"><i class="fas fa-cogs"></i> Serv. Admin</a>
        </nav>
        <div class="user-controls">
            <span class="user-badge">Admin</span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-power-off"></i></a>
        </div>
    </header>

    <main class="main-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div class="page-title" style="margin:0; border:none"><i class="fas fa-cogs"></i> Servicios</div>
            <button onclick="document.getElementById('modalService').style.display='flex'" class="btn-submit" style="width:auto; padding:0 20px;">
                <i class="fas fa-plus"></i> Nuevo Servicio
            </button>
        </div>

        <div class="catalog-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>URL</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($services as $s): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <i class="<?php echo htmlspecialchars($s['icon']); ?>" style="color:var(--text-secondary)"></i>
                                <strong><?php echo htmlspecialchars($s['name']); ?></strong>
                            </div>
                        </td>
                        <td style="font-family:monospace; color:var(--text-secondary)"><?php echo htmlspecialchars($s['url']); ?></td>
                        <td>
                            <span style="padding:4px 8px; border-radius:4px; font-size:0.8rem; background:<?php echo $s['status']=='online'?'rgba(16,185,129,0.1)':'rgba(239,68,68,0.1)'; ?>; color:<?php echo $s['status']=='online'?'var(--success)':'var(--danger)'; ?>">
                                <?php echo strtoupper($s['status']); ?>
                            </span>
                        </td>
                        <td style="text-align:right">
                            <form method="POST" onsubmit="return confirm('¿Eliminar?');" style="margin:0;">
                                <input type="hidden" name="delete" value="1">
                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                <button class="action-btn delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Servicio -->
    <div id="modalService" class="modal-overlay" style="display:none;">
        <div class="form-panel modal-content">
            <div class="form-title">
                <span>Nuevo Servicio</span>
                <button onclick="document.getElementById('modalService').style.display='none'" style="background:none; border:none; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="admin-form-grid">
  		<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group"><label>Nombre</label><input type="text" name="name" class="form-input" required></div>
                <div class="form-group"><label>URL</label><input type="text" name="url" class="form-input" required></div>
                <div class="form-group"><label>Icono</label><input type="text" name="icon" class="form-input" value="fas fa-server"></div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="status" class="form-input">
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1/-1"><label>Descripción</label><input type="text" name="desc" class="form-input"></div>
                <div class="form-group" style="flex-direction:row; gap:10px; align-items:center;">
                    <input type="checkbox" name="public" style="width:20px; height:20px;">
                    <label style="margin:0;">Público</label>
                </div>
                <button class="btn-submit" style="grid-column: 1/-1">GUARDAR</button>
            </form>
        </div>
    </div>
</body>
</html>

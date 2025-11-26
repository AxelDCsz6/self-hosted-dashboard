<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$msg = '';
$current_user = null;
$user_services = [];

// Obtener Usuario seleccionado
if (isset($_GET['user_id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['user_id']]);
    $current_user = $stmt->fetch();
    
    if($current_user) {
        // Obtener permisos actuales
        $stmt = $db->prepare("SELECT service_id FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$current_user['id']]);
        $user_services = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_user) {
    $selected = $_POST['services'] ?? [];
    
    // Limpiar anteriores
    $stmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
    $stmt->execute([$current_user['id']]);
    
    // Insertar nuevos
    if(!empty($selected)) {
        $stmt = $db->prepare("INSERT INTO user_permissions (user_id, service_id) VALUES (?, ?)");
        foreach($selected as $sid) {
            $stmt->execute([$current_user['id'], $sid]);
        }
    }
    $msg = "Permisos actualizados correctamente.";
    log_event("PERMISSIONS_UPDATED", "Usuario ID: " . $current_user['id']);
    
    // Recargar lista
    $stmt = $db->prepare("SELECT service_id FROM user_permissions WHERE user_id = ?");
    $stmt->execute([$current_user['id']]);
    $user_services = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$users = $db->query("SELECT * FROM users WHERE role != 'admin' ORDER BY username")->fetchAll();
$services = $db->query("SELECT * FROM services ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Permisos | Admin</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="brand"><i class="fas fa-key"></i> CONTROL DE ACCESO</div>
            <nav class="nav-menu">
                <a href="users.php">Volver a Usuarios</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-power-off"></i></a>
            </nav>
        </header>

        <?php if($msg): ?><div style="padding:15px; background:rgba(0,200,83,0.2); color:var(--success); border-radius:6px; margin-bottom:20px"><?php echo $msg; ?></div><?php endif; ?>

        <div class="dashboard-grid">
            <!-- Selector de Usuario -->
            <div class="card">
                <h3>1. Seleccionar Usuario</h3>
                <p style="color:var(--text-muted); margin-bottom:15px; font-size:0.9rem">Solo se gestionan permisos para usuarios estándar. Los admins ven todo.</p>
                <form method="GET">
                    <select name="user_id" onchange="this.form.submit()" class="login-input">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($current_user && $current_user['id'] == $u['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Lista de Checkbox -->
            <div class="card" style="grid-column: span 2">
                <h3>2. Asignar Servicios</h3>
                <?php if($current_user): ?>
                    <div style="margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:10px;">
                        Editando a: <strong style="color:var(--primary)"><?php echo htmlspecialchars($current_user['username']); ?></strong>
                    </div>
                    
                    <form method="POST">
                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px; margin-bottom:20px;">
                            <?php foreach($services as $s): ?>
                                <label style="display:flex; align-items:center; gap:10px; padding:10px; background:rgba(255,255,255,0.05); border-radius:6px; cursor:pointer; transition:0.2s; border:1px solid transparent;" class="check-item">
                                    <input type="checkbox" name="services[]" value="<?php echo $s['id']; ?>" 
                                        <?php echo ($s['is_public'] || in_array($s['id'], $user_services)) ? 'checked' : ''; ?>
                                        <?php echo $s['is_public'] ? 'disabled' : ''; ?>>
                                    
                                    <div>
                                        <div style="font-weight:500"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <?php if($s['is_public']): ?>
                                            <div style="font-size:0.7rem; color:var(--secondary)">Público (Todos ven)</div>
                                        <?php else: ?>
                                            <div style="font-size:0.7rem; color:var(--text-muted)">Privado</div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn">Guardar Cambios</button>
                    </form>
                <?php else: ?>
                    <p style="color:var(--text-muted)">Selecciona un usuario a la izquierda para comenzar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <style>
        .check-item:hover { background: rgba(255,255,255,0.1); border-color:var(--border); }
        input[type="checkbox"] { accent-color: var(--primary); transform: scale(1.2); }
    </style>
</body>
</html>

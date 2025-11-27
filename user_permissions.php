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
    
    <style>
    /* Estilos específicos para user_permissions.php */
    .permissions-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .permissions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .permissions-header .brand {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .permissions-nav {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 25px;
        margin-top: 20px;
    }

    .permission-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .permission-card h3 {
        margin: 0 0 15px 0;
        color: var(--text-primary);
        font-size: 1.2rem;
        font-weight: 600;
    }

    .user-selector {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--bg-body);
        color: var(--text-primary);
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .user-selector:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .check-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .check-item:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .check-item input[type="checkbox"] {
        accent-color: var(--primary-color);
        transform: scale(1.2);
        margin-top: 2px;
    }

    .check-item input[type="checkbox"]:disabled {
        opacity: 0.5;
    }

    .check-item.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background: rgba(255, 255, 255, 0.02);
    }

    .check-item.disabled:hover {
        transform: none;
        border-color: var(--border-color);
        box-shadow: none;
    }

    .check-item-content {
        flex: 1;
    }

    .service-name {
        font-weight: 500;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .service-type {
        font-size: 0.75rem;
    }

    .service-type.public {
        color: var(--success-color);
    }

    .service-type.private {
        color: var(--text-secondary);
    }

    .public-badge {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    .btn-permissions {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .btn-permissions:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
    }

    .btn-back {
        background: var(--bg-body);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--primary-color);
    }

    .btn-logout {
        background: var(--danger-color);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-logout:hover {
        background: var(--danger-dark);
    }

    .current-user {
        background: rgba(74, 144, 226, 0.1);
        border-left: 4px solid var(--primary-color);
        padding: 12px 15px;
        border-radius: 8px;
        margin: 15px 0;
    }

    .alert-permissions {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border-left: 4px solid;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
        border-left-color: var(--success-color);
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .services-grid {
            grid-template-columns: 1fr;
        }
        
        .permissions-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .permissions-nav {
            width: 100%;
            justify-content: space-between;
        }
    }
    </style>
</head>
<body>
    <div class="permissions-container">
        <header class="permissions-header">
            <div class="brand"><i class="fas fa-key"></i> CONTROL DE ACCESO</div>
            <nav class="permissions-nav">
                <a href="users.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver a Usuarios
                </a>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-power-off"></i> Cerrar Sesión
                </a>
            </nav>
        </header>

        <?php if($msg): ?>
            <div class="alert-permissions alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Selector de Usuario -->
            <div class="permission-card">
                <h3><i class="fas fa-user-check"></i> Seleccionar Usuario</h3>
                <p style="color:var(--text-secondary); margin-bottom:20px; font-size:0.9rem">
                    Solo se gestionan permisos para usuarios estándar. Los administradores tienen acceso completo.
                </p>
                <form method="GET">
                    <select name="user_id" onchange="this.form.submit()" class="user-selector">
                        <option value="">-- Seleccionar Usuario --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" 
                                <?php echo ($current_user && $current_user['id'] == $u['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if($current_user): ?>
                    <div class="current-user">
                        Editando permisos de: <strong><?php echo htmlspecialchars($current_user['username']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lista de Servicios -->
            <div class="permission-card">
                <h3><i class="fas fa-server"></i> Asignar Servicios</h3>
                <?php if($current_user): ?>
                    <form method="POST">
                        <div class="services-grid">
                            <?php foreach($services as $s): ?>
                                <label class="check-item <?php echo $s['is_public'] ? 'disabled' : ''; ?>">
                                    <input type="checkbox" name="services[]" value="<?php echo $s['id']; ?>" 
                                        <?php echo ($s['is_public'] || in_array($s['id'], $user_services)) ? 'checked' : ''; ?>
                                        <?php echo $s['is_public'] ? 'disabled' : ''; ?>>
                                    
                                    <div class="check-item-content">
                                        <div class="service-name"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <div class="service-type <?php echo $s['is_public'] ? 'public' : 'private'; ?>">
                                            <?php if($s['is_public']): ?>
                                                <span class="public-badge">Público</span> - Visible para todos los usuarios
                                            <?php else: ?>
                                                Privado - Solo con permisos
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn-permissions">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </form>
                <?php else: ?>
                    <div style="text-align:center; padding:40px 20px; color:var(--text-secondary);">
                        <i class="fas fa-user-slash" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
                        <p>Selecciona un usuario para gestionar sus permisos</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

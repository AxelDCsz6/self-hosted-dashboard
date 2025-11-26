<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$msg = '';
$err = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $err = "Invalid security token.";
    } else {
        // CSRF token is valid, process the form
        // CREAR
        if(isset($_POST['create_user'])) {
            if(create_new_user($_POST['username'], $_POST['password'], $_POST['role'])) {
                $msg = "Usuario creado exitosamente.";
            } else {
                $err = "Error: El nombre de usuario ya existe.";
            }
        } 
        // EDITAR
        elseif(isset($_POST['edit_user'])) {
            $pass = !empty($_POST['password']) ? $_POST['password'] : null;
            update_user_data($_POST['id'], $_POST['username'], $_POST['role'], $pass);
            $msg = "Usuario actualizado correctamente.";
        }
        // ELIMINAR
        elseif(isset($_POST['delete_user'])) {
            delete_system_user($_POST['id']);
            $msg = "Usuario eliminado.";
        }
    }
}

$users = get_all_users();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios | Admin</title>
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
            <a href="users.php" class="nav-link active"><i class="fas fa-users-cog"></i> Usuarios</a>
            <a href="services.php" class="nav-link"><i class="fas fa-cogs"></i> Serv. Admin</a>
        </nav>
        <div class="user-controls">
            <span class="user-badge">Admin</span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-power-off"></i></a>
        </div>
    </header>

    <main class="main-container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div class="page-title" style="margin:0; border:none"><i class="fas fa-users"></i> Gestión de Usuarios</div>
            <button onclick="openModal('modalCreate')" class="btn-submit" style="width:auto; padding:0 20px;">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </button>
        </div>

        <?php if($msg): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <!-- TABLA DE USUARIOS -->
        <div class="catalog-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:35px; height:35px; background:var(--bg-body); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--text-secondary);">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <strong style="display:block;"><?php echo htmlspecialchars($u['username']); ?></strong>
                                    <?php if($u['id'] == $_SESSION['user_id']): ?>
                                        <span style="font-size:0.75rem; color:var(--primary);">Sesión Actual</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge <?php echo $u['role'] == 'admin' ? 'role-admin' : ''; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($u['status']=='active'): ?>
                                <span style="color:var(--success); font-weight:500"><i class="fas fa-circle" style="font-size:0.5rem"></i> Activo</span>
                            <?php else: ?>
                                <span style="color:var(--danger); font-weight:500"><i class="fas fa-circle" style="font-size:0.5rem"></i> Bloqueado</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right">
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <div style="display:flex; justify-content:end; gap:5px;">
                                <!-- EDITAR -->
                                <button class="action-btn" onclick='openEditModal(<?php echo json_encode($u); ?>)' title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- PERMISOS -->
                                <a href="user_permissions.php?user_id=<?php echo $u['id']; ?>" class="action-btn" title="Permisos">
                                    <i class="fas fa-key"></i>
                                </a>
                                <!-- ELIMINAR -->
                                <form method="POST" onsubmit="return confirm('¿Eliminar usuario irreversiblemente?');" style="margin:0;">
                                    <input type="hidden" name="delete_user" value="1">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button class="action-btn delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- MODAL CREAR -->
    <div id="modalCreate" class="modal-overlay" style="display:none;">
        <div class="form-panel modal-content">
            <div class="form-title">
                <span><i class="fas fa-user-plus"></i> Nuevo Usuario</span>
                <button onclick="closeModal('modalCreate')" style="background:none; border:none; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="admin-form-grid">
		<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="create_user" value="1">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="role" class="form-input">
                        <option value="user">Usuario</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button class="btn-submit" style="grid-column: 1 / -1; margin-top:10px;">CREAR USUARIO</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="modalEdit" class="modal-overlay" style="display:none;">
        <div class="form-panel modal-content">
            <div class="form-title">
                <span><i class="fas fa-edit"></i> Editar Usuario</span>
                <button onclick="closeModal('modalEdit')" style="background:none; border:none; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="admin-form-grid">
		<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="username" id="edit_username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña (Opcional)</label>
                    <input type="password" name="password" class="form-input" placeholder="Dejar vacío para no cambiar">
                </div>
                
                <div class="form-group">
                    <label>Rol</label>
                    <select name="role" id="edit_role" class="form-input">
                        <option value="user">Usuario</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button class="btn-submit" style="grid-column: 1 / -1; margin-top:10px;">GUARDAR CAMBIOS</button>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    function openEditModal(user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_role').value = user.role;
        openModal('modalEdit');
    }
    // Cerrar con tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape") {
            closeModal('modalCreate');
            closeModal('modalEdit');
        }
    });
    </script>
</body>
</html>

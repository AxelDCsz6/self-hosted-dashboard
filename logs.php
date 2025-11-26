<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

// Limpieza de logs antiguos (opcional)
if (isset($_POST['clear_logs'])) {
    $db->exec("DELETE FROM system_logs");
    log_event("LOGS_CLEARED", "El administrador vació los registros");
    header("Location: logs.php");
    exit;
}

// Obtener logs con nombre de usuario
$sql = "SELECT l.*, u.username 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC LIMIT 100";
$logs = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría del Sistema</title>
<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        tr:hover td { background: rgba(255,255,255,0.03); }
        .log-time { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-family: monospace; }
        .action-create { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .action-delete { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .action-update { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .action-auth { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="brand"><i class="fas fa-history"></i> Logs del Sistema</div>
            <nav class="nav-menu">
                <a href="index.php">Dashboard</a>
                <a href="users.php">Usuarios</a>
                <a href="logout.php" class="btn-logout">Salir</a>
            </nav>
        </header>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Actividad Reciente</h2>
            <form method="POST" onsubmit="return confirm('¿Borrar todo el historial?');">
                <button name="clear_logs" class="btn btn-outline" style="border-color:var(--border); color:var(--text-muted);">
                    <i class="fas fa-trash"></i> Limpiar Logs
                </button>
            </form>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>IP</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): 
                            $cls = 'action-auth';
                            if(strpos($log['action'], 'CREATE')) $cls = 'action-create';
                            if(strpos($log['action'], 'DELETE')) $cls = 'action-delete';
                            if(strpos($log['action'], 'UPDATE')) $cls = 'action-update';
                        ?>
                        <tr>
                            <td class="log-time" title="<?php echo $log['created_at']; ?>">
                                <?php echo time_elapsed_string($log['created_at']); ?>
                            </td>
                            <td>
                                <?php if($log['username']): ?>
                                    <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                <?php else: ?>
                                    <span style="color:var(--text-muted)">Sistema</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo $cls; ?>"><?php echo $log['action']; ?></span></td>
                            <td style="font-family:monospace; color:var(--text-muted)"><?php echo $log['ip_address']; ?></td>
                            <td style="color:var(--text-muted)"><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if(empty($logs)): ?>
                    <div style="padding:30px; text-align:center; color:var(--text-muted);">No hay registros de actividad.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

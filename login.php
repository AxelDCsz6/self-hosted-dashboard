<?php
require_once __DIR__ . '/includes/functions.php';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username']; 
    $p = $_POST['password'];
    
    // Buscar usuario
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch();

    if ($user && password_verify($p, $user['password'])) {
        if($user['status'] === 'blocked') {
            $error = "Tu cuenta está desactivada.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            log_event("LOGIN_SUCCESS", "Usuario: $u");
            header("Location: index.php");
            exit;
        }
    } else {
        $error = "Credenciales incorrectas.";
        log_event("LOGIN_FAILED", "Intento: $u");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso | AxelHost</title>
<link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div style="margin-bottom:30px">
            <i class="fas fa-shield-alt" style="font-size:3rem; color:var(--primary)"></i>
        </div>
        <h2 style="margin-bottom:5px">Bienvenido</h2>
        <p style="color:var(--text-muted); margin-bottom:25px; font-size:0.9rem">Ingresa tus credenciales para continuar</p>
        
        <?php if($error): ?>
            <div style="background:rgba(207,102,121,0.2); color:#cf6679; padding:10px; border-radius:4px; margin-bottom:20px; font-size:0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="text-align:left; margin-bottom:5px; color:var(--text-muted); font-size:0.8rem">Usuario</div>
            <input type="text" name="username" class="login-input" required autofocus>
            
            <div style="text-align:left; margin-bottom:5px; color:var(--text-muted); font-size:0.8rem">Contraseña</div>
            <input type="password" name="password" class="login-input" required>
            
            <button class="btn" style="width:100%; margin-top:10px; padding:12px;">INICIAR SESIÓN</button>
        </form>
    </div>
</body>
</html>

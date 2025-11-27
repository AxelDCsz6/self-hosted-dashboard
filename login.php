<?php
// login.php - VERSIÓN LIMPIA Y CORREGIDA

// Iniciar sesión primero
if (session_status() == PHP_SESSION_NONE) {
    session_name('AXELHOST_SESSION');
    session_start();
}

// Incluir functions después de iniciar sesión
require_once __DIR__ . '/includes/functions.php';

// Verificación simple de sesión - SOLO UNA VEZ
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// --- CONFIGURACIÓN DE SEGURIDAD ---
define('MAX_LOGIN_ATTEMPTS', 5);
define('COOLDOWN_TIME', 60);

// Inicializar contadores de intentos
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['last_attempt_time'] = $_SESSION['last_attempt_time'] ?? 0;

// Generar token CSRF
$csrf_token = generate_csrf_token();

// --- PROCESAR LOGIN ---
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $current_time = time();

    // 1. Verificar límite de intentos
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && 
        ($current_time - $_SESSION['last_attempt_time']) < COOLDOWN_TIME) {
        $remaining = COOLDOWN_TIME - ($current_time - $_SESSION['last_attempt_time']);
        $error = "Demasiados intentos de sesión. Espera $remaining segundos.";
        log_event("LOGIN_BLOCKED", "Usuario: $username, Bloqueo por fuerza bruta");
    } else {
        // Resetear contador si pasó el cooldown
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && 
            ($current_time - $_SESSION['last_attempt_time']) >= COOLDOWN_TIME) {
            $_SESSION['login_attempts'] = 0;
        }

        // 2. Validar token CSRF
        if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
            $error = "Token de seguridad inválido. Inténtalo de nuevo.";
            log_event("CSRF_FAILED", "Intento: $username");
        } elseif (empty($username) || empty($password)) {
            $error = "Usuario y contraseña son requeridos.";
        } else {
            // 3. Buscar usuario en la base de datos
            global $db;
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // LOGIN EXITOSO
                if ($user['status'] === 'blocked') {
                    $error = "Tu cuenta está desactivada.";
                    log_event("LOGIN_BLOCKED", "Usuario: $username, Cuenta desactivada");
                } else {
                    // Establecer sesión correctamente
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_attempts'] = 0;
                    
                    log_event("LOGIN_SUCCESS", "Usuario: $username");
                    
                    // Redirigir
                    $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
                    unset($_SESSION['redirect_url']);
                    header("Location: " . $redirect_url);
                    exit;
                }
            } else {
                // LOGIN FALLIDO
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = $current_time;
                $error = "Credenciales incorrectas.";
                log_event("LOGIN_FAILED", "Intento: $username");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso | AxelHost</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h2 class="login-title">Bienvenido</h2>
        <p class="login-subtitle">Ingresa tus credenciales para continuar</p>

        <?php if($error): ?>
            <div class="login-alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group-login">
                <label class="form-label-login">Usuario</label>
                <input type="text" name="username" class="login-input" required autofocus 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group-login">
                <label class="form-label-login">Contraseña</label>
                <input type="password" name="password" class="login-input" required>
            </div>

            <button type="submit" class="btn-submit btn-login">INICIAR SESIÓN</button>
        </form>
    </div>
</body>
</html>

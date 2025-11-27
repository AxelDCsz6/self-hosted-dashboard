<?php
if (session_status() == PHP_SESSION_NONE) {
    session_name('AXELHOST_SESSION');
    session_start();
}

// Limpiar completamente la sesión
$_SESSION = array();

// Si se desea destruir la cookie también
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir al login
header("Location: login.php");
exit;
?>

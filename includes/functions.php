<?php
// Incluir db.php PRIMERO
require_once __DIR__ . '/db.php';
date_default_timezone_set('America/Mexico_City');

// Configuración Base SIMPLIFICADA
if (!defined('BASE_URL')) {
    // Para desarrollo local en puerto 8000
    define('BASE_URL', '/');
}

// SOLO iniciar sesión si no está activa - SIN configuraciones complejas
if (session_status() === PHP_SESSION_NONE) {
    session_name('AXELHOST_SESSION');
    session_start();
}

// --- SETUP INICIAL (Crea tablas si no existen) ---
function setup_database() {
    global $db; // Asegurar que $db esté disponible
    
    try {
        // Tabla Usuarios
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabla Servicios
        $db->exec("CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            url TEXT NOT NULL,
            description TEXT,
            icon TEXT DEFAULT 'fas fa-server',
            status TEXT DEFAULT 'online',
            is_public INTEGER DEFAULT 0
        )");

        // Tabla Permisos
        $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            service_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
        )");

        // Tabla Logs
        $db->exec("CREATE TABLE IF NOT EXISTS system_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT,
            ip_address TEXT,
            details TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
    } catch (Exception $e) {
        error_log("Database setup error: " . $e->getMessage());
    }
}

// Solo ejecutar setup si la base de datos está disponible
if (isset($db)) {
    setup_database();
}

// --- CSRF PROTECTION ---
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

// --- SEGURIDAD ---
function require_login() {
    // Si ya estamos en login.php, no redirigir
    $current_script = basename($_SERVER['PHP_SELF']);
    if ($current_script === 'login.php') {
        return;
    }
    
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_URL . "index.php");
        exit;
    }
}

// Alias para compatibilidad
function check_admin() { require_admin(); }

// --- LOGS ---
function log_event($action, $details = '') {
    global $db;
    $uid = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$uid, $action, $ip, $details]);
}

// --- SERVICIOS ---
function get_all_services() {
    global $db;
    return $db->query("SELECT * FROM services ORDER BY name ASC")->fetchAll();
}

function get_my_services() {
    global $db;
    $uid = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['role'] ?? 'user';

    if ($role === 'admin') {
        return get_all_services();
    }

    $stmt = $db->prepare("SELECT s.* FROM services s 
                          LEFT JOIN user_permissions p ON s.id = p.service_id 
                          WHERE s.is_public = 1 OR p.user_id = ? 
                          GROUP BY s.id ORDER BY s.name ASC");
    $stmt->execute([$uid]);
    return $stmt->fetchAll();
}

// --- USUARIOS ---
function get_all_users() {
    global $db;
    return $db->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
}

function create_new_user($u, $p, $r) {
    global $db;
    // Verificar duplicados
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$u]);
    if ($stmt->fetchColumn() > 0) return false;

    $h = password_hash($p, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$u, $h, $r]);
    log_event("USER_CREATED", "Usuario: $u");
    return true;
}

function update_user_data($id, $username, $role, $password = null) {
    global $db;
    if ($password) {
        $h = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $role, $h, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $id]);
    }
    log_event("USER_UPDATED", "ID: $id");
}

function delete_system_user($id) {
    global $db;
    if ($id == $_SESSION['user_id']) return; // No auto-borrarse
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    log_event("USER_DELETED", "ID: $id");
}
?>

<?php
// includes/db.php
define('DB_PATH', __DIR__ . '/../data/server_hub.sqlite');

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error crítico de sistema: No se puede conectar a la base de datos.");
}
?>

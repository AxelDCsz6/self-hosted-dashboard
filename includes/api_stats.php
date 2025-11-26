<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

// Iniciar sesión para validar acceso
ob_start();
require_once __DIR__ . '/functions.php';
ob_end_clean();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 1. CPU Load
$stat1 = file('/proc/stat'); 
usleep(100000); // Esperar 100ms
$stat2 = file('/proc/stat');
$info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0]));
$info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0]));
$dif_total = array_sum($info2) - array_sum($info1);
$dif_idle = $info2[3] - $info1[3];
$cpu = $dif_total > 0 ? (1 - ($dif_idle / $dif_total)) * 100 : 0;

// 2. RAM Usage
$mem = file_get_contents("/proc/meminfo");
preg_match('/MemTotal:\s+(\d+)/', $mem, $mt);
preg_match('/MemAvailable:\s+(\d+)/', $mem, $ma);
$total_ram = $mt[1]; 
$used_ram = $total_ram - $ma[1];
$ram_pct = ($used_ram / $total_ram) * 100;

// 3. Disk Usage (Root)
$dt = disk_total_space("/"); 
$df = disk_free_space("/"); 
$du = $dt - $df;
$disk_pct = ($du / $dt) * 100;

// 4. Partitions
$parts = [];
exec('df -B1 -T -x tmpfs -x devtmpfs -x squashfs -x overlay 2>/dev/null', $out);
array_shift($out); // Quitar header
foreach($out as $line) {
    $cols = preg_split('/\s+/', $line);
    if(count($cols) >= 6) {
        $mnt = end($cols);
        // Filtrar montajes del sistema irrelevantes para dashboard simple
        if(strpos($mnt, '/boot') === false && strpos($mnt, '/snap') === false) {
            $parts[] = [
                'mount' => $mnt,
                'pct' => (float)str_replace('%','', $cols[5]),
                'used' => round($cols[3]/1024/1024/1024, 2),
                'total' => round($cols[2]/1024/1024/1024, 2)
            ];
        }
    }
}

// 5. Uptime
$uptime = explode(' ', file_get_contents('/proc/uptime'))[0];

echo json_encode([
    'cpu' => round($cpu, 1),
    'ram_percent' => round($ram_pct, 1),
    'ram_text' => round($used_ram/1024/1024, 1) . ' GB / ' . round($total_ram/1024/1024, 1) . ' GB',
    'disk_percent' => round($disk_pct, 1),
    'disk_text' => round($du/1024/1024/1024, 1) . ' GB / ' . round($dt/1024/1024/1024, 1) . ' GB',
    'uptime' => (int)$uptime,
    'partitions' => $parts
]);
?>

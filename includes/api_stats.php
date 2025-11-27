<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$response = [];
$debug_info = []; // Para debugging

try {
    // 1. CPU - Método específico para Ubuntu/Linux
    $cpu_usage = 0;
    
    // Método 1: Usando /proc/stat (más confiable)
    $stat1 = @file('/proc/stat');
    if ($stat1 !== false) {
        $info1 = explode(" ", preg_replace("/^cpu\s+/", "", $stat1[0]));
        usleep(200000); // 200ms para mejor precisión
        $stat2 = @file('/proc/stat');
        if ($stat2 !== false) {
            $info2 = explode(" ", preg_replace("/^cpu\s+/", "", $stat2[0]));
            if (count($info1) >= 4 && count($info2) >= 4) {
                $total1 = array_sum($info1);
                $total2 = array_sum($info2);
                $idle1 = $info1[3];
                $idle2 = $info2[3];
                
                $total_diff = $total2 - $total1;
                $idle_diff = $idle2 - $idle1;
                
                if ($total_diff > 0) {
                    $cpu_usage = (1 - $idle_diff / $total_diff) * 100;
                }
            }
        }
    }
    
    // Fallback: sys_getloadavg()
    if ($cpu_usage == 0 && function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $cpu_usage = $load[0] * 100;
    }

    $response['cpu'] = round($cpu_usage, 1);
    $debug_info['cpu_method'] = 'proc_stat';

    // 2. RAM - Método mejorado para Ubuntu
    $mem_total = 0;
    $mem_used = 0;
    
    // Método 1: /proc/meminfo
    $meminfo = @file_get_contents('/proc/meminfo');
    if ($meminfo !== false) {
        preg_match('/MemTotal:\s*(\d+)\s*kB/i', $meminfo, $total_match);
        preg_match('/MemAvailable:\s*(\d+)\s*kB/i', $meminfo, $available_match);
        
        if ($total_match) {
            $mem_total = $total_match[1] * 1024; // Convertir a bytes
        }
        if ($available_match) {
            $mem_available = $available_match[1] * 1024;
            $mem_used = $mem_total - $mem_available;
        } else {
            // Fallback si MemAvailable no existe
            preg_match('/MemFree:\s*(\d+)\s*kB/i', $meminfo, $free_match);
            if ($free_match) {
                $mem_free = $free_match[1] * 1024;
                $mem_used = $mem_total - $mem_free;
            }
        }
    }
    
    // Método 2: comando free
    if ($mem_total == 0) {
        $free_output = [];
        exec('free -b 2>/dev/null', $free_output);
        if (count($free_output) >= 2) {
            $mem_line = preg_split('/\s+/', $free_output[1]);
            if (count($mem_line) >= 3) {
                $mem_total = $mem_line[1];
                $mem_used = $mem_line[2];
            }
        }
    }

    $ram_percent = $mem_total > 0 ? ($mem_used / $mem_total) * 100 : 0;
    $response['ram_percent'] = round($ram_percent, 1);
    $response['ram_text'] = round($mem_used/1024/1024/1024, 1) . ' GB / ' . round($mem_total/1024/1024/1024, 1) . ' GB';

    // 3. DISCO - Para Ubuntu
    $disk_total = @disk_total_space("/");
    $disk_free = @disk_free_space("/");
    
    if ($disk_total === false || $disk_free === false) {
        // Fallback usando df
        $df_output = [];
        exec('df -B1 / 2>/dev/null | tail -1', $df_output);
        if (!empty($df_output[0])) {
            $df_parts = preg_split('/\s+/', $df_output[0]);
            if (count($df_parts) >= 3) {
                $disk_total = $df_parts[1];
                $disk_free = $df_parts[3];
            }
        }
    }
    
    $disk_used = $disk_total - $disk_free;
    $disk_percent = $disk_total > 0 ? ($disk_used / $disk_total) * 100 : 0;

    $response['disk_percent'] = round($disk_percent, 1);
    $response['disk_text'] = round($disk_used/1024/1024/1024, 1) . ' GB / ' . round($disk_total/1024/1024/1024, 1) . ' GB';

    // 4. UPTIME - Método Ubuntu
    $uptime_seconds = 0;
    $uptime_content = @file_get_contents('/proc/uptime');
    if ($uptime_content !== false) {
        $uptime_parts = explode(' ', $uptime_seconds);
        $uptime_seconds = (float)$uptime_parts[0];
    }
    
    // Formatear uptime
    $days = floor($uptime_seconds / 86400);
    $hours = floor(($uptime_seconds % 86400) / 3600);
    $minutes = floor(($uptime_seconds % 3600) / 60);
    
    $uptime_formatted = '';
    if ($days > 0) $uptime_formatted .= $days . 'd ';
    if ($hours > 0) $uptime_formatted .= $hours . 'h ';
    if ($minutes > 0) $uptime_formatted .= $minutes . 'm';
    if (empty($uptime_formatted)) $uptime_formatted = '0m';
    
    $response['uptime'] = trim($uptime_formatted);

    // 5. PARTITIONS - Específico para Ubuntu
    $partitions = [];
    $df_output = [];
    
    // Comando df optimizado para Ubuntu
    exec('df -B1 -x tmpfs -x devtmpfs -x squashfs -x overlay --output=target,size,used,pcent 2>/dev/null', $df_output);
    
    if (count($df_output) > 1) {
        array_shift($df_output); // Remove header
        
        foreach($df_output as $line) {
            $cols = preg_split('/\s+/', trim($line));
            if(count($cols) >= 4) {
                $mount_point = $cols[0];
                $total_bytes = (float)($cols[1] ?? 0);
                $used_bytes = (float)($cols[2] ?? 0);
                $percent_str = $cols[3] ?? '0%';
                
                // Convertir porcentaje string a número
                $percent = (float)str_replace('%', '', $percent_str);
                
                // Filtrar mounts no deseados
                $excluded_mounts = ['/boot', '/snap', '/var/lib/docker'];
                $exclude = false;
                foreach ($excluded_mounts as $excluded) {
                    if (strpos($mount_point, $excluded) !== false) {
                        $exclude = true;
                        break;
                    }
                }
                
                if (!$exclude && $total_bytes > 0 && $mount_point !== '/') {
                    $partitions[] = [
                        'mount' => $mount_point,
                        'pct' => $percent,
                        'used' => round($used_bytes/1024/1024/1024, 2),
                        'total' => round($total_bytes/1024/1024/1024, 2)
                    ];
                }
            }
        }
    }
    
    // Siempre incluir root
    $partitions[] = [
        'mount' => '/',
        'pct' => $response['disk_percent'],
        'used' => round($disk_used/1024/1024/1024, 2),
        'total' => round($disk_total/1024/1024/1024, 2)
    ];

    $response['partitions'] = $partitions;
    
    // Para debugging - remover en producción
    // $response['debug'] = $debug_info;

} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => 'Server stats unavailable: ' . $e->getMessage()];
}

echo json_encode($response);
?>

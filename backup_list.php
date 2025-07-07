<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    http_response_code(403);
    exit();
}
$backupDir = __DIR__ . '/backups';
$archivos = glob("$backupDir/backup_*.sql");
$resp = [];
foreach ($archivos as $a) {
    $nombre = basename($a);
    $fecha = date("d/m/Y H:i:s", filemtime($a));
    $resp[] = ['file' => $nombre, 'fecha' => $fecha];
}
usort($resp, function($a,$b) { return strcmp($b['file'], $a['file']); });
header('Content-Type: application/json');
echo json_encode($resp);
?>

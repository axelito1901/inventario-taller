<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'No autorizado.']);
    exit();
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);

$fecha = date('d-m-Y_H-i-s');
$filename = "backup_{$fecha}.sql";
$filepath = $backupDir . "/" . $filename;

// CONFIGURACIÓN XAMPP WINDOWS:
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = ''; // Tu password, si tenés ponela acá
$dbname = 'inventario';
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe'; // Path completo

// Comando y salida de error para debug
$comando = "\"$mysqldump\" --user={$dbuser} --password=\"{$dbpass}\" --host={$dbhost} {$dbname} 2>&1 > " . escapeshellarg($filepath);

$output = [];
exec($comando, $output, $retval);

// Verificar existencia y tamaño (>500 bytes = backup real)
if (file_exists($filepath) && filesize($filepath) > 500) {
    echo json_encode(['success' => true, 'archivo' => $filename]);
} else {
    if (file_exists($filepath)) unlink($filepath); // borra backups vacíos
    // Dejar error en un archivo para que puedas leerlo
    file_put_contents(__DIR__ . '/backup_debug.txt', implode("\n", $output));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg' => 'Error al crear el backup. Revisá backup_debug.txt para más detalles.'
    ]);
}
?>

<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['gerente'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'No autorizado.']);
    exit();
}

require_once 'includes/conexion.php';

$pass = $_POST['password'] ?? '';
$backup = $_POST['backup'] ?? '';

if (!$backup) {
    echo json_encode(['success' => false, 'msg' => 'No se especificó backup.']);
    exit();
}

$usuario_admin = $_SESSION['gerente'];

// Buscar contraseña del usuario gerente (texto plano)
$stmt = $conexion->prepare("SELECT contraseña FROM usuarios WHERE nombre = ? AND tipo = 'gerente' LIMIT 1");
$stmt->bind_param("s", $usuario_admin);
$stmt->execute();
$stmt->bind_result($hash);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'msg' => 'Usuario gerente no encontrado.']);
    exit();
}
$stmt->close();

if ($pass !== $hash) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => 'Contraseña incorrecta.']);
    exit();
}

// Seguridad: validar ruta backup
$backupDir = __DIR__ . '/backups';
$filepath = realpath($backupDir . '/' . $backup);
if(!$backup || !file_exists($filepath) || strpos($filepath, realpath($backupDir)) !== 0) {
    echo json_encode(['success' => false, 'msg' => 'Backup no encontrado.']);
    exit();
}

// Configuración de base de datos
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = ''; // tu pass MySQL
$dbname = 'inventario';
$mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe'; // ruta absoluta a mysql.exe

// Ejecutar restauración
$comando = "\"$mysql\" --user={$dbuser} --password=\"{$dbpass}\" --host={$dbhost} {$dbname} < " . escapeshellarg($filepath) . " 2>&1";
$output = [];
exec($comando, $output, $retval);

if($retval === 0) {
    echo json_encode(['success' => true]);
} else {
    file_put_contents(__DIR__ . '/restore_debug.txt', implode("\n", $output));
    echo json_encode(['success' => false, 'msg' => 'Error al restaurar la base. Revisá restore_debug.txt']);
}
?>

<?php
// CONFIGURACIÓN
$host = 'localhost';
$usuario = 'root';
$clave = ''; 
$bd = 'inventario';

$fecha = date('Y-m-d_H-i-s');
$archivo = "backups/respaldo_$fecha.sql";

// Crear carpeta si no existe
if (!is_dir('backups')) {
    mkdir('backups', 0777, true);
}

// Buscar mysqldump
$mysqldump = trim(shell_exec("where mysqldump")); // busca en el PATH

// Si no está en PATH, probar con ruta por defecto de XAMPP
if (!$mysqldump || !file_exists($mysqldump)) {
    $mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';
}

// Si sigue sin existir, mostrar error
if (!file_exists($mysqldump)) {
    die("❌ Error: No se encontró <code>mysqldump</code>. Asegurate de tener XAMPP instalado o que esté en el PATH.");
}

// Asegurarse de que esté entre comillas por si hay espacios en el path
$mysqldump = '"' . $mysqldump . '"';

// Comando final
$comando = "$mysqldump -h $host -u $usuario " . ($clave ? "-p$clave " : "") . "$bd > \"$archivo\"";

// Ejecutar
system($comando, $resultado);

// Mostrar resultado
if ($resultado === 0) {
    echo "✅ Backup realizado correctamente: <a href='$archivo'>$archivo</a>";
} else {
    echo "❌ Error al crear el backup. Revisá la configuración o permisos.";
}
?>

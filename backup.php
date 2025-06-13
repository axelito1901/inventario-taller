<?php
$host = 'localhost';
$usuario = 'root';
$clave = ''; 
$bd = 'inventario';

$fecha = date('Y-m-d_H-i-s');
$archivo = "backups/respaldo_$fecha.sql";

// se crea una carpeta de backups si no existe
if (!is_dir('backups')) {
    mkdir('backups', 0777, true);
}

// se busca el comando mysqldump en el PATH del sistema
$mysqldump = trim(shell_exec("where mysqldump")); // busca en el PATH

// si no se encuentra, se intenta con la ruta por defecto de XAMPP
if (!$mysqldump || !file_exists($mysqldump)) {
    $mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';
}

// si todavia no se encuentra, se muestra un error
if (!file_exists($mysqldump)) {
    die("❌ Error: No se encontró <code>mysqldump</code>. Asegurate de tener XAMPP instalado o que esté en el PATH.");
}

$mysqldump = '"' . $mysqldump . '"';

$comando = "$mysqldump -h $host -u $usuario " . ($clave ? "-p$clave " : "") . "$bd > \"$archivo\"";

system($comando, $resultado);

// muestra ek resultado
if ($resultado === 0) {
    echo "✅ Backup realizado correctamente: <a href='$archivo'>$archivo</a>";
} else {
    echo "❌ Error al crear el backup. Revisá la configuración o permisos.";
}
?>

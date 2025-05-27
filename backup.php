<?php
// CONFIGURACIÓN
$host = 'localhost';
$usuario = 'root';
$clave = ''; 
$bd = 'inventario';

$fecha = date('Y-m-d_H-i-s');
$archivo = "backups/respaldo_$fecha.sql";

// Asegurar que la carpeta backups existe
if (!is_dir('backups')) {
    mkdir('backups', 0777, true);
}

// Comando mysqldump
$mysqldump = '"C:\xampp\mysql\bin\mysqldump.exe"'; // con comillas por los espacios
$comando = "$mysqldump -h $host -u $usuario " . ($clave ? "-p$clave " : "") . "$bd > $archivo";

// Ejecutar
system($comando, $resultado);

// Mostrar resultado
if ($resultado === 0) {
    echo "✅ Backup realizado: <a href='$archivo'>$archivo</a>";
} else {
    echo "❌ Error al crear el backup. Verificá que mysqldump esté en PATH.";
}
?>

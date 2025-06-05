<?php
include 'includes/conexion.php';

$resultado = $conexion->query("SELECT MAX(UNIX_TIMESTAMP(fecha_actualizacion)) AS ultima FROM actualizaciones");
$fila = $resultado->fetch_assoc();
echo $fila['ultima'];

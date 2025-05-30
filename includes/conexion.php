<?php
$host = "localhost";
$usuario = "root";
$contraseña = "";
$base_de_datos = "inventario";

$conexion = new mysqli($host, $usuario, $contraseña, $base_de_datos);
date_default_timezone_set('America/Argentina/Buenos_Aires');


if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>

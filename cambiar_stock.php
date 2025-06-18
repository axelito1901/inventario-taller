<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if (isset($_POST['herramienta_id'])) {
    $id = intval($_POST['herramienta_id']);

    // de aca se puede obtener el stock actual
    $resultado = $conexion->query("SELECT stock FROM herramientas WHERE id = $id");
    if ($fila = $resultado->fetch_assoc()) {
        $nuevoStock = $fila['stock'] > 0 ? 0 : 1;
        $conexion->query("UPDATE herramientas SET stock = $nuevoStock WHERE id = $id");
    }
}

header("Location: listar_herramientas.php");
exit();

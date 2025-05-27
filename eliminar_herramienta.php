<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Borrar imagen si existe
    $img = $conexion->query("SELECT imagen FROM herramientas WHERE id = $id")->fetch_assoc();
    if ($img && $img['imagen'] && file_exists($img['imagen'])) {
        unlink($img['imagen']);
    }

    $conexion->query("DELETE FROM herramientas WHERE id = $id");
}

header("Location: listar_herramientas.php");
exit();

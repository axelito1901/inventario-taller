<?php
include 'includes/conexion.php';

$term = $_GET['term'] ?? '';
$sucursal = $_GET['sucursal'] ?? '';

$nombres = [];

if ($term && $sucursal) {
    if ($sucursal === 'Lanús') {
        $stmt = $conexion->prepare("SELECT nombre FROM mecanicos WHERE nombre LIKE CONCAT(?, '%') LIMIT 10");
    } elseif ($sucursal === 'Osvaldo Cruz') {
        $stmt = $conexion->prepare("SELECT nombre FROM nombres_personalizados WHERE nombre LIKE CONCAT(?, '%') LIMIT 10");
    } else {
        exit;
    }

    $stmt->bind_param("s", $term);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $nombres[] = $row['nombre'];
    }
}

echo json_encode($nombres);

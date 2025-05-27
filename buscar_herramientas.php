<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include 'includes/conexion.php';

if (!isset($_GET['q'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta parámetro de búsqueda']);
    exit;
}

$q = '%' . strtolower(trim($_GET['q'])) . '%';

$stmt = $conexion->prepare("
    SELECT id, nombre, ubicacion 
    FROM herramientas 
    WHERE LOWER(nombre) LIKE ?
    AND id NOT IN (
        SELECT herramienta_id FROM prestamos WHERE devuelta = 0
    )
    LIMIT 10
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar consulta SQL']);
    exit;
}

$stmt->bind_param("s", $q);
$stmt->execute();

$resultado = $stmt->get_result();

$herramientas = [];

while ($fila = $resultado->fetch_assoc()) {
    $herramientas[] = $fila;
}

echo json_encode($herramientas);

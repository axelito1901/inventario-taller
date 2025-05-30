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

$texto = strtolower(trim($_GET['q']));
$like = '%' . $texto . '%';

$stmt = $conexion->prepare("
    SELECT h.id, h.nombre, h.codigo, h.ubicacion, h.imagen, h.cantidad,
           EXISTS (
               SELECT 1 FROM prestamos p
               WHERE p.herramienta_id = h.id AND p.devuelta = 0
           ) AS prestada,
           CASE 
               WHEN LOWER(h.codigo) = ? THEN 4
               WHEN LOWER(h.codigo) LIKE CONCAT(?, '%') THEN 3
               WHEN LOWER(h.nombre) LIKE CONCAT(?, '%') THEN 2
               WHEN LOWER(h.nombre) LIKE ? OR LOWER(h.codigo) LIKE ? THEN 1
               ELSE 0
           END AS prioridad
    FROM herramientas h
    WHERE LOWER(h.nombre) LIKE ? OR LOWER(h.codigo) LIKE ?
    ORDER BY prioridad DESC, LOWER(h.nombre)
    LIMIT 20
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar consulta SQL']);
    exit;
}

$exacto = $texto;
$empieza = $texto;
$empiezaNombre = $texto;
$likeNombre = $like;
$likeCodigo = $like;

$stmt->bind_param("sssssss", $exacto, $empieza, $empiezaNombre, $likeNombre, $likeCodigo, $likeNombre, $likeCodigo);
$stmt->execute();

$resultado = $stmt->get_result();
$herramientas = [];

while ($fila = $resultado->fetch_assoc()) {
    if (empty($fila['imagen']) || !file_exists($fila['imagen'])) {
        $fila['imagen'] = '';
    }

    $fila['prestada'] = (bool) $fila['prestada'];
    $herramientas[] = $fila;
}

echo json_encode($herramientas);

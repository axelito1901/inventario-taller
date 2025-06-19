<?php
include 'includes/conexion.php';

$res = $conexion->query("SELECT COUNT(*) AS total FROM comentarios WHERE leido = 0");
$total = $res->fetch_assoc()['total'] ?? 0;

echo json_encode(['total' => intval($total)]);
    
<?php
include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conexion->prepare("UPDATE comentarios SET leido = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false]);
}

<?php
session_start();
include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $conexion->query("UPDATE prestamos SET leido = 1 WHERE id = $id");

    // Si es petición AJAX, respondemos con JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => true]);
        exit;
    }

    // Redirige si vino de un formulario normal
    header("Location: dashboard.php");
    exit;
}
?>

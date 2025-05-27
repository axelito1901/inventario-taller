<?php
session_start();
include 'includes/conexion.php';

$mensaje = '';
$error = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $herramientas = $_POST['herramienta_id'] ?? [];
    $sucursal = $_POST['sucursal'] ?? '';
    $nombre = ucwords(mb_strtolower(trim($_POST['nombre_personalizado'] ?? '')));
    $fecha_hora = date('Y-m-d H:i:s');

    if ($nombre === '') {
        $error = "Debés ingresar el nombre.";
    } elseif (empty($herramientas)) {
        $error = "No se seleccionó ninguna herramienta.";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM prestamos WHERE herramienta_id = ? AND devuelta = 0");

        $herramientasDisponibles = [];
        foreach ($herramientas as $id) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $herramientasDisponibles[] = intval($id);
            }
        }

        if (empty($herramientasDisponibles)) {
            $error = "Todas las herramientas seleccionadas ya están prestadas.";
        } else {
            if ($sucursal === 'Lanús') {
                $conexion->query("INSERT IGNORE INTO mecanicos (nombre) VALUES ('$nombre')");
                $res = $conexion->query("SELECT id FROM mecanicos WHERE nombre = '$nombre'");
                $mecanico = $res->fetch_assoc();
                $mecanico_id = $mecanico['id'];

                $stmt = $conexion->prepare("INSERT INTO prestamos (herramienta_id, mecanico_id, fecha_hora, devuelta, sucursal) VALUES (?, ?, ?, 0, ?)");
                foreach ($herramientasDisponibles as $id) {
                    $stmt->bind_param("iiss", $id, $mecanico_id, $fecha_hora, $sucursal);
                    $stmt->execute();
                }
            } else {
                $conexion->query("INSERT IGNORE INTO nombres_personalizados (nombre) VALUES ('$nombre')");
                $stmt = $conexion->prepare("INSERT INTO prestamos (herramienta_id, fecha_hora, devuelta, nombre_personalizado, sucursal) VALUES (?, ?, 0, ?, ?)");
                foreach ($herramientasDisponibles as $id) {
                    $stmt->bind_param("isss", $id, $fecha_hora, $nombre, $sucursal);
                    $stmt->execute();
                }
            }

            $mensaje = "Préstamo registrado correctamente para " . count($herramientasDisponibles) . " herramienta(s).";
            $exito = true;
        }
    }
}

$herramientas = $conexion->query("SELECT id, nombre FROM herramientas ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Préstamo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
<div class="container">
    <h1 class="title is-3">Registrar préstamo</h1>

    <?php if ($mensaje): ?>
        <div class="notification is-success"><?= htmlspecialchars($mensaje) ?></div>
        <a href="index.php" class="button is-link">Volver</a>
    <?php elseif ($error): ?>
        <div class="notification is-danger"><?= htmlspecialchars($error) ?></div>
        <a href="index.php" class="button is-light">Volver</a>
    <?php endif; ?>
</div>
</section>
</body>
</html>

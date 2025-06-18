<?php
session_start();
include 'includes/conexion.php';

$mensaje = '';
$error = '';
$exito = false;
$detallePrestadas = [];

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

            // Obtener nombres y códigos de las herramientas prestadas
            if (!empty($herramientasDisponibles)) {
                $ids = implode(',', $herramientasDisponibles);
                $resultado = $conexion->query("SELECT nombre, codigo FROM herramientas WHERE id IN ($ids)");
                while ($h = $resultado->fetch_assoc()) {
                    $detallePrestadas[] = $h;
                }
            }

            $mensaje = "✅ Préstamo registrado correctamente para " . count($herramientasDisponibles) . " herramienta(s).";
            $exito = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Préstamo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">
<div class="max-w-3xl mx-auto py-12 px-6">
    <h1 class="text-3xl font-bold text-blue-900 mb-6">📋 Registrar Préstamo</h1>

    <?php if ($mensaje): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded shadow mb-6 border border-green-300">
            <?= htmlspecialchars($mensaje) ?>
        </div>

        <?php if (count($detallePrestadas) > 0): ?>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full bg-white shadow rounded-lg">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">Nombre</th>
                            <th class="px-4 py-2 text-left">Código</th>
                            <th class="px-4 py-2 text-left">Sucursal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detallePrestadas as $h): ?>
                            <tr class="border-b border-gray-200">
                                <td class="px-4 py-2"><?= htmlspecialchars($h['nombre']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($h['codigo']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($sucursal) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition">⬅ Volver al inicio</a>
    <?php elseif ($error): ?>
        <div class="bg-red-100 text-red-800 p-4 rounded shadow mb-6 border border-red-300">
            <?= htmlspecialchars($error) ?>
        </div>
        <a href="index.php" class="inline-block bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded shadow transition">⬅ Volver al inicio</a>
    <?php endif; ?>
</div>
</body>
</html>

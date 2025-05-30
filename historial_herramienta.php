<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido.";
    exit();
}

$herramienta_id = intval($_GET['id']);

// Obtener nombre de la herramienta
$herramienta = $conexion->query("SELECT nombre, codigo FROM herramientas WHERE id = $herramienta_id")->fetch_assoc();

if (!$herramienta) {
    echo "Herramienta no encontrada.";
    exit();
}

// Buscar historial de préstamos
$query = $conexion->query("
    SELECT p.fecha_hora, p.devuelta, p.fecha_devolucion, p.sucursal,
           m.nombre AS mecanico, p.nombre_personalizado
    FROM prestamos p
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.herramienta_id = $herramienta_id
    ORDER BY p.fecha_hora DESC
");

$historial = [];
while ($row = $query->fetch_assoc()) {
    $historial[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial - <?= htmlspecialchars($herramienta['nombre']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <h1 class="title">📜 Historial de la herramienta</h1>
        <h2 class="subtitle">
            <strong><?= htmlspecialchars($herramienta['nombre']) ?></strong> (Código: <?= htmlspecialchars($herramienta['codigo']) ?>)
        </h2>

        <?php if (count($historial) === 0): ?>
            <p>No hay préstamos registrados para esta herramienta.</p>
        <?php else: ?>
            <table class="table is-striped is-fullwidth">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Prestado a</th>
                        <th>Sucursal</th>
                        <th>Devuelta</th>
                        <th>Fecha de devolución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></td>
                            <td><?= htmlspecialchars($h['mecanico'] ?? $h['nombre_personalizado']) ?></td>
                            <td><?= htmlspecialchars($h['sucursal']) ?></td>
                            <td><?= $h['devuelta'] ? '✅' : '❌' ?></td>
                            <td><?= $h['fecha_devolucion'] ? date('d/m/Y H:i', strtotime($h['fecha_devolucion'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="dashboard.php" class="button is-link mt-4">⬅ Volver al panel</a>
    </div>
</section>
</body>
</html>

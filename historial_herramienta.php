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
$herramienta = $conexion->query("SELECT nombre, codigo FROM herramientas WHERE id = $herramienta_id")->fetch_assoc();

if (!$herramienta) {
    echo "Herramienta no encontrada.";
    exit();
}

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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
<main class="max-w-6xl mx-auto p-6">
    <h1 class="text-3xl font-bold text-[var(--vw-blue)] mb-2">📜 Historial de la herramienta</h1>
    <h2 class="text-lg mb-6">
        <strong><?= htmlspecialchars($herramienta['nombre']) ?></strong> (Código: <?= htmlspecialchars($herramienta['codigo']) ?>)
    </h2>

    <?php if (count($historial) === 0): ?>
        <p class="p-4 bg-yellow-100 text-yellow-800 rounded shadow">No hay préstamos registrados para esta herramienta.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white text-sm rounded shadow">
                <thead class="bg-[var(--vw-blue)] text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha y hora</th>
                        <th class="px-4 py-2 text-left">Prestado a</th>
                        <th class="px-4 py-2 text-left">Sucursal</th>
                        <th class="px-4 py-2 text-left">Devuelta</th>
                        <th class="px-4 py-2 text-left">Fecha de devolución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($h['mecanico'] ?? $h['nombre_personalizado']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($h['sucursal']) ?></td>
                            <td class="px-4 py-2">
                                <?= $h['devuelta'] ? '✅' : '❌' ?>
                            </td>
                            <td class="px-4 py-2">
                                <?= $h['fecha_devolucion'] ? date('d/m/Y H:i', strtotime($h['fecha_devolucion'])) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="dashboard.php" class="mt-6 inline-block bg-[var(--vw-blue)] text-white px-5 py-2 rounded hover:bg-blue-900 transition">⬅ Volver al panel</a>
</main>
</body>
</html>

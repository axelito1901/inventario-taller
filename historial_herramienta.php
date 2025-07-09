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
       m.nombre AS mecanico, p.nombre_personalizado, p.comentario_devolucion
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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        .table-hover tbody tr:hover {
            background-color: #f8fafc;
            transform: scale(1.005);
            transition: all 0.15s ease;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">
<div class="max-w-7xl mx-auto p-6">
    <!-- Header con título arriba y botón abajo -->
    <div class="mb-6">
        <!-- Título y logo arriba -->
        <div class="flex items-center gap-3 mb-4">
            <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Historial de herramienta</h1>
                <p class="text-gray-600 font-medium">
                    <span class="font-bold text-[var(--vw-blue)]"><?= htmlspecialchars($herramienta['nombre']) ?></span>
                    <span class="text-gray-400">•</span>
                    Código: <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm"><?= htmlspecialchars($herramienta['codigo']) ?></span>
                </p>
            </div>
        </div>

        <!-- Botón volver abajo del título -->
        <a href="listar_herramientas.php" class="inline-flex items-center gap-2 bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-5 py-3 rounded-lg shadow font-semibold transition">
            <i class="fa-solid fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <?php if (count($historial) === 0): ?>
        <!-- Estado vacío SIN botón adicional -->
        <div class="bg-white rounded-2xl shadow border border-gray-200 py-10 px-6 text-center">
            <div class="max-w-md mx-auto">
                <i class="fa-solid fa-history text-6xl text-gray-300"></i>
                <h3 class="text-xl font-bold text-gray-700">Sin historial de préstamos</h3>
                <p class="text-gray-500">Esta herramienta aún no ha sido prestada a ningún mecánico.</p>
            </div>
        </div>
    <?php else: ?>
        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <?php 
            $total_prestamos = count($historial);
            $prestamos_devueltos = array_filter($historial, fn($h) => $h['devuelta']);
            $prestamos_pendientes = $total_prestamos - count($prestamos_devueltos);
            ?>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fa-solid fa-clock text-[var(--vw-blue)] text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_prestamos ?></p>
                        <p class="text-sm text-gray-600">Total préstamos</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fa-solid fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= count($prestamos_devueltos) ?></p>
                        <p class="text-sm text-gray-600">Devueltos</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i class="fa-solid fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $prestamos_pendientes ?></p>
                        <p class="text-sm text-gray-600">Pendientes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de historial -->
        <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-bold text-[var(--vw-blue)] flex items-center gap-2">
                    <i class="fa-solid fa-history"></i>
                    Historial completo de préstamos
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-hover">
                    <thead class="bg-[var(--vw-blue)] text-white">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold">
                                <i class="fa-solid fa-calendar mr-2"></i>Fecha y hora
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">
                                <i class="fa-solid fa-user mr-2"></i>Prestado a
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">
                                <i class="fa-solid fa-building mr-2"></i>Sucursal
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">
                                <i class="fa-solid fa-check mr-2"></i>Estado
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">
                                <i class="fa-solid fa-undo mr-2"></i>Fecha devolución
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">
                                <i class="fa-solid fa-comment mr-2"></i>Comentario
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($historial as $h): ?>
                            <tr class="transition-all duration-150">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        <?= $h['fecha_hora'] ? date('d/m/Y', strtotime($h['fecha_hora'])) : '-' ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= $h['fecha_hora'] ? date('H:i', strtotime($h['fecha_hora'])) : '' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="bg-gray-100 p-2 rounded-full">
                                            <i class="fa-solid fa-user text-gray-600 text-sm"></i>
                                        </div>
                                        <span class="font-medium text-gray-900">
                                            <?= htmlspecialchars($h['mecanico'] ?? $h['nombre_personalizado']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                        <i class="fa-solid fa-building text-xs"></i>
                                        <?= htmlspecialchars($h['sucursal']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($h['devuelta']): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-semibold">
                                            <i class="fa-solid fa-check-circle"></i> Devuelto
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 text-red-800 text-sm font-semibold">
                                            <i class="fa-solid fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($h['fecha_devolucion']): ?>
                                        <div class="font-medium text-gray-900">
                                            <?= date('d/m/Y', strtotime($h['fecha_devolucion'])) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= date('H:i', strtotime($h['fecha_devolucion'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($h['comentario_devolucion']): ?>
                                        <div class="max-w-xs">
                                            <p class="text-sm text-gray-700 truncate" title="<?= htmlspecialchars($h['comentario_devolucion']) ?>">
                                                <?= htmlspecialchars($h['comentario_devolucion']) ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="fontawesome/js/all.min.js"></script>
</body>
</html>
<?php
session_start();
include 'includes/conexion.php';

$timeout = 1800;

if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?mensaje=sesion_expirada");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

$nombreGerente = $_SESSION['gerente'];

$prestamos = $conexion->query("
    SELECT p.*, h.nombre AS herramienta, m.nombre AS mecanico
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Gerente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
    <header class="bg-white shadow p-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-[var(--vw-blue)]">Bienvenido, <?= htmlspecialchars($nombreGerente) ?></h1>
        <a href="logout.php" class="text-sm text-red-600 hover:underline">🚪 Cerrar sesión</a>
    </header>

    <main class="p-6 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <a href="listar_herramientas.php" class="bg-[var(--vw-blue)] text-white p-4 rounded-lg shadow hover:bg-blue-900 transition text-center">🔧 Ver herramientas</a>
            <a href="informe_diario.php" class="bg-blue-500 text-white p-4 rounded-lg shadow hover:bg-blue-600 transition text-center">📅 Informe diario</a>
            <a href="exportar_informe_excel.php" class="bg-green-500 text-white p-4 rounded-lg shadow hover:bg-green-600 transition text-center">📁 Exportar Excel</a>
            <a href="historial_informes.php" class="bg-gray-800 text-white p-4 rounded-lg shadow hover:bg-gray-900 transition text-center">🗂 Historial informes</a>
            <a href="gestion_nombres.php" class="bg-yellow-400 text-black p-4 rounded-lg shadow hover:bg-yellow-500 transition text-center">👤 Gestionar nombres</a>
        </div>

        <h2 class="text-2xl font-semibold text-[var(--vw-blue)] mb-4">Préstamos activos</h2>

        <?php if ($prestamos->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg shadow text-sm">
                    <thead class="bg-[var(--vw-blue)] text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">Herramienta</th>
                            <th class="px-4 py-2 text-left">Prestado por</th>
                            <th class="px-4 py-2 text-left">Sucursal</th>
                            <th class="px-4 py-2 text-left">Fecha y hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $prestamos->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-[var(--vw-gray)]">
                                <td class="px-4 py-2"><?= htmlspecialchars($row['herramienta']) ?></td>
                                <td class="px-4 py-2"><?= $row['mecanico'] ?? htmlspecialchars($row['nombre_personalizado']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['sucursal']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['fecha_hora']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 bg-blue-100 text-blue-800 rounded-lg shadow-sm">No hay préstamos activos en este momento.</div>
        <?php endif; ?>
    </main>
</body>
</html>

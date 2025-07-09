<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$sucursal = $_GET['sucursal'] ?? 'todas';

$sql = "
    SELECT h.nombre AS herramienta, m.nombre AS mecanico,
           p.nombre_personalizado, p.fecha_hora, p.devuelta, 
           p.fecha_devolucion, p.sucursal
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE DATE(p.fecha_hora) = ?
";
if ($sucursal !== 'todas') {
    $sql .= " AND p.sucursal = ?";
}
$sql .= " ORDER BY p.fecha_hora DESC";

$stmt = $sucursal === 'todas'
    ? $conexion->prepare($sql)
    : $conexion->prepare($sql);

if ($sucursal === 'todas') {
    $stmt->bind_param("s", $fecha);
} else {
    $stmt->bind_param("ss", $fecha, $sucursal);
}

$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe diario</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">
<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center gap-3 mb-6">
        <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Informe de préstamos</h1>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow border border-gray-200 mb-8">
        <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-end md:gap-4">
            <div class="flex-1">
                <label class="font-semibold text-[var(--vw-blue)] mb-1 block">Sucursal</label>
                <div class="relative">
                    <select name="sucursal" class="border-2 border-gray-200 rounded-lg px-4 py-2 pr-10 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none appearance-none">
                        <option value="todas" <?= $sucursal === 'todas' ? 'selected' : '' ?>>Todas</option>
                        <option value="Lanús" <?= $sucursal === 'Lanús' ? 'selected' : '' ?>>Lanús</option>
                        <option value="Osvaldo Cruz" <?= $sucursal === 'Osvaldo Cruz' ? 'selected' : '' ?>>Osvaldo Cruz</option>
                    </select>
                    <i class="fa-solid text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </div>
            <div class="flex-1">
                <label class="font-semibold text-[var(--vw-blue)] mb-1 block">Fecha</label>
                <div class="relative">
                    <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="border-2 border-gray-200 rounded-lg px-4 py-2 pr-10 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none">
                    <i class="fa-solid text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </div>
            <div class="flex-shrink-0 mt-3 md:mt-0 flex items-end">
                <button class="bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-bold shadow transition flex items-center gap-2 w-full md:w-auto">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Ver informe</span>
                </button>
            </div>
        </form>
    </div>


    <div class="mb-6">
        <a href="dashboard.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-3 rounded-lg shadow font-semibold transition">
            <i class="fa-solid fa-arrow-left"></i> Volver al panel
        </a>
    </div>

    <?php if ($resultado->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-2xl shadow border border-gray-200">
        <table class="min-w-full border-collapse">
            <thead class="sticky top-0 z-10 bg-[var(--vw-blue)] text-white shadow-sm">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Herramienta</th>
                    <th class="px-4 py-3 text-left font-semibold">Retirado por</th>
                    <th class="px-4 py-3 text-left font-semibold">Sucursal</th>
                    <th class="px-4 py-3 text-left font-semibold">Hora préstamo</th>
                    <th class="px-4 py-3 text-left font-semibold">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold">Hora devolución</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-blue-50 transition">
                        <td class="px-4 py-3"><?= htmlspecialchars($row['herramienta']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['mecanico'] ?? $row['nombre_personalizado']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['sucursal']) ?></td>
                        <td class="px-4 py-3"><?= date('H:i', strtotime($row['fecha_hora'])) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($row['devuelta']): ?>
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold shadow-sm text-sm">
                                    <i class="fa-solid fa-check-circle"></i> Devuelta
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 font-semibold shadow-sm text-sm">
                                    <i class="fa-solid fa-hourglass-half"></i> Activa
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?= $row['devuelta'] ? date('H:i', strtotime($row['fecha_devolucion'])) : '-' ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="p-6 bg-blue-100 text-blue-800 rounded-lg shadow font-semibold text-lg mt-6">
            No se encontraron préstamos para esta sucursal.
        </div>
    <?php endif; ?>
</div>
<script src="fontawesome/js/all.min.js"></script>
</body>
</html>

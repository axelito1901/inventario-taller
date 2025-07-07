<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

$where = "1";

if ($buscar) {
    $where .= " AND (codigo LIKE '%$buscar%' OR nombre LIKE '%$buscar%')";
    $orderBy = "ORDER BY 
        CASE 
            WHEN codigo = '' OR codigo IS NULL THEN 6
            WHEN codigo LIKE '$buscar%' THEN 1 
            WHEN codigo LIKE '%$buscar%' THEN 2 
            WHEN nombre LIKE '$buscar%' THEN 3 
            WHEN nombre LIKE '%$buscar%' THEN 4 
            ELSE 5 
        END,
        codigo + 0";
} else {
    $orderBy = "ORDER BY 
        CASE WHEN codigo = '' OR codigo IS NULL THEN 2 ELSE 1 END,
        codigo + 0";
}

if ($filtro === 'stock') {
    $where .= " AND cantidad > 0";
} elseif ($filtro === 'sin_stock') {
    $where .= " AND cantidad = 0";
}

$sql = "SELECT * FROM herramientas WHERE $where $orderBy";
$herramientas = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Stock</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
<main class="max-w-7xl mx-auto p-6">
    <h2 class="text-3xl font-bold text-[var(--vw-blue)] mb-4">📦 Informe de Stock de Herramientas</h2>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="dashboard.php" class="bg-white border px-4 py-2 rounded shadow hover:bg-gray-100 transition">🏠 Volver al panel</a>
        <a href="informe_stock_excel.php" class="bg-green-100 text-green-700 px-4 py-2 rounded shadow hover:bg-green-200 transition">📥 Exportar a Excel</a>
    </div>

    <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <input type="text" name="buscar" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($buscar) ?>" class="px-4 py-2 rounded border w-full">
        <select name="filtro" onchange="this.form.submit()" class="px-4 py-2 rounded border w-full">
            <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Mostrar todas</option>
            <option value="stock" <?= $filtro === 'stock' ? 'selected' : '' ?>>Solo en stock</option>
            <option value="sin_stock" <?= $filtro === 'sin_stock' ? 'selected' : '' ?>>Solo sin stock</option>
        </select>
        <button type="submit" class="bg-[var(--vw-blue)] text-white rounded px-4 py-2 hover:bg-blue-900 transition w-full">🔎 Buscar</button>
        <a href="informe_stock.php" class="bg-white border rounded px-4 py-2 text-center hover:bg-gray-100 transition w-full">🔄 Limpiar</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full bg-white rounded shadow text-sm">
            <thead class="bg-[var(--vw-blue)] text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Código</th>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">Ubicación</th>
                    <th class="px-4 py-2 text-left">Cantidad</th>
                    <th class="px-4 py-2 text-left">Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $herramientas->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2"><?= htmlspecialchars($h['codigo']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($h['nombre']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($h['ubicacion']) ?></td>
                        <td class="px-4 py-2"><?= intval($h['cantidad']) ?></td>
                        <td class="px-4 py-2">
                            <?php if ($h['cantidad'] == 0): ?>
                                <span class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-xs">🔴 Sin stock</span>
                            <?php else: ?>
                                <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs">🟢 En stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

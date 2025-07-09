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

// Calcular estadísticas
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN cantidad > 0 THEN 1 ELSE 0 END) as con_stock,
    SUM(CASE WHEN cantidad = 0 THEN 1 ELSE 0 END) as sin_stock,
    SUM(cantidad) as cantidad_total
    FROM herramientas WHERE $where";
$stats = $conexion->query($stats_sql)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Stock</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        .search-form {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }
        /* Modern table styles */
        .modern-table thead th {
            background: var(--vw-blue);
            color: #fff;
            font-weight: bold;
            border: none;
        }
        .modern-table th:first-child {
            border-top-left-radius: 1.5rem;
        }
        .modern-table th:last-child {
            border-top-right-radius: 1.5rem;
        }
        .modern-table td:first-child {
            border-bottom-left-radius: 1rem;
        }
        .modern-table td:last-child {
            border-bottom-right-radius: 1rem;
        }
        .modern-table tr {
            transition: background 0.15s;
        }
        .modern-table tr:hover {
            background: #e6f4ff;
        }
        .modern-table tbody tr:nth-child(even) {
            background: #f7fafd;
        }
        .modern-table th, .modern-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">
<div class="max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Informe de Stock</h1>
                <p class="text-gray-600 font-medium">Control y seguimiento de inventario de herramientas</p>
            </div>
        </div>

        <!-- Botones de navegación -->
        <div class="flex flex-wrap gap-2">
            <a href="dashboard.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-3 rounded-lg shadow font-semibold transition">
                <i class="fa-solid fa-arrow-left"></i> Volver al panel
            </a>
            <a href="informe_stock_excel.php" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg shadow font-semibold transition">
                <i class="fa-solid fa-file-excel"></i> Exportar a Excel
            </a>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fa-solid fa-tools text-[var(--vw-blue)] text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
                    <p class="text-sm text-gray-600">Total herramientas</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fa-solid fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['con_stock'] ?></p>
                    <p class="text-sm text-gray-600">Con stock</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fa-solid fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['sin_stock'] ?></p>
                    <p class="text-sm text-gray-600">Sin stock</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fa-solid fa-calculator text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['cantidad_total'] ?></p>
                    <p class="text-sm text-gray-600">Cantidad total</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de búsqueda y filtros -->
    <div class="bg-white rounded-2xl shadow border border-gray-200 p-6 mb-6 search-form">
        <div class="flex items-center gap-2 mb-4">
            <i class="fa-solid fa-search text-[var(--vw-blue)] text-lg"></i>
            <h3 class="text-lg font-bold text-[var(--vw-blue)]">Buscar y filtrar herramientas</h3>
        </div>

        <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Buscar herramienta</label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="buscar" 
                        placeholder="  Código o nombre..." 
                        value="<?= htmlspecialchars($buscar) ?>" 
                        class="pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                    >
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Filtrar por stock</label>
                <div class="relative">
                    <select 
                        name="filtro" 
                        onchange="this.form.submit()" 
                        class="pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition appearance-none bg-white"
                    >
                        <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Mostrar todas</option>
                        <option value="stock" <?= $filtro === 'stock' ? 'selected' : '' ?>>Solo en stock</option>
                        <option value="sin_stock" <?= $filtro === 'sin_stock' ? 'selected' : '' ?>>Solo sin stock</option>
                    </select>
                </div>
            </div>

            <div class="flex items-end">
                <button 
                    type="submit" 
                    class="w-full bg-[var(--vw-blue)] hover:bg-blue-900 text-white rounded-lg px-4 py-3 font-bold shadow transition flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-search"></i>
                    <span>Buscar</span>
                </button>
            </div>

            <div class="flex items-end">
                <a 
                    href="informe_stock.php" 
                    class="w-full bg-gray-500 hover:bg-gray-600 text-blue rounded-lg px-4 py-3 font-bold shadow transition flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-refresh"></i>
                    <span>Limpiar</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de herramientas moderna y redondeada -->
    <div class="bg-white rounded-3xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm modern-table">
                <thead>
                    <tr>
                        <th class="text-right" style="border-top-left-radius:1.5rem">
                            <i class="fa-solid fa-barcode mr-1"></i> Código
                        </th>
                        <th class="text-left">
                            <i class="fa-solid fa-tools mr-1"></i> Nombre
                        </th>
                        <th class="text-left">
                            <i class="fa-solid fa-map-marker-alt mr-1"></i> Ubicación
                        </th>
                        <th class="text-right">
                            <i class="fa-solid fa-calculator mr-1"></i> Cantidad
                        </th>
                        <th class="text-center" style="border-top-right-radius:1.5rem">
                            <i class="fa-solid fa-info-circle mr-1"></i> Estado
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($herramientas->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 bg-gray-50" style="border-bottom-left-radius:1.5rem;border-bottom-right-radius:1.5rem">
                                <div class="max-w-md mx-auto">
                                    <h3 class="text-lg font-bold text-gray-700 mb-2">No se encontraron herramientas</h3>
                                    <p class="text-gray-500 text-sm">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 0; while ($h = $herramientas->fetch_assoc()): $i++; ?>
                            <tr class="even:bg-gray-50 hover:bg-blue-50 transition-all duration-150">
                                <td class="font-mono text-[var(--vw-blue)] text-right" style="border-bottom-left-radius:1rem">
                                    <?= htmlspecialchars($h['codigo']) ?>
                                </td>
                                <td class="text-gray-900">
                                    <?= htmlspecialchars($h['nombre']) ?>
                                </td>
                                <td class="text-gray-700">
                                    <?= htmlspecialchars($h['ubicacion']) ?>
                                </td>
                                <td class="font-mono text-right <?= $h['cantidad'] == 0 ? 'text-red-600' : 'text-green-700' ?>">
                                    <?= intval($h['cantidad']) ?>
                                </td>
                                <td class="text-center" style="border-bottom-right-radius:1rem">
                                    <?php if ($h['cantidad'] == 0): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold border border-red-200 shadow-sm">
                                            <i class="fa-solid fa-exclamation-circle"></i> Sin stock
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold border border-green-200 shadow-sm">
                                            <i class="fa-solid fa-check-circle"></i> En stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="fontawesome/js/all.min.js"></script>
</body>
</html>
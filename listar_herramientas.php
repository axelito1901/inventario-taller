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
    $where .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%')";
}
if ($filtro === 'stock') {
    $where .= " AND cantidad > 0";
} elseif ($filtro === 'sin_stock') {
    $where .= " AND cantidad = 0";
}

$sql = "SELECT * FROM herramientas WHERE $where
        ORDER BY
            CASE 
                WHEN codigo REGEXP '^[0-9]+$' THEN 0
                ELSE 1
            END,
            CAST(codigo AS UNSIGNED)";
$herramientas = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de herramientas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
    <main class="max-w-7xl mx-auto p-6">
        <h2 class="text-3xl font-bold text-[var(--vw-blue)] mb-4">Herramientas registradas</h2>

        <div class="flex flex-wrap gap-2 mb-4">
            <a href="agregar_herramienta.php" class="bg-blue-100 text-[var(--vw-blue)] px-4 py-2 rounded shadow hover:bg-blue-200 transition">➕ Agregar nueva herramienta</a>
            <a href="informe_stock.php" class="bg-green-100 text-green-700 px-4 py-2 rounded shadow hover:bg-green-200 transition">📋 Ver informe de stock</a>
            <a href="dashboard.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded shadow hover:bg-gray-200 transition">🏠 Volver al panel</a>
        </div>

        <form method="get" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="buscar" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($buscar) ?>" class="px-4 py-2 rounded border w-full">
            <select name="filtro" onchange="this.form.submit()" class="px-4 py-2 rounded border w-full">
                <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Mostrar todas</option>
                <option value="stock" <?= $filtro === 'stock' ? 'selected' : '' ?>>Solo en stock</option>
                <option value="sin_stock" <?= $filtro === 'sin_stock' ? 'selected' : '' ?>>Solo sin stock</option>
            </select>
            <button type="submit" class="bg-[var(--vw-blue)] text-white rounded px-4 py-2 hover:bg-blue-900 transition w-full">Buscar</button>
            <a href="listar_herramientas.php" class="bg-white border rounded px-4 py-2 text-center hover:bg-gray-100 transition w-full">🔄 Limpiar filtros</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm bg-white shadow rounded">
                <thead class="bg-[var(--vw-blue)] text-white">
                    <tr>
                        <th class="px-4 py-2">Imagen</th>
                        <th class="px-4 py-2">Código</th>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Ubicación</th>
                        <th class="px-4 py-2">Cantidad</th>
                        <th class="px-4 py-2">Stock</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <?php while ($herramienta = $herramientas->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2">
                                <?php if (!empty($herramienta['imagen']) && file_exists($herramienta['imagen'])): ?>
                                    <img src="<?= $herramienta['imagen'] ?>" alt="<?= $herramienta['nombre'] ?>" class="h-16 mx-auto object-contain">
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Sin imagen</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2"><?= htmlspecialchars($herramienta['codigo']) ?></td>
                            <td class="px-2"><?= htmlspecialchars($herramienta['nombre']) ?></td>
                            <td class="px-2"><?= htmlspecialchars($herramienta['ubicacion']) ?></td>
                            <td class="px-2"><?= intval($herramienta['cantidad']) ?></td>
                            <td class="px-2">
                                <?php if ($herramienta['cantidad'] == 0): ?>
                                    <span class="bg-red-100 text-red-600 px-2 py-1 rounded-full text-xs">🔴 Sin stock</span>
                                <?php else: ?>
                                    <span class="bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs">🟢 En stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 space-x-1">
                                <a href="editar_herramienta.php?id=<?= $herramienta['id'] ?>" class="bg-yellow-400 text-black px-3 py-1 rounded text-xs hover:bg-yellow-500">✏️</a>
                                <a href="eliminar_herramienta.php?id=<?= $herramienta['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600" onclick="return confirm('¿Estás seguro de eliminar esta herramienta?')">🗑️</a>
                                <a href="historial_herramienta.php?id=<?= $herramienta['id'] ?>" class="bg-blue-100 text-[var(--vw-blue)] px-3 py-1 rounded text-xs hover:bg-blue-200">📜</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Botón subir -->
    <button id="btnSubir" title="Subir arriba" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-[var(--vw-blue)] text-white text-xl shadow-lg transition transform hover:scale-110 opacity-0 pointer-events-none">↑</button>

    <script>
    const btnSubir = document.getElementById("btnSubir");

    window.addEventListener("scroll", () => {
        if (window.scrollY > 150) {
            btnSubir.style.opacity = "1";
            btnSubir.style.pointerEvents = "auto";
        } else {
            btnSubir.style.opacity = "0";
            btnSubir.style.pointerEvents = "none";
        }
    });

    btnSubir.addEventListener("click", () => {
        btnSubir.classList.add("animate-ping");
        window.scrollTo({ top: 0, behavior: "smooth" });
        setTimeout(() => btnSubir.classList.remove("animate-ping"), 300);
    });

    window.addEventListener("beforeunload", () => {
        localStorage.setItem("scrollY_herramientas", window.scrollY);
    });

    window.addEventListener("load", () => {
        const y = localStorage.getItem("scrollY_herramientas");
        if (y !== null) {
            window.scrollTo(0, parseInt(y));
            localStorage.removeItem("scrollY_herramientas");
        }
    });
    </script>
</body>
</html>

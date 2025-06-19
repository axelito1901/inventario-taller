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

        <form method="get" class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <input type="text" name="buscar" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($buscar) ?>" class="px-4 py-2 rounded border w-full focus:ring-2 focus:ring-blue-300 transition">
            <select name="filtro" onchange="this.form.submit()" class="px-4 py-2 rounded border w-full focus:ring-2 focus:ring-blue-300 transition">
                <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Mostrar todas</option>
                <option value="stock" <?= $filtro === 'stock' ? 'selected' : '' ?>>Solo en stock</option>
                <option value="sin_stock" <?= $filtro === 'sin_stock' ? 'selected' : '' ?>>Solo sin stock</option>
            </select>
            <button type="submit" class="bg-[var(--vw-blue)] text-white rounded px-4 py-2 hover:bg-blue-900 transition w-full">🔍 Buscar</button>
            <a href="listar_herramientas.php" class="bg-white border rounded px-4 py-2 text-center hover:bg-gray-100 transition w-full">🔄 Limpiar filtros</a>
        </form>

        <!-- Vista única tipo tarjeta -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php while ($herramienta = $herramientas->fetch_assoc()): ?>
                <div class="bg-white shadow rounded-lg p-4 flex flex-col">
                    <?php if (!empty($herramienta['imagen']) && file_exists($herramienta['imagen'])): ?>
                        <img src="<?= $herramienta['imagen'] ?>" alt="<?= $herramienta['nombre'] ?>" class="h-40 object-contain mx-auto mb-4">
                    <?php else: ?>
                        <div class="h-40 flex items-center justify-center bg-gray-100 text-gray-400 text-xs rounded mb-4">Sin imagen</div>
                    <?php endif; ?>
                    
                    <h3 class="text-lg font-bold text-[var(--vw-blue)] mb-1"><?= htmlspecialchars($herramienta['nombre']) ?></h3>
                    <p class="text-sm text-gray-500 mb-1">Código: <strong><?= htmlspecialchars($herramienta['codigo']) ?></strong></p>
                    <p class="text-sm text-gray-500 mb-1">Ubicación: <?= htmlspecialchars($herramienta['ubicacion']) ?></p>
                    <p class="text-sm text-gray-500 mb-2">Cantidad: <?= intval($herramienta['cantidad']) ?></p>

                    <?php if ($herramienta['cantidad'] == 0): ?>
                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded-full text-xs mb-2 w-max">🔴 Sin stock</span>
                    <?php else: ?>
                        <span class="bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs mb-2 w-max">🟢 En stock</span>
                    <?php endif; ?>

                    <div class="mt-auto flex gap-2 pt-2">
                        <a href="editar_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-yellow-400 text-black py-2 rounded text-sm hover:bg-yellow-500">✏️ Editar</a>
                        <a href="eliminar_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-red-500 text-white py-2 rounded text-sm hover:bg-red-600" onclick="return confirm('¿Estás seguro de eliminar esta herramienta?')">🗑️ Borrar</a>
                        <a href="historial_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-blue-100 text-[var(--vw-blue)] py-2 rounded text-sm hover:bg-blue-200">📜 Historial</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- Botón subir -->
    <button id="btnSubir" title="Subir arriba" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-[var(--vw-blue)] text-white text-xl shadow-lg transition transform hover:scale-110 opacity-0 pointer-events-none z-50">↑</button>

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

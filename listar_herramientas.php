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

// Orden dinámico según búsqueda
if ($buscar) {
    // Si la búsqueda es un número, prioridad al código
    if (preg_match('/^\d+$/', $buscar)) {
        $where .= " AND (codigo LIKE '%$buscar%' OR nombre LIKE '%$buscar%')";
        $sqlOrder = "ORDER BY (codigo LIKE '%$buscar%') DESC, 
                            CASE WHEN codigo REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, 
                            CAST(codigo AS UNSIGNED), 
                            nombre";
    } else {
        $where .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%')";
        $sqlOrder = "ORDER BY (nombre LIKE '%$buscar%') DESC, 
                            CASE WHEN codigo REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, 
                            CAST(codigo AS UNSIGNED), 
                            nombre";
    }
} else {
    $sqlOrder = "ORDER BY CASE WHEN codigo REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, 
                            CAST(codigo AS UNSIGNED), nombre";
}

if ($filtro === 'stock') {
    $where .= " AND cantidad > 0";
} elseif ($filtro === 'sin_stock') {
    $where .= " AND cantidad = 0";
}

$sql = "SELECT * FROM herramientas WHERE $where $sqlOrder";
$herramientas = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de herramientas</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        .card-hover {
            transition: transform .15s, box-shadow .15s;
        }
        .card-hover:hover {
            transform: translateY(-6px) scale(1.025);
            box-shadow: 0 10px 30px 0 rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.06);
            border-color: #00247d33;
        }
        .modal-imagen { display: none; }
        .modal-imagen.active { display: flex; }
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

        <!-- Cards mejoradas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($herramienta = $herramientas->fetch_assoc()): ?>
            <div class="bg-white border card-hover rounded-2xl p-5 flex flex-col shadow-md group relative transition">
                <div class="relative mb-4 flex justify-center items-center">
                    <?php if (!empty($herramienta['imagen']) && file_exists($herramienta['imagen'])): ?>
                        <img src="<?= $herramienta['imagen'] ?>" alt="<?= $herramienta['nombre'] ?>" class="h-36 object-contain mx-auto rounded shadow cursor-pointer hover:scale-110 transition"
                            onclick="mostrarModal('<?= $herramienta['imagen'] ?>')" />
                    <?php else: ?>
                        <div class="h-36 flex items-center justify-center bg-gray-100 text-gray-400 text-xs rounded mb-4 w-full">Sin imagen</div>
                    <?php endif; ?>
                </div>
                <h3 class="text-lg font-bold text-[var(--vw-blue)] mb-1"><?= htmlspecialchars($herramienta['nombre']) ?></h3>
                <p class="text-sm text-gray-500 mb-1">Código: <strong><?= htmlspecialchars($herramienta['codigo']) ?></strong></p>
                <p class="text-sm text-gray-500 mb-1">Ubicación: <?= htmlspecialchars($herramienta['ubicacion']) ?></p>
                <p class="text-sm text-gray-500 mb-2">Cantidad: <?= intval($herramienta['cantidad']) ?></p>
                <div class="mb-3">
                <?php if ($herramienta['cantidad'] == 0): ?>
                    <span class="bg-red-200 text-red-800 px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> Sin stock
                    </span>
                <?php else: ?>
                    <span class="bg-green-200 text-green-900 px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                        <i class="fa-solid fa-check-circle"></i> En stock
                    </span>
                <?php endif; ?>
                </div>
                <div class="mt-auto flex gap-2 pt-2">
                    <a href="editar_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-yellow-400 text-black py-2 rounded-lg text-sm font-semibold hover:bg-yellow-500 transition">✏️ Editar</a>
                    <a href="eliminar_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-red-500 text-white py-2 rounded-lg text-sm font-semibold hover:bg-red-600 transition" onclick="return confirm('¿Estás seguro de eliminar esta herramienta?')">🗑️ Borrar</a>
                    <a href="historial_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-blue-100 text-[var(--vw-blue)] py-2 rounded-lg text-sm font-semibold hover:bg-blue-200 transition">📜 Historial</a>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    </main>

    <!-- Modal para ampliar imagen -->
    <div id="modalImagen" class="modal-imagen fixed inset-0 bg-black bg-opacity-80 items-center justify-center z-50 transition-all" onclick="cerrarModal()">
        <img id="imagenAmpliada" src="" class="max-h-[70vh] max-w-[90vw] m-auto rounded-lg border-4 border-white shadow-xl">
    </div>

    <!-- Botón subir -->
    <button id="btnSubir" title="Subir arriba" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-[var(--vw-blue)] text-white text-xl shadow-lg transition transform hover:scale-110 opacity-0 pointer-events-none z-50">↑</button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <script>
    // Modal imagen
    function mostrarModal(src) {
        document.getElementById("imagenAmpliada").src = src;
        document.getElementById("modalImagen").classList.add("active");
    }
    function cerrarModal() {
        document.getElementById("modalImagen").classList.remove("active");
    }
    // Botón subir arriba
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

<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';
$herramientaAConfirmar = $_GET['confirmar'] ?? null;

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

// Eliminar herramienta (POST/REDIRECT/GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $id = intval($_POST['eliminar']);
    $sql = "SELECT nombre FROM herramientas WHERE id = $id";
    $res = $conexion->query($sql);
    if ($res && $res->num_rows > 0) {
        $nombre = $res->fetch_assoc()['nombre'];
        $conexion->query("DELETE FROM herramientas WHERE id = $id");
        $mensaje = "La herramienta '$nombre' fue eliminada correctamente.";
        $tipoMsg = "ok";
    } else {
        $mensaje = "No se encontró la herramienta.";
        $tipoMsg = "error";
    }
    // Redirigir con mensaje por GET (PRG)
    $params = $_GET;
    $params['msg'] = $mensaje;
    $params['msgtype'] = $tipoMsg;
    unset($params['confirmar']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . (count($params) ? '?' . http_build_query($params) : ''));
    exit();
}

// Filtros y orden
$where = "1";
if ($buscar) {
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
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
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
        .notificacion-flotante {
            position: fixed;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            background: #18181b;
            color: #fff;
            padding: 1.1em 2.2em;
            border-radius: 1em;
            font-size: 1.08em;
            font-weight: 600;
            box-shadow: 0 6px 32px #0005;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.8em;
            min-width: 320px;
            max-width: 90vw;
            text-align: center;
            animation: fadein 0.4s;
        }
        .notificacion-flotante .fa-check-circle { color: #22c55e; }
        .notificacion-flotante .fa-xmark { color: #f87171; }
        @keyframes fadein {
            from { opacity: 0; top: 0; }
            to { opacity: 1; top: 32px; }
        }
        @media (max-width: 500px) {
            .notificacion-flotante { font-size: 0.98em; min-width: 0; padding: 0.7em 1em; }
        }
        /* Modal confirmación */
        .modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal-confirm {
            background: #fff;
            border-radius: 1.2em;
            padding: 2.5em 2em 2em 2em;
            max-width: 350px;
            width: 100%;
            box-shadow: 0 8px 32px #0005;
            text-align: center;
            position: relative;
        }
        .modal-confirm h2 {
            color: #b91c1c;
            font-size: 1.25em;
            font-weight: bold;
            margin-bottom: 0.7em;
            display: flex;
            align-items: center;
            gap: 0.5em;
            justify-content: center;
        }
        .modal-confirm p {
            color: #222;
            margin-bottom: 1.5em;
        }
        .modal-confirm .btns {
            display: flex;
            flex-direction: column;
            gap: 0.7em;
        }
        .modal-confirm button,
        .modal-confirm a {
            font-weight: bold;
            border: none;
            border-radius: 0.7em;
            padding: 0.7em 0;
            width: 100%;
            font-size: 1em;
            cursor: pointer;
            box-shadow: 0 2px 8px #0001;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
            display: block;
        }
        .modal-confirm button {
            background: #dc2626;
            color: #fff;
        }
        .modal-confirm button:hover {
            background: #b91c1c;
        }
        .modal-confirm a {
            background: #f3f4f6;
            color: #222;
        }
        .modal-confirm a:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">

<?php if ($mensaje): ?>
    <div class="notificacion-flotante">
        <?php if ($tipoMsg === "ok"): ?>
            <i class="fa-solid fa-check-circle"></i>
        <?php else: ?>
            <i class="fa-solid fa-xmark"></i>
        <?php endif; ?>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<?php if ($herramientaAConfirmar): ?>
    <div class="modal-bg">
        <div class="modal-confirm">
            <h2><i class="fa-solid fa-triangle-exclamation"></i> Confirmar eliminación</h2>
            <p>¿Seguro que querés eliminar la herramienta<br>
                <span class="font-semibold text-[var(--vw-blue)]">
                    <?php
                    $id = intval($herramientaAConfirmar);
                    $res = $conexion->query("SELECT nombre FROM herramientas WHERE id = $id");
                    echo $res && $res->num_rows > 0 ? htmlspecialchars($res->fetch_assoc()['nombre']) : 'ID ' . $id;
                    ?>
                </span>?
            </p>
            <form method="POST" class="btns">
                <input type="hidden" name="eliminar" value="<?= htmlspecialchars($herramientaAConfirmar) ?>">
                <button type="submit">Sí, eliminar</button>
                <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') . (count($_GET) > 1 ? '?' . http_build_query(array_diff_key($_GET, ['confirmar' => '', 'msg' => '', 'msgtype' => ''])) : '') ?>">Cancelar</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center gap-3 mb-6">
        <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Herramientas registradas</h1>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow border border-gray-200 mb-8">
        <form method="get" class="flex flex-col gap-3 md:flex-row md:items-end md:gap-4">
            <div class="flex-1">
                <label class="font-semibold text-[var(--vw-blue)] mb-1 block">Buscar herramienta</label>
                <div class="relative">
                    <input type="text" name="buscar" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($buscar) ?>" class="border-2 border-gray-200 rounded-lg px-4 py-2 pr-10 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none">
                </div>
            </div>
            <div class="flex-1">
                <label class="font-semibold text-[var(--vw-blue)] mb-1 block">Filtrar por stock</label>
                <div class="relative">
                    <select name="filtro" class="border-2 border-gray-200 rounded-lg px-4 py-2 pr-10 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none appearance-none">
                        <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Mostrar todas</option>
                        <option value="stock" <?= $filtro === 'stock' ? 'selected' : '' ?>>Solo en stock</option>
                        <option value="sin_stock" <?= $filtro === 'sin_stock' ? 'selected' : '' ?>>Solo sin stock</option>
                    </select>       
                </div>
            </div>
            <div class="flex-shrink-0 mt-3 md:mt-0 flex items-end gap-2">
                <button type="submit" class="bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-bold shadow transition flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Buscar</span>
                </button>
                <a href="listar_herramientas.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-2 rounded-lg shadow font-semibold transition">
                    <i class="fa-solid fa-sync-alt"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="agregar_herramienta.php" class="inline-flex items-center gap-2 bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-5 py-3 rounded-lg shadow font-semibold transition">
            <i class="fa-solid fa-plus"></i> Agregar herramienta
        </a>
        <a href="informe_stock.php" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg shadow font-semibold transition">
            <i class="fa-solid fa-clipboard-list"></i> Informe de stock
        </a>
        <a href="dashboard.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-3 rounded-lg shadow font-semibold transition">
            <i class="fa-solid fa-arrow-left"></i> Volver al panel
        </a>
    </div>

    <!-- Cards mejoradas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php while ($herramienta = $herramientas->fetch_assoc()): ?>
        <div class="bg-white border card-hover rounded-2xl p-6 flex flex-col shadow border border-gray-200 group relative transition">
            <div class="relative mb-4 flex justify-center items-center">
                <?php if (!empty($herramienta['imagen']) && file_exists($herramienta['imagen'])): ?>
                    <img src="<?= $herramienta['imagen'] ?>" alt="<?= $herramienta['nombre'] ?>" class="h-36 object-contain mx-auto rounded shadow cursor-pointer hover:scale-110 transition"
                        onclick="mostrarModal('<?= $herramienta['imagen'] ?>')" />
                <?php else: ?>
                    <div class="h-36 flex items-center justify-center bg-gray-100 text-gray-400 text-xs rounded w-full">
                        <i class="fa-solid fa-image text-2xl"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h3 class="text-lg font-extrabold text-[var(--vw-blue)] mb-2 tracking-tight"><?= htmlspecialchars($herramienta['nombre']) ?></h3>
            <div class="space-y-1 mb-4">
                <p class="text-sm text-gray-600"><span class="font-semibold">Código:</span> <?= htmlspecialchars($herramienta['codigo']) ?></p>
                <p class="text-sm text-gray-600"><span class="font-semibold">Ubicación:</span> <?= htmlspecialchars($herramienta['ubicacion']) ?></p>
                <p class="text-sm text-gray-600"><span class="font-semibold">Cantidad:</span> <?= intval($herramienta['cantidad']) ?></p>
            </div>
            <div class="mb-4">
            <?php if ($herramienta['cantidad'] == 0): ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-100 text-red-700 font-semibold shadow-sm text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i> Sin stock
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold shadow-sm text-sm">
                    <i class="fa-solid fa-check-circle"></i> En stock
                </span>
            <?php endif; ?>
            </div>
            <div class="mt-auto flex gap-2 pt-2">
                <a href="editar_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-yellow-400 hover:bg-yellow-500 text-black py-2 rounded-lg text-sm font-semibold transition inline-flex items-center justify-center gap-1">
                    <i class="fa-solid fa-edit"></i> Editar
                </a>
                <a href="?confirmar=<?= $herramienta['id'] ?>&<?= http_build_query($_GET) ?>" class="flex-1 text-center bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-sm font-semibold transition inline-flex items-center justify-center gap-1">
                    <i class="fa-solid fa-trash"></i> Borrar
                </a>
                <a href="historial_herramienta.php?id=<?= $herramienta['id'] ?>" class="flex-1 text-center bg-blue-100 hover:bg-blue-200 text-[var(--vw-blue)] py-2 rounded-lg text-sm font-semibold transition inline-flex items-center justify-center gap-1">
                    <i class="fa-solid fa-history"></i> Historial
                </a>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

    <?php if ($herramientas->num_rows == 0): ?>
        <div class="p-6 bg-blue-100 text-blue-800 rounded-lg shadow font-semibold text-lg mt-6">
            <i class="fa-solid fa-info-circle mr-2"></i>
            No se encontraron herramientas con los filtros aplicados.
        </div>
    <?php endif; ?>
</div>

<!-- Modal para ampliar imagen -->
<div id="modalImagen" class="modal-imagen fixed inset-0 bg-black bg-opacity-80 items-center justify-center z-50 transition-all" onclick="cerrarModal()">
    <img id="imagenAmpliada" src="" class="max-h-[70vh] max-w-[90vw] m-auto rounded-lg border-4 border-white shadow-xl">
</div>

<!-- Botón subir -->
<button id="btnSubir" title="Subir arriba" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-[var(--vw-blue)] hover:bg-blue-900 text-white text-xl shadow-lg transition transform hover:scale-110 opacity-0 pointer-events-none z-50">
    <i class="fa-solid fa-chevron-up"></i>
</button>

<script src="fontawesome/js/all.min.js"></script>
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
// Notificación flotante auto-oculta
window.onload = function() {
    var notif = document.querySelector('.notificacion-flotante');
    if (notif) {
        setTimeout(function() {
            notif.style.display = 'none';
        }, 3000);
    }
}
</script>
</body>
</html>
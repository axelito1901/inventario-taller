<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

$directorio = "informes/";
$archivos = [];
$filtrados = [];

$fechaSeleccionada = $_GET['fecha'] ?? '';
$mesSeleccionado = $_GET['mes'] ?? '';
$tipoSeleccionado = $_GET['tipo'] ?? 'todos';
$archivoAConfirmar = $_GET['confirmar'] ?? null;

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $archivo = basename($_POST['eliminar']);
    $ruta = $directorio . $archivo;

    if (file_exists($ruta)) {
        unlink($ruta);
        $mensaje = "El archivo '$archivo' fue eliminado correctamente.";
        $tipoMsg = "ok";
    } else {
        $mensaje = "No se encontró el archivo.";
        $tipoMsg = "error";
    }

    // Redirigir con mensaje por GET (PRG)
    $params = $_GET;
    $params['msg'] = $mensaje;
    $params['msgtype'] = $tipoMsg;
    unset($params['confirmar']); // para que no vuelva a mostrar el modal
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . (count($params) ? '?' . http_build_query($params) : ''));
    exit();
}

if (is_dir($directorio)) {
    $archivos = array_diff(scandir($directorio), ['.', '..']);
    rsort($archivos);

    foreach ($archivos as $archivo) {
        if (!str_ends_with($archivo, '.xls')) continue;

        $coincideTipo =
            $tipoSeleccionado === 'todos' ||
            ($tipoSeleccionado === 'stock' && str_contains($archivo, 'stock')) ||
            ($tipoSeleccionado === 'prestamo' && !str_contains($archivo, 'stock'));

        $coincideFecha = $fechaSeleccionada && str_contains($archivo, $fechaSeleccionada);
        $coincideMes = $mesSeleccionado && str_starts_with($archivo, 'informe_' . $mesSeleccionado);

        if ($coincideTipo) {
            if (
                ($fechaSeleccionada && $coincideFecha) ||
                ($mesSeleccionado && $coincideMes) ||
                (!$fechaSeleccionada && !$mesSeleccionado)
            ) {
                $filtrados[] = $archivo;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de informes</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
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

    <?php if ($archivoAConfirmar): ?>
        <div class="modal-bg">
            <div class="modal-confirm">
                <h2><i class="fa-solid fa-triangle-exclamation"></i> Confirmar eliminación</h2>
                <p>¿Seguro que querés eliminar el archivo<br>
                    <span class="font-semibold text-[var(--vw-blue)]"><?= htmlspecialchars($archivoAConfirmar) ?></span>?
                </p>
                <form method="POST" class="btns">
                    <input type="hidden" name="eliminar" value="<?= htmlspecialchars($archivoAConfirmar) ?>">
                    <button type="submit">Sí, eliminar</button>
                    <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') . (count($_GET) > 1 ? '?' . http_build_query(array_diff_key($_GET, ['confirmar' => '', 'msg' => '', 'msgtype' => ''])) : '') ?>">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-4xl mx-auto p-6">
        <div class="flex items-center gap-3 mb-6">
            <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Historial de informes</h1>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow border border-gray-200 mb-8">
            <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end md:gap-4">
                <div class="flex-1">
                    <label class="font-semibold text-[var(--vw-blue)] mb-1 block">Filtrar por fecha exacta</label>
                    <input type="date" name="fecha" class="border-2 border-gray-200 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none" value="<?= htmlspecialchars($fechaSeleccionada) ?>">
                </div>
                <div class="flex-1">
                    <label class="font-semibold text-[var(--vw-blue)] mb-1 block">Filtrar por tipo</label>
                    <select name="tipo" onchange="this.form.submit()" class="border-2 border-gray-200 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none appearance-none">
                        <option value="todos" <?= $tipoSeleccionado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="prestamo" <?= $tipoSeleccionado === 'prestamo' ? 'selected' : '' ?>>Préstamos</option>
                        <option value="stock" <?= $tipoSeleccionado === 'stock' ? 'selected' : '' ?>>Stock</option>
                    </select>
                </div>
                <div class="flex-shrink-0 mt-2 md:mt-0 flex items-end gap-2">
                    <button type="submit" class="bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-5 py-2 rounded-lg font-bold shadow flex items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                    </button>
                    <a href="historial_informes.php" class="bg-gray-200 hover:bg-gray-300 text-[var(--vw-blue)] px-5 py-2 rounded-lg font-bold shadow flex items-center gap-2 transition">
                        <i class="fa-solid fa-rotate-right"></i> Limpiar filtros
                    </a>
                </div>
            </form>
        </div>

        <!-- Botón volver entre filtros y tabla, bien separado -->
        <div class="mb-6">
            <a href="dashboard.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-3 rounded-lg shadow font-semibold transition">
                <i class="fa-solid fa-arrow-left"></i> Volver al panel
            </a>
        </div>

        <?php if (count($filtrados) > 0): ?>
            <div class="overflow-x-auto bg-white rounded-2xl shadow border border-gray-200">
                <table class="min-w-full border-collapse">
                    <thead class="bg-[var(--vw-blue)] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Archivo</th>
                            <th class="px-4 py-3 text-left font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtrados as $archivo): ?>
                            <tr class="border-b border-gray-200 hover:bg-blue-50 transition">
                                <td class="px-4 py-3"><?= htmlspecialchars($archivo) ?></td>
                                <td class="px-4 py-3 flex gap-3 items-center">
                                    <a href="<?= $directorio . $archivo ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 transition shadow" download>
                                        <i class="fa-solid fa-download"></i> Descargar
                                    </a>
                                    <a href="?confirmar=<?= urlencode($archivo) ?>&<?= http_build_query($_GET) ?>"
                                       class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 transition shadow">
                                        <i class="fa-solid fa-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-6 bg-blue-100 text-blue-800 rounded-lg shadow font-semibold text-lg mt-6 text-center">
                No se encontraron informes con esos filtros.
            </div>
        <?php endif; ?>
    </div>
    <script src="fontawesome/js/all.min.js"></script>
    <script>
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
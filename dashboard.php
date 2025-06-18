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

$prestamos = $conexion->query("SELECT p.*, h.nombre AS herramienta, m.nombre AS mecanico FROM prestamos p LEFT JOIN herramientas h ON p.herramienta_id = h.id LEFT JOIN mecanicos m ON p.mecanico_id = m.id WHERE p.devuelta = 0 ORDER BY p.fecha_hora DESC");

$comentarios = $conexion->query("SELECT p.id, p.comentario_devolucion, p.leido, h.id AS herramienta_id, h.nombre AS herramienta, h.codigo FROM prestamos p JOIN herramientas h ON p.herramienta_id = h.id WHERE p.comentario_devolucion IS NOT NULL AND TRIM(p.comentario_devolucion) != '' ORDER BY p.fecha_devolucion DESC LIMIT 10");

$noLeidosRes = $conexion->query("SELECT COUNT(*) AS total FROM prestamos WHERE comentario_devolucion IS NOT NULL AND TRIM(comentario_devolucion) != '' AND leido = 0");
$noLeidos = $noLeidosRes->fetch_assoc()['total'] ?? 0;
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
<header class="bg-white shadow p-4 flex items-center justify-between relative">
    <h1 class="text-xl font-bold text-[var(--vw-blue)]">Bienvenido, <?= htmlspecialchars($nombreGerente) ?></h1>
    <div class="flex items-center gap-4">
        <!-- Botón mensajes -->
        <div class="relative p-3 rounded border border-blue-300 hover:bg-blue-50 transition">
            <button id="btnMensajes" class="text-[var(--vw-blue)] hover:text-blue-800 transition text-lg font-bold relative">
                💬 Comentarios
                <?php if ($noLeidos > 0): ?>
                    <span class="absolute -top-2 -right-3 bg-red-600 text-white text-xs px-2 py-1 rounded-full shadow"><?= $noLeidos ?></span>
                <?php endif; ?>
            </button>
            <div id="panelMensajes" class="hidden absolute right-0 mt-2 w-96 bg-white border border-gray-300 rounded shadow z-50 max-h-96 overflow-y-auto text-sm">
                <?php if ($comentarios && $comentarios->num_rows > 0): ?>
                    <?php while ($c = $comentarios->fetch_assoc()): ?>
                        <div class="p-3 border-b <?= $c['leido'] ? '' : 'bg-yellow-50' ?>">
                            <div class="text-gray-800 font-medium">🔧 <?= htmlspecialchars($c['herramienta']) ?> <span class="text-xs text-gray-500">(<?= htmlspecialchars($c['codigo']) ?>)</span></div>
                            <div class="text-gray-600 italic text-xs mt-1">“<?= htmlspecialchars($c['comentario_devolucion']) ?>”</div>
                            <div class="mt-1 flex justify-between items-center">
                                <a href="historial_herramienta.php?id=<?= $c['herramienta_id'] ?>" class="text-blue-700 text-xs hover:underline">🔎 Ver historial</a>
                                <?php if (!$c['leido']): ?>
                                    <form method="post" action="marcar_leido.php">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button onclick="marcarComoLeido(<?= $c['id'] ?>, this)" class="text-xs text-blue-600 hover:underline">✅ Marcar como leído</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-gray-500">No hay comentarios recientes.</div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Cerrar sesión -->
        <a href="logout.php" class="p-3 text-sm text-red-600 rounded border border-red-300 hover:bg-red-300 transition">🚪 Cerrar sesión</a>
    </div>
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

<script>
    const btnMensajes = document.getElementById('btnMensajes');
    const panelMensajes = document.getElementById('panelMensajes');

    btnMensajes.addEventListener('click', () => {
        panelMensajes.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!btnMensajes.contains(e.target) && !panelMensajes.contains(e.target)) {
            panelMensajes.classList.add('hidden');
        }
    });

    function marcarComoLeido(id, btn) {
        fetch('marcar_leido.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(id)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                btn.closest('.p-3').remove();
            }
        })
    }
</script>
</body>
</html>

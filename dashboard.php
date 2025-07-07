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

$comentarios = $conexion->query("
    SELECT c.*, h.nombre AS herramienta, h.codigo
    FROM comentarios c
    JOIN herramientas h ON c.herramienta_id = h.id
    ORDER BY c.fecha DESC
    LIMIT 10
");

$noLeidosRes = $conexion->query("SELECT COUNT(*) AS total FROM comentarios WHERE leido = 0");
$noLeidos = $noLeidosRes->fetch_assoc()['total'] ?? 0;

// Buscar backups
$backups = [];
$dir = __DIR__ . '/backups';
if (is_dir($dir)) {
    foreach (scandir($dir) as $file) {
        if (str_ends_with($file, '.sql')) $backups[] = $file;
    }
    rsort($backups); // Último arriba
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Gerente</title>
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
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
<header class="bg-white shadow p-4 flex items-center justify-between relative">
    <div class="flex items-center gap-4">
        <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto ml-1">
        <h1 class="text-xl font-bold text-[var(--vw-blue)]">Bienvenido, <?= htmlspecialchars($nombreGerente) ?></h1>
    </div>
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
                        <div class="p-3 border-b <?= $c['leido'] ? '' : 'bg-yellow-50' ?>" id="comentario-<?= $c['id'] ?>">
                            <div class="text-gray-800 font-medium">🔧 <?= htmlspecialchars($c['herramienta']) ?> <span class="text-xs text-gray-500">(<?= htmlspecialchars($c['codigo']) ?>)</span></div>
                            <div class="text-gray-600 italic text-xs mt-1">“<?= htmlspecialchars($c['comentario']) ?>”</div>
                            <div class="text-blue-800 text-xs mt-1">Por: <strong><?= htmlspecialchars($c['nombre']) ?></strong></div>
                            <div class="mt-1 text-xs text-gray-500">📍 <?= htmlspecialchars($c['sucursal']) ?> - <?= $c['fecha'] ?></div>
                            <div class="mt-1 flex justify-between items-center">
                                <a href="historial_herramienta.php?id=<?= $c['herramienta_id'] ?>" class="text-blue-700 text-xs hover:underline">🔎 Ver historial</a>
                                <?php if (!$c['leido']): ?>
                                    <button onclick="marcarComoLeido(<?= $c['id'] ?>, this)" class="text-xs text-blue-600 hover:underline">✅ Marcar como leído</button>
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
        <a href="actualizar_cantidad.php" class="inline-block bg-red-400 text-white p-4 rounded-lg shadow hover:bg-red-500 transition text-center">➕ Controlar Stock</a>
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

<!-- Botones abajo -->
<div class="mt-12 flex flex-col md:flex-row gap-4 justify-center items-center">
  <a href="cambiar_credenciales.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg shadow hover:bg-purple-700 transition text-lg font-semibold">
    🔒 Cambiar contraseña
  </a>
  <button id="btnBackup" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg shadow hover:bg-indigo-700 transition text-lg font-semibold flex items-center gap-2">
    <i class="fa-solid fa-database"></i>
    Backup BD
  </button>
  <button id="btnRestaurar" class="inline-block bg-red-600 text-white px-6 py-3 rounded-lg shadow hover:bg-red-700 transition text-lg font-semibold flex items-center gap-2">
    <i class="fa-solid fa-upload"></i>
    Actualizar Base de Datos
  </button>
</div>

<!-- Toast de éxito/error -->
<div id="toast" class="fixed bottom-8 left-1/2 -translate-x-1/2 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg text-lg z-50 hidden">
  Backup generado y guardado en /backups ✅
</div>

<!-- Modal Restaurar -->
<div id="modalRestaurar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl p-8 max-w-sm w-full shadow-xl relative">
    <button type="button" onclick="cerrarModalRestaurar()" class="absolute right-4 top-4 text-gray-400 hover:text-black text-2xl">&times;</button>
    <h2 class="text-xl font-bold text-red-700 mb-3">⚠ Confirmar Actualización</h2>
    <p class="mb-2 text-gray-800">Esta acción restaurará la base de datos.<br>
      <span class="font-semibold text-red-600">¡Se reemplazarán todos los datos actuales!</span>
    </p>
    <label class="block mb-2 mt-3 text-gray-700 font-semibold">Elegí el backup:</label>
    <select id="backupSelect" class="w-full px-4 py-2 border rounded mb-3">
        <?php foreach ($backups as $file): ?>
            <option value="<?= htmlspecialchars($file) ?>"><?= htmlspecialchars($file) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="block mb-2 mt-3 text-gray-700 font-semibold">Contraseña de administrador:</label>
    <input type="password" id="passAdmin" class="w-full px-4 py-2 border rounded mb-3" placeholder="Ingresá la contraseña" autocomplete="off">
    <div id="restaurarError" class="text-red-600 mb-3 hidden"></div>
    <button id="confirmRestaurar" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded shadow w-full font-bold">Restaurar Base de Datos</button>
  </div>
</div>

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
                const contenedor = btn.closest('.p-3');
                contenedor.classList.remove('bg-yellow-50');
                btn.remove();
            }
        });
    }

    setInterval(() => {
        fetch('contar_no_leidos.php')
            .then(res => res.json())
            .then(data => {
                const btn = document.getElementById('btnMensajes');
                let burbuja = btn.querySelector('span');

                if (data.total > 0) {
                    if (!burbuja) {
                        burbuja = document.createElement('span');
                        burbuja.className = 'absolute -top-2 -right-3 bg-red-600 text-white text-xs px-2 py-1 rounded-full shadow';
                        btn.appendChild(burbuja);
                    }   
                    burbuja.textContent = data.total;
                } else if (burbuja) {
                    burbuja.remove();
                }
            });
    }, 30000);

    // --- BACKUP.PHP con alerta ---
    document.getElementById("btnBackup").addEventListener("click", function() {
      const btn = this;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando backup...';

      fetch('backup.php')
        .then(res => res.json())
        .then(data => {
          if(data.success) {
            mostrarToast('Backup generado y guardado en /backups ✅');
          } else {
            mostrarToast(data.msg || 'Error al crear el backup', true);
          }
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-database"></i> Backup BD';
        })
        .catch(() => {
          mostrarToast('Error al crear el backup', true);
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-database"></i> Backup BD';
        });
    });

    function mostrarToast(mensaje, error=false) {
      const toast = document.getElementById('toast');
      toast.textContent = mensaje;
      toast.classList.remove('hidden');
      toast.classList.remove('bg-green-600','bg-red-600');
      toast.classList.add(error ? 'bg-red-600' : 'bg-green-600');
      setTimeout(() => { toast.classList.add('hidden'); }, 3200);
    }

    // -- RESTAURAR BASE DE DATOS (modal + validación) --
    function cerrarModalRestaurar() {
        document.getElementById('modalRestaurar').classList.add('hidden');
        document.getElementById('passAdmin').value = '';
        document.getElementById('restaurarError').classList.add('hidden');
    }

    document.getElementById("btnRestaurar").addEventListener("click", function() {
        document.getElementById('modalRestaurar').classList.remove('hidden');
        setTimeout(() => { document.getElementById('passAdmin').focus(); }, 100);
    });

    document.getElementById("confirmRestaurar").addEventListener("click", function() {
        const pass = document.getElementById('passAdmin').value.trim();
        const backup = document.getElementById('backupSelect').value;
        const errDiv = document.getElementById('restaurarError');
        errDiv.classList.add('hidden');

        if(pass === "") {
            errDiv.textContent = "La contraseña es obligatoria.";
            errDiv.classList.remove('hidden');
            return;
        }
        if(!backup) {
            errDiv.textContent = "Debés seleccionar un archivo de backup.";
            errDiv.classList.remove('hidden');
            return;
        }
        this.disabled = true;
        this.textContent = "Restaurando...";

        fetch('restaurar_backup.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'password=' + encodeURIComponent(pass) + '&backup=' + encodeURIComponent(backup)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                cerrarModalRestaurar();
                mostrarToast("¡Base de datos restaurada con éxito!");
            } else {
                errDiv.textContent = data.msg || "Contraseña incorrecta o error en restauración.";
                errDiv.classList.remove('hidden');
            }
            document.getElementById("confirmRestaurar").disabled = false;
            document.getElementById("confirmRestaurar").textContent = "Restaurar Base de Datos";
        })
        .catch(() => {
            errDiv.textContent = "Ocurrió un error inesperado.";
            errDiv.classList.remove('hidden');
            document.getElementById("confirmRestaurar").disabled = false;
            document.getElementById("confirmRestaurar").textContent = "Restaurar Base de Datos";
        });
    });
</script>
</body>
</html>

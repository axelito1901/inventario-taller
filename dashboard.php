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
        .vw-card {
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 4px 24px #0001;
        }
        .vw-min-row {
            border-bottom: 1.5px solid #f4f7fb;
            transition: background 0.15s;
        }
        .vw-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            background: #e7f3ff;
            color: #1363c6;
            font-weight: 500;
            padding: 0.3em 1.1em;
            border-radius: 1em;
            font-size: 0.99em;
        }
        /* Modern panel de comentarios */
        #panelMensajes {
            min-width: 340px;
        }
        #panelMensajes .comentario-burbuja {
            border-radius: 1.2rem;
            margin-bottom: 0.5rem;
            background: #f8fafc;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.03);
            border-left: 4px solid #e7f3ff;
            padding: 1.1rem 1.2rem 0.7rem 1.2rem;
        }
        #panelMensajes .comentario-burbuja.no-leido {
            background: #fffbe7;
            border-left: 4px solid #facc15;
        }
        #panelMensajes .comentario-burbuja .herramienta {
            background: #e7f3ff;
            color: var(--vw-blue);
            font-weight: 600;
            padding: 0.2em 0.9em;
            border-radius: 1em;
            font-size: 0.97em;
            margin-right: 0.5em;
        }
        #panelMensajes .comentario-burbuja .codigo {
            color: #b0b0b0;
            font-size: 0.93em;
            font-family: monospace;
        }
        #panelMensajes .comentario-burbuja .comentario {
            color: #444;
            font-style: italic;
            border-left: 2.5px solid #b3d1fa;
            margin: 0.7em 0 0.5em 0;
            padding-left: 0.8em;
            font-size: 1em;
            transition: max-height 0.2s;
            white-space: pre-line;
            word-break: break-word;
        }
        .comentario-collapsed {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-height: 4.5em;
            position: relative;
            transition: max-height 0.2s;
            white-space: pre-line;
            word-break: break-word;
        }
        .comentario-expanded {
            display: block !important;
            max-height: 1000px;
            transition: max-height 0.2s;
            white-space: pre-line;
            word-break: break-word;
        }
        .ver-mas-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin: 0;
            color: #1363c6;
            transition: color 0.15s;
        }
        .ver-mas-btn:hover {
            color: #0a3a7a;
            text-decoration: underline;
        }
        #panelMensajes .comentario-burbuja .info {
            display: flex;
            justify-content: space-between;
            font-size: 0.93em;
            color: #6b7280;
        }
        #panelMensajes .comentario-burbuja .acciones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.7em;
        }
        #panelMensajes .comentario-burbuja .acciones a,
        #panelMensajes .comentario-burbuja .acciones button {
            font-size: 0.95em;
            font-weight: 600;
            color: var(--vw-blue);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            transition: color 0.15s;
        }
        #panelMensajes .comentario-burbuja .acciones a:hover,
        #panelMensajes .comentario-burbuja .acciones button:hover {
            color: #0a3a7a;
            text-decoration: underline;
        }
        #panelMensajes .panel-header {
            padding: 1rem 1.2rem 0.7rem 1.2rem;
            border-bottom: 1.5px solid #e7f3ff;
            background: linear-gradient(90deg, #e7f3ff 0%, #fff 100%);
            border-top-left-radius: 1.2rem;
            border-top-right-radius: 1.2rem;
            font-weight: bold;
            color: var(--vw-blue);
            font-size: 1.08em;
            display: flex;
            align-items: center;
            gap: 0.6em;
        }
        #panelMensajes .no-comentarios {
            padding: 2.5rem 1.2rem;
            color: #b0b0b0;
            text-align: center;
        }
        /* Badge notificación */
        .badge-notificacion {
            position: absolute;
            top: -0.7em;
            right: -0.9em;
            background: #e11d48;
            color: #fff;
            font-size: 0.85em;
            padding: 0.2em 0.7em;
            border-radius: 999px;
            font-weight: bold;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
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
            <button id="btnMensajes" class="text-[var(--vw-blue)] hover:text-blue-800 transition text-lg font-bold relative flex items-center gap-2">
                <i class="fa-solid fa-comments"></i> Comentarios
                <?php if ($noLeidos > 0): ?>
                    <span class="badge-notificacion"><?= $noLeidos ?></span>
                <?php endif; ?>
            </button>
            <!-- PANEL MODERNO DE MENSAJES -->
            <div id="panelMensajes" class="hidden absolute right-0 mt-2 w-96 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 max-h-96 overflow-y-auto text-sm p-0">
                <?php if ($comentarios && $comentarios->num_rows > 0): ?>
                    <div class="panel-header">
                        <i class="fa-solid fa-comments"></i> Últimos comentarios
                    </div>
                    <div>
                    <?php while ($c = $comentarios->fetch_assoc()): ?>
                        <div class="comentario-burbuja<?= $c['leido'] ? '' : ' no-leido' ?>" id="comentario-<?= $c['id'] ?>">
                            <div>
                                <span class="herramienta"><?= htmlspecialchars($c['herramienta']) ?></span>
                                <span class="codigo">(<?= htmlspecialchars($c['codigo']) ?>)</span>
                            </div>
                            <div class="comentario comentario-collapsed" id="comentario-texto-<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['comentario']) ?>
                            </div>
                            <?php if (mb_strlen($c['comentario']) > 120): ?>
                                <button class="ver-mas-btn text-blue-700 font-semibold text-xs mt-1" data-id="<?= $c['id'] ?>">Ver más</button>
                            <?php endif; ?>
                            <div class="info">
                                <span>Por <strong style="color:#1363c6"><?= htmlspecialchars($c['nombre']) ?></strong> — <?= htmlspecialchars($c['sucursal']) ?></span>
                                <span><?= date('d/m H:i', strtotime($c['fecha'])) ?></span>
                            </div>
                            <div class="acciones">
                                <a href="historial_herramienta.php?id=<?= $c['herramienta_id'] ?>">Ver historial</a>
                                <?php if (!$c['leido']): ?>
                                    <button onclick="marcarComoLeido(<?= $c['id'] ?>, this)">Marcar como leído</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-comentarios">No hay comentarios recientes.</div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Cerrar sesión -->
        <a href="logout.php" class="p-3 text-sm text-red-600 rounded border border-red-300 hover:bg-red-300 transition flex items-center gap-1">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
        </a>
    </div>
</header>

<main class="p-6 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <a href="listar_herramientas.php" class="bg-[var(--vw-blue)] text-white p-4 rounded-lg shadow hover:bg-blue-900 transition text-center flex items-center justify-center gap-2"><i class="fa-solid fa-wrench"></i> Ver herramientas</a>
        <a href="informe_diario.php" class="bg-blue-500 text-white p-4 rounded-lg shadow hover:bg-blue-600 transition text-center flex items-center justify-center gap-2"><i class="fa-solid fa-calendar-days"></i> Informe diario</a>
        <a href="exportar_informe_excel.php" class="bg-green-500 text-white p-4 rounded-lg shadow hover:bg-green-600 transition text-center flex items-center justify-center gap-2"><i class="fa-solid fa-file-excel"></i> Exportar Excel</a>
        <a href="historial_informes.php" class="bg-gray-800 text-white p-4 rounded-lg shadow hover:bg-gray-900 transition text-center flex items-center justify-center gap-2"><i class="fa-solid fa-folder-open"></i> Historial informes</a>
        <a href="actualizar_cantidad.php" class="inline-block bg-red-400 text-white p-4 rounded-lg shadow hover:bg-red-500 transition text-center flex items-center justify-center gap-2"><i class="fa-solid fa-plus"></i> Controlar Stock</a>
        <a href="gestion_nombres.php" class="bg-yellow-400 text-black p-4 rounded-lg shadow hover:bg-yellow-500 transition text-center flex items-center justify-center gap-2"> <i class="fa-solid fa-users"></i> Gestionar nombres</a>
    </div>

    <h2 class="text-2xl font-semibold text-[var(--vw-blue)] mb-4 flex items-center gap-2"><i class="fa-solid fa-list-check"></i> Préstamos activos</h2>

    <?php if ($prestamos->num_rows > 0): ?>
    <div class="overflow-x-auto vw-card px-0 py-0 mb-2">
        <table class="min-w-full bg-transparent text-base">
            <thead>
                <tr>
                    <th class="py-3 px-5 font-semibold text-left bg-[var(--vw-blue)] text-white border-0 rounded-tl-2xl">Herramienta</th>
                    <th class="py-3 px-5 font-semibold text-left bg-[var(--vw-blue)] text-white border-0">Prestado por</th>
                    <th class="py-3 px-5 font-semibold text-left bg-[var(--vw-blue)] text-white border-0">Sucursal</th>
                    <th class="py-3 px-5 font-semibold text-left bg-[var(--vw-blue)] text-white border-0">Fecha y hora</th>
                    <th class="py-3 px-5 font-semibold text-left bg-[var(--vw-blue)] text-white border-0 rounded-tr-2xl">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $prestamos->fetch_assoc()): ?>
                    <tr class="vw-min-row hover:bg-[#f4f7fb] transition-all">
                        <td class="py-3 px-5"><?= htmlspecialchars($row['herramienta']) ?></td>
                        <td class="py-3 px-5"><?= $row['mecanico'] ?? htmlspecialchars($row['nombre_personalizado']) ?></td>
                        <td class="py-3 px-5"><?= htmlspecialchars($row['sucursal']) ?></td>
                        <td class="py-3 px-5"><?= htmlspecialchars($row['fecha_hora']) ?></td>
                        <td class="py-3 px-5">
                            <span class="vw-pill bg-blue-100 text-[var(--vw-blue)] px-4 py-1 rounded-full text-sm font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-hourglass-half"></i> Activo
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="p-4 bg-blue-50 text-blue-800 rounded-lg shadow-sm">No hay préstamos activos en este momento.</div>
    <?php endif; ?>

</main>

<!-- Botones abajo -->
<div class="mt-12 flex flex-col md:flex-row gap-4 justify-center items-center">
  <a href="cambiar_credenciales.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg shadow hover:bg-purple-700 transition text-lg font-semibold flex items-center gap-2"><i class="fa-solid fa-lock"></i> Cambiar contraseña</a>
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
<div id="toast" class="fixed bottom-8 left-1/2 -translate-x-1/2 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg text-lg z-50 hidden flex items-center gap-2">
  <i class="fa-solid fa-check-circle"></i>
  Backup generado y guardado en /backups
</div>

<!-- Modal Restaurar -->
<div id="modalRestaurar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-xl p-8 max-w-sm w-full shadow-xl relative">
        <button type="button" onclick="cerrarModalRestaurar()" class="absolute right-4 top-4 text-gray-400 hover:text-black text-2xl">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="text-xl font-bold text-red-700 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> Confirmar Actualización
        </h2>
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

    // ---------- MODIFICADO: badge se actualiza instantáneamente ----------
    function marcarComoLeido(id, btn) {
        fetch('marcar_leido.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(id)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const contenedor = btn.closest('.comentario-burbuja');
                contenedor.classList.remove('no-leido');
                btn.remove();

                // ACTUALIZA EL CONTADOR DEL BADGE EN TIEMPO REAL
                const btnMensajes = document.getElementById('btnMensajes');
                const burbuja = btnMensajes.querySelector('.badge-notificacion');
                if (burbuja) {
                    let numero = parseInt(burbuja.textContent, 10) || 0;
                    numero--;
                    if (numero <= 0) {
                        burbuja.remove();
                    } else {
                        burbuja.textContent = numero;
                    }
                }
            }
        });
    }
    // ---------------------------------------------------------------------

    setInterval(() => {
        fetch('contar_no_leidos.php')
            .then(res => res.json())
            .then(data => {
                const btn = document.getElementById('btnMensajes');
                let burbuja = btn.querySelector('.badge-notificacion');

                if (data.total > 0) {
                    if (!burbuja) {
                        burbuja = document.createElement('span');
                        burbuja.className = 'badge-notificacion';
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
            mostrarToast('Backup generado y guardado en /backups');
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

      let iconHtml = error
        ? '<i class="fa-solid fa-xmark mr-2"></i>'
        : '<i class="fa-solid fa-check-circle mr-2"></i>';
      toast.innerHTML = iconHtml + mensaje;
      toast.classList.remove('hidden');
      toast.classList.remove('bg-green-600', 'bg-red-600');
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

    // --------- VER MÁS / VER MENOS en comentarios ---------
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.ver-mas-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const texto = document.getElementById('comentario-texto-' + id);
                if (texto.classList.contains('comentario-collapsed')) {
                    texto.classList.remove('comentario-collapsed');
                    texto.classList.add('comentario-expanded');
                    this.textContent = 'Ver menos';
                } else {
                    texto.classList.add('comentario-collapsed');
                    texto.classList.remove('comentario-expanded');
                    this.textContent = 'Ver más';
                }
            });
        });
    });
    // ------------------------------------------------------
</script>
</body>
</html>